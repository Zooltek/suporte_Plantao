<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'               => fake()->name(),
            'email'              => fake()->unique()->safeEmail(),
            'email_verified_at'  => now(),
            'password'           => static::$password ??= Hash::make('password'),
            'remember_token'     => Str::random(10),
            'active'             => true,
            'company_id'         => null,
            'ticketit_admin'     => false,
            'ticketit_agent'     => false,
            // Lazy: reaproveita um dos departamentos seedados (Suporte/
            // Financeiro/Comercial/Administração). Sem isso, cada User
            // criado pelo factory inflava user_department com nomes
            // aleatórios (jobTitle do faker) — virou lixo em DEV.
            'department_id'      => fn () => $this->pickDepartmentId(),
        ];
    }

    private function pickDepartmentId(): int
    {
        $id = Department::query()->inRandomOrder()->value('id');

        return $id ?? Department::factory()->asDefault()->create()->id;
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'ticketit_admin' => true,
            'ticketit_agent' => true,
        ])->afterCreating(fn ($user) => $user->syncRoles($this->role('admin')));
    }

    public function agent(): static
    {
        return $this->state(fn (array $attributes) => [
            'ticketit_admin' => false,
            'ticketit_agent' => true,
        ])->afterCreating(fn ($user) => $user->syncRoles($this->role('agent')));
    }

    public function crm(): static
    {
        return $this->state(fn (array $attributes) => [
            'ticketit_admin' => false,
            'ticketit_agent' => false,
            'department_id'  => 3,
        ])->afterCreating(fn ($user) => $user->syncRoles($this->role('crm')));
    }

    public function deploymentAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'deployment_admin' => true,
        ])->afterCreating(fn ($user) => $user->syncRoles($this->role('deployment-admin')));
    }

    /**
     * Busca o Role pelo nome e guard 'web' explicitamente,
     * evitando que o Spatie resolva o guard pelo contexto de autenticação ativo.
     */
    private function role(string $name): \Illuminate\Support\Collection
    {
        return Role::where('name', $name)->where('guard_name', 'web')->get();
    }
}
