<?php

use App\Models\Tasks\Project;
use App\Models\Tasks\UserProject;
use App\Models\User;
use App\Repositories\UserProjectRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function upr_repo(): UserProjectRepository
{
    return new UserProjectRepository();
}

function upr_user(): User
{
    return User::factory()->agent()->create();
}

function upr_project(array $attrs = []): Project
{
    return Project::factory()->create($attrs);
}

function upr_link(User $user, Project $project, array $attrs = []): UserProject
{
    return UserProject::create(array_merge([
        'user_id'    => $user->id,
        'project_id' => $project->id,
        'color'      => '#ff5733',
    ], $attrs));
}

// ─── allForUser ───────────────────────────────────────────────────────────────

describe('UserProjectRepository — allForUser', function () {

    it('retorna apenas os vínculos do usuário especificado', function () {
        $userA = upr_user();
        $userB = upr_user();

        $projectA = upr_project();
        $projectB = upr_project();

        upr_link($userA, $projectA);
        upr_link($userB, $projectB);

        $result = upr_repo()->allForUser($userA->id);

        $projectIds = $result->pluck('project_id')->toArray();
        expect($projectIds)->toContain($projectA->id);
        expect($projectIds)->not->toContain($projectB->id);
    });

    it('retorna coleção vazia quando o usuário não tem vínculos', function () {
        $user = upr_user();

        $result = upr_repo()->allForUser($user->id);

        expect($result->isEmpty())->toBeTrue();
    });

    it('carrega a relação project', function () {
        $user    = upr_user();
        $project = upr_project();

        upr_link($user, $project);

        $result = upr_repo()->allForUser($user->id);

        expect($result->first()->relationLoaded('project'))->toBeTrue();
    });

});

// ─── existsForUser ────────────────────────────────────────────────────────────

describe('UserProjectRepository — existsForUser', function () {

    it('retorna true quando o vínculo existe', function () {
        $user    = upr_user();
        $project = upr_project();

        upr_link($user, $project);

        expect(upr_repo()->existsForUser($user->id, $project->id))->toBeTrue();
    });

    it('retorna false quando o vínculo não existe', function () {
        $user    = upr_user();
        $project = upr_project();

        expect(upr_repo()->existsForUser($user->id, $project->id))->toBeFalse();
    });

});

// ─── findProject ──────────────────────────────────────────────────────────────

describe('UserProjectRepository — findProject', function () {

    it('retorna o projeto pelo id', function () {
        $project = upr_project(['name' => 'Projeto Alpha']);

        $found = upr_repo()->findProject($project->id);

        expect($found)->toBeInstanceOf(Project::class);
        expect($found->id)->toBe($project->id);
    });

    it('lança exceção quando o projeto não existe', function () {
        expect(fn () => upr_repo()->findProject(999999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

// ─── create ───────────────────────────────────────────────────────────────────

describe('UserProjectRepository — create', function () {

    it('persiste o vínculo no banco', function () {
        $user    = upr_user();
        $project = upr_project();

        $userProject = upr_repo()->create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'color'      => '#aabbcc',
        ]);

        expect($userProject)->toBeInstanceOf(UserProject::class);
        expect($userProject->exists)->toBeTrue();

        test()->assertDatabaseHas('tasks_users_projects', [
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);
    });

    it('carrega a relação project após a criação', function () {
        $user    = upr_user();
        $project = upr_project();

        $userProject = upr_repo()->create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'color'      => null,
        ]);

        expect($userProject->relationLoaded('project'))->toBeTrue();
    });

});

// ─── findByIdAndUser ──────────────────────────────────────────────────────────

describe('UserProjectRepository — findByIdAndUser', function () {

    it('retorna o vínculo correto pelo id e user_id', function () {
        $user    = upr_user();
        $project = upr_project();

        $link = upr_link($user, $project);

        $found = upr_repo()->findByIdAndUser($link->id, $user->id);

        expect($found->id)->toBe($link->id);
    });

    it('lança exceção quando o id pertence a outro usuário', function () {
        $userA   = upr_user();
        $userB   = upr_user();
        $project = upr_project();

        $link = upr_link($userA, $project);

        expect(fn () => upr_repo()->findByIdAndUser($link->id, $userB->id))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

// ─── findByProjectAndUser ─────────────────────────────────────────────────────

describe('UserProjectRepository — findByProjectAndUser', function () {

    it('retorna o vínculo quando existe', function () {
        $user    = upr_user();
        $project = upr_project();

        $link = upr_link($user, $project);

        $found = upr_repo()->findByProjectAndUser($project->id, $user->id);

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($link->id);
    });

    it('retorna null quando o vínculo não existe', function () {
        $user    = upr_user();
        $project = upr_project();

        $found = upr_repo()->findByProjectAndUser($project->id, $user->id);

        expect($found)->toBeNull();
    });

});

// ─── delete ───────────────────────────────────────────────────────────────────

describe('UserProjectRepository — delete', function () {

    it('remove o vínculo do banco', function () {
        $user    = upr_user();
        $project = upr_project();

        $link = upr_link($user, $project);
        $id   = $link->id;

        upr_repo()->delete($link);

        test()->assertDatabaseMissing('tasks_users_projects', ['id' => $id]);
    });

    it('retorna true após a remoção bem-sucedida', function () {
        $user    = upr_user();
        $project = upr_project();

        $link   = upr_link($user, $project);
        $result = upr_repo()->delete($link);

        expect($result)->toBeTrue();
    });

});
