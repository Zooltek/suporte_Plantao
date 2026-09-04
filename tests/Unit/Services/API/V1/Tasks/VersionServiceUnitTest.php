<?php

/**
 * Testes UNITÁRIOS do VersionService — VersionRepositoryInterface mockado.
 * Testam exclusivamente regras de negócio: validações de data, data futura,
 * data anterior à última versão, changelogs vazios, e montagem do payload.
 */

use App\Contracts\Repositories\VersionRepositoryInterface;
use App\Models\Tasks\Changelog;
use App\Models\Tasks\Changelog\Version;
use App\Services\API\V1\Tasks\Changelog\VersionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function vsu_repo(): MockInterface
{
    return Mockery::mock(VersionRepositoryInterface::class);
}

function vsu_service(MockInterface $repo): VersionService
{
    return new VersionService($repo);
}

function vsu_version(array $attrs = []): Version
{
    $v = new Version();
    foreach (array_merge(['id' => 1, 'reference_date' => Carbon::now()->subDay()], $attrs) as $k => $val) {
        $v->$k = $val;
    }
    return $v;
}

function vsu_changelog(array $attrs = []): Changelog
{
    $cl = new Changelog();
    foreach (array_merge(['id' => 1, 'version_id' => null], $attrs) as $k => $v) {
        $cl->$k = $v;
    }
    return $cl;
}

// ─── getVersions ──────────────────────────────────────────────────────────────

describe('VersionService (unit) — getVersions', function () {

    it('delega ao repositório e retorna a coleção', function () {
        $repo     = vsu_repo();
        $versions = new Collection([vsu_version()]);

        $repo->shouldReceive('getVersionsByProject')->with(5)->once()->andReturn($versions);

        $result = vsu_service($repo)->getVersions(5);

        expect($result->count())->toBe(1);
    });

    it('passa null quando projectId não é informado', function () {
        $repo = vsu_repo();

        $repo->shouldReceive('getVersionsByProject')->with(null)->once()->andReturn(new Collection());

        vsu_service($repo)->getVersions(null);
    });

});

// ─── createVersion — validação data futura ────────────────────────────────────

describe('VersionService (unit) — createVersion (data futura)', function () {

    it('lança Exception 422 com status future_date quando reference_date é futura', function () {
        $repo = vsu_repo();

        expect(function () use ($repo) {
            vsu_service($repo)->createVersion([
                'project_id'     => 1,
                'reference_date' => Carbon::now()->addDay()->toDateString(),
                'time'           => '23:59',
            ], 1);
        })->toThrow(\Exception::class);
    });

    it('o payload da exceção contém status=future_date', function () {
        $repo = vsu_repo();

        try {
            vsu_service($repo)->createVersion([
                'project_id'     => 1,
                'reference_date' => Carbon::now()->addDay()->toDateString(),
                'time'           => '23:59',
            ], 1);
            fail('Exceção esperada não foi lançada');
        } catch (\Exception $e) {
            $payload = json_decode($e->getMessage(), true);
            expect($payload['status'])->toBe('future_date');
        }
    });

});

// ─── createVersion — data anterior à última versão ────────────────────────────

describe('VersionService (unit) — createVersion (data anterior)', function () {

    it('lança Exception 422 quando reference_date é anterior à última versão', function () {
        $repo        = vsu_repo();
        $lastVersion = vsu_version(['reference_date' => Carbon::now()->subHour()]);

        $repo->shouldReceive('getLatestVersion')->with(1)->andReturn($lastVersion);

        expect(function () use ($repo) {
            vsu_service($repo)->createVersion([
                'project_id'     => 1,
                'reference_date' => Carbon::now()->subDays(2)->toDateString(),
            ], 1);
        })->toThrow(\Exception::class);
    });

    it('o payload da exceção contém status=false', function () {
        $repo        = vsu_repo();
        $lastVersion = vsu_version(['reference_date' => Carbon::now()->subHour()]);

        $repo->shouldReceive('getLatestVersion')->with(1)->andReturn($lastVersion);

        try {
            vsu_service($repo)->createVersion([
                'project_id'     => 1,
                'reference_date' => Carbon::now()->subDays(2)->toDateString(),
            ], 1);
            fail('Exceção esperada não foi lançada');
        } catch (\Exception $e) {
            $payload = json_decode($e->getMessage(), true);
            expect($payload['status'])->toBe('false');
        }
    });

});

// ─── createVersion — sem changelogs pendentes ─────────────────────────────────

describe('VersionService (unit) — createVersion (sem changelogs)', function () {

    it('lança Exception 422 quando não há changelogs pendentes', function () {
        $repo = vsu_repo();

        $repo->shouldReceive('getLatestVersion')->with(1)->andReturn(null);
        $repo->shouldReceive('getPendingChangelogsUpTo')->andReturn(new Collection());

        expect(function () use ($repo) {
            vsu_service($repo)->createVersion([
                'project_id'     => 1,
                'reference_date' => Carbon::yesterday()->toDateString(),
            ], 1);
        })->toThrow(\Exception::class);
    });

    it('o payload da exceção contém status=no_changelog_count_available', function () {
        $repo = vsu_repo();

        $repo->shouldReceive('getLatestVersion')->with(1)->andReturn(null);
        $repo->shouldReceive('getPendingChangelogsUpTo')->andReturn(new Collection());

        try {
            vsu_service($repo)->createVersion([
                'project_id'     => 1,
                'reference_date' => Carbon::yesterday()->toDateString(),
            ], 1);
            fail('Exceção esperada não foi lançada');
        } catch (\Exception $e) {
            $payload = json_decode($e->getMessage(), true);
            expect($payload['status'])->toBe('no_changelog_count_available');
        }
    });

});

// ─── createVersion — fluxo de sucesso ────────────────────────────────────────

describe('VersionService (unit) — createVersion (sucesso)', function () {

    it('chama createVersion e assignChangelogsToVersion no repositório', function () {
        $repo       = vsu_repo();
        $changelogs = new Collection([vsu_changelog()]);
        $version    = vsu_version(['id' => 42]);

        $repo->shouldReceive('getLatestVersion')->with(1)->andReturn(null);
        $repo->shouldReceive('getPendingChangelogsUpTo')
            ->with(1, Mockery::type(Carbon::class))
            ->andReturn($changelogs);

        $repo->shouldReceive('createVersion')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['project_id'] === 1 && $d['user_id'] === 7))
            ->andReturn($version);

        $repo->shouldReceive('assignChangelogsToVersion')
            ->once()
            ->with($version, $changelogs)
            ->andReturn($version->setRelation('changelogs', $changelogs));

        // DB::transaction precisa ser mockado para chamar o callback direto
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn ($callback) => $callback());

        $result = vsu_service($repo)->createVersion([
            'project_id'     => 1,
            'reference_date' => Carbon::yesterday()->toDateString(),
            'name'           => 'v2.0',
        ], 7);

        expect($result)->toBeInstanceOf(Version::class);
    });

    it('monta o payload de createVersion com name=null quando não informado', function () {
        $repo       = vsu_repo();
        $changelogs = new Collection([vsu_changelog()]);
        $version    = vsu_version();

        $repo->shouldReceive('getLatestVersion')->andReturn(null);
        $repo->shouldReceive('getPendingChangelogsUpTo')->andReturn($changelogs);
        $repo->shouldReceive('createVersion')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['name'] === null))
            ->andReturn($version);
        $repo->shouldReceive('assignChangelogsToVersion')->andReturn($version);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn ($callback) => $callback());

        vsu_service($repo)->createVersion([
            'project_id'     => 1,
            'reference_date' => Carbon::yesterday()->toDateString(),
        ], 1);
    });

});
