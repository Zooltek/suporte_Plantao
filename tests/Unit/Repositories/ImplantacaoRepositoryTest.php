<?php

/**
 * Testes de integração do ImplantacaoRepository.
 *
 * O repositório possui fallback para SQLite no cálculo de minutos,
 * então a suíte valida o mesmo comportamento em memória.
 */

use App\Models\Company;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Ticket\Status;
use App\Models\User;
use App\Repositories\ImplantacaoRepository;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ir_module(): Module
{
    return Module::firstOrCreate(['name' => 'Implantação'], ['project' => 'EasyMaster']);
}

function ir_agent(): User
{
    return User::factory()->agent()->create();
}

function ir_insertRecord(int $scheduleId, int $moduleId, int $customerId, int $agentId, int $status = 1): void
{
    DB::table('schedule_record')->insert([
        'schedule_id' => $scheduleId,
        'module_id'   => $moduleId,
        'customer_id' => $customerId,
        'agent_id'    => $agentId,
        'status'      => $status,
        'contact'     => 'Teste',
        'start'       => now()->subHours(3),
        'end'         => now()->subHours(1),
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
}

function ir_scheduleWithRecord(Company $company, string $status = 'con'): Schedule
{
    $agent  = ir_agent();
    $module = ir_module();

    $schedule = Schedule::factory()->create([
        'customer_id' => $company->id,
        'agent_id'    => $agent->id,
        'module_id'   => $module->id,
        'status'      => $status,
    ]);

    ir_insertRecord($schedule->id, $module->id, $company->id, $agent->id);

    return $schedule->fresh();
}

function ir_repo(): ImplantacaoRepository
{
    return new ImplantacaoRepository();
}

// ─── getImplementationClientIds ───────────────────────────────────────────────

describe('ImplantacaoRepository — getImplementationClientIds()', function () {

    it('retorna IDs de clientes que têm records com status 1', function () {
        $company = Company::factory()->create();
        ir_scheduleWithRecord($company);

        $ids = ir_repo()->getImplementationClientIds();

        expect($ids)->toContain($company->id);
    });

    it('não retorna IDs de clientes sem records ativos', function () {
        $company = Company::factory()->create();
        $agent   = ir_agent();
        $module  = ir_module();

        $schedule = Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'module_id'   => $module->id,
            'status'      => 'con',
        ]);
        ir_insertRecord($schedule->id, $module->id, $company->id, $agent->id, status: 0);

        $ids = ir_repo()->getImplementationClientIds();

        expect($ids)->not->toContain($company->id);
    });

    it('retorna array vazio quando não há records', function () {
        expect(ir_repo()->getImplementationClientIds())->toBe([]);
    });

});

// ─── countActiveSchedules ─────────────────────────────────────────────────────

describe('ImplantacaoRepository — countActiveSchedules()', function () {

    it('conta apenas agendamentos não finalizados/cancelados com records ativos', function () {
        $company = Company::factory()->create();

        ir_scheduleWithRecord($company, 'con'); // conta
        ir_scheduleWithRecord($company, 'fin'); // não
        ir_scheduleWithRecord($company, 'can'); // não

        expect(ir_repo()->countActiveSchedules())->toBe(1);
    });

    it('exclui agendamentos com status del (scope active)', function () {
        $company = Company::factory()->create();

        ir_scheduleWithRecord($company, 'con'); // conta
        ir_scheduleWithRecord($company, 'del'); // excluído pelo scope active()

        expect(ir_repo()->countActiveSchedules())->toBe(1);
    });

    it('exclui agendamentos sem records ativos', function () {
        $module = ir_module();
        $agent  = ir_agent();

        Schedule::factory()->create([
            'status'    => 'con',
            'module_id' => $module->id,
            'agent_id'  => $agent->id,
        ]);

        expect(ir_repo()->countActiveSchedules())->toBe(0);
    });

    it('retorna zero quando não há agendamentos', function () {
        expect(ir_repo()->countActiveSchedules())->toBe(0);
    });

});

// ─── getOpenTicketsCount ──────────────────────────────────────────────────────

describe('ImplantacaoRepository — getOpenTicketsCount()', function () {

    it('retorna zero com lista de clientIds vazia', function () {
        expect(ir_repo()->getOpenTicketsCount([]))->toBe(0);
    });

    it('conta apenas tickets não-terminais dos clientes informados', function () {
        $company = Company::factory()->create();

        // Cria status explicitamente para isolamento do teste
        $openStatus     = DB::table('ticketit_statuses')->insertGetId(['name' => 'Aberto',     'color' => '#0f0', 'is_terminal' => 0]);
        $terminalStatus = DB::table('ticketit_statuses')->insertGetId(['name' => 'Finalizado', 'color' => '#f00', 'is_terminal' => 1]);

        DB::table('ticketit')->insert([
            'subject'      => 'Aberto',
            'content'      => 'Conteúdo',
            'status_id'    => $openStatus,
            'priority_id'  => 1,
            'user_id'      => 1,
            'agent_id'     => null,
            'category_id'  => 1,
            'company_id'   => $company->id,
            'rate_id'      => 0,
            'visible'      => 1,
            'is_recurring' => 0,
            'origin_id'    => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('ticketit')->insert([
            'subject'      => 'Finalizado',
            'content'      => 'Conteúdo',
            'status_id'    => $terminalStatus,
            'priority_id'  => 1,
            'user_id'      => 1,
            'agent_id'     => null,
            'category_id'  => 1,
            'company_id'   => $company->id,
            'rate_id'      => 0,
            'visible'      => 1,
            'is_recurring' => 0,
            'origin_id'    => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $count = ir_repo()->getOpenTicketsCount([$company->id]);

        expect($count)->toBe(1);
    });

    it('ignora tickets de clientes não listados', function () {
        $company  = Company::factory()->create();
        $outra    = Company::factory()->create();

        DB::table('ticketit')->insert([
            'subject'      => 'Ticket Outra',
            'content'      => 'X',
            'status_id'    => 1,
            'priority_id'  => 1,
            'user_id'      => 1,
            'agent_id'     => null,
            'category_id'  => 1,
            'company_id'   => $outra->id,
            'rate_id'      => 0,
            'visible'      => 1,
            'is_recurring' => 0,
            'origin_id'    => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $count = ir_repo()->getOpenTicketsCount([$company->id]);

        expect($count)->toBe(0);
    });

});

// ─── getTotalMinutes ──────────────────────────────────────────────────────────

describe('ImplantacaoRepository — getTotalMinutes()', function () {

    it('retorna zero com lista de clientIds vazia', function () {
        expect(ir_repo()->getTotalMinutes([]))->toBe(0);
    });

    it('retorna total de minutos também em SQLite via fallback compatível', function () {
        $company = Company::factory()->create();
        ir_scheduleWithRecord($company);

        $total = ir_repo()->getTotalMinutes([$company->id]);

        expect($total)->toBe(120);
    });

});

// ─── getClientsWithDetails ────────────────────────────────────────────────────

describe('ImplantacaoRepository — getClientsWithDetails()', function () {

    it('retorna coleção vazia com lista de clientIds vazia', function () {
        $result = ir_repo()->getClientsWithDetails([]);
        expect($result)->toHaveCount(0);
    });

    it('retorna apenas clientes cujos IDs foram informados', function () {
        $clientA = Company::factory()->create(['name' => 'Alpha SA']);
        $clientB = Company::factory()->create(['name' => 'Beta Ltda']);
        Company::factory()->create(['name' => 'Gama Inc']);

        ir_scheduleWithRecord($clientA);
        ir_scheduleWithRecord($clientB);

        $ids    = [$clientA->id, $clientB->id];
        $result = ir_repo()->getClientsWithDetails($ids);

        expect($result)->toHaveCount(2);
        $names = $result->pluck('name')->toArray();
        expect($names)->toContain('Alpha SA')
            ->and($names)->toContain('Beta Ltda')
            ->and($names)->not->toContain('Gama Inc');
    });

    it('resultado é ordenado por nome (A → Z)', function () {
        $z = Company::factory()->create(['name' => 'Zebra LTDA']);
        $a = Company::factory()->create(['name' => 'Alpha SA']);

        $result = ir_repo()->getClientsWithDetails([$z->id, $a->id]);

        expect($result->pluck('name')->first())->toBe('Alpha SA');
    });

    it('cada cliente recebe o atributo active_records', function () {
        $company = Company::factory()->create();
        ir_scheduleWithRecord($company);

        $result = ir_repo()->getClientsWithDetails([$company->id]);

        expect($result->first()->active_records)->toBe(1)
            ->and($result->first()->total_minutes)->toBe(120);
    });

    it('eager loading de state e software está carregado', function () {
        $company = Company::factory()->create();
        ir_scheduleWithRecord($company);

        $result = ir_repo()->getClientsWithDetails([$company->id]);

        expect($result->first()->relationLoaded('state'))->toBeTrue()
            ->and($result->first()->relationLoaded('software'))->toBeTrue();
    });

});

// ─── getSchedulesPaginated ────────────────────────────────────────────────────

describe('ImplantacaoRepository — getSchedulesPaginated()', function () {

    it('exclui agendamentos com status fin e can', function () {
        $company = Company::factory()->create();

        ir_scheduleWithRecord($company, 'con');
        ir_scheduleWithRecord($company, 'fin');
        ir_scheduleWithRecord($company, 'can');

        $paginator = ir_repo()->getSchedulesPaginated(20);

        expect($paginator->total())->toBe(1);
    });

    it('exclui agendamentos sem records ativos', function () {
        $module = ir_module();
        $agent  = ir_agent();

        Schedule::factory()->create([
            'status'    => 'con',
            'module_id' => $module->id,
            'agent_id'  => $agent->id,
        ]);

        expect(ir_repo()->getSchedulesPaginated(20)->total())->toBe(0);
    });

    it('respeita o perPage informado', function () {
        $company = Company::factory()->create();
        foreach (range(1, 4) as $_) {
            ir_scheduleWithRecord($company);
        }

        $paginator = ir_repo()->getSchedulesPaginated(2);

        expect($paginator->perPage())->toBe(2)
            ->and($paginator->total())->toBe(4)
            ->and(count($paginator->items()))->toBe(2);
    });

    it('eager loading de customer, agent, module e records está ativo', function () {
        $company = Company::factory()->create();
        ir_scheduleWithRecord($company);

        $schedule = ir_repo()->getSchedulesPaginated(20)->items()[0];

        expect($schedule->relationLoaded('customer'))->toBeTrue()
            ->and($schedule->relationLoaded('agent'))->toBeTrue()
            ->and($schedule->relationLoaded('module'))->toBeTrue()
            ->and($schedule->relationLoaded('records'))->toBeTrue();
    });

    it('ordena por created_at DESC — mais novo primeiro', function () {
        $company = Company::factory()->create();
        $module  = ir_module();
        $agent   = ir_agent();

        $antigo = Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'module_id'   => $module->id,
            'status'      => 'con',
            'created_at'  => now()->subDays(5),
        ]);
        ir_insertRecord($antigo->id, $module->id, $company->id, $agent->id);

        $novo = Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'module_id'   => $module->id,
            'status'      => 'con',
            'created_at'  => now()->subDay(),
        ]);
        ir_insertRecord($novo->id, $module->id, $company->id, $agent->id);

        $primeiro = ir_repo()->getSchedulesPaginated(20)->items()[0];

        expect($primeiro->id)->toBe($novo->id);
    });

});
