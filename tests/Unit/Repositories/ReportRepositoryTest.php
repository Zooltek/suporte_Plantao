<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Repositories\ReportRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function rr_company(array $attrs = []): Company
{
    return Company::factory()->create(array_merge(['is_active' => true], $attrs));
}

function rr_period(): array
{
    return [Carbon::now()->subDays(30), Carbon::now()];
}

// ─── getAgentsWithTicketCounts ────────────────────────────────────────────────

describe('ReportRepository — getAgentsWithTicketCounts', function () {

    it('retorna uma Collection (mesmo vazia)', function () {
        [$start, $end] = rr_period();

        $result = (new ReportRepository)->getAgentsWithTicketCounts($start, $end);

        expect($result)->toBeInstanceOf(Collection::class);
    });

    it('contém agentes ativos com ticketit_agent=1', function () {
        $user = User::factory()->create([
            'active' => true,
            'ticketit_agent' => 1,
        ]);

        [$start, $end] = rr_period();
        $result = (new ReportRepository)->getAgentsWithTicketCounts($start, $end);

        expect($result->pluck('id'))->toContain($user->id);
    });

    it('cada agente tem os atributos de contagem esperados', function () {
        $user = User::factory()->create([
            'active' => true,
            'ticketit_agent' => 1,
        ]);

        [$start, $end] = rr_period();
        $result = (new ReportRepository)->getAgentsWithTicketCounts($start, $end);

        $agent = $result->firstWhere('id', $user->id);
        expect($agent)->not->toBeNull();
        expect($agent)->toHaveKey('pendings_total')
            ->toHaveKey('pendings_date')
            ->toHaveKey('completed');
    });

});

// ─── countTicketsWithoutAgent ─────────────────────────────────────────────────

describe('ReportRepository — countTicketsWithoutAgent', function () {

    it('retorna array com as chaves esperadas', function () {
        [$start, $end] = rr_period();

        $result = (new ReportRepository)->countTicketsWithoutAgent($start, $end);

        expect($result)->toBeArray()
            ->toHaveKey('pendings_total')
            ->toHaveKey('pendings_date')
            ->toHaveKey('completed');
    });

    it('todos os valores são inteiros', function () {
        [$start, $end] = rr_period();

        $result = (new ReportRepository)->countTicketsWithoutAgent($start, $end);

        expect($result['pendings_total'])->toBeInt();
        expect($result['pendings_date'])->toBeInt();
        expect($result['completed'])->toBeInt();
    });

    it('conta tickets sem agente com status_id 4', function () {
        $company = rr_company();

        Ticket::factory()->create([
            'company_id' => $company->id,
            'agent_id' => null,
            'status_id' => 4,
            'created_at' => now(),
        ]);

        [$start, $end] = rr_period();
        $result = (new ReportRepository)->countTicketsWithoutAgent($start, $end);

        expect($result['pendings_total'])->toBeGreaterThanOrEqual(1);
    });

    it('considera agent_id 0 como ticket sem agente na fila', function () {
        $company = rr_company();

        Ticket::factory()->create([
            'company_id' => $company->id,
            'agent_id' => 0,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'created_at' => now(),
        ]);

        [$start, $end] = rr_period();
        $result = (new ReportRepository)->countTicketsWithoutAgent($start, $end);

        expect($result['pendings_total'])->toBeGreaterThanOrEqual(1);
    });

});

// ─── getTicketStatsByCompany ──────────────────────────────────────────────────

describe('ReportRepository — getTicketStatsByCompany', function () {

    it('retorna uma Collection', function () {
        [$start, $end] = rr_period();

        $result = (new ReportRepository)->getTicketStatsByCompany($start, $end);

        expect($result)->toBeInstanceOf(Collection::class);
    });

    it('agrupa por company_id e traz estatísticas', function () {
        $company = rr_company();

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => 4,
            'created_at' => now(),
        ]);

        [$start, $end] = rr_period();
        $result = (new ReportRepository)->getTicketStatsByCompany($start, $end);

        $found = $result->firstWhere('company_id', $company->id);
        expect($found)->not->toBeNull();
        expect($found)->toHaveKey('pendings');
        expect($found)->toHaveKey('pendings_date');
        expect($found)->toHaveKey('completed');
    });

});

// ─── getImplementationClientIds ───────────────────────────────────────────────

describe('ReportRepository — getImplementationClientIds', function () {

    it('retorna um array', function () {
        $result = (new ReportRepository)->getImplementationClientIds(null, null);

        expect($result)->toBeArray();
    });

    it('retorna array de IDs únicos (sem duplicatas)', function () {
        $result = (new ReportRepository)->getImplementationClientIds(null, null);

        expect($result)->toBe(array_unique($result));
    });

    it('inclui empresa com ticket aberto', function () {
        $company = rr_company();

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => 4,
        ]);

        $result = (new ReportRepository)->getImplementationClientIds(null, null);

        expect($result)->toContain($company->id);
    });

});

// ─── getClientsWithCounts ─────────────────────────────────────────────────────

describe('ReportRepository — getClientsWithCounts', function () {

    it('retorna uma Collection', function () {
        $result = (new ReportRepository)->getClientsWithCounts([], null, null);

        expect($result)->toBeInstanceOf(Collection::class);
    });

    it('retorna apenas clientes dos IDs informados', function () {
        $company1 = rr_company();
        $company2 = rr_company();

        $result = (new ReportRepository)->getClientsWithCounts([$company1->id], null, null);

        $ids = $result->pluck('id')->toArray();
        expect($ids)->toContain($company1->id);
        expect($ids)->not->toContain($company2->id);
    });

    it('cada cliente tem o atributo open_tickets', function () {
        $company = rr_company();

        $result = (new ReportRepository)->getClientsWithCounts([$company->id], null, null);

        expect($result->first())->toHaveKey('open_tickets');
    });

    it('retorna collection vazia para array de IDs vazio', function () {
        $result = (new ReportRepository)->getClientsWithCounts([], null, null);

        expect($result->count())->toBe(0);
    });

});

// ─── getScheduleActiveCount ───────────────────────────────────────────────────

describe('ReportRepository — getScheduleActiveCount', function () {

    it('retorna um inteiro', function () {
        $result = (new ReportRepository)->getScheduleActiveCount(999999, null, null);

        expect($result)->toBeInt();
    });

    it('retorna zero para cliente sem agendamentos', function () {
        $company = rr_company();

        $result = (new ReportRepository)->getScheduleActiveCount($company->id, null, null);

        expect($result)->toBe(0);
    });

});

// ─── getImplementationMinutes ────────────────────────────────────────────────

it('getImplementationMinutes calcula minutos descontando intervalo também em SQLite', function () {
    $company = rr_company();
    $start   = now()->subHours(3);
    $end     = now()->subMinutes(30);

    DB::table('schedule_record')->insert([
        'schedule_id' => 1,
        'module_id' => 1,
        'customer_id' => $company->id,
        'agent_id' => 1,
        'status' => 1,
        'contact' => 'Teste',
        'start' => $start,
        'end' => $end,
        'interval_start' => $start->copy()->addHour(),
        'interval_end' => $start->copy()->addMinutes(90),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $total = (new ReportRepository)->getImplementationMinutes($company->id, null, null);

    expect($total)->toBe(120);
});

// ─── getTicketStatsByProblem ──────────────────────────────────────────────────

describe('ReportRepository — getTicketStatsByProblem', function () {

    it('retorna uma Collection', function () {
        [$start, $end] = rr_period();

        $result = (new ReportRepository)->getTicketStatsByProblem($start, $end);

        expect($result)->toBeInstanceOf(Collection::class);
    });

    it('cada item tem os campos de estatística esperados', function () {
        [$start, $end] = rr_period();
        $company = rr_company();
        $problem = Category::factory()->withDescription('Erro de Impressão')->create();

        Ticket::factory()->create([
            'company_id' => $company->id,
            'category_id' => $problem->category_id,
            'sub_category_id' => $problem->category_id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $result = (new ReportRepository)->getTicketStatsByProblem($start, $end);
        $item = $result->firstWhere('sub_category_id', $problem->category_id);

        expect($item)->not->toBeNull()
            ->and($item->problem_name)->toBe('Erro de Impressão');
        expect($item)->toHaveKey('problem_name');
        expect($item)->toHaveKey('pendings');
        expect($item)->toHaveKey('pendings_date');
        expect($item)->toHaveKey('completed');
    });

});

describe('ReportRepository — getDashboardTickets', function () {

    it('retorna apenas chamados com status não terminal e sem agente vinculado', function () {
        $company = rr_company();
        $agent = rr_agent();

        $openWithoutAgent = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id' => null,
        ]);

        $openWithAgentZero = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id' => 0,
        ]);

        $openWithAgent = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => Ticket::STATUS_IN_PROGRESS_ID,
            'agent_id' => $agent->id,
        ]);

        $closed = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => 3, // Resolvido (terminal)
            'agent_id' => null,
            'completed_at' => now(),
        ]);

        $result = (new ReportRepository)->getDashboardTickets();
        $ids = $result->pluck('id')->all();

        expect($ids)->toContain($openWithoutAgent->id)
            ->and($ids)->toContain($openWithAgentZero->id)
            ->and($ids)->not->toContain($openWithAgent->id)
            ->and($ids)->not->toContain($closed->id);
    });

});

