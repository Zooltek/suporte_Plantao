<?php

/**
 * Testes de integração do UserRepository (Admin) — SQLite in-memory.
 *
 * Cobrem todos os métodos relevantes para o UserService:
 * create, update e anonymize.
 */

use App\Models\User;
use App\Repositories\UserRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ur_repo(): UserRepository
{
    return new UserRepository();
}

function ur_makeUser(array $attrs = []): User
{
    return User::factory()->create($attrs);
}

// ─── create ───────────────────────────────────────────────────────────────────

describe('UserRepository — create', function () {

    it('persiste um novo usuário no banco', function () {
        $data = [
            'name'            => 'Novo Usuário',
            'email'           => 'novo@example.com',
            'password'        => bcrypt('secret'),
            'department_id'   => \App\Models\Department::factory()->create()->id,
            'active'          => true,
            'ticketit_agent'  => false,
            'ticketit_admin'  => false,
        ];

        $user = ur_repo()->create($data);

        expect($user->id)->not->toBeNull();
        expect($user->email)->toBe('novo@example.com');
        $this->assertDatabaseHas('users', ['email' => 'novo@example.com']);
    });

});

// ─── update ───────────────────────────────────────────────────────────────────

describe('UserRepository — update', function () {

    it('atualiza os campos do usuário e retorna a instância', function () {
        $user = ur_makeUser(['name' => 'Original']);

        $updated = ur_repo()->update($user, ['name' => 'Atualizado']);

        expect($updated->name)->toBe('Atualizado');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Atualizado']);
    });

});

// ─── anonymize ────────────────────────────────────────────────────────────────

describe('UserRepository — anonymize', function () {

    it('anonimiza o usuário substituindo nome e email e faz soft-delete', function () {
        $user = ur_makeUser(['name' => 'João Silva', 'email' => 'joao@example.com']);

        ur_repo()->anonymize($user);

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => 'Usuário Excluído',
        ]);

        // Verifica soft-delete
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });

});

// ─── all ──────────────────────────────────────────────────────────────────────

describe('UserRepository — all', function () {

    it('retorna todos os usuários não deletados', function () {
        $u1 = ur_makeUser();
        $u2 = ur_makeUser();

        $result = ur_repo()->all();

        expect($result->pluck('id')->toArray())
            ->toContain($u1->id)
            ->toContain($u2->id);
    });

});

// ─── allDepartments ───────────────────────────────────────────────────────────

describe('UserRepository — allDepartments', function () {

    it('retorna os departamentos cadastrados', function () {
        $dept = \App\Models\Department::factory()->create();

        $result = ur_repo()->allDepartments();

        expect($result->pluck('id')->toArray())->toContain($dept->id);
    });

});
