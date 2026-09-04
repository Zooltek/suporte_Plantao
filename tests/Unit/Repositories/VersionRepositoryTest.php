<?php

/**
 * Testes de integração do VersionRepository — SQLite in-memory via RefreshDatabase.
 * Cada teste exercita a implementação real contra o banco.
 */

use App\Models\Tasks\Changelog;
use App\Models\Tasks\Changelog\Version;
use App\Models\Tasks\Project;
use App\Models\User;
use App\Repositories\VersionRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function vr_repo(): VersionRepository
{
    return new VersionRepository();
}

function vr_user(): User
{
    return User::factory()->create();
}

function vr_project(): Project
{
    return Project::factory()->create();
}

function vr_version(Project $project, User $user, array $attrs = []): Version
{
    return Version::create(array_merge([
        'project_id'     => $project->id,
        'user_id'        => $user->id,
        'reference_date' => now()->subHour(),
    ], $attrs));
}

function vr_changelog(Project $project, User $user, array $attrs = []): Changelog
{
    return Changelog::create(array_merge([
        'content'    => 'Item de changelog',
        'user_id'    => $user->id,
        'project_id' => $project->id,
        'sort_order' => 10000,
        'status'     => 1,
    ], $attrs));
}

// ─── getVersionsByProject ─────────────────────────────────────────────────────

describe('VersionRepository — getVersionsByProject', function () {

    it('retorna todas as versões quando projectId é null', function () {
        $user     = vr_user();
        $project1 = vr_project();
        $project2 = vr_project();

        vr_version($project1, $user);
        vr_version($project2, $user);

        $result = vr_repo()->getVersionsByProject(null);

        expect($result->count())->toBe(2);
    });

    it('filtra por project_id quando informado', function () {
        $user     = vr_user();
        $project1 = vr_project();
        $project2 = vr_project();

        vr_version($project1, $user);
        vr_version($project2, $user);

        $result = vr_repo()->getVersionsByProject($project1->id);

        expect($result->count())->toBe(1)
            ->and($result->first()->project_id)->toBe($project1->id);
    });

    it('carrega changelogs via eager loading ordenados por sort_order', function () {
        $user    = vr_user();
        $project = vr_project();
        $version = vr_version($project, $user);

        vr_changelog($project, $user, ['version_id' => $version->id, 'sort_order' => 20000]);
        vr_changelog($project, $user, ['version_id' => $version->id, 'sort_order' => 10000]);

        $result    = vr_repo()->getVersionsByProject($project->id);
        $changelogs = $result->first()->changelogs;

        expect($result->first()->relationLoaded('changelogs'))->toBeTrue()
            ->and($changelogs->count())->toBe(2)
            ->and($changelogs->first()->sort_order)->toBe(10000);
    });

    it('ordena versões por reference_date decrescente', function () {
        $user    = vr_user();
        $project = vr_project();

        $older = vr_version($project, $user, ['reference_date' => now()->subDays(5)]);
        $newer = vr_version($project, $user, ['reference_date' => now()->subDay()]);

        $result = vr_repo()->getVersionsByProject($project->id);

        expect($result->first()->id)->toBe($newer->id);
    });

});

// ─── findVersion ──────────────────────────────────────────────────────────────

describe('VersionRepository — findVersion', function () {

    it('retorna a versão pelo id', function () {
        $user    = vr_user();
        $project = vr_project();
        $version = vr_version($project, $user);

        $found = vr_repo()->findVersion($version->id);

        expect($found->id)->toBe($version->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => vr_repo()->findVersion(999999))
            ->toThrow(ModelNotFoundException::class);
    });

});

// ─── createVersion ────────────────────────────────────────────────────────────

describe('VersionRepository — createVersion', function () {

    it('persiste a versão e retorna com id', function () {
        $user    = vr_user();
        $project = vr_project();

        $version = vr_repo()->createVersion([
            'project_id'     => $project->id,
            'user_id'        => $user->id,
            'name'           => 'v1.0.0',
            'reference_date' => now(),
        ]);

        expect($version->id)->not->toBeNull()
            ->and($version->name)->toBe('v1.0.0');

        $this->assertDatabaseHas('tasks_changelog_versions', ['id' => $version->id]);
    });

    it('aceita name=null', function () {
        $user    = vr_user();
        $project = vr_project();

        $version = vr_repo()->createVersion([
            'project_id'     => $project->id,
            'user_id'        => $user->id,
            'name'           => null,
            'reference_date' => now(),
        ]);

        expect($version->name)->toBeNull();
    });

});

// ─── assignChangelogsToVersion ────────────────────────────────────────────────

describe('VersionRepository — assignChangelogsToVersion', function () {

    it('vincula changelogs à versão e retorna versão com changelogs carregados', function () {
        $user    = vr_user();
        $project = vr_project();
        $version = vr_version($project, $user);

        $cl1 = vr_changelog($project, $user, ['version_id' => null]);
        $cl2 = vr_changelog($project, $user, ['version_id' => null]);

        $changelogs = collect([$cl1, $cl2]);

        $result = vr_repo()->assignChangelogsToVersion($version, $changelogs);

        expect($result->relationLoaded('changelogs'))->toBeTrue()
            ->and($result->changelogs->count())->toBe(2);

        $this->assertDatabaseHas('tasks_changelogs', ['id' => $cl1->id, 'version_id' => $version->id]);
        $this->assertDatabaseHas('tasks_changelogs', ['id' => $cl2->id, 'version_id' => $version->id]);
    });

    it('não altera changelogs não incluídos na coleção', function () {
        $user    = vr_user();
        $project = vr_project();
        $version = vr_version($project, $user);

        $cl1   = vr_changelog($project, $user, ['version_id' => null]);
        $other = vr_changelog($project, $user, ['version_id' => null]);

        vr_repo()->assignChangelogsToVersion($version, collect([$cl1]));

        $this->assertDatabaseHas('tasks_changelogs', ['id' => $other->id, 'version_id' => null]);
    });

});

// ─── getLatestVersion ─────────────────────────────────────────────────────────

describe('VersionRepository — getLatestVersion', function () {

    it('retorna a versão mais recente por reference_date', function () {
        $user    = vr_user();
        $project = vr_project();

        $older  = vr_version($project, $user, ['reference_date' => now()->subDays(3)]);
        $newest = vr_version($project, $user, ['reference_date' => now()->subDay()]);

        $result = vr_repo()->getLatestVersion($project->id);

        expect($result->id)->toBe($newest->id);
    });

    it('retorna null quando não há versões para o projeto', function () {
        $project = vr_project();

        expect(vr_repo()->getLatestVersion($project->id))->toBeNull();
    });

    it('ignora versões de outros projetos', function () {
        $user     = vr_user();
        $project1 = vr_project();
        $project2 = vr_project();

        vr_version($project2, $user);

        expect(vr_repo()->getLatestVersion($project1->id))->toBeNull();
    });

});

// ─── getPendingChangelogsUpTo ─────────────────────────────────────────────────

describe('VersionRepository — getPendingChangelogsUpTo', function () {

    it('retorna changelogs ativos sem version_id criados até a data', function () {
        $user    = vr_user();
        $project = vr_project();
        $date    = Carbon::now();

        $cl = vr_changelog($project, $user, ['version_id' => null]);

        // Atualiza created_at para garantir que está antes da data de corte
        $cl->created_at = $date->clone()->subMinute();
        $cl->save();

        $result = vr_repo()->getPendingChangelogsUpTo($project->id, $date);

        expect($result->count())->toBe(1)
            ->and($result->first()->id)->toBe($cl->id);
    });

    it('não retorna changelogs criados após a data de corte', function () {
        $user    = vr_user();
        $project = vr_project();
        $cutoff  = Carbon::now()->subHour();

        $cl = vr_changelog($project, $user, ['version_id' => null]);
        // created_at será now() pelo factory — posterior ao cutoff

        $result = vr_repo()->getPendingChangelogsUpTo($project->id, $cutoff);

        expect($result->count())->toBe(0);
    });

    it('não retorna changelogs já vinculados a uma versão', function () {
        $user    = vr_user();
        $project = vr_project();
        $version = vr_version($project, $user);
        $date    = Carbon::now();

        $cl = vr_changelog($project, $user, ['version_id' => $version->id]);
        $cl->created_at = $date->clone()->subMinute();
        $cl->save();

        $result = vr_repo()->getPendingChangelogsUpTo($project->id, $date);

        expect($result->count())->toBe(0);
    });

    it('não retorna changelogs com status=can', function () {
        $user    = vr_user();
        $project = vr_project();
        $date    = Carbon::now();

        $cl = vr_changelog($project, $user, ['status' => 'can', 'version_id' => null]);
        $cl->created_at = $date->clone()->subMinute();
        $cl->save();

        $result = vr_repo()->getPendingChangelogsUpTo($project->id, $date);

        expect($result->count())->toBe(0);
    });

});
