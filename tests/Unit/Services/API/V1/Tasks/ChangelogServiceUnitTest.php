<?php

/**
 * Testes UNITÁRIOS do ChangelogService — ChangelogRepositoryInterface mockado.
 * Testam exclusivamente regras de negócio: permissão TI, montagem do payload,
 * validação de estado, cálculo de sort_order e delegação ao repositório.
 */

use App\Contracts\Repositories\ChangelogRepositoryInterface;
use App\Models\Tasks\Changelog;
use App\Models\Tasks\Changelog\Version;
use App\Models\Tasks\Project;
use App\Models\User;
use App\Services\API\V1\Tasks\ChangelogService;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cls_repo(): MockInterface
{
    return Mockery::mock(ChangelogRepositoryInterface::class);
}

function cls_service(MockInterface $repo): ChangelogService
{
    return new ChangelogService($repo);
}

/** Cria User sem banco. */
function cls_user(array $attrs = []): User
{
    $user = new User();
    foreach (array_merge(['id' => 1, 'department_id' => 4], $attrs) as $k => $v) {
        $user->$k = $v;
    }
    return $user;
}

/** Cria Changelog sem banco. */
function cls_changelog(array $attrs = []): Changelog
{
    $cl = new Changelog();
    foreach (array_merge(['id' => 1, 'version_id' => null, 'content' => 'Conteúdo'], $attrs) as $k => $v) {
        $cl->$k = $v;
    }
    return $cl;
}

/** Cria Project sem banco. */
function cls_project(array $attrs = []): Project
{
    $p = new Project();
    foreach (array_merge(['id' => 1, 'name' => 'Projeto'], $attrs) as $k => $v) {
        $p->$k = $v;
    }
    return $p;
}

// ─── checkTechPermission ──────────────────────────────────────────────────────

describe('ChangelogService (unit) — checkTechPermission', function () {

    it('lança Exception 403 quando usuário não pertence ao TI (dept != 4)', function () {
        $repo = cls_repo();
        $user = cls_user(['department_id' => 2]);

        expect(fn () => cls_service($repo)->createChangelog(['content' => 'x', 'project_id' => 1], $user))
            ->toThrow(\Exception::class, 'Acesso restrito ao TI');
    });

    it('não lança exceção quando usuário é do departamento TI (dept == 4)', function () {
        $repo = cls_repo();
        $user = cls_user(['department_id' => 4]);

        $repo->shouldReceive('getMaxSortOrder')->andReturn(0);
        $repo->shouldReceive('createChangelog')->once()->andReturn(cls_changelog());

        $result = cls_service($repo)->createChangelog([
            'content'    => 'Novo item',
            'project_id' => 1,
        ], $user);

        expect($result)->toBeInstanceOf(Changelog::class);
    });

});

// ─── createChangelog ──────────────────────────────────────────────────────────

describe('ChangelogService (unit) — createChangelog', function () {

    it('monta o payload com sort_order calculado', function () {
        $repo = cls_repo();
        $user = cls_user(['id' => 7, 'department_id' => 4]);

        $repo->shouldReceive('getMaxSortOrder')->with(1)->andReturn(10000);
        $repo->shouldReceive('createChangelog')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['sort_order'] === 20000 &&
                $d['user_id'] === 7 &&
                $d['project_id'] === 1
            ))
            ->andReturn(cls_changelog());

        cls_service($repo)->createChangelog([
            'content'    => 'Item',
            'project_id' => 1,
        ], $user);
    });

    it('define bold=true quando title está presente', function () {
        $repo = cls_repo();
        $user = cls_user(['department_id' => 4]);

        $repo->shouldReceive('getMaxSortOrder')->andReturn(0);
        $repo->shouldReceive('createChangelog')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['bold'] === true && $d['title'] === true))
            ->andReturn(cls_changelog());

        cls_service($repo)->createChangelog([
            'content'    => 'Título',
            'project_id' => 1,
            'title'      => true,
        ], $user);
    });

    it('define blank=true quando informado', function () {
        $repo = cls_repo();
        $user = cls_user(['department_id' => 4]);

        $repo->shouldReceive('getMaxSortOrder')->andReturn(0);
        $repo->shouldReceive('createChangelog')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['blank'] === true))
            ->andReturn(cls_changelog());

        cls_service($repo)->createChangelog([
            'content'    => 'Blank',
            'project_id' => 1,
            'blank'      => true,
        ], $user);
    });

    it('passa task_id como null quando não informado', function () {
        $repo = cls_repo();
        $user = cls_user(['department_id' => 4]);

        $repo->shouldReceive('getMaxSortOrder')->andReturn(0);
        $repo->shouldReceive('createChangelog')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['task_id'] === null))
            ->andReturn(cls_changelog());

        cls_service($repo)->createChangelog([
            'content'    => 'Sem task',
            'project_id' => 1,
        ], $user);
    });

});

// ─── updateChangelog ──────────────────────────────────────────────────────────

describe('ChangelogService (unit) — updateChangelog', function () {

    it('lança Exception 403 quando usuário não é do TI', function () {
        $repo = cls_repo();
        $user = cls_user(['department_id' => 1]);

        expect(fn () => cls_service($repo)->updateChangelog(1, [], $user, false, null))
            ->toThrow(\Exception::class);
    });

    it('lança Exception 422 quando changelog já está vinculado a uma versão', function () {
        $repo      = cls_repo();
        $user      = cls_user(['department_id' => 4]);
        $changelog = cls_changelog(['version_id' => 99]);

        $repo->shouldReceive('findChangelog')->with(1)->andReturn($changelog);

        expect(fn () => cls_service($repo)->updateChangelog(1, ['content' => 'Novo'], $user, false, null))
            ->toThrow(\Exception::class, 'Não é possível editar versão fechada');
    });

    it('chama updateChangelog no repositório com dados corretos', function () {
        $repo      = cls_repo();
        $user      = cls_user(['department_id' => 4]);
        $changelog = cls_changelog(['version_id' => null]);
        $updated   = cls_changelog(['content' => 'Atualizado']);

        $repo->shouldReceive('findChangelog')->with(1)->andReturn($changelog);
        $repo->shouldReceive('updateChangelog')
            ->once()
            ->with($changelog, Mockery::on(fn ($d) => isset($d['content']) && $d['content'] === 'Novo conteúdo'))
            ->andReturn($updated);

        $result = cls_service($repo)->updateChangelog(1, ['content' => 'Novo conteúdo'], $user, false, null);

        expect($result)->toBeInstanceOf(Changelog::class);
    });

    it('não inclui blank/bold/title no payload quando isSorting=true', function () {
        $repo      = cls_repo();
        $user      = cls_user(['department_id' => 4]);
        $changelog = cls_changelog(['version_id' => null]);
        $updated   = cls_changelog();

        $repo->shouldReceive('findChangelog')->andReturn($changelog);
        $repo->shouldReceive('updateChangelog')
            ->once()
            ->with($changelog, Mockery::on(fn ($d) => !array_key_exists('blank', $d) && !array_key_exists('bold', $d)))
            ->andReturn($updated);

        cls_service($repo)->updateChangelog(1, ['content' => 'x', 'blank' => true], $user, true, null);
    });

});

// ─── deleteChangelog ──────────────────────────────────────────────────────────

describe('ChangelogService (unit) — deleteChangelog', function () {

    it('lança Exception 403 quando usuário não é do TI', function () {
        $repo = cls_repo();
        $user = cls_user(['department_id' => 3]);

        expect(fn () => cls_service($repo)->deleteChangelog(1, $user))
            ->toThrow(\Exception::class);
    });

    it('delega ao repositório e retorna true', function () {
        $repo      = cls_repo();
        $user      = cls_user(['department_id' => 4]);
        $changelog = cls_changelog();

        $repo->shouldReceive('findChangelog')->with(5)->andReturn($changelog);
        $repo->shouldReceive('deleteChangelog')->with($changelog)->once()->andReturn(true);

        $result = cls_service($repo)->deleteChangelog(5, $user);

        expect($result)->toBeTrue();
    });

});

// ─── getReportDataForModificationList ─────────────────────────────────────────

describe('ChangelogService (unit) — getReportDataForModificationList', function () {

    it('retorna array com project e versions', function () {
        $repo    = cls_repo();
        $project = cls_project(['id' => 10]);
        $versions = new \Illuminate\Database\Eloquent\Collection([
            (new Version())->forceFill(['id' => 1]),
        ]);

        $repo->shouldReceive('findProject')->with(10)->andReturn($project);
        $repo->shouldReceive('getVersionsByProject')->with(10)->andReturn($versions);

        $result = cls_service($repo)->getReportDataForModificationList(10);

        expect($result)->toHaveKeys(['project', 'versions'])
            ->and($result['project']->id)->toBe(10)
            ->and($result['versions']->count())->toBe(1);
    });

});
