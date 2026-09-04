<?php

use App\Enums\Reports\ImplementationClientSituation;
use App\Enums\Reports\ReportPeriodPreset;
use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\Software;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-03-24 10:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function reportTicketBaseData(): array
{
    DB::table('ticketit_statuses')->updateOrInsert([
        'id' => 1,
    ], [
        'name' => 'Aberto',
        'color' => '#2563eb',
        'is_terminal' => false,
        'requires_schedule' => false,
        'requires_solution' => false,
        'requires_agent' => false,
    ]);

    DB::table('ticketit_statuses')->updateOrInsert([
        'id' => 3,
    ], [
        'name' => 'Resolvido',
        'color' => '#10b981',
        'is_terminal' => true,
        'requires_schedule' => false,
        'requires_solution' => false,
        'requires_agent' => true,
    ]);

    DB::table('ticketit_priorities')->updateOrInsert([
        'id' => 1,
    ], [
        'name' => 'Média',
        'color' => '#0ea5e9',
    ]);

    DB::table('ticketit_origin')->updateOrInsert([
        'id' => 1,
    ], [
        'name' => 'Portal',
    ]);

    $openStatus = Status::query()->findOrFail(1);
    $resolvedStatus = Status::query()->findOrFail(3);
    $priority = Priority::query()->findOrFail(1);
    $origin = Origin::query()->findOrFail(1);
    $agent = User::factory()->agent()->create();
    $requester = User::factory()->create();
    $company = Company::factory()->create();

    return compact('openStatus', 'resolvedStatus', 'priority', 'origin', 'agent', 'requester', 'company');
}

function reportProblemCategory(string $problemName): array
{
    $parent = Category::factory()->create(['parent_id' => 0, 'priority' => Category::PRIORITY_LOW]);
    CategoryDescription::factory()->create([
        'category_id' => $parent->category_id,
        'name' => 'Suporte Técnico',
    ]);

    $problem = Category::factory()->create([
        'parent_id' => $parent->category_id,
        'priority' => Category::PRIORITY_LOW,
    ]);

    CategoryDescription::factory()->create([
        'category_id' => $problem->category_id,
        'name' => $problemName,
    ]);

    return [$parent, $problem];
}

function reportScheduleRecord(Company $company, User $agent, Module $module, Carbon $start): void
{
    $schedule = Schedule::factory()->create([
        'customer_id' => $company->id,
        'agent_id' => $agent->id,
        'module_id' => $module->id,
        'status' => 'con',
        'start_at' => $start->copy(),
    ]);

    Record::factory()->active()->forSchedule($schedule)->create([
        'customer_id' => $company->id,
        'agent_id' => $agent->id,
        'module_id' => $module->id,
        'start' => $start->copy(),
        'end' => $start->copy()->addHours(2),
        'interval_start' => null,
        'interval_end' => null,
    ]);
}

function reportOpenTicket(
    array $base,
    Company $company,
    Category $parent,
    Category $problem,
    Carbon $createdAt,
): void {
    Ticket::factory()->create([
        'author_id' => $base['requester']->id,
        'user_id' => $base['requester']->id,
        'agent_id' => $base['agent']->id,
        'status_id' => $base['openStatus']->id,
        'priority_id' => $base['priority']->id,
        'origin_id' => $base['origin']->id,
        'company_id' => $company->id,
        'category_id' => $parent->category_id,
        'sub_category_id' => $problem->category_id,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

describe('Admin Reports — filters', function () {

    it('carrega o resumo por problema com os últimos 30 dias por padrão', function () {
        actingAsAdmin();

        $base = reportTicketBaseData();
        [$parent, $problem] = reportProblemCategory('Falha na emissão');

        Ticket::factory()->create([
            'author_id' => $base['requester']->id,
            'user_id' => $base['requester']->id,
            'agent_id' => $base['agent']->id,
            'status_id' => $base['openStatus']->id,
            'priority_id' => $base['priority']->id,
            'origin_id' => $base['origin']->id,
            'company_id' => $base['company']->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $problem->category_id,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $response = $this->get(route('admin.reports.daily-problems'))
            ->assertOk()
            ->assertViewIs('admin.reports.daily-problems')
            ->assertViewHas('dateFrom', '2026-02-22')
            ->assertViewHas('dateTo', '2026-03-24')
            ->assertViewHas('displayPeriod', 'Últimos 30 dias (22/02/2026 até 24/03/2026)')
            ->assertViewHas('selectedPeriodPreset', ReportPeriodPreset::LAST_30_DAYS->value)
            ->assertSee('Últimos 7 dias')
            ->assertSee('Este mês')
            ->assertSee('Personalizado');

        $row = collect($response->viewData('data'))->firstWhere('name', 'Falha na emissão');

        expect($row)->not->toBeNull()
            ->and($row['pendings'])->toBe(0)
            ->and($row['pendings_date'])->toBe(1)
            ->and($row['completed'])->toBe(0)
            ->and($row['total'])->toBe(1);
    });

    it('contabiliza tickets concluídos no período mesmo quando foram abertos antes dele', function () {
        actingAsAdmin();

        $base = reportTicketBaseData();
        [$parent, $problem] = reportProblemCategory('Erro de cálculo');

        $ticket = Ticket::factory()->create([
            'author_id' => $base['requester']->id,
            'user_id' => $base['requester']->id,
            'agent_id' => $base['agent']->id,
            'status_id' => $base['resolvedStatus']->id,
            'priority_id' => $base['priority']->id,
            'origin_id' => $base['origin']->id,
            'company_id' => $base['company']->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $problem->category_id,
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
        ]);

        expect($ticket->fresh()->status_id)->toBe($base['resolvedStatus']->id)
            ->and($ticket->fresh()->completed_at?->format('Y-m-d'))->toBe('2026-03-23');

        $response = $this->get(route('admin.reports.daily-problems', [
            'date_from' => '2026-03-23',
            'date_to' => '2026-03-24',
        ]))->assertOk();

        $row = collect($response->viewData('data'))->firstWhere('name', 'Erro de cálculo');

        expect($row)->not->toBeNull()
            ->and($row['pendings'])->toBe(0)
            ->and($row['pendings_date'])->toBe(0)
            ->and($row['completed'])->toBe(1)
            ->and($row['total'])->toBe(1);
    });

    it('aplica o preset hoje no resumo por problema', function () {
        actingAsAdmin();

        $base = reportTicketBaseData();
        [$parent, $problem] = reportProblemCategory('Erro de sincronização');

        Ticket::factory()->create([
            'author_id' => $base['requester']->id,
            'user_id' => $base['requester']->id,
            'agent_id' => $base['agent']->id,
            'status_id' => $base['resolvedStatus']->id,
            'priority_id' => $base['priority']->id,
            'origin_id' => $base['origin']->id,
            'company_id' => $base['company']->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $problem->category_id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
        ]);

        Ticket::factory()->create([
            'author_id' => $base['requester']->id,
            'user_id' => $base['requester']->id,
            'agent_id' => $base['agent']->id,
            'status_id' => $base['resolvedStatus']->id,
            'priority_id' => $base['priority']->id,
            'origin_id' => $base['origin']->id,
            'company_id' => $base['company']->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $problem->category_id,
            'created_at' => now(),
            'updated_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->get(route('admin.reports.daily-problems', [
            'period_preset' => ReportPeriodPreset::TODAY->value,
        ]))
            ->assertOk()
            ->assertViewHas('dateFrom', '2026-03-24')
            ->assertViewHas('dateTo', '2026-03-24')
            ->assertViewHas('displayPeriod', 'Hoje (24/03/2026)')
            ->assertViewHas('selectedPeriodPreset', ReportPeriodPreset::TODAY->value);

        $row = collect($response->viewData('data'))->firstWhere('name', 'Erro de sincronização');

        expect($row)->not->toBeNull()
            ->and($row['pendings'])->toBe(0)
            ->and($row['pendings_date'])->toBe(0)
            ->and($row['completed'])->toBe(1)
            ->and($row['total'])->toBe(1);
    });

    it('aplica o filtro de software no resumo por problema', function () {
        actingAsAdmin();

        $base = reportTicketBaseData();
        [$parent, $problem] = reportProblemCategory('Falha fiscal');
        $softwareA = Software::factory()->create(['name' => 'Amura ERP']);
        $softwareB = Software::factory()->create(['name' => 'Amura PDV']);
        $companyA = $base['company'];
        $companyB = Company::factory()->create(['name' => 'Empresa B', 'software_id' => $softwareB->id]);

        $companyA->update(['software_id' => $softwareA->id]);

        reportOpenTicket($base, $companyA, $parent, $problem, now()->subDays(2));
        reportOpenTicket($base, $companyB, $parent, $problem, now()->subDays(2));

        $response = $this->get(route('admin.reports.daily-problems', [
            'software_id' => $softwareA->id,
        ]))
            ->assertOk()
            ->assertViewHas('selectedPeriodPreset', ReportPeriodPreset::LAST_30_DAYS->value)
            ->assertSee('Amura ERP')
            ->assertSee('Amura PDV');

        $row = collect($response->viewData('data'))->firstWhere('name', 'Falha fiscal');

        expect($row)->not->toBeNull()
            ->and($row['pendings'])->toBe(0)
            ->and($row['pendings_date'])->toBe(1)
            ->and($row['completed'])->toBe(0)
            ->and($row['total'])->toBe(1);
    });

    it('aplica o filtro de resumo por problema quando apenas a data final é informada', function () {
        actingAsAdmin();

        $base = reportTicketBaseData();
        [$parent, $problem] = reportProblemCategory('Falha fiscal');

        Ticket::factory()->create([
            'author_id' => $base['requester']->id,
            'user_id' => $base['requester']->id,
            'agent_id' => $base['agent']->id,
            'status_id' => $base['openStatus']->id,
            'priority_id' => $base['priority']->id,
            'origin_id' => $base['origin']->id,
            'company_id' => $base['company']->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $problem->category_id,
            'created_at' => Carbon::parse('2026-03-10 09:00:00'),
            'updated_at' => Carbon::parse('2026-03-10 09:00:00'),
        ]);

        Ticket::factory()->create([
            'author_id' => $base['requester']->id,
            'user_id' => $base['requester']->id,
            'agent_id' => $base['agent']->id,
            'status_id' => $base['openStatus']->id,
            'priority_id' => $base['priority']->id,
            'origin_id' => $base['origin']->id,
            'company_id' => $base['company']->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $problem->category_id,
            'created_at' => Carbon::parse('2026-03-24 09:00:00'),
            'updated_at' => Carbon::parse('2026-03-24 09:00:00'),
        ]);

        $response = $this->get(route('admin.reports.daily-problems', [
            'date_to' => '2026-03-15',
        ]))
            ->assertOk()
            ->assertViewHas('displayPeriod', 'Até 15/03/2026');

        $row = collect($response->viewData('data'))->firstWhere('name', 'Falha fiscal');

        expect($row)->not->toBeNull()
            ->and($row['pendings'])->toBe(0)
            ->and($row['pendings_date'])->toBe(1)
            ->and($row['completed'])->toBe(0)
            ->and($row['total'])->toBe(1);
    });

    it('aplica o filtro de resumo por problema quando apenas a data inicial é informada', function () {
        actingAsAdmin();

        $base = reportTicketBaseData();
        [$parent, $problem] = reportProblemCategory('Erro de permissão');

        Ticket::factory()->create([
            'author_id' => $base['requester']->id,
            'user_id' => $base['requester']->id,
            'agent_id' => $base['agent']->id,
            'status_id' => $base['openStatus']->id,
            'priority_id' => $base['priority']->id,
            'origin_id' => $base['origin']->id,
            'company_id' => $base['company']->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $problem->category_id,
            'created_at' => Carbon::parse('2026-03-10 09:00:00'),
            'updated_at' => Carbon::parse('2026-03-10 09:00:00'),
        ]);

        Ticket::factory()->create([
            'author_id' => $base['requester']->id,
            'user_id' => $base['requester']->id,
            'agent_id' => $base['agent']->id,
            'status_id' => $base['openStatus']->id,
            'priority_id' => $base['priority']->id,
            'origin_id' => $base['origin']->id,
            'company_id' => $base['company']->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $problem->category_id,
            'created_at' => Carbon::parse('2026-03-24 09:00:00'),
            'updated_at' => Carbon::parse('2026-03-24 09:00:00'),
        ]);

        $response = $this->get(route('admin.reports.daily-problems', [
            'date_from' => '2026-03-20',
        ]))
            ->assertOk()
            ->assertViewHas('displayPeriod', 'A partir de 20/03/2026');

        $row = collect($response->viewData('data'))->firstWhere('name', 'Erro de permissão');

        expect($row)->not->toBeNull()
            ->and($row['pendings'])->toBe(1)
            ->and($row['pendings_date'])->toBe(1)
            ->and($row['completed'])->toBe(0)
            ->and($row['total'])->toBe(2);
    });

    it('aplica o filtro de clientes em implantação quando apenas a data inicial é informada', function () {
        actingAsAdmin();

        $agent = User::factory()->agent()->create();
        $module = Module::factory()->create(['name' => 'Implantação']);
        $recentClient = Company::factory()->create(['name' => 'Cliente Recente']);
        $oldClient = Company::factory()->create(['name' => 'Cliente Antigo']);

        reportScheduleRecord($recentClient, $agent, $module, now()->subDays(10)->setTime(9, 0));
        reportScheduleRecord($oldClient, $agent, $module, now()->subDays(45)->setTime(9, 0));

        $response = $this->get(route('admin.reports.implementation-clients', [
            'date_from' => '2026-02-23',
        ]))
            ->assertOk()
            ->assertViewIs('admin.reports.implementation-clients')
            ->assertViewHas('displayPeriod', 'A partir de 23/02/2026')
            ->assertViewHas('totalClients', 1);

        $clientIds = $response->viewData('clients')->pluck('id')->all();

        expect($clientIds)->toContain($recentClient->id)
            ->and($clientIds)->not->toContain($oldClient->id);
    });

    it('aplica o filtro de clientes em implantação quando apenas a data final é informada', function () {
        actingAsAdmin();

        $agent = User::factory()->agent()->create();
        $module = Module::factory()->create(['name' => 'Implantação']);
        $recentClient = Company::factory()->create(['name' => 'Cliente Recente']);
        $oldClient = Company::factory()->create(['name' => 'Cliente Antigo']);

        reportScheduleRecord($recentClient, $agent, $module, now()->subDays(10)->setTime(9, 0));
        reportScheduleRecord($oldClient, $agent, $module, now()->subDays(45)->setTime(9, 0));

        $response = $this->get(route('admin.reports.implementation-clients', [
            'date_to' => '2026-02-20',
        ]))
            ->assertOk()
            ->assertViewHas('displayPeriod', 'Até 20/02/2026')
            ->assertViewHas('totalClients', 1);

        $clientIds = $response->viewData('clients')->pluck('id')->all();

        expect($clientIds)->toContain($oldClient->id)
            ->and($clientIds)->not->toContain($recentClient->id);
    });

    it('aplica o preset dos últimos 7 dias em clientes em implantação', function () {
        actingAsAdmin();

        $agent = User::factory()->agent()->create();
        $module = Module::factory()->create(['name' => 'Implantação']);
        $recentClient = Company::factory()->create(['name' => 'Cliente da Semana']);
        $oldClient = Company::factory()->create(['name' => 'Cliente Fora da Semana']);

        reportScheduleRecord($recentClient, $agent, $module, now()->subDays(3)->setTime(9, 0));
        reportScheduleRecord($oldClient, $agent, $module, now()->subDays(12)->setTime(9, 0));

        $response = $this->get(route('admin.reports.implementation-clients', [
            'period_preset' => ReportPeriodPreset::LAST_7_DAYS->value,
        ]))
            ->assertOk()
            ->assertViewHas('dateFrom', '2026-03-17')
            ->assertViewHas('dateTo', '2026-03-24')
            ->assertViewHas('displayPeriod', 'Últimos 7 dias (17/03/2026 até 24/03/2026)')
            ->assertViewHas('selectedPeriodPreset', ReportPeriodPreset::LAST_7_DAYS->value)
            ->assertViewHas('totalClients', 1);

        $clientIds = $response->viewData('clients')->pluck('id')->all();

        expect($clientIds)->toContain($recentClient->id)
            ->and($clientIds)->not->toContain($oldClient->id);
    });

    it('aplica o filtro de situação em clientes em implantação', function () {
        actingAsAdmin();

        $base = reportTicketBaseData();
        [$parent, $problem] = reportProblemCategory('Implantação em andamento');
        $agent = User::factory()->agent()->create();
        $module = Module::factory()->create(['name' => 'Implantação']);
        $ticketsClient = Company::factory()->create(['name' => 'Cliente com Tickets']);
        $scheduleClient = Company::factory()->create(['name' => 'Cliente com Agenda']);
        $bothClient = Company::factory()->create(['name' => 'Cliente Completo']);

        reportOpenTicket($base, $ticketsClient, $parent, $problem, now()->subDays(2));
        reportScheduleRecord($scheduleClient, $agent, $module, now()->subDays(2)->setTime(9, 0));
        reportOpenTicket($base, $bothClient, $parent, $problem, now()->subDays(2));
        reportScheduleRecord($bothClient, $agent, $module, now()->subDays(2)->setTime(10, 0));

        $ticketsResponse = $this->get(route('admin.reports.implementation-clients', [
            'implementation_status' => ImplementationClientSituation::TICKETS->value,
        ]))->assertOk();

        $ticketsClientIds = $ticketsResponse->viewData('clients')->pluck('id')->all();

        expect($ticketsClientIds)->toContain($ticketsClient->id)
            ->and($ticketsClientIds)->toContain($bothClient->id)
            ->and($ticketsClientIds)->not->toContain($scheduleClient->id);

        $schedulesResponse = $this->get(route('admin.reports.implementation-clients', [
            'implementation_status' => ImplementationClientSituation::SCHEDULES->value,
        ]))->assertOk();

        $scheduleClientIds = $schedulesResponse->viewData('clients')->pluck('id')->all();

        expect($scheduleClientIds)->toContain($scheduleClient->id)
            ->and($scheduleClientIds)->toContain($bothClient->id)
            ->and($scheduleClientIds)->not->toContain($ticketsClient->id);

        $bothResponse = $this->get(route('admin.reports.implementation-clients', [
            'implementation_status' => ImplementationClientSituation::BOTH->value,
        ]))
            ->assertOk()
            ->assertSee('Com tickets')
            ->assertSee('Com agendamentos')
            ->assertSee('Ambos');

        $bothClientIds = $bothResponse->viewData('clients')->pluck('id')->all();

        expect($bothClientIds)->toContain($bothClient->id)
            ->and($bothClientIds)->not->toContain($ticketsClient->id)
            ->and($bothClientIds)->not->toContain($scheduleClient->id);
    });

});
