<?php

namespace Database\Factories\Helpdesk\Chat;

use App\Models\Helpdesk\Chat\ConversationParticipant;
use App\Models\Helpdesk\Chat\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationParticipantFactory extends Factory
{
    protected $model = ConversationParticipant::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'socket_id' => null,
            'session' => $this->faker->uuid(),
            'display_name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'token' => $this->faker->uuid(),
        ];
    }
}
