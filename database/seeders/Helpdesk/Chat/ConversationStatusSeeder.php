<?php

namespace Database\Seeders\Helpdesk\Chat;

use App\Models\Helpdesk\Chat\ConversationStatus;
use Illuminate\Database\Seeder;

class ConversationStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Aberto'],
            ['name' => 'Em Atendimento'],
            ['name' => 'Aguardando Cliente'],
            ['name' => 'Fechado'],
        ];

        foreach ($statuses as $status) {
            ConversationStatus::firstOrCreate(
                ['name' => $status['name']]
            );
        }

        $this->command->info('Status de conversa criados com sucesso!');
    }
}
