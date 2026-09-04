<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $agents = User::where('ticketit_agent', true)->get();

        if ($agents->isEmpty()) {
            $this->command->info('Nenhum agente encontrado. Crie agentes primeiro.');
            return;
        }

        Schedule::factory()->count(20)->create([
            'agent_id' => $agents->random()->id,
        ]);

        // Garante que existam agendamentos "hoje" (manhã e tarde) para o monitor preencher as estatísticas
        $now = \Carbon\Carbon::now();
        
        Schedule::factory()->create([
            'agent_id' => $agents->random()->id,
            'start_at' => $now->copy()->setTime(9, 30),
        ]);

        Schedule::factory()->create([
            'agent_id' => $agents->random()->id,
            'start_at' => $now->copy()->setTime(14, 0),
        ]);

        $this->command->info('Agendamentos de teste criados com sucesso!');
    }
}
