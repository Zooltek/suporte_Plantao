<?php

namespace Database\Seeders\Helpdesk\Chat;

use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Helpdesk\Chat\ConversationStatus;
use App\Models\Helpdesk\Chat\Message;
use App\Models\Helpdesk\Chat\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        // Garante que existam status
        if (ConversationStatus::count() === 0) {
            $this->call(ConversationStatusSeeder::class);
        }

        $users = User::all();
        $statuses = ConversationStatus::all();

        if ($users->isEmpty()) {
            $users = User::factory(5)->create();
        }

        // Cria conversas
        Conversation::factory(10)->create([
            'name'      => fake()->sentence(3),
            'owner_id' => $users->random()->id,
            'agent_id' => $users->random()->id,
            'status_id' => $statuses->random()->id,
        ])->each(function ($conversation) use ($users) {
            $owner = $users->firstWhere('id', $conversation->owner_id);
            $agent = $conversation->agent_id ? $users->firstWhere('id', $conversation->agent_id) : null;

            // Adiciona Participantes (Dono e Agente)
            ConversationParticipant::firstOrCreate([
                'conversation_id' => $conversation->id,
                'user_id' => $conversation->owner_id,
            ], [
                'session' => $conversation->session,
                'display_name' => $owner?->name,
                'email' => $owner?->email,
                'token' => Str::uuid()->toString(),
            ]);

            if ($conversation->agent_id) {
                ConversationParticipant::firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'user_id' => $conversation->agent_id,
                ], [
                    'session' => $conversation->session,
                    'display_name' => $agent?->name,
                    'email' => $agent?->email,
                    'token' => Str::uuid()->toString(),
                ]);
            }

            // Adiciona Mensagens
            Message::factory(rand(2, 5))->create([
                'chat_id' => $conversation->id,
                'user_id' => $conversation->owner_id,
            ]);
        });
    }
}
