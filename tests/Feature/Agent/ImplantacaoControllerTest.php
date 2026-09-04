<?php

use App\Models\Company;

// ─── index() ─────────────────────────────────────────────────────────────────

describe('ImplantacaoController — index()', function () {

    it('retorna view correta para agente autenticado', function () {
        actingAsAgent();

        $this->get(route('agent.implantacao.index'))
            ->assertOk()
            ->assertViewIs('agent.implantacao.index');
    });

    it('view recebe stats como array', function () {
        actingAsAgent();

        $stats = $this->get(route('agent.implantacao.index'))
            ->assertOk()
            ->viewData('stats');

        expect($stats)->toBeArray()
            ->and(array_key_exists('totalClients', $stats))->toBeTrue()
            ->and(array_key_exists('totalSchedules', $stats))->toBeTrue()
            ->and(array_key_exists('totalOpenTickets', $stats))->toBeTrue();
    });

    it('view recebe clients como Collection', function () {
        actingAsAgent();

        $clients = $this->get(route('agent.implantacao.index'))
            ->assertOk()
            ->viewData('clients');

        expect($clients)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('layout reforça o contexto de implantação na visão geral', function () {
        actingAsAgent();

        $this->get(route('agent.implantacao.index'))
            ->assertOk()
            ->assertSee('Visão Geral de Implantação')
            ->assertSee('Agendamentos de Implantação')
            ->assertSee('Relatório de Implantação');
    });

    it('stats refletem clientes com records ativos', function () {
        actingAsAgent();

        $company = Company::factory()->create();
        $agent   = \App\Models\User::factory()->agent()->create();
        $module  = \App\Models\Schedule\Module::firstOrCreate(
            ['name' => 'Implantação'],
            ['project' => 'EasyMaster']
        );
        $schedule = \App\Models\Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'module_id'   => $module->id,
            'status'      => 'con',
        ]);
        \Illuminate\Support\Facades\DB::table('schedule_record')->insert([
            'schedule_id' => $schedule->id,
            'module_id'   => $module->id,
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'status'      => 1,
            'contact'     => 'Teste',
            'start'       => now()->subHours(3),
            'end'         => now()->subHours(1),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $stats = $this->get(route('agent.implantacao.index'))
            ->assertOk()
            ->viewData('stats');

        expect($stats['totalClients'])->toBeGreaterThanOrEqual(1);
    });

    it('redireciona visitante não autenticado', function () {
        $this->get(route('agent.implantacao.index'))
            ->assertRedirect();
    });

});

// ─── schedules() ─────────────────────────────────────────────────────────────

describe('ImplantacaoController — schedules()', function () {

    it('retorna view correta para agente autenticado', function () {
        actingAsAgent();

        $this->get(route('agent.implantacao.schedules'))
            ->assertOk()
            ->assertViewIs('agent.implantacao.schedules');
    });

    it('view recebe schedules como paginador', function () {
        actingAsAgent();

        $schedules = $this->get(route('agent.implantacao.schedules'))
            ->assertOk()
            ->viewData('schedules');

        expect($schedules)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
    });

    it('layout mantém a listagem no contexto de implantação', function () {
        actingAsAgent();

        $this->get(route('agent.implantacao.schedules'))
            ->assertOk()
            ->assertSee('Agendamentos de Implantação')
            ->assertSee('Calendário de Implantação');
    });

    it('redireciona visitante não autenticado', function () {
        $this->get(route('agent.implantacao.schedules'))
            ->assertRedirect();
    });

});
