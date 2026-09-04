<?php

/**
 * Testes de integração do ChangelogRepository — SQLite in-memory via RefreshDatabase.
 * Cada teste exercita a implementação real contra o banco.
 */

use App\Models\Tasks\Changelog;
use App\Models\Tasks\Changelog\Version;
use App\Models\Tasks\Project;
use App\Models\User;
use App\Repositories\ChangelogRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function clr_repo(): ChangelogRepository
{
    return new ChangelogRepository();
}

function clr_user(): User
{
    return User::factory()->create();
}

function clr_project(): Project
{
    return Project::factory()->create();
}

function clr_version(Project $project, User $user): Version
{
    return Version::create([
        'project_id'     => $project->id,
        'user_id'        => $user->id,
        'reference_date' => now(),
    ]);
}

function clr_changelog(Project $project, User $user, array $attrs = []): Changelog
{
    return Changelog::create(array_merge([
        'content'    => 'Changelog de teste',
        'user_id'    => $user->id,
        'project_id' => $project->id,
        'sort_order' => 10000,
        'status'     => 1,
    ], $attrs));
}

// ─── getPendingChangelogs ──────────────────────────────────────────────────────

describe('ChangelogRepository — getPendingChangelogs', function () {

    it('retorna changelogs ativos sem version_id', function () {
        $user    = clr_user();
        $project = clr_project();

        clr_changelog($project, $user, ['version_id' => null]);
        clr_changelog($project, $user, ['version_id' => null]);

        $result = clr_repo()->getPendingChangelogs(null);

        expect($result->count())->toBe(2);
    });

    it('filtra por project_id quando informado', function () {
        $user     = clr_user();
        $project1 = clr_project();
        $project2 = clr_project();

        clr_changelog($project1, $user);
        clr_changelog($project2, $user);

        $result = clr_repo()->getPendingChangelogs($project1->id);

        expect($result->count())->toBe(1)
            ->and($result->first()->project_id)->toBe($project1->id);
    });

    it('não retorna changelogs com version_id preenchido', function () {
        $user    = clr_user();
        $project = clr_project();
        $version = clr_version($project, $user);

        clr_changelog($project, $user, ['version_id' => $version->id]);

        $result = clr_repo()->getPendingChangelogs($project->id);

        expect($result->count())->toBe(0);
    });

    it('não retorna changelogs com status=can', function () {
        $user    = clr_user();
        $project = clr_project();

        clr_changelog($project, $user, ['status' => 'can']);

        $result = clr_repo()->getPendingChangelogs($project->id);

        expect($result->count())->toBe(0);
    });

    it('carrega relação user via eager loading', function () {
        $user    = clr_user();
        $project = clr_project();

        clr_changelog($project, $user);

        $result = clr_repo()->getPendingChangelogs(null);

        expect($result->first()->relationLoaded('user'))->toBeTrue();
    });

});

// ─── findChangelog ────────────────────────────────────────────────────────────

describe('ChangelogRepository — findChangelog', function () {

    it('retorna o changelog pelo id', function () {
        $user      = clr_user();
        $project   = clr_project();
        $changelog = clr_changelog($project, $user);

        $found = clr_repo()->findChangelog($changelog->id);

        expect($found->id)->toBe($changelog->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => clr_repo()->findChangelog(999999))
            ->toThrow(ModelNotFoundException::class);
    });

});

// ─── findProject ──────────────────────────────────────────────────────────────

describe('ChangelogRepository — findProject', function () {

    it('retorna o projeto pelo id', function () {
        $project = clr_project();

        $found = clr_repo()->findProject($project->id);

        expect($found->id)->toBe($project->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => clr_repo()->findProject(999999))
            ->toThrow(ModelNotFoundException::class);
    });

});

// ─── createChangelog ──────────────────────────────────────────────────────────

describe('ChangelogRepository — createChangelog', function () {

    it('persiste o changelog e retorna com id', function () {
        $user    = clr_user();
        $project = clr_project();

        $changelog = clr_repo()->createChangelog([
            'content'    => 'Novo item',
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'sort_order' => 10000,
            'status'     => 1,
        ]);

        expect($changelog->id)->not->toBeNull()
            ->and($changelog->content)->toBe('Novo item');

        $this->assertDatabaseHas('tasks_changelogs', ['id' => $changelog->id]);
    });

});

// ─── updateChangelog ──────────────────────────────────────────────────────────

describe('ChangelogRepository — updateChangelog', function () {

    it('atualiza os campos e retorna instância fresh', function () {
        $user      = clr_user();
        $project   = clr_project();
        $changelog = clr_changelog($project, $user, ['content' => 'Conteúdo antigo']);

        $updated = clr_repo()->updateChangelog($changelog, ['content' => 'Conteúdo novo']);

        expect($updated->content)->toBe('Conteúdo novo');

        $this->assertDatabaseHas('tasks_changelogs', [
            'id'      => $changelog->id,
            'content' => 'Conteúdo novo',
        ]);
    });

});

// ─── deleteChangelog ──────────────────────────────────────────────────────────

describe('ChangelogRepository — deleteChangelog', function () {

    it('define status=0 no changelog', function () {
        $user      = clr_user();
        $project   = clr_project();
        $changelog = clr_changelog($project, $user, ['status' => 1]);

        $result = clr_repo()->deleteChangelog($changelog);

        expect($result)->toBeTrue();

        $this->assertDatabaseHas('tasks_changelogs', [
            'id'     => $changelog->id,
            'status' => 0,
        ]);
    });

});

// ─── getVersionsByProject ─────────────────────────────────────────────────────

describe('ChangelogRepository — getVersionsByProject', function () {

    it('retorna versões do projeto com changelogs carregados', function () {
        $user    = clr_user();
        $project = clr_project();
        $version = clr_version($project, $user);

        clr_changelog($project, $user, ['version_id' => $version->id, 'sort_order' => 1]);

        $result = clr_repo()->getVersionsByProject($project->id);

        expect($result->count())->toBe(1)
            ->and($result->first()->id)->toBe($version->id)
            ->and($result->first()->relationLoaded('changelogs'))->toBeTrue()
            ->and($result->first()->changelogs->count())->toBe(1);
    });

    it('não retorna versões de outro projeto', function () {
        $user     = clr_user();
        $project1 = clr_project();
        $project2 = clr_project();

        clr_version($project2, $user);

        $result = clr_repo()->getVersionsByProject($project1->id);

        expect($result->count())->toBe(0);
    });

});

// ─── getMaxSortOrder ──────────────────────────────────────────────────────────

describe('ChangelogRepository — getMaxSortOrder', function () {

    it('retorna o maior sort_order do projeto', function () {
        $user    = clr_user();
        $project = clr_project();

        clr_changelog($project, $user, ['sort_order' => 10000]);
        clr_changelog($project, $user, ['sort_order' => 20000]);

        $max = clr_repo()->getMaxSortOrder($project->id);

        expect($max)->toBe(20000);
    });

    it('retorna 0 quando não há changelogs no projeto', function () {
        $project = clr_project();

        expect(clr_repo()->getMaxSortOrder($project->id))->toBe(0);
    });

    it('ignora changelogs de outro projeto', function () {
        $user     = clr_user();
        $project1 = clr_project();
        $project2 = clr_project();

        clr_changelog($project2, $user, ['sort_order' => 50000]);

        expect(clr_repo()->getMaxSortOrder($project1->id))->toBe(0);
    });

});
