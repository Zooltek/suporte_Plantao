<?php

namespace Database\Factories\Helpdesk\Ticketit;

use App\Models\Helpdesk\Ticketit\Ticket;
use App\Models\Helpdesk\Ticketit\Status;
use App\Models\Helpdesk\Ticketit\Priority;
use App\Models\Helpdesk\Ticketit\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $statusId = Status::inRandomOrder()->value('id') ?? Status::factory()->create()->id;
        $priorityId = Priority::inRandomOrder()->value('id') ?? Priority::factory()->create()->id;
        $categoryId = Category::inRandomOrder()->value('id') ?? Category::factory()->create()->id;

        $requester = User::inRandomOrder()->first() ?? User::factory()->create();
        $agent = User::where('ticketit_agent', true)->inRandomOrder()->first() ?? User::factory()->create([
            'ticketit_agent' => true,
        ]);

        return [
            'subject'             => $this->faker->sentence(6),
            'content'             => $this->faker->paragraph(3),
            'status_id'           => $statusId,
            'priority_id'         => $priorityId,
            'user_id'             => $requester->id,
            'agent_id'            => $agent->id,
            'category_id'         => $categoryId,
            'sub_category_id'     => null,
            'rate_id'             => $this->faker->randomElement([0, 1, 2, 3]),
            'files'               => null,
            'conversation_id_list'=> null,
            'company_id'          => 1,
            'created_at'          => now()->subMinutes($this->faker->numberBetween(0, 1440)), // Entre 0 e 24h atrás para o gráfico povoar
            'updated_at'          => now(),
        ];
    }

    public function completed(): self
    {
        return $this->state(fn() => [
            'completed_at' => now()->subMinutes($this->faker->numberBetween(1, 1440)),
        ]);
    }
}
