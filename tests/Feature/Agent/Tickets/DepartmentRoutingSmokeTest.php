<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;

/**
 * Smoke test ponta a ponta do encaminhamento por setor.
 *
 * Reproduz o relato do cliente:
 *  1. Existem dois setores distintos (Suporte e Comercial).
 *  2. Um usuário do Comercial é criado com perfil de agente.
 *  3. Um administrador abre um chamado escolhendo "Setor: Comercial".
 *  4. O usuário do Comercial precisa enxergar esse chamado na sua fila.
 */
describe('Smoke — encaminhamento de chamado para setor explícito', function () {

    beforeEach(function () {
        $this->suporteDept = Department::create(['name' => 'Suporte Técnico Smoke']);
        $this->comercialDept = Department::create(['name' => 'Comercial Smoke']);

        $this->company = Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
        ]);

        $this->parentCategory = Category::factory()->create([
            'parent_id' => 0,
            'priority' => 'low',
        ]);
        $this->subCategory = Category::factory()->create([
            'parent_id' => $this->parentCategory->category_id,
            'priority' => 'low',
        ]);

        $this->pendingStatus = Status::query()->updateOrCreate(
            ['id' => Ticket::STATUS_PENDING_ID],
            [
                'name' => 'Pendente',
                'color' => '#2563eb',
                'requires_agent' => false,
                'is_terminal' => false,
                'requires_schedule' => false,
                'requires_solution' => false,
            ]
        );
    });

    it('agente do Comercial enxerga chamado endereçado ao seu setor mesmo sem ser responsável', function () {
        actingAsAdmin([
            'department_id' => $this->suporteDept->id,
        ]);

        $usuarioComercial = User::factory()->agent()->create([
            'department_id' => $this->comercialDept->id,
        ]);

        $payload = [
            'company_id' => $this->company->id,
            'status_id' => $this->pendingStatus->id,
            'category_id' => $this->parentCategory->category_id,
            'sub_category_id' => $this->subCategory->category_id,
            'contact' => 'CLIENTE TESTE',
            'trouble' => 'Solicitação para o setor comercial',
            'agent_id' => null,
            'department_id' => $this->comercialDept->id,
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionDoesntHaveErrors();

        $ticket = Ticket::latest()->first();
        expect($ticket)->not->toBeNull()
            ->and($ticket->department_id)->toBe($this->comercialDept->id)
            ->and($ticket->agent_id)->toBeNull();

        // Trocando para o usuário do Comercial — deve ver o chamado na listagem
        $this->actingAs($usuarioComercial, 'admin');

        $response = $this->get(route('agent.ticket.index'));

        $response->assertOk()
            ->assertSee('CLIENTE TESTE');
    });

    it('agente do Suporte (de outro setor) NÃO vê chamado direcionado ao Comercial', function () {
        actingAsAdmin([
            'department_id' => $this->suporteDept->id,
        ]);

        $outroSuporte = User::factory()->agent()->create([
            'department_id' => $this->suporteDept->id,
        ]);

        $payload = [
            'company_id' => $this->company->id,
            'status_id' => $this->pendingStatus->id,
            'category_id' => $this->parentCategory->category_id,
            'sub_category_id' => $this->subCategory->category_id,
            'contact' => 'CLIENTE COMERCIAL EXCLUSIVO',
            'trouble' => 'Apenas para Comercial',
            'agent_id' => null,
            'department_id' => $this->comercialDept->id,
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionDoesntHaveErrors();

        // Outro agente do Suporte abre a listagem — não pode ver o chamado do Comercial
        $this->actingAs($outroSuporte, 'admin');
        $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->assertDontSee('CLIENTE COMERCIAL EXCLUSIVO');
    });

    it('override autorizado tem precedência sobre o setor do agente atribuído', function () {
        actingAsAdmin([
            'department_id' => $this->suporteDept->id,
        ]);

        $agenteComercial = User::factory()->agent()->create([
            'department_id' => $this->comercialDept->id,
        ]);

        $payload = [
            'company_id' => $this->company->id,
            'status_id' => $this->pendingStatus->id,
            'category_id' => $this->parentCategory->category_id,
            'sub_category_id' => $this->subCategory->category_id,
            'contact' => 'CLIENTE PRECEDENCIA',
            'trouble' => 'Teste de precedência',
            'agent_id' => $agenteComercial->id,
            'department_id' => $this->suporteDept->id,
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionDoesntHaveErrors();

        $ticket = Ticket::latest()->first();

        // O override manual foi autorizado para Admin e vence os demais sinais.
        expect($ticket->department_id)->toBe($this->suporteDept->id)
            ->and((int) $ticket->agent_id)->toBe($agenteComercial->id);
    });

    it('sem sinais de roteamento, o setor cai para o fallback de Suporte', function () {
        $atendenteSuporte = User::factory()->agent()->create([
            'department_id' => $this->comercialDept->id,
        ]);

        $this->actingAs($atendenteSuporte, 'admin');

        $payload = [
            'company_id' => $this->company->id,
            'status_id' => $this->pendingStatus->id,
            'category_id' => $this->parentCategory->category_id,
            'sub_category_id' => $this->subCategory->category_id,
            'contact' => 'CLIENTE FALLBACK',
            'trouble' => 'Sem setor explícito',
            'agent_id' => null,
            // department_id ausente
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionDoesntHaveErrors();

        $ticket = Ticket::latest()->first();
        $resolvedDepartment = Department::find($ticket->department_id);

        expect($resolvedDepartment)->not->toBeNull()
            ->and(mb_strtolower($resolvedDepartment->name))->toContain('suporte')
            ->and($ticket->department_id)->not->toBe($this->comercialDept->id);
    });

    it('usuário criado com perfil CRM tem ticketit_agent=true e enxerga chamados do setor', function () {
        $crmDepartment = Department::create([
            'name' => 'CRM / Comercial',
            'is_crm' => true,
            'is_feedback' => true,
        ]);
        $crmDeptId = $crmDepartment->id;

        // Cria via UserService para validar o pipeline completo
        $userService = app(\App\Services\Admin\UserService::class);

        $crmUser = $userService->store([
            'name' => 'Comercial Smoke',
            'email' => 'comercial.smoke@example.com',
            'password' => 'senha-temp',
            'role' => '3',
            'department_id' => $crmDeptId,
        ]);

        expect($crmUser->ticketit_agent)->toBeTrue()
            ->and((int) $crmUser->department_id)->toBe($crmDeptId);

        // Para o teste de acesso, contornamos o force-change-password (regra do store
        // quando há senha temporária). O ponto é validar a permissão de acesso, não
        // o fluxo de troca de senha.
        $crmUser->update(['must_change_password' => false]);
        $crmUser->refresh();

        // Abre chamado direcionado ao setor CRM com override autorizado.
        actingAsAdmin([
            'department_id' => $this->suporteDept->id,
        ]);

        $this->post(route('agent.ticket.store'), [
            'company_id' => $this->company->id,
            'status_id' => $this->pendingStatus->id,
            'category_id' => $this->parentCategory->category_id,
            'sub_category_id' => $this->subCategory->category_id,
            'contact' => 'CLIENTE CRM',
            'trouble' => 'Demanda comercial',
            'agent_id' => null,
            'department_id' => $crmDeptId,
        ])->assertSessionDoesntHaveErrors();

        // CRM user vê o chamado
        $this->actingAs($crmUser->fresh(), 'admin');
        $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->assertSee('CLIENTE CRM');
    });

    it('agente comum não pode forçar um departamento explícito', function () {
        $atendenteSuporte = User::factory()->agent()->create([
            'department_id' => $this->suporteDept->id,
        ]);

        $this->actingAs($atendenteSuporte, 'admin');

        $this->post(route('agent.ticket.store'), [
            'company_id' => $this->company->id,
            'status_id' => $this->pendingStatus->id,
            'category_id' => $this->parentCategory->category_id,
            'sub_category_id' => $this->subCategory->category_id,
            'contact' => 'CLIENTE SEM OVERRIDE',
            'trouble' => 'Tentativa sem permissão',
            'agent_id' => null,
            'department_id' => $this->comercialDept->id,
        ])->assertSessionHasErrors('department_id');

        $this->assertDatabaseMissing('ticketit', [
            'contact' => 'CLIENTE SEM OVERRIDE',
        ]);
    });

});
