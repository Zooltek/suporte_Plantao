<?php

namespace Database\Factories\Tasks;

use App\Models\Customer;
use App\Models\Software;
use App\Models\Tasks\Project;
use App\Models\Tasks\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tasks\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $requestAt  = Carbon::instance($this->faker->dateTimeBetween('-45 days', 'now'))->startOfMinute();
        $deliveryAt = (clone $requestAt)->addDays($this->faker->numberBetween(2, 18))
            ->setTime($this->faker->numberBetween(8, 18), $this->faker->randomElement([0, 15, 30, 45]));

        $status      = $this->faker->randomElement(['new', 'pen', 'pro', 'don', 'sto']);
        $startedAt   = in_array($status, ['pro', 'don', 'sto'], true)
            ? (clone $requestAt)->addHours($this->faker->numberBetween(1, 48))
            : null;
        $completedAt = $status === 'don'
            ? (clone ($startedAt ?? $requestAt))->addHours($this->faker->numberBetween(2, 72))
            : null;

        return [
            'title'          => $this->faker->sentence(5),
            'content'        => $this->faker->paragraph(),
            'status'         => $status,
            'classification' => $this->faker->randomElement(array_keys(Task::CLASSIFICATIONS)),
            'user_id'      => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'author_id'    => fn (array $attr) => $attr['user_id'],
            'tester_id'    => null,
            'customer_id'  => Customer::query()->inRandomOrder()->value('id') ?? null,
            'project_id'   => null,
            'request_at'   => $requestAt,
            'delivery_at'  => $deliveryAt,
            'started_at'   => $startedAt,
            'completed_at' => $completedAt,
            'created_at'   => $requestAt,
            'updated_at'   => $completedAt ?? $requestAt,
        ];
    }

    /** Tarefa aberta (new / pen / pro / sto). */
    public function open(): static
    {
        return $this->state(fn () => [
            'status'       => $this->faker->randomElement(['new', 'pen', 'pro', 'sto']),
            'completed_at' => null,
        ]);
    }

    /** Tarefa concluída. */
    public function done(): static
    {
        return $this->state(function () {
            $completedAt = Carbon::now()->subDays($this->faker->numberBetween(1, 10));
            return [
                'status'       => $this->faker->randomElement(['don', 'tdo']),
                'started_at'   => (clone $completedAt)->subHours($this->faker->numberBetween(2, 48)),
                'completed_at' => $completedAt,
            ];
        });
    }

    /** Tarefa com prazo vencido. */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'status'      => $this->faker->randomElement(['new', 'pen', 'pro', 'sto']),
            'delivery_at' => Carbon::now()->subDays($this->faker->numberBetween(1, 7))->startOfDay(),
            'completed_at' => null,
        ]);
    }

    /** Atribui a tarefa a um usuário específico como responsável. */
    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /** Define o autor da tarefa. */
    public function authoredBy(User $user): static
    {
        return $this->state(fn () => ['author_id' => $user->id]);
    }

    /** Define o testador da tarefa. */
    public function testedBy(User $user): static
    {
        return $this->state(fn () => ['tester_id' => $user->id]);
    }

    /** Vincula a tarefa a um projeto. */
    public function withProject(Project $project): static
    {
        return $this->state(fn () => ['project_id' => $project->id]);
    }

    /** Vincula a tarefa a um sistema/software. */
    public function withSoftware(Software $software): static
    {
        return $this->state(fn () => ['software_id' => $software->id]);
    }
}
