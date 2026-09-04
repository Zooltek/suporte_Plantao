<?php

/**
 * Testes unitários do UserService (Admin).
 *
 * Todas as chamadas ao banco são interceptadas pelo Mock do
 * UserRepositoryInterface, isolando as regras de negócio do Service.
 *
 * Dependências externas (Password::broker, Log) são mockadas via Laravel Facades.
 */

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function us_service(UserRepositoryInterface $repo): UserService
{
    return new UserService($repo);
}

function us_makeUser(array $attrs = []): User
{
    $user = new User(array_merge([
        'name'            => 'Fulano',
        'email'           => 'fulano@example.com',
        'department_id'   => 1,
        'ticketit_agent'  => false,
        'ticketit_admin'  => false,
    ], $attrs));
    $user->id = $attrs['id'] ?? 42;

    return $user;
}

// ─── store — com senha temporária ─────────────────────────────────────────────

describe('UserService — store com senha temporária', function () {

    it('cria o usuário com must_change_password=true e NÃO envia reset link', function () {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $user = us_makeUser();

        Log::shouldReceive('info')->once()->with('Usuário criado', Mockery::any());

        $repo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['must_change_password'] === true
                    && $data['ticketit_agent'] === true  // role=1
                    && $data['ticketit_admin'] === false
                    && $data['department_id'] === 2;
            })
            ->andReturn($user);

        $data = [
            'name'          => 'Fulano',
            'email'         => 'fulano@example.com',
            'password'      => 'senha123',
            'role'          => '1',
            'department_id' => '2',
        ];

        $result = us_service($repo)->store($data);

        expect($result)->toBe($user);
    });

});

// ─── store — sem senha (reset link) ───────────────────────────────────────────

describe('UserService — store sem senha', function () {

    it('cria o usuário com must_change_password=false e envia reset link', function () {
        $repo   = Mockery::mock(UserRepositoryInterface::class);
        $user   = us_makeUser(['email' => 'sem@senha.com']);
        $broker = Mockery::mock();

        Password::shouldReceive('broker')->once()->with('admins')->andReturn($broker);
        $broker->shouldReceive('sendResetLink')->once()->with(['email' => 'sem@senha.com']);

        Log::shouldReceive('info')->once()->with('Usuário criado', Mockery::any());

        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn (array $d) => $d['must_change_password'] === false)
            ->andReturn($user);

        $data = [
            'name'          => 'Sem Senha',
            'email'         => 'sem@senha.com',
            'password'      => '',
            'role'          => '2',
            'department_id' => '1',
        ];

        $result = us_service($repo)->store($data);

        expect($result)->toBe($user);
    });

});

// ─── store — role CRM (respeita departamento e concede acesso a chamados) ────

describe('UserService — store role CRM', function () {

    it('respeita o department_id enviado e habilita ticketit_agent para role=3', function () {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $user = us_makeUser(['department_id' => 7]);
        $broker = Mockery::mock();

        Password::shouldReceive('broker')->once()->with('admins')->andReturn($broker);
        $broker->shouldReceive('sendResetLink')->once();

        Log::shouldReceive('info')->once()->with('Usuário criado', Mockery::any());

        $repo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $d) {
                return $d['department_id'] === 7
                    && $d['ticketit_agent'] === true
                    && $d['ticketit_admin'] === false;
            })
            ->andReturn($user);

        us_service($repo)->store([
            'name'          => 'CRM User',
            'email'         => 'crm@example.com',
            'password'      => '',
            'role'          => '3',
            'department_id' => '7',
        ]);
    });

});

// ─── update ───────────────────────────────────────────────────────────────────

describe('UserService — update', function () {

    it('delega ao repositório com dados corretos e loga a atualização', function () {
        $repo    = Mockery::mock(UserRepositoryInterface::class);
        $user    = us_makeUser();
        $updated = us_makeUser(['name' => 'Atualizado']);

        Log::shouldReceive('info')->once()->with('Usuário atualizado', Mockery::any());

        $repo->shouldReceive('update')
            ->once()
            ->withArgs(function (User $u, array $d) use ($user) {
                return $u === $user
                    && $d['name'] === 'Atualizado'
                    && $d['ticketit_agent'] === true   // role=2 (admin também tem privilégios de agent)
                    && $d['ticketit_admin'] === true   // role=2
                    && $d['department_id'] === 1;
            })
            ->andReturn($updated);

        $data = [
            'name'          => 'Atualizado',
            'email'         => 'fulano@example.com',
            'role'          => '2',
            'department_id' => '1',
        ];

        $result = us_service($repo)->update($user, $data);

        expect($result)->toBe($updated);
    });

});

// ─── update — role inválido ────────────────────────────────────────────────────

describe('UserService — update com role inválido', function () {

    it('lança DomainException para role=0 (string vazia convertida)', function () {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $user = us_makeUser();

        expect(fn () => us_service($repo)->update($user, [
            'name'          => 'Fulano',
            'email'         => 'fulano@example.com',
            'role'          => '0',
            'department_id' => '1',
        ]))->toThrow(\DomainException::class);
    });

    it('lança DomainException para role=4 (fora do domínio)', function () {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $user = us_makeUser();

        expect(fn () => us_service($repo)->update($user, [
            'name'          => 'Fulano',
            'email'         => 'fulano@example.com',
            'role'          => '4',
            'department_id' => '1',
        ]))->toThrow(\DomainException::class);
    });

});

// ─── resetPassword ────────────────────────────────────────────────────────────

describe('UserService — resetPassword', function () {

    it('envia reset link e loga', function () {
        $repo   = Mockery::mock(UserRepositoryInterface::class);
        $user   = us_makeUser(['email' => 'reset@example.com']);
        $broker = Mockery::mock();

        Password::shouldReceive('broker')->once()->with('admins')->andReturn($broker);
        $broker->shouldReceive('sendResetLink')->once()->with(['email' => 'reset@example.com']);
        Log::shouldReceive('info')->once()->with('Reset de senha enviado', Mockery::any());

        us_service($repo)->resetPassword($user);
    });

});

// ─── anonymize ────────────────────────────────────────────────────────────────

describe('UserService — anonymize', function () {

    it('loga e delega anonymize ao repositório', function () {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $user = us_makeUser();

        Log::shouldReceive('warning')->once()->with('Usuário anonimizado (exclusão)', Mockery::any());
        $repo->shouldReceive('anonymize')->once()->with($user)->andReturnNull();

        us_service($repo)->anonymize($user);

        expect(true)->toBeTrue();
    });

});
