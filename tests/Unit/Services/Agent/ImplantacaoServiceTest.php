<?php

use App\Contracts\Repositories\ImplantacaoRepositoryInterface;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Ticket\Status;
use App\Models\User;
use App\Services\Agent\ImplantacaoService;
use App\Support\Tickets\TicketStatusCatalog;
use Illuminate\Support\Facades\DB;

/** Cria (ou localiza) o módulo de implantação */
function implModule(): Module
{
    return Module::firstOrCreate(['name' => 'Implantação'], ['project' => 'EasyMaster']);
}

/** Cria um agente SEM autenticar na sessão */
function implAgent(): User
{
    return User::factory()->agent()->create();
}

/** Insere um record ativo na tabela, sem acionar Mass-Assignment guard */
function insertRecord(int $scheduleId, int $moduleId, int $customerId, int $agentId, int $status = 1): void
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

/** Cria Schedule + Record ativo para o cliente informado */
function implScheduleWithRecord(Company $company, string $status = 'con'): Schedule
{
    $agent  = implAgent();
    $module = implModule();

    $schedule = Schedule::factory()->create([
        'customer_id' => $company->id,
        'agent_id'    => $agent->id,
        'module_id'   => $module->id,
        'status'      => $status,
    ]);

    insertRecord($schedule->id, $module->id, $company->id, $agent->id);

    return $schedule->fresh();
}

/** Resolve o serviço via container para garantir injeção de dependência */
function makeImplantacaoService(): ImplantacaoService
{
    return app(ImplantacaoService::class);
}

function implSyncTicketStatus(int $statusId): Status
{
    $definition = TicketStatusCatalog::findById($statusId);

    if ($definition === null) {
        throw new InvalidArgumentException("Status canônico {$statusId} não encontrado.");
    }

    return Status::query()->updateOrCreate(
        ['id' => $definition['id']],
        [
            'name' => $definition['name'],
            'color' => $definition['color'],
            'is_terminal' => $definition['is_terminal'],
            'requires_schedule' => $definition['requires_schedule'],
            'requires_solution' => $definition['requires_solution'],
            'requires_agent' => $definition['requires_agent'],
        ]
    );
}

// ─────────────────────────────────────────────
// getStats()
// ─────────────────────────────────────────────
describe('ImplantacaoService — getStats()', function () {

    it('totalClients conta apenas clientes com records ativos', function () {
        $clientA = Company::factory()->create();
        $clientB = Company::factory()->create();
        Company::factory()->create(); // sem record — não deve contar

        implScheduleWithRecord($clientA);
        implScheduleWithRecord($clientB);

        $stats = makeImplantacaoService()->getStats();

        expect($stats['totalClients'])->toBe(2)
            ->and($stats['totalMinutes'])->toBe(240)
            ->and($stats['totalHoursFormatted'])->toBe('4h 00min');
    });

    it('totalSchedules exclui agendamentos com status fin ou can', function () {
        $company = Company::factory()->create();

        implScheduleWithRecord($company, 'con'); // deve contar
        implScheduleWithRecord($company, 'fin'); // não deve
        implScheduleWithRecord($company, 'can'); // não deve

        $stats = makeImplantacaoService()->getStats();

        expect($stats['totalSchedules'])->toBe(1);
    });

    it('totalSchedules exclui agendamentos deletados', function () {
        $company = Company::factory()->create();

        implScheduleWithRecord($company, 'con'); // conta
        implScheduleWithRecord($company, 'del'); // active() scope exclui 'del'

        $stats = makeImplantacaoService()->getStats();

        expect($stats['totalSchedules'])->toBe(1);
    });

    it('totalSchedules exclui agendamentos sem records ativos', function () {
        $module = implModule();
        $agent  = implAgent();

        Schedule::factory()->create([
            'status'    => 'con',
            'module_id' => $module->id,
            'agent_id'  => $agent->id,
        ]);
        // nenhum record inserido

        $stats = makeImplantacaoService()->getStats();

        expect($stats['totalSchedules'])->toBe(0);
    });

    it('totalOpenTickets conta tickets não finalizados dos clientes em implantação', function () {
        $company = Company::factory()->create();
        implScheduleWithRecord($company);
        implSyncTicketStatus(TicketStatusCatalog::OPEN_ID);
        implSyncTicketStatus(TicketStatusCatalog::RESOLVED_ID);

        DB::table('ticketit')->insert([
            'subject'      => 'Aberto',
            'content'      => 'Conteúdo',
            'status_id'    => 1,
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
            'content'      => 'Fechado',
            'status_id'    => 3, // finalizado — não deve contar
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

        $stats = makeImplantacaoService()->getStats();

        expect($stats['totalOpenTickets'])->toBe(1);
    });

    it('retorna zero em todas as KPIs quando não há dados', function () {
        $stats = makeImplantacaoService()->getStats();

        expect($stats['totalClients'])->toBe(0)
            ->and($stats['totalSchedules'])->toBe(0)
            ->and($stats['totalOpenTickets'])->toBe(0)
            ->and($stats['totalMinutes'])->toBe(0)
            ->and($stats['totalHoursFormatted'])->toBe('—');
    });

});

// ─────────────────────────────────────────────
// getClients()
// ─────────────────────────────────────────────
describe('ImplantacaoService — getClients()', function () {

    it('retorna apenas clientes que têm records ativos', function () {
        $comRecord = Company::factory()->create(['name' => 'Com Record SA']);
        $semRecord = Company::factory()->create(['name' => 'Sem Record Ltda']);

        implScheduleWithRecord($comRecord);

        $nomes = makeImplantacaoService()->getClients()->pluck('name')->toArray();

        expect($nomes)->toContain('Com Record SA')
            ->and($nomes)->not->toContain('Sem Record Ltda');
    });

    it('resultado é ordenado pelo nome do cliente (A → Z)', function () {
        $z = Company::factory()->create(['name' => 'Zebra LTDA']);
        $a = Company::factory()->create(['name' => 'Alpha SA']);
        $m = Company::factory()->create(['name' => 'Medio Inc']);

        implScheduleWithRecord($z);
        implScheduleWithRecord($a);
        implScheduleWithRecord($m);

        $nomes = makeImplantacaoService()->getClients()->pluck('name')->toArray();

        expect($nomes)->toBe(['Alpha SA', 'Medio Inc', 'Zebra LTDA']);
    });

    it('active_records é calculado por cliente corretamente', function () {
        $company = Company::factory()->create();
        $module  = implModule();
        $agent   = implAgent();

        // 2 records ativos
        foreach (range(1, 2) as $_) {
            $schedule = Schedule::factory()->create([
                'customer_id' => $company->id,
                'agent_id'    => $agent->id,
                'module_id'   => $module->id,
                'status'      => 'con',
            ]);
            insertRecord($schedule->id, $module->id, $company->id, $agent->id, status: 1);
        }

        // 1 record inativo — não deve contar
        $scheduleInativo = Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'module_id'   => $module->id,
            'status'      => 'con',
        ]);
        insertRecord($scheduleInativo->id, $module->id, $company->id, $agent->id, status: 0);

        $client = makeImplantacaoService()->getClients()->first();

        expect($client->active_records)->toBe(2)
            ->and($client->total_minutes)->toBe(360)
            ->and($client->total_hours_formatted)->toBe('6h 00min');
    });

    it('retorna coleção vazia quando não há registros', function () {
        expect(makeImplantacaoService()->getClients())->toHaveCount(0);
    });

});

// ─────────────────────────────────────────────
// getSchedules()
// ─────────────────────────────────────────────
describe('ImplantacaoService — getSchedules()', function () {

    it('exclui agendamentos com status fin e can', function () {
        $company = Company::factory()->create();

        implScheduleWithRecord($company, 'con'); // aparece
        implScheduleWithRecord($company, 'fin'); // excluído
        implScheduleWithRecord($company, 'can'); // excluído

        expect(makeImplantacaoService()->getSchedules()->total())->toBe(1);
    });

    it('ordena por created_at DESC — mais novo primeiro', function () {
        $company = Company::factory()->create();
        $module  = implModule();
        $agent   = implAgent();

        $antigo = Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'module_id'   => $module->id,
            'status'      => 'con',
            'created_at'  => now()->subDays(5),
        ]);
        insertRecord($antigo->id, $module->id, $company->id, $agent->id);

        $novo = Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'module_id'   => $module->id,
            'status'      => 'con',
            'created_at'  => now()->subDay(),
        ]);
        insertRecord($novo->id, $module->id, $company->id, $agent->id);

        $primeiro = makeImplantacaoService()->getSchedules()->items()[0];

        expect($primeiro->id)->toBe($novo->id);
    });

    it('eager load: customer, agent, module, records estão carregados', function () {
        $company = Company::factory()->create();
        implScheduleWithRecord($company);

        $schedule = makeImplantacaoService()->getSchedules()->items()[0];

        expect($schedule->relationLoaded('customer'))->toBeTrue()
            ->and($schedule->relationLoaded('agent'))->toBeTrue()
            ->and($schedule->relationLoaded('module'))->toBeTrue()
            ->and($schedule->relationLoaded('records'))->toBeTrue();
    });

    it('paginação respeita perPage informado', function () {
        $company = Company::factory()->create();

        foreach (range(1, 5) as $_) {
            implScheduleWithRecord($company);
        }

        $paginator = makeImplantacaoService()->getSchedules(perPage: 2);

        expect($paginator->perPage())->toBe(2)
            ->and($paginator->total())->toBe(5)
            ->and(count($paginator->items()))->toBe(2);
    });

    it('exclui agendamentos sem records ativos', function () {
        $module = implModule();
        $agent  = implAgent();

        Schedule::factory()->create([
            'status'    => 'con',
            'module_id' => $module->id,
            'agent_id'  => $agent->id,
        ]);
        // nenhum record

        expect(makeImplantacaoService()->getSchedules()->total())->toBe(0);
    });

});
