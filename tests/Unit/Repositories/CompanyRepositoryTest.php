<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Repositories\CompanyRepository;
use Illuminate\Support\Facades\Cache;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cr_company(array $attrs = []): Company
{
    return Company::factory()->create(array_merge(['is_active' => true], $attrs));
}

function cr_ticket(int $companyId, array $attrs = []): Ticket
{
    return Ticket::factory()->create(array_merge(['company_id' => $companyId], $attrs));
}

function cr_terminal(): Status
{
    return Status::factory()->terminal()->create();
}

// ─── paginateCompanyTickets ───────────────────────────────────────────────────

describe('CompanyRepository — paginateCompanyTickets', function () {

    it('retorna apenas tickets da empresa', function () {
        $company = cr_company();
        $other = cr_company();
        cr_ticket($company->id);
        cr_ticket($other->id);

        $result = (new CompanyRepository)->paginateCompanyTickets($company, []);

        expect($result->total())->toBe(1);
    });

    it('aplica filtro agent_id', function () {
        $company = cr_company();
        $agent1 = User::factory()->agent()->create();
        $agent2 = User::factory()->agent()->create();

        cr_ticket($company->id, ['agent_id' => $agent1->id]);
        cr_ticket($company->id, ['agent_id' => $agent2->id]);

        $result = (new CompanyRepository)->paginateCompanyTickets($company, ['agent_id' => $agent1->id]);

        expect($result->total())->toBe(1);
    });

    it('aplica filtro category_id', function () {
        $company = cr_company();
        $cat = Category::factory()->create(['parent_id' => 0]);
        $otherCat = Category::factory()->create(['parent_id' => 0]);

        cr_ticket($company->id, ['category_id' => $cat->category_id]);
        cr_ticket($company->id, ['category_id' => $otherCat->category_id]);

        $result = (new CompanyRepository)->paginateCompanyTickets($company, [
            'category_id' => $cat->category_id,
        ]);

        expect($result->total())->toBe(1);
    });

    it('aplica filtro start', function () {
        $company = cr_company();
        cr_ticket($company->id, ['created_at' => now()->subDays(30)]);
        cr_ticket($company->id, ['created_at' => now()]);

        $result = (new CompanyRepository)->paginateCompanyTickets($company, [
            'start' => now()->subDays(7)->toDateString(),
        ]);

        expect($result->total())->toBe(1);
    });

    it('aplica filtro end', function () {
        $company = cr_company();
        cr_ticket($company->id, ['created_at' => now()->subDays(10)]);
        cr_ticket($company->id, ['created_at' => now()]);

        $result = (new CompanyRepository)->paginateCompanyTickets($company, [
            'end' => now()->subDays(5)->toDateString(),
        ]);

        expect($result->total())->toBe(1);
    });

    it('retorna LengthAwarePaginator com perPage correto', function () {
        $company = cr_company();
        for ($i = 0; $i < 20; $i++) {
            cr_ticket($company->id);
        }

        $result = (new CompanyRepository)->paginateCompanyTickets($company, [], perPage: 10);

        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class)
            ->and($result->perPage())->toBe(10)
            ->and($result->total())->toBe(20);
    });

    it('eager loading de status, agent e category está presente', function () {
        $company = cr_company();
        cr_ticket($company->id);

        $result = (new CompanyRepository)->paginateCompanyTickets($company, []);

        expect($result->first()->relationLoaded('status'))->toBeTrue()
            ->and($result->first()->relationLoaded('agent'))->toBeTrue()
            ->and($result->first()->relationLoaded('category'))->toBeTrue();
    });

});

// ─── limitCompanyTickets ──────────────────────────────────────────────────────

describe('CompanyRepository — limitCompanyTickets', function () {

    it('retorna coleção respeitando o limite', function () {
        $company = cr_company();
        for ($i = 0; $i < 10; $i++) {
            cr_ticket($company->id);
        }

        $result = (new CompanyRepository)->limitCompanyTickets($company, [], take: 5);

        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($result->count())->toBe(5);
    });

});

// ─── getTerminalStatusIds ─────────────────────────────────────────────────────

describe('CompanyRepository — getTerminalStatusIds', function () {

    it('retorna apenas IDs de status terminais', function () {
        $terminal = cr_terminal();
        $nonTerminal = Status::factory()->create(['is_terminal' => false]);

        Cache::flush();
        $ids = (new CompanyRepository)->getTerminalStatusIds();

        expect($ids)->toContain($terminal->id)
            ->and($ids)->not->toContain($nonTerminal->id);
    });

    it('armazena resultado em cache', function () {
        Cache::flush();
        (new CompanyRepository)->getTerminalStatusIds();

        expect(Cache::has('ticket.status_ids.terminal'))->toBeTrue();
    });

    it('retorna array de inteiros', function () {
        Cache::flush();
        $ids = (new CompanyRepository)->getTerminalStatusIds();

        expect($ids)->toBeArray();
        foreach ($ids as $id) {
            expect($id)->toBeInt();
        }
    });

});

// ─── getPendingTickets ────────────────────────────────────────────────────────

describe('CompanyRepository — getPendingTickets', function () {

    it('retorna apenas tickets da fila de pendências sem agente', function () {
        $company = cr_company();
        $terminal = cr_terminal();

        cr_ticket($company->id, ['status_id' => Ticket::STATUS_PENDING_ID, 'agent_id' => null]);
        cr_ticket($company->id, ['status_id' => Ticket::STATUS_PENDING_ID, 'agent_id' => 7]);
        cr_ticket($company->id, ['status_id' => 1, 'agent_id' => null]);
        cr_ticket($company->id, ['status_id' => $terminal->id]);

        $ids = [$terminal->id];
        $result = (new CompanyRepository)->getPendingTickets($company, $ids);

        expect($result->count())->toBe(1)
            ->and((int) $result->first()->status_id)->toBe(Ticket::STATUS_PENDING_ID)
            ->and($result->first()->agent_id)->toBeNull();
    });

    it('retorna collection vazia quando não há tickets pendentes sem agente', function () {
        $company = cr_company();
        $terminal = cr_terminal();

        cr_ticket($company->id, ['status_id' => Ticket::STATUS_PENDING_ID, 'agent_id' => 4]);
        cr_ticket($company->id, ['status_id' => 1, 'agent_id' => null]);
        cr_ticket($company->id, ['status_id' => $terminal->id]);

        $result = (new CompanyRepository)->getPendingTickets($company, [$terminal->id]);

        expect($result->count())->toBe(0);
    });

    it('não retorna tickets de outras empresas', function () {
        $company = cr_company();
        $other = cr_company();

        cr_ticket($other->id, ['status_id' => Ticket::STATUS_PENDING_ID]);

        $result = (new CompanyRepository)->getPendingTickets($company, []);

        expect($result->count())->toBe(0);
    });

});

// ─── getFinalizedTickets ──────────────────────────────────────────────────────

describe('CompanyRepository — getFinalizedTickets', function () {

    it('retorna apenas tickets com status terminal', function () {
        $company = cr_company();
        $terminal = cr_terminal();
        $nonTerminal = Status::factory()->create(['is_terminal' => false]);

        cr_ticket($company->id, ['status_id' => $terminal->id,    'completed_at' => now()]);
        cr_ticket($company->id, ['status_id' => $nonTerminal->id, 'completed_at' => null]);

        $result = (new CompanyRepository)->getFinalizedTickets($company, [$terminal->id]);

        expect($result->count())->toBe(1)
            ->and((int) $result->first()->status_id)->toBe($terminal->id);
    });

    it('respeita o limite máximo de registros', function () {
        $company = cr_company();
        $terminal = cr_terminal();

        for ($i = 0; $i < 8; $i++) {
            cr_ticket($company->id, [
                'status_id' => $terminal->id,
                'completed_at' => now()->subDays($i),
            ]);
        }

        $result = (new CompanyRepository)->getFinalizedTickets($company, [$terminal->id], limit: 5);

        expect($result->count())->toBe(5);
    });

    it('não retorna tickets de outras empresas', function () {
        $company = cr_company();
        $other = cr_company();
        $terminal = cr_terminal();

        cr_ticket($other->id, ['status_id' => $terminal->id, 'completed_at' => now()]);

        $result = (new CompanyRepository)->getFinalizedTickets($company, [$terminal->id]);

        expect($result->count())->toBe(0);
    });

    it('eager loading de status, agent e category está presente', function () {
        $company = cr_company();
        $terminal = cr_terminal();

        cr_ticket($company->id, ['status_id' => $terminal->id, 'completed_at' => now()]);

        $result = (new CompanyRepository)->getFinalizedTickets($company, [$terminal->id]);

        $ticket = $result->first();
        expect($ticket->relationLoaded('status'))->toBeTrue()
            ->and($ticket->relationLoaded('agent'))->toBeTrue()
            ->and($ticket->relationLoaded('category'))->toBeTrue();
    });

});

// ─── getCompanySchedules ──────────────────────────────────────────────────────

describe('CompanyRepository — getCompanySchedules', function () {

    it('retorna agendamentos da empresa em ordem decrescente', function () {
        $company = cr_company();

        Schedule::factory()->create([
            'customer_id' => $company->id,
            'start_at' => now()->subDay(),
        ]);
        Schedule::factory()->create([
            'customer_id' => $company->id,
            'start_at' => now(),
        ]);

        $result = (new CompanyRepository)->getCompanySchedules($company);

        expect($result->count())->toBe(2)
            ->and($result->first()->start_at->gt($result->last()->start_at))->toBeTrue();
    });

    it('não retorna agendamentos de outras empresas', function () {
        $company = cr_company();
        $other = cr_company();

        Schedule::factory()->create(['customer_id' => $other->id]);

        $result = (new CompanyRepository)->getCompanySchedules($company);

        expect($result->count())->toBe(0);
    });

});

// ─── listWithFilters ──────────────────────────────────────────────────────────

describe('CompanyRepository — listWithFilters', function () {

    it('retorna LengthAwarePaginator', function () {
        $result = (new CompanyRepository)->listWithFilters([], 15);

        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    });

    it('filtro status=active retorna apenas ativas', function () {
        Company::factory()->create(['is_active' => true,  'trade_name' => 'Ativa X']);
        Company::factory()->create(['is_active' => false, 'trade_name' => 'Inativa X']);

        $result = (new CompanyRepository)->listWithFilters(['status' => 'active'], 15);

        $names = $result->pluck('trade_name');
        expect($names)->toContain('Ativa X')
            ->and($names)->not->toContain('Inativa X');
    });

    it('filtro search encontra por trade_name', function () {
        Company::factory()->create(['trade_name' => 'UniqueTradeXYZ']);

        $result = (new CompanyRepository)->listWithFilters(['search' => 'UniqueTradeXYZ'], 15);

        expect($result->pluck('trade_name'))->toContain('UniqueTradeXYZ');
    });

});

// ─── searchForApi ─────────────────────────────────────────────────────────────

describe('CompanyRepository — searchForApi', function () {

    it('retorna no máximo 15 resultados', function () {
        Company::factory()->count(20)->create();

        $result = (new CompanyRepository)->searchForApi([]);

        expect($result->count())->toBeLessThanOrEqual(15);
    });

    it('busca por q≥2 chars filtra por name', function () {
        Company::factory()->create(['name' => 'ApiSearchUnique', 'trade_name' => 'ApiU']);

        $result = (new CompanyRepository)->searchForApi(['q' => 'ApiSearchUnique']);

        expect($result->contains(fn ($item) => $item['name'] ?? null === 'ApiSearchUnique'))->toBeTrue();
    });

    it('filtro status=active exclui inativas', function () {
        Company::factory()->create(['is_active' => true,  'name' => 'AtivaApi',   'trade_name' => 'AtivaApi']);
        Company::factory()->create(['is_active' => false, 'name' => 'InativaApi', 'trade_name' => 'InativaApi']);

        $result = (new CompanyRepository)->searchForApi(['status' => 'active']);

        $names = collect($result)->pluck('name')->filter();
        expect($names)->toContain('AtivaApi')
            ->and($names)->not->toContain('InativaApi');
    });

});

// ─── getLatestTicketTechnicalContexts ────────────────────────────────────────

describe('CompanyRepository — getLatestTicketTechnicalContexts', function () {

    it('retorna o último version/release válido por empresa', function () {
        $companyA = cr_company();
        $companyB = cr_company();

        cr_ticket($companyA->id, [
            'version' => '01.28.01',
            'release' => 'R1',
            'created_at' => now()->subDays(2),
        ]);

        cr_ticket($companyA->id, [
            'version' => '01.32.01',
            'release' => 'R9',
            'created_at' => now()->subDay(),
        ]);

        cr_ticket($companyB->id, [
            'version' => '02.10.00',
            'release' => 'WEB-3',
            'created_at' => now(),
        ]);

        $result = (new CompanyRepository)->getLatestTicketTechnicalContexts([$companyA->id, $companyB->id]);

        expect($result[$companyA->id]['version'])->toBe('01.32.01')
            ->and($result[$companyA->id]['release'])->toBe('R9')
            ->and($result[$companyB->id]['version'])->toBe('02.10.00')
            ->and($result[$companyB->id]['release'])->toBe('WEB-3');
    });

    it('ignora tickets sem versão e release informados', function () {
        $company = cr_company();

        cr_ticket($company->id, [
            'version' => '',
            'release' => '',
            'created_at' => now(),
        ]);

        $result = (new CompanyRepository)->getLatestTicketTechnicalContexts([$company->id]);

        expect($result)->toBeArray()
            ->and($result)->not->toHaveKey($company->id);
    });

});

// ─── save ─────────────────────────────────────────────────────────────────────

describe('CompanyRepository — save', function () {

    it('persiste alterações no banco', function () {
        $company = cr_company(['trade_name' => 'Original']);
        $company->trade_name = 'Alterada';

        (new CompanyRepository)->save($company);

        $this->assertDatabaseHas('customers', [
            'id' => $company->id,
            'trade_name' => 'Alterada',
        ]);
    });

});
