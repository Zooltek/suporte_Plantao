<?php

use App\Models\Company;
use App\Models\Schedule;
use App\Models\Software;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-19 12:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('Relatório: Clientes sem Atendimento', function () {
    it('bloqueia visitantes não autenticados', function () {
        $response = $this->get(route('admin.reports.clients-without-attendance'));
        $response->assertRedirect(route('admin.login'));
    });

    it('exibe o relatório com as colunas corretas e filtra clientes sem atendimento', function () {
        actingAsAdmin();

        // 1. Criar dados de teste
        // Cliente A: ativo e com ticket no período (não deve aparecer)
        $clientA = Company::factory()->create([
            'is_active' => true,
            'name' => 'Cliente A com Chamado',
            'cnpj' => '12.345.678/0001-90',
        ]);
        Ticket::factory()->create([
            'company_id' => $clientA->id,
            'created_at' => now()->subDays(5),
        ]);

        // Cliente B: ativo e SEM ticket no período (deve aparecer)
        $clientB = Company::factory()->create([
            'is_active' => true,
            'name' => 'Cliente B sem Chamado',
            'cnpj' => '98.765.432/0001-10',
            'contact_name' => 'Fabricio Contato',
            'phone' => '(27) 99999-9999',
        ]);

        // Cliente C: inativo e sem ticket no período (não deve aparecer)
        $clientC = Company::factory()->create([
            'is_active' => false,
            'name' => 'Cliente C inativo',
        ]);

        // 2. Chamar endpoint
        $response = $this->get(route('admin.reports.clients-without-attendance'));

        // 3. Asserções
        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.clients-without-attendance');
        
        $response->assertSee('Cliente B sem Chamado');
        $response->assertSee('Fabricio Contato');
        $response->assertSee('(27) 99999-9999');
        $response->assertSee('98.765.432/0001-10');

        $response->assertDontSee('Cliente A com Chamado');
        $response->assertDontSee('Cliente C inativo');
    });
});

describe('Relatório: Atualização de Clientes', function () {
    it('bloqueia visitantes não autenticados', function () {
        $response = $this->get(route('admin.reports.client-updates'));
        $response->assertRedirect(route('admin.login'));
    });

    it('exibe a listagem de clientes com e-mail, telefone, software e versão', function () {
        actingAsAdmin();

        $software = Software::firstOrCreate(['name' => 'EasyMaster'], ['version' => '01.32.01', 'status' => 1]);

        $client = Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
            'name' => 'Auto Posto Alpha',
            'cnpj' => '11.111.111/0001-11',
            'contact_name' => 'Lucas Contato',
            'contact_email' => 'lucas@alpha.com',
            'phone' => '2733333333',
            'software_id' => $software->id,
        ]);

        $response = $this->get(route('admin.reports.client-updates'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.client-updates');
        $response->assertSee('Auto Posto Alpha');
        $response->assertSee('Lucas Contato');
        $response->assertSee('lucas@alpha.com');
        $response->assertSee('2733333333');
        $response->assertSee('EasyMaster');
        $response->assertSee('v01.32.01');
    });

    it('permite exportar os dados em formato CSV UTF-8 com BOM e delimitador ponto e vírgula', function () {
        actingAsAdmin();

        $software = Software::firstOrCreate(['name' => 'EasyMaster'], ['version' => '01.32.01', 'status' => 1]);

        Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
            'name' => 'Auto Posto Alpha',
            'cnpj' => '11.111.111/0001-11',
            'contact_name' => 'Lucas Contato',
            'contact_email' => 'lucas@alpha.com',
            'phone' => '2733333333',
            'software_id' => $software->id,
        ]);

        $response = $this->get(route('admin.reports.client-updates.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();

        // Verifica a presença do UTF-8 BOM
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        expect(str_starts_with($content, $bom))->toBeTrue();

        // Verifica a presença do delimitador e das colunas
        expect($content)->toContain('"Grupo Empresarial";Cliente;CNPJ;Contato;E-mail;Telefone;"Telefone 2";Sistema;Versão;Ativo');
        expect($content)->toContain('Auto Posto Alpha');
        expect($content)->toContain('11.111.111/0001-11');
        expect($content)->toContain('EasyMaster');
    });
});

describe('Dashboard TV', function () {
    it('bloqueia acesso sem token válido', function () {
        $response = $this->get(route('admin.dashboard-tv'));
        $response->assertStatus(403);
    });

    it('permite acesso com o token padrão correto', function () {
        $response = $this->get(route('admin.dashboard-tv', ['token' => 'amuratv2026']));
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard-tv');
        $response->assertSee('TV Dashboard');
    });

    it('retorna os dados corretos no endpoint JSON', function () {
        // Criar um agente de suporte ativo e alguns chamados de teste
        $supportDeptId = DB::table('user_department')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Suporte Técnico']
        );
        $agent = User::factory()->agent()->create([
            'active' => true,
            'ticketit_agent' => true,
            'department_id' => 1,
        ]);

        $company = Company::factory()->create();

        // Status aberto
        DB::table('ticketit_statuses')->updateOrInsert(['id' => 1], ['name' => 'Aberto', 'is_terminal' => false, 'requires_schedule' => false, 'requires_solution' => false, 'requires_agent' => false]);
        
        Ticket::factory()->create([
            'company_id' => $company->id,
            'agent_id' => $agent->id,
            'status_id' => 1,
            'department_id' => 1,
        ]);

        // Cria uma sessão ativa para o usuário
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                'id' => 'test-session-id',
                'user_id' => $agent->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'dummy',
                'last_activity' => now()->timestamp,
            ]);
        }

        // Cria um agendamento para hoje
        Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id' => $agent->id,
            'start_at' => now()->setTime(14, 0),
            'status' => 'sch',
            'title' => 'Treinamento ERP',
        ]);

        $response = $this->get(route('admin.dashboard-tv.data', ['token' => 'amuratv2026']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'kpis' => [
                'open_tickets_count',
                'closed_tickets_count',
                'sla_overdue_count',
                'active_techs_count',
                'active_users_count',
            ],
            'tickets',
            'schedules',
        ]);

        $data = $response->json();
        expect($data['kpis']['open_tickets_count'])->toBeGreaterThanOrEqual(1);
        if (Schema::hasTable('sessions')) {
            expect($data['kpis']['active_users_count'])->toBeGreaterThanOrEqual(1);
        }
        expect(count($data['schedules']))->toBeGreaterThanOrEqual(1);
        expect($data['schedules'][0]['title'])->toBe('Treinamento ERP');
    });

    it('registra atividade do usuário e atualiza contador de usuários ativos via tracker', function () {
        $user = User::factory()->create(['active' => true]);
        \App\Services\Auth\UserOnlineTracker::hit($user->id);

        $response = $this->get(route('admin.dashboard-tv.data', ['token' => 'amuratv2026']));
        $response->assertStatus(200);

        $data = $response->json();
        expect($data['kpis']['active_users_count'])->toBeGreaterThanOrEqual(1);

        \App\Services\Auth\UserOnlineTracker::forget($user->id);
    });
});
