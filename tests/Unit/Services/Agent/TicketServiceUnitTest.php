<?php

/**
 * Testes UNITÁRIOS do TicketService — Repository mockado via interface.
 *
 * Estes testes isolam a Regra de Negócio do Service sem tocar o banco de dados.
 * Complementam os testes de integração existentes em TicketServiceTest.php.
 */

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\TicketRepositoryInterface;
use App\Exceptions\TicketBusinessException;
use App\Models\Category;
use App\Models\Company;
use App\Models\Tasks\Task;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Agent\TicketService;
use App\Services\Agent\TicketTechnicalContextService;
use App\Support\Tickets\TicketStatusCatalog;
use Mockery\MockInterface;

// ─── Factories de mocks ───────────────────────────────────────────────────────

/**
 * Constrói uma instância real de TicketService injetando um mock de repositório.
 */
function tu_service(MockInterface $repoMock): TicketService
{
    return new TicketService($repoMock, tu_technical_context_service());
}

function tu_technical_context_service(): TicketTechnicalContextService
{
    $repo = Mockery::mock(CompanyRepositoryInterface::class);
    $repo->shouldReceive('getLatestTicketTechnicalContexts')->andReturn([])->byDefault();

    return new TicketTechnicalContextService($repo);
}

/**
 * Cria um mock do repositório com defaults razoáveis (empresa ativa, sem task).
 *
 * @param  Category  $cat  Categoria principal (pai)
 * @param  Category  $sub  Subcategoria (filha de $cat)
 * @param  Company  $company  Empresa ativa
 */
function tu_repo(Category $cat, Category $sub, Company $company): MockInterface
{
    $repo = Mockery::mock(TicketRepositoryInterface::class);

    $repo->shouldReceive('findCategoryById')
        ->with($cat->category_id)
        ->andReturn($cat);

    $repo->shouldReceive('findCategoryById')
        ->with($sub->category_id)
        ->andReturn($sub);

    $repo->shouldReceive('findCompanyOrFail')
        ->with($company->id)
        ->andReturn($company);

    $repo->shouldReceive('isStatusTerminal')->andReturn(false);
    $repo->shouldReceive('isStatusRequiresSchedule')->andReturn(false);
    $repo->shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
    $repo->shouldReceive('save')->once();
    $repo->shouldReceive('syncExtraCategories')->once();
    $repo->shouldReceive('updateAttachments')->andReturn(null);
    $repo->shouldReceive('getSolicitacaoStatusId')->andReturn(99);
    $repo->shouldReceive('createTask')->andReturn(new Task);
    $repo->shouldReceive('updateTicket')->andReturn(null);

    return $repo;
}

/**
 * Cria par de categorias em memória (sem banco).
 */
function tu_cat_pair(): array
{
    $cat = new Category(['parent_id' => 0, 'name' => 'Cat']);
    $cat->category_id = 100;

    $sub = new Category(['parent_id' => 100, 'name' => 'Sub']);
    $sub->category_id = 101;

    return [$cat, $sub];
}

/**
 * Empresa ativa em memória (sem banco).
 */
function tu_company(bool $active = true, bool $irregular = false): Company
{
    $c = new Company;
    $c->id = 1;
    $c->is_active = $active;
    $c->financial_irregular = $irregular;
    $c->observations = '';

    return $c;
}

/**
 * Payload mínimo válido.
 */
function tu_data(array $override = []): array
{
    return array_merge([
        'company_id' => 1,
        'status_id' => Ticket::STATUS_PENDING_ID,
        'category_id' => 100,
        'sub_category_id' => 101,
        'contact' => 'USUARIO TESTE',
        'agent_id' => null,
        'is_recurring' => false,
    ], $override);
}

// ─── Testes ───────────────────────────────────────────────────────────────────

describe('TicketService (unit) — validateBusinessRules', function () {

    it('status terminal sem agente lança TicketBusinessException', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company();
        Status::query()->updateOrCreate(
            ['id' => TicketStatusCatalog::IN_PROGRESS_ID],
            [
                'name' => 'Em andamento',
                'color' => '#2563eb',
                'is_terminal' => false,
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent' => true,
            ]
        );

        $repo = Mockery::mock(TicketRepositoryInterface::class);
        $repo->shouldReceive('findCategoryById')->with(100)->andReturn($cat);
        $repo->shouldReceive('findCategoryById')->with(101)->andReturn($sub);
        $repo->shouldReceive('findCompanyOrFail')->with(1)->andReturn($company);
        $repo->shouldReceive('isStatusTerminal')->andReturn(true);
        $repo->shouldReceive('isStatusRequiresSchedule')->andReturn(false);

        $service = tu_service($repo);
        $data = tu_data(['status_id' => TicketStatusCatalog::IN_PROGRESS_ID, 'agent_id' => null]);

        expect(fn () => $service->saveTicket(new Ticket, $data, new User))
            ->toThrow(TicketBusinessException::class, 'agente atribuído');
    });

    it('subcategoria sem vínculo correto lança TicketBusinessException', function () {
        $cat = new Category(['parent_id' => 0,   'name' => 'Cat']);
        $cat->category_id = 100;

        $subSemVinculo = new Category(['parent_id' => 999, 'name' => 'Orphan']);
        $subSemVinculo->category_id = 101;

        $company = tu_company();

        $repo = Mockery::mock(TicketRepositoryInterface::class);
        $repo->shouldReceive('findCategoryById')->with(100)->andReturn($cat);
        $repo->shouldReceive('findCategoryById')->with(101)->andReturn($subSemVinculo);
        $repo->shouldReceive('findCompanyOrFail')->with(1)->andReturn($company);
        $repo->shouldReceive('isStatusTerminal')->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSchedule')->andReturn(false);

        $service = tu_service($repo);
        $data = tu_data(['status_id' => Ticket::STATUS_PENDING_ID]);

        expect(fn () => $service->saveTicket(new Ticket, $data, new User))
            ->toThrow(TicketBusinessException::class, 'inválido');
    });

    it('categoria inexistente lança TicketBusinessException', function () {
        $company = tu_company();

        $repo = Mockery::mock(TicketRepositoryInterface::class);
        $repo->shouldReceive('findCategoryById')->with(100)->andReturn(null);
        $repo->shouldReceive('findCategoryById')->with(101)->andReturn(null);
        $repo->shouldReceive('findCompanyOrFail')->with(1)->andReturn($company);
        $repo->shouldReceive('isStatusTerminal')->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSchedule')->andReturn(false);

        $service = tu_service($repo);

        expect(fn () => $service->saveTicket(new Ticket, tu_data(), new User))
            ->toThrow(TicketBusinessException::class, 'inválido');
    });

    it('empresa inativa lança TicketBusinessException', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company(active: false);

        $repo = Mockery::mock(TicketRepositoryInterface::class);
        $repo->shouldReceive('findCategoryById')->with(100)->andReturn($cat);
        $repo->shouldReceive('findCategoryById')->with(101)->andReturn($sub);
        $repo->shouldReceive('findCompanyOrFail')->with(1)->andReturn($company);
        $repo->shouldReceive('isStatusTerminal')->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSchedule')->andReturn(false);

        $service = tu_service($repo);

        expect(fn () => $service->saveTicket(new Ticket, tu_data(), new User))
            ->toThrow(TicketBusinessException::class, 'inativo');
    });

    it('empresa inadimplente bloqueia criação de novo ticket', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company(active: true, irregular: true);

        $repo = Mockery::mock(TicketRepositoryInterface::class);
        $repo->shouldReceive('findCategoryById')->with(100)->andReturn($cat);
        $repo->shouldReceive('findCategoryById')->with(101)->andReturn($sub);
        $repo->shouldReceive('findCompanyOrFail')->with(1)->andReturn($company);
        $repo->shouldReceive('isStatusTerminal')->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSchedule')->andReturn(false);

        $service = tu_service($repo);

        expect(fn () => $service->saveTicket(new Ticket, tu_data(), new User))
            ->toThrow(TicketBusinessException::class, 'inadimplência');
    });

    it('empresa inadimplente não bloqueia atualização de ticket existente', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company(active: true, irregular: true);

        $repo = tu_repo($cat, $sub, $company);

        $service = tu_service($repo);
        $existing = new Ticket;
        $existing->exists = true; // simula ticket já persistido

        // Não deve lançar exceção
        $result = $service->saveTicket($existing, tu_data(), new User);

        expect($result)->toBeInstanceOf(Ticket::class);
    });

});

describe('TicketService (unit) — parseVersion (via saveTicket)', function () {

    it('version null resulta em campo vazio', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company();

        $repo = tu_repo($cat, $sub, $company);
        $service = tu_service($repo);

        $ticket = $service->saveTicket(new Ticket, tu_data(), new User);

        expect($ticket->version)->toBe('');
    });

    it('version "1" é convertida para 01.28.01', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company();

        $repo = tu_repo($cat, $sub, $company);
        $service = tu_service($repo);

        $ticket = $service->saveTicket(new Ticket, tu_data(['version' => '1']), new User);

        expect($ticket->version)->toBe('01.28.01');
    });

    it('version "02.01.05" já formatada é mantida', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company();

        $repo = tu_repo($cat, $sub, $company);
        $service = tu_service($repo);

        $ticket = $service->saveTicket(new Ticket, tu_data(['version' => '02.01.05']), new User);

        expect($ticket->version)->toBe('02.01.05');
    });

});

describe('TicketService (unit) — contact uppercase', function () {

    it('contact é sempre armazenado em maiúsculas', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company();

        $repo = tu_repo($cat, $sub, $company);
        $service = tu_service($repo);

        $ticket = $service->saveTicket(new Ticket, tu_data(['contact' => 'maria jose']), new User);

        expect($ticket->contact)->toBe('MARIA JOSE');
    });

});

describe('TicketService (unit) — requestElapsedToken', function () {

    it('gera token sha256 de 64 caracteres e chama updateTicket no repositório', function () {
        $repo = Mockery::mock(TicketRepositoryInterface::class);
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(function ($ticket, $attrs) {
                return isset($attrs['elapsed_token']) && strlen($attrs['elapsed_token']) === 64;
            });

        $ticket = new Ticket;
        $ticket->id = 42;

        $token = tu_service($repo)->requestElapsedToken($ticket);

        expect($token)->toMatch('/^[a-f0-9]{64}$/');
    });

    it('cada chamada produz token diferente', function () {
        $repo = Mockery::mock(TicketRepositoryInterface::class);
        $repo->shouldReceive('updateTicket')->twice();

        $ticket = new Ticket;
        $ticket->id = 42;

        $service = tu_service($repo);
        $t1 = $service->requestElapsedToken($ticket);
        $t2 = $service->requestElapsedToken($ticket);

        expect($t1)->not->toBe($t2);
    });

});

describe('TicketService (unit) — repositório é chamado corretamente', function () {

    it('save() é invocado exatamente uma vez por saveTicket()', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company();

        $repo = tu_repo($cat, $sub, $company);

        tu_service($repo)->saveTicket(new Ticket, tu_data(), new User);

        // Mockery verifica automaticamente ao final que save() foi chamado 1x
        $repo->shouldHaveReceived('save')->once();
    });

    it('honra department_id enviado no formulário quando não há agente atribuído', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company();

        $ticket = new Ticket;
        $repo = tu_repo($cat, $sub, $company);

        $author = new User(['department_id' => 1]);
        $author->id = 999;

        $data = tu_data(['agent_id' => null, 'department_id' => 7]);

        tu_service($repo)->saveTicket($ticket, $data, $author);

        expect($ticket->department_id)->toBe(7);
    });

    it('override manual no formulário vence o departamento do agente atribuído', function () {
        [$cat, $sub] = tu_cat_pair();
        $company = tu_company();

        $agent = User::factory()->create(['department_id' => 5]);

        $ticket = new Ticket;
        $repo = tu_repo($cat, $sub, $company);

        $author = new User(['department_id' => 1]);
        $author->id = 998;

        $data = tu_data([
            'agent_id' => $agent->id,
            'department_id' => 99, // override explícito vence
            'status_id' => Ticket::STATUS_PENDING_ID,
        ]);

        tu_service($repo)->saveTicket($ticket, $data, $author);

        expect($ticket->department_id)->toBe(99);
    });

    it('cai no fallback Suporte quando nenhum sinal define o departamento', function () {
        // Sem agent, sem override, sem categoria com dept e sem canal,
        // o Resolver garante uma fila de Suporte (evita NULL).
        \App\Models\Department::query()->whereRaw('LOWER(name) like ?', ['%suporte%'])->delete();
        $support = \App\Models\Department::factory()->create(['name' => 'Suporte Técnico Unit']);

        [$cat, $sub] = tu_cat_pair();
        $company = tu_company();

        $ticket = new Ticket;
        $repo = tu_repo($cat, $sub, $company);

        $author = new User(['department_id' => 11]);
        $author->id = 997;

        tu_service($repo)->saveTicket($ticket, tu_data(), $author);

        expect($ticket->department_id)->toBe($support->id);
    });

    it('syncExtraCategories() é invocado com os extras corretos', function () {
        [$cat, $sub] = tu_cat_pair();
        [$cat2, $sub2] = tu_cat_pair();
        $cat2->category_id = 200;
        $sub2->category_id = 201;
        $sub2->parent_id = 200;

        $company = tu_company();

        $extras = [['category_id' => 200, 'sub_category_id' => 201]];

        $repo = Mockery::mock(TicketRepositoryInterface::class);
        $repo->shouldReceive('findCategoryById')->with(100)->andReturn($cat);
        $repo->shouldReceive('findCategoryById')->with(101)->andReturn($sub);
        $repo->shouldReceive('findCompanyOrFail')->with(1)->andReturn($company);
        $repo->shouldReceive('isStatusTerminal')->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSchedule')->andReturn(false);
        $repo->shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        $repo->shouldReceive('save')->once();
        $repo->shouldReceive('syncExtraCategories')
            ->once()
            ->withArgs(fn ($t, $e) => $e === $extras);
        $repo->shouldReceive('updateAttachments')->andReturn(null);
        $repo->shouldReceive('getSolicitacaoStatusId')->andReturn(99);
        $repo->shouldReceive('updateTicket')->andReturn(null);

        $data = tu_data(['extra_categories' => $extras]);
        tu_service($repo)->saveTicket(new Ticket, $data, new User);

        expect(true)->toBeTrue(); // asserção feita via Mockery expectation
    });

});
