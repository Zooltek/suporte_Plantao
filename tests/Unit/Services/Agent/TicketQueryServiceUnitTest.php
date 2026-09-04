<?php

/**
 * Testes UNITÁRIOS do TicketQueryService — Repository e AccessService mockados.
 * Testam exclusivamente as regras de negócio: determinação de agentScope, delegação de filtros,
 * e montagem do array de filterData por papel.
 */

use App\Contracts\Repositories\TicketQueryRepositoryInterface;
use App\Http\Requests\Agent\Tickets\TicketIndexRequest;
use App\Models\User;
use App\Services\Access\AccessService;
use App\Services\Agent\TicketQueryService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;

// ─── Factory helpers ───────────────────────────────────────────────────────────

function tqs_unit_service(MockInterface $repo, MockInterface $access): TicketQueryService
{
    return new TicketQueryService($access, $repo);
}

function tqs_unit_repo(): MockInterface
{
    return Mockery::mock(TicketQueryRepositoryInterface::class);
}

function tqs_unit_access(): MockInterface
{
    return Mockery::mock(AccessService::class);
}

function tqs_unit_paginator(): LengthAwarePaginator
{
    return new LengthAwarePaginator([], 0, 15);
}

function tqs_unit_request(array $params = []): TicketIndexRequest
{
    $request = TicketIndexRequest::create('/tickets', 'GET', $params);
    $request->setContainer(app());

    return $request;
}

function tqs_mock_user(int $id = 1, int $ticketitAgent = 1, ?int $departmentId = 1): MockInterface
{
    $user = Mockery::mock(User::class)->makePartial();
    $user->id = $id;
    $user->ticketit_agent = $ticketitAgent;
    $user->department_id = $departmentId;

    return $user;
}

// ─── listForUser — agentScope ──────────────────────────────────────────────────

describe('TicketQueryService (unit) — listForUser agentScope', function () {

    it('agente (não-admin) usa seu próprio id como agentScope', function () {
        $userId = 42;
        $user = tqs_mock_user($userId);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->with($user)->andReturn(false);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::type('array'),
                $userId,
                1,
                false,
                Mockery::type('int'),
                Mockery::type('int'),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request());
    });

    it('agente com ?unassigned=1 usa null como agentScope e passa unassigned=true', function () {
        $userId = 42;
        $user = tqs_mock_user($userId);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->with($user)->andReturn(false);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::on(fn ($filters) => ($filters['unassigned'] ?? false) === true),
                null,
                1,
                false,
                Mockery::type('int'),
                Mockery::type('int'),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request(['unassigned' => '1']));
    });

    it('admin sem ?mine usa null como agentScope', function () {
        $user = tqs_mock_user(1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->with($user)->andReturn(true);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::type('array'),
                null,
                null,
                false,
                Mockery::type('int'),
                Mockery::type('int'),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request());
    });

    it('admin com ?mine=1 usa seu próprio id como agentScope', function () {
        $userId = 7;
        $user = tqs_mock_user($userId);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->with($user)->andReturn(true);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::type('array'),
                $userId,
                null,
                false,
                Mockery::type('int'),
                Mockery::type('int'),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request(['mine' => '1']));
    });

});

// ─── listForUser — filtros passados ao repository ─────────────────────────────

describe('TicketQueryService (unit) — listForUser filtros', function () {

    it('passa os filtros corretos ao repositório', function () {
        $user = tqs_mock_user(1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->andReturn(true);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::on(function ($filters) {
                    return $filters['q'] === 'impressora'
                        && $filters['status'] === 3
                        && $filters['category'] === 5
                        && $filters['company'] === 10
                        && (int) $filters['agent'] === 2;
                }),
                null,
                null,
                false,
                1,
                15,
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser(
            $user,
            tqs_unit_request([
                'q' => 'impressora',
                'status' => '3',
                'category' => '5',
                'company' => '10',
                'agent' => '2',
                'order' => '1',
            ]),
        );
    });

    it('em modo mine sem data informada, passa a data atual como padrão para date_from e date_to', function () {
        $user = tqs_mock_user(7);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->andReturn(false);

        $today = now()->toDateString();

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::on(function ($filters) use ($today) {
                    return $filters['date_from'] === $today
                        && $filters['date_to'] === $today;
                }),
                7,
                1,
                false,
                Mockery::any(),
                Mockery::any(),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request());
    });

    it('em modo mine com datas informadas, repassa o período selecionado', function () {
        $user = tqs_mock_user(7);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->andReturn(false);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::on(function ($filters) {
                    return $filters['date_from'] === '2026-08-01'
                        && $filters['date_to'] === '2026-08-15';
                }),
                7,
                1,
                false,
                Mockery::any(),
                Mockery::any(),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request([
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
        ]));
    });

    it('em modo unassigned sem data informada, passa a data atual como padrão para date_from e date_to', function () {
        $user = tqs_mock_user(1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->andReturn(true);

        $today = now()->toDateString();

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::on(function ($filters) use ($today) {
                    return $filters['unassigned'] === true
                        && $filters['date_from'] === $today
                        && $filters['date_to'] === $today;
                }),
                null,
                null,
                false,
                Mockery::any(),
                Mockery::any(),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request(['unassigned' => 1]));
    });

    it('em modo unassigned com datas informadas, repassa o período selecionado', function () {
        $user = tqs_mock_user(1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->andReturn(true);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::on(function ($filters) {
                    return $filters['unassigned'] === true
                        && $filters['date_from'] === '2026-08-01'
                        && $filters['date_to'] === '2026-08-15';
                }),
                null,
                null,
                false,
                Mockery::any(),
                Mockery::any(),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request([
            'unassigned' => 1,
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
        ]));
    });

    it('filtros ausentes são passados como null', function () {
        $user = tqs_mock_user(1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->andReturn(true);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::on(function ($filters) {
                    return $filters['q'] === null
                        && $filters['status'] === null
                        && $filters['category'] === null
                        && $filters['company'] === null
                        && $filters['agent'] === null;
                }),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request());
    });

    it('order é convertido para int antes de ser passado ao repositório', function () {
        $user = tqs_mock_user(1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->andReturn(true);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                2,
                Mockery::any(),
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser($user, tqs_unit_request(['order' => '2']));
    });

    it('ignora filtro de agente quando a tela está em modo mine', function () {
        $user = tqs_mock_user(7);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->andReturn(true);

        $repo->shouldReceive('paginateTickets')
            ->once()
            ->with(
                Mockery::on(fn ($filters) => $filters['agent'] === null),
                7,
                null,
                false,
                3,
                15,
            )
            ->andReturn(tqs_unit_paginator());

        tqs_unit_service($repo, $access)->listForUser(
            $user,
            tqs_unit_request(['mine' => '1', 'agent' => '99', 'order' => '3']),
        );
    });

});

// ─── getFilterData ─────────────────────────────────────────────────────────────

describe('TicketQueryService (unit) — getFilterData', function () {

    it('admin recebe statuses, categories, companies e agents', function () {
        $user = tqs_mock_user(1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->with($user)->andReturn(true);

        $repo->shouldReceive('getAllStatuses')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getCategoryFilterOptions')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getAllCompanies')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getActiveAgents')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getAllOrigins')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);

        $result = tqs_unit_service($repo, $access)->getFilterData($user, tqs_unit_request());

        expect($result)->toHaveKeys([
            'statuses',
            'categories',
            'companies',
            'agents',
            'origins',
            'departments',
            'isAdmin',
            'isMineView',
            'currentAgentName',
        ])
            ->and($result['isAdmin'])->toBeTrue()
            ->and($result['isMineView'])->toBeFalse();
    });

    it('agente recebe statuses, categories e companies — mas NÃO agents', function () {
        $user = tqs_mock_user(1, 1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->with($user)->andReturn(false);

        $repo->shouldReceive('getAllStatuses')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getCategoryFilterOptions')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getAllCompanies')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldNotReceive('getActiveAgents');
        $repo->shouldReceive('getAllOrigins')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);

        $result = tqs_unit_service($repo, $access)->getFilterData($user, tqs_unit_request());

        expect($result['isAdmin'])->toBeFalse()
            ->and($result['agents'])->toBeInstanceOf(Collection::class)
            ->and($result['agents']->isEmpty())->toBeTrue()
            ->and($result['isMineView'])->toBeTrue();
    });

    it('usuário não-agente não recebe companies', function () {
        $user = tqs_mock_user(1, 0); // ticketit_agent = 0
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->with($user)->andReturn(false);

        $repo->shouldReceive('getAllStatuses')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getCategoryFilterOptions')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldNotReceive('getAllCompanies');
        $repo->shouldNotReceive('getActiveAgents');
        $repo->shouldReceive('getAllOrigins')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);

        $result = tqs_unit_service($repo, $access)->getFilterData($user, tqs_unit_request());

        expect($result['companies']->isEmpty())->toBeTrue();
    });

    it('isAdmin é false para agente', function () {
        $user = tqs_mock_user(1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->andReturn(false);
        $repo->shouldReceive('getAllStatuses')->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getCategoryFilterOptions')->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getAllCompanies')->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getAllOrigins')->andReturn(new \Illuminate\Database\Eloquent\Collection);

        $result = tqs_unit_service($repo, $access)->getFilterData($user, tqs_unit_request());

        expect($result['isAdmin'])->toBeFalse();
    });

    it('admin em modo mine não expõe seletor editável de agentes', function () {
        $user = tqs_mock_user(7, 1);
        $repo = tqs_unit_repo();
        $access = tqs_unit_access();

        $access->shouldReceive('isAdmin')->with($user)->andReturn(true);

        $repo->shouldReceive('getAllStatuses')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getCategoryFilterOptions')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldReceive('getAllCompanies')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);
        $repo->shouldNotReceive('getActiveAgents');
        $repo->shouldReceive('getAllOrigins')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection);

        $result = tqs_unit_service($repo, $access)->getFilterData($user, tqs_unit_request(['mine' => '1']));

        expect($result['isMineView'])->toBeTrue()
            ->and($result['agents']->isEmpty())->toBeTrue();
    });

});
