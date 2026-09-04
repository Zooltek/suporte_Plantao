<?php

/**
 * Testes de integração — ProjectRepository.
 * Usa RefreshDatabase para garantir isolamento.
 */

use App\Models\Tasks\Project;
use App\Models\Tasks\UserProject;
use App\Models\User;
use App\Repositories\ProjectRepository;

// ─── getActiveProjects ────────────────────────────────────────────────────────

describe('ProjectRepository — getActiveProjects', function () {

    it('retorna apenas projetos com status 1 (ativo)', function () {
        Project::factory()->create(['status' => 1]);
        Project::factory()->create(['status' => 0]);

        $repo     = new ProjectRepository();
        $projects = $repo->getActiveProjects();

        expect($projects->every(fn ($p) => $p->status === 1))->toBeTrue();
    });

    it('retorna projetos ordenados por nome', function () {
        Project::factory()->create(['name' => 'Zeta Project', 'status' => 1]);
        Project::factory()->create(['name' => 'Alpha Project', 'status' => 1]);

        $repo     = new ProjectRepository();
        $projects = $repo->getActiveProjects();

        $names = $projects->pluck('name')->toArray();

        expect($names)->toBe(collect($names)->sort()->values()->toArray());
    });

});

// ─── getUserProjectIds ────────────────────────────────────────────────────────

describe('ProjectRepository — getUserProjectIds', function () {

    it('retorna os ids de projetos em que o usuário participa', function () {
        $user    = User::factory()->create();
        $project = Project::factory()->create(['status' => 1]);

        UserProject::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);

        $repo = new ProjectRepository();
        $ids  = $repo->getUserProjectIds($user->id);

        expect($ids)->toContain($project->id);
    });

    it('retorna array vazio para usuário sem participações', function () {
        $user = User::factory()->create();

        $repo = new ProjectRepository();
        $ids  = $repo->getUserProjectIds($user->id);

        expect($ids)->toBeArray()->toBeEmpty();
    });

});

// ─── findParticipation ────────────────────────────────────────────────────────

describe('ProjectRepository — findParticipation', function () {

    it('retorna o UserProject existente', function () {
        $user    = User::factory()->create();
        $project = Project::factory()->create(['status' => 1]);

        UserProject::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);

        $repo          = new ProjectRepository();
        $participation = $repo->findParticipation($project->id, $user->id);

        expect($participation)->toBeInstanceOf(UserProject::class)
            ->and($participation->project_id)->toBe($project->id)
            ->and($participation->user_id)->toBe($user->id);
    });

    it('retorna null quando o usuário não participa do projeto', function () {
        $user    = User::factory()->create();
        $project = Project::factory()->create(['status' => 1]);

        $repo = new ProjectRepository();

        expect($repo->findParticipation($project->id, $user->id))->toBeNull();
    });

});

// ─── deleteParticipation ──────────────────────────────────────────────────────

describe('ProjectRepository — deleteParticipation', function () {

    it('remove o vínculo do banco', function () {
        $user    = User::factory()->create();
        $project = Project::factory()->create(['status' => 1]);

        $participation = UserProject::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);

        $repo = new ProjectRepository();
        $repo->deleteParticipation($participation);

        $this->assertDatabaseMissing('tasks_users_projects', [
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);
    });

});

// ─── createParticipation ─────────────────────────────────────────────────────

describe('ProjectRepository — createParticipation', function () {

    it('cria o vínculo e retorna a instância', function () {
        $user    = User::factory()->create();
        $project = Project::factory()->create(['status' => 1]);

        $repo          = new ProjectRepository();
        $participation = $repo->createParticipation($project->id, $user->id);

        expect($participation)->toBeInstanceOf(UserProject::class)
            ->and($participation->user_id)->toBe($user->id)
            ->and($participation->project_id)->toBe($project->id);

        $this->assertDatabaseHas('tasks_users_projects', [
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);
    });

});
