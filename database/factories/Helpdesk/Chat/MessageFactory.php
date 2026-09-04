<?php

namespace Database\Factories\Helpdesk\Chat;

use App\Models\Helpdesk\Chat\Message;
use App\Models\Helpdesk\Chat\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'chat_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
