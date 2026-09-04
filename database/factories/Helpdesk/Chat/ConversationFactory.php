<?php

namespace Database\Factories\Helpdesk\Chat;

use App\Models\Helpdesk\Chat\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'name'      => $this->faker->sentence(),
            'desc'      => $this->faker->sentence(),
            'session'   => $this->faker->uuid(),
            'owner_id'  => User::factory(),
            'agent_id'  => null,
            'status_id' => 1,
            'subject'   => $this->faker->words(3, true),
            'password'  => null,
            'expire_at' => now()->addDays(7),
            'closed_at' => null,
            'ticket_id' => null,
        ];
    }
}
