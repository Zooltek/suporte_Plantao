<?php

/**
 * Testes UNITÁRIOS — ProjectService.
 * O ProjectRepositoryInterface é mockado; nenhum DB é tocado.
 */

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Tasks\Project;
use App\Models\Tasks\UserProject;
use App\Services\API\V1\Tasks\ProjectService;
use Illuminate\Database\Eloquent\Collection;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ps_repo(): MockInterface
{
    return Mockery::mock(ProjectRepositoryInterface::class);
}

function ps_service(MockInterface $repo): ProjectService
{
    return new ProjectService($repo);
}

function ps_project(int $id = 1): Project
{
    $p = new Project();
    $p->id     = $id;
    $p->name   = "Projeto {$id}";
    $p->status = 1;
    return $p;
}

// ─── getActiveProjects — sem participação ─────────────────────────────────────

describe('ProjectService (unit) — getActiveProjects sem participação', function () {

    it('retorna os projetos sem chamar getUserProjectIds quando checkParticipation=false', function () {
        $repo       = ps_repo();
        $collection = new Collection([ps_project(1), ps_project(2)]);

        $repo->shouldReceive('getActiveProjects')->once()->andReturn($collection);
        $repo->shouldNotReceive('getUserProjectIds');

        $result = ps_service($repo)->getActiveProjects(false);

        expect($result->count())->toBe(2);
    });

    it('retorna os projetos sem chamar getUserProjectIds quando userId é null', function () {
        $repo       = ps_repo();
        $collection = new Collection([ps_project(1)]);

        $repo->shouldReceive('getActiveProjects')->once()->andReturn($collection);
        $repo->shouldNotReceive('getUserProjectIds');

        $result = ps_service($repo)->getActiveProjects(true, null);

        expect($result->count())->toBe(1);
    });

});

// ─── getActiveProjects — com participação ────────────────────────────────────

describe('ProjectService (unit) — getActiveProjects com participação', function () {

    it('marca "already=true" nos projetos em que o usuário participa', function () {
        $repo = ps_repo();
        $p1   = ps_project(1);
        $p2   = ps_project(2);
        $collection = new Collection([$p1, $p2]);

        $repo->shouldReceive('getActiveProjects')->andReturn($collection);
        $repo->shouldReceive('getUserProjectIds')->with(7)->andReturn([1]);

        $result = ps_service($repo)->getActiveProjects(true, 7);

        expect($result->firstWhere('id', 1)->already)->toBeTrue()
            ->and($result->firstWhere('id', 2)->already)->toBeFalse();
    });

    it('marca "already=false" para projetos fora da lista do usuário', function () {
        $repo       = ps_repo();
        $collection = new Collection([ps_project(10)]);

        $repo->shouldReceive('getActiveProjects')->andReturn($collection);
        $repo->shouldReceive('getUserProjectIds')->with(3)->andReturn([]);

        $result = ps_service($repo)->getActiveProjects(true, 3);

        expect($result->first()->already)->toBeFalse();
    });

});

// ─── toggleParticipation ─────────────────────────────────────────────────────

describe('ProjectService (unit) — toggleParticipation', function () {

    it('retorna false e chama deleteParticipation quando participação existe', function () {
        $repo          = ps_repo();
        $participation = new UserProject();

        $repo->shouldReceive('findParticipation')->with(1, 5)->andReturn($participation);
        $repo->shouldReceive('deleteParticipation')->with($participation)->once();
        $repo->shouldNotReceive('createParticipation');

        $result = ps_service($repo)->toggleParticipation(1, 5);

        expect($result)->toBeFalse();
    });

    it('retorna true e chama createParticipation quando não há participação', function () {
        $repo          = ps_repo();
        $newParticipation = new UserProject();

        $repo->shouldReceive('findParticipation')->with(2, 9)->andReturn(null);
        $repo->shouldNotReceive('deleteParticipation');
        $repo->shouldReceive('createParticipation')->with(2, 9)->once()->andReturn($newParticipation);

        $result = ps_service($repo)->toggleParticipation(2, 9);

        expect($result)->toBeTrue();
    });

    it('nunca chama ambos deleteParticipation e createParticipation na mesma chamada', function () {
        $repo          = ps_repo();
        $participation = new UserProject();

        $repo->shouldReceive('findParticipation')->andReturn($participation);
        $repo->shouldReceive('deleteParticipation')->once();
        $repo->shouldNotReceive('createParticipation');

        ps_service($repo)->toggleParticipation(1, 1);
    });

});
