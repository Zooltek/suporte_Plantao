<?php

namespace Database\Seeders\Ticket;

use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        if (Attendance::count() > 0) {
            $this->command->info('AttendanceSeeder: atendimentos já existem, pulando.');
            return;
        }

        $ticketIds = Ticket::pluck('id');

        if ($ticketIds->isEmpty()) {
            $this->command->warn('AttendanceSeeder: nenhum ticket encontrado.');
            return;
        }

        $total = 0;

        foreach ($ticketIds as $ticketId) {
            // 70% dos tickets têm 1-3 atendimentos
            if (rand(1, 10) > 3) {
                $count = rand(1, 3);
                Attendance::factory()
                    ->count($count)
                    ->create(['ticket_id' => $ticketId]);
                $total += $count;
            }
        }

        // Alguns atendimentos com retorno registrado
        Attendance::factory()->withReturn()->count(20)->create();
        $total += 20;

        $this->command->info("AttendanceSeeder: {$total} atendimentos criados.");
    }
}
