<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Agent\CompanyService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('ticket.status_ids.terminal');
});

afterEach(function () {
    Cache::forget('ticket.status_ids.terminal');
});

// ─── Helpers locais ───────────────────────────────────────────────────────────

/**
 * Cria um ticket associado a uma empresa via factory.
 */
function cs_ticket(int $companyId, array $attrs = []): Ticket
{
    return Ticket::factory()->create(array_merge(['company_id' => $companyId], $attrs));
}

// ─── getCompanyHistory ────────────────────────────────────────────────────────

describe('CompanyService — getCompanyHistory', function () {

    it('retorna apenas tickets da empresa solicitada', function () {
        $company = Company::factory()->create();
        $other = Company::factory()->create();

        cs_ticket($company->id, ['subject' => 'Chamado Empresa A']);
        cs_ticket($other->id, ['subject' => 'Chamado Empresa B']);

        $result = app(CompanyService::class)->getCompanyHistory($company, []);

        $tickets = $result['tickets'];

        expect($tickets->total())->toBe(1);
    });

    it('filtro agent_id retorna apenas tickets do agente', function () {
        $company = Company::factory()->create();
        $agent1 = User::factory()->agent()->create();
        $agent2 = User::factory()->agent()->create();

        cs_ticket($company->id, ['agent_id' => $agent1->id]);
        cs_ticket($company->id, ['agent_id' => $agent1->id]);
        cs_ticket($company->id, ['agent_id' => $agent2->id]);

        $result = app(CompanyService::class)->getCompanyHistory($company, [
            'agent_id' => $agent1->id,
        ]);

        expect($result['tickets']->total())->toBe(2);
    });

    it('filtro category_id retorna apenas tickets da categoria', function () {
        $company = Company::factory()->create();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        $otherCat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        cs_ticket($company->id, ['category_id' => $cat->category_id]);
        cs_ticket($company->id, ['category_id' => $cat->category_id]);
        cs_ticket($company->id, ['category_id' => $otherCat->category_id]);

        $result = app(CompanyService::class)->getCompanyHistory($company, [
            'category_id' => $cat->category_id,
        ]);

        expect($result['tickets']->total())->toBe(2);
    });

    it('filtro start exclui tickets mais antigos que o período', function () {
        $company = Company::factory()->create();

        cs_ticket($company->id, ['created_at' => now()->subDays(30), 'updated_at' => now()->subDays(30)]);
        cs_ticket($company->id, ['created_at' => now(),              'updated_at' => now()]);

        $result = app(CompanyService::class)->getCompanyHistory($company, [
            'start' => now()->subDays(7)->toDateString(),
        ]);

        expect($result['tickets']->total())->toBe(1);
    });

    it('filtro end exclui tickets mais recentes que o período', function () {
        $company = Company::factory()->create();

        cs_ticket($company->id, ['created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)]);
        cs_ticket($company->id, ['created_at' => now(),              'updated_at' => now()]);

        $result = app(CompanyService::class)->getCompanyHistory($company, [
            'end' => now()->subDays(5)->toDateString(),
        ]);

        expect($result['tickets']->total())->toBe(1);
    });

    it('modo normal retorna paginação (LengthAwarePaginator)', function () {
        $company = Company::factory()->create();

        for ($i = 0; $i < 20; $i++) {
            cs_ticket($company->id);
        }

        $result = app(CompanyService::class)->getCompanyHistory($company, []);
        $tickets = $result['tickets'];

        expect($tickets)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
            ->and($tickets->perPage())->toBe(15)
            ->and($tickets->total())->toBe(20);
    });

    it('modo AJAX (mode=true) retorna collection limitada ao take', function () {
        $company = Company::factory()->create();

        for ($i = 0; $i < 15; $i++) {
            cs_ticket($company->id);
        }

        $result = app(CompanyService::class)->getCompanyHistory($company, ['mode' => true, 'take' => 5]);
        $tickets = $result['tickets'];

        expect($tickets)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($tickets->count())->toBe(5)
            ->and($result['has_more'])->toBeTrue();
    });

    it('modo AJAX sem has_more quando registros abaixo do take', function () {
        $company = Company::factory()->create();

        cs_ticket($company->id);
        cs_ticket($company->id);

        $result = app(CompanyService::class)->getCompanyHistory($company, ['mode' => true, 'take' => 10]);

        expect($result['has_more'])->toBeFalse();
    });

    it('hasPending=true quando existe ticket na fila de pendências', function () {
        $company = Company::factory()->create();

        cs_ticket($company->id, ['status_id' => Ticket::STATUS_PENDING_ID, 'agent_id' => null]);

        $result = app(CompanyService::class)->getCompanyHistory($company, []);

        expect($result['pending'])->toBeTrue();
    });

    it('hasPending=false quando todos os tickets têm status terminal', function () {
        $company = Company::factory()->create();
        $terminalStatus = Status::factory()->terminal()->create();

        cs_ticket($company->id, ['status_id' => $terminalStatus->id]);
        cs_ticket($company->id, ['status_id' => $terminalStatus->id]);

        $result = app(CompanyService::class)->getCompanyHistory($company, []);

        expect($result['pending'])->toBeFalse();
    });

    it('hasPending=false quando o único ticket da fila é o ticket atual', function () {
        $company = Company::factory()->create();
        $ticket = cs_ticket($company->id, ['status_id' => Ticket::STATUS_PENDING_ID, 'agent_id' => null]);

        // Passa o id do ticket atual — não deve marcar como pendência
        $result = app(CompanyService::class)->getCompanyHistory($company, ['id' => $ticket->id]);

        expect($result['pending'])->toBeFalse();
    });

    it('hasPending=false quando existe chamado aberto fora da fila de pendências', function () {
        $company = Company::factory()->create();

        cs_ticket($company->id, ['status_id' => 1, 'agent_id' => null]);
        cs_ticket($company->id, ['status_id' => Ticket::STATUS_PENDING_ID, 'agent_id' => User::factory()->agent()->create()->id]);

        $result = app(CompanyService::class)->getCompanyHistory($company, []);

        expect($result['pending'])->toBeFalse()
            ->and($result['pendings'])->toBeEmpty();
    });

    it('retorna agendamentos da empresa', function () {
        $company = Company::factory()->create();
        Schedule::factory()->create(['customer_id' => $company->id]);

        $result = app(CompanyService::class)->getCompanyHistory($company, []);

        expect($result['schedules'])->toHaveCount(1);
    });

    it('retorna a empresa no resultado', function () {
        $company = Company::factory()->create(['trade_name' => 'Empresa Teste']);

        $result = app(CompanyService::class)->getCompanyHistory($company, []);

        expect($result['company']->trade_name)->toBe('Empresa Teste');
    });

    it('finalized_tickets contém apenas tickets com status terminal', function () {
        $company = Company::factory()->create();
        $terminal = Status::factory()->terminal()->create();
        $nonTerminal = Status::factory()->create();

        cs_ticket($company->id, ['status_id' => $terminal->id,    'completed_at' => now()]);
        cs_ticket($company->id, ['status_id' => $terminal->id,    'completed_at' => now()->subDay()]);
        cs_ticket($company->id, ['status_id' => $nonTerminal->id, 'completed_at' => null]);

        $result = app(CompanyService::class)->getCompanyHistory($company, []);

        expect($result['finalized_tickets'])->toHaveCount(2)
            ->and($result['finalized_tickets']->pluck('status_id')->unique()->toArray())
            ->toEqual([$terminal->id]);
    });

    it('finalized_tickets retorna no máximo 5 registros', function () {
        $company = Company::factory()->create();
        $terminal = Status::factory()->terminal()->create();

        for ($i = 0; $i < 8; $i++) {
            cs_ticket($company->id, ['status_id' => $terminal->id, 'completed_at' => now()->subDays($i)]);
        }

        $result = app(CompanyService::class)->getCompanyHistory($company, []);

        expect($result['finalized_tickets'])->toHaveCount(5);
    });

    it('finalized_tickets não inclui tickets de outras empresas', function () {
        $company = Company::factory()->create();
        $other = Company::factory()->create();
        $terminal = Status::factory()->terminal()->create();

        cs_ticket($company->id, ['status_id' => $terminal->id, 'completed_at' => now()]);
        cs_ticket($other->id, ['status_id' => $terminal->id, 'completed_at' => now()]);

        $result = app(CompanyService::class)->getCompanyHistory($company, []);

        expect($result['finalized_tickets'])->toHaveCount(1);
    });

});

// ─── listCompanies ────────────────────────────────────────────────────────────

describe('CompanyService — listCompanies', function () {

    it('retorna todas as empresas por padrão', function () {
        Company::factory()->count(3)->create();

        $result = app(CompanyService::class)->listCompanies([]);

        expect($result->total())->toBeGreaterThanOrEqual(3);
    });

    it('filtro status=active retorna apenas empresas ativas', function () {
        Company::factory()->create(['is_active' => true,  'trade_name' => 'Ativa']);
        Company::factory()->create(['is_active' => false, 'trade_name' => 'Inativa']);

        $result = app(CompanyService::class)->listCompanies(['status' => 'active']);

        $names = $result->pluck('trade_name');

        expect($names)->toContain('Ativa')
            ->and($names)->not->toContain('Inativa');
    });

    it('filtro status=inactive retorna apenas empresas inativas', function () {
        Company::factory()->create(['is_active' => true,  'trade_name' => 'Ativa']);
        Company::factory()->create(['is_active' => false, 'trade_name' => 'Inativa']);

        $result = app(CompanyService::class)->listCompanies(['status' => 'inactive']);

        $names = $result->pluck('trade_name');

        expect($names)->toContain('Inativa')
            ->and($names)->not->toContain('Ativa');
    });

    it('filtro search encontra por trade_name', function () {
        Company::factory()->create(['trade_name' => 'Supermercado Lima']);
        Company::factory()->create(['trade_name' => 'Farmácia Central']);

        $result = app(CompanyService::class)->listCompanies(['search' => 'Lima']);

        expect($result->pluck('trade_name'))->toContain('Supermercado Lima')
            ->and($result->pluck('trade_name'))->not->toContain('Farmácia Central');
    });

    it('filtro search encontra por name', function () {
        Company::factory()->create(['name' => 'LTDA Exemplo', 'trade_name' => 'Exemplo']);
        Company::factory()->create(['name' => 'Outra SA',     'trade_name' => 'Outra']);

        $result = app(CompanyService::class)->listCompanies(['search' => 'LTDA Exemplo']);

        expect($result->pluck('name'))->toContain('LTDA Exemplo');
    });

    it('retorna LengthAwarePaginator', function () {
        $result = app(CompanyService::class)->listCompanies([]);

        expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
    });

});

// ─── searchApi ────────────────────────────────────────────────────────────────

describe('CompanyService — searchApi', function () {

    it('busca por query ≥2 chars filtra por name', function () {
        Company::factory()->create(['name' => 'Tech Solutions', 'trade_name' => 'Tech']);
        Company::factory()->create(['name' => 'Padaria Pão Quente', 'trade_name' => 'Padaria']);

        $result = app(CompanyService::class)->searchApi(['q' => 'Tech']);

        expect($result->pluck('name'))->toContain('Tech Solutions')
            ->and($result->pluck('name'))->not->toContain('Padaria Pão Quente');
    });

    it('busca com q de 1 char não aplica filtro (retorna tudo até 15)', function () {
        Company::factory()->count(3)->create();

        $result = app(CompanyService::class)->searchApi(['q' => 'X']);

        // Sem filtro de texto (q muito curto), retorna até 15 registros
        expect($result->count())->toBeGreaterThanOrEqual(3);
    });

    it('filtro status=active exclui empresas inativas', function () {
        Company::factory()->create(['is_active' => true,  'name' => 'Empresa Ativa',   'trade_name' => 'Ativa']);
        Company::factory()->create(['is_active' => false, 'name' => 'Empresa Inativa', 'trade_name' => 'Inativa']);

        $result = app(CompanyService::class)->searchApi(['status' => 'active']);

        expect($result->pluck('name'))->toContain('Empresa Ativa')
            ->and($result->pluck('name'))->not->toContain('Empresa Inativa');
    });

    it('filtro service=ecommerce retorna apenas empresas com e-commerce', function () {
        Company::factory()->create(['has_ecommerce' => true,  'trade_name' => 'Com Ecommerce']);
        Company::factory()->create(['has_ecommerce' => false, 'trade_name' => 'Sem Ecommerce']);

        $result = app(CompanyService::class)->searchApi(['service' => 'ecommerce']);

        $names = $result->pluck('name');

        // Todas retornadas têm e-commerce (a outra não deve aparecer)
        Company::whereIn('name', $names->toArray())->each(function ($c) {
            expect($c->has_ecommerce)->toBeTrue();
        });
    });

    it('retorna no máximo 15 resultados', function () {
        Company::factory()->count(20)->create();

        $result = app(CompanyService::class)->searchApi([]);

        expect($result->count())->toBeLessThanOrEqual(15);
    });

    it('retorna arrays simplificados com os campos públicos da busca', function () {
        Company::factory()->create();

        $result = app(CompanyService::class)->searchApi([]);

        expect($result->first())->toBeArray()->toHaveKeys([
            'id',
            'name',
            'trade_name',
            'cnpj',
            'contact_name',
            'phone',
            'city',
            'state_abbr',
            'observations',
            'is_active',
            'financial_irregular',
            'group_hash',
            'module_types',
            'schedule_rat_modules',
        ]);
    });

});

// ─── toggleAttribute ─────────────────────────────────────────────────────────

describe('CompanyService — toggleAttribute', function () {

    it('bloqueia alternância de situação controlada pelo financeiro', function () {
        $company = Company::factory()->create(['is_active' => true]);

        expect(fn () => app(CompanyService::class)->toggleAttribute($company, 'is_active'))
            ->toThrow(DomainException::class, 'financeiro');

        $this->assertDatabaseHas('customers', ['id' => $company->id, 'is_active' => true]);
    });

    it('alterna has_ecommerce corretamente', function () {
        $company = Company::factory()->create(['has_ecommerce' => false]);

        $result = app(CompanyService::class)->toggleAttribute($company, 'has_ecommerce');

        expect($result)->toBeTrue();
        $this->assertDatabaseHas('customers', ['id' => $company->id, 'has_ecommerce' => true]);
    });

});
