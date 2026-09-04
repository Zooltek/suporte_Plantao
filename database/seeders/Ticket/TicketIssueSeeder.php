<?php

namespace Database\Seeders\Ticket;

use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketIssue;
use Illuminate\Database\Seeder;

class TicketIssueSeeder extends Seeder
{
    public function run(): void
    {
        if (TicketIssue::count() > 0) {
            $this->command->info('TicketIssueSeeder: problemas já existem, pulando.');
            return;
        }

        $ticketIds = Ticket::pluck('id');

        if ($ticketIds->isEmpty()) {
            $this->command->warn('TicketIssueSeeder: nenhum ticket encontrado.');
            return;
        }

        $total = 0;

        foreach ($ticketIds as $ticketId) {
            // 40% dos tickets têm 1-2 problemas registrados
            if (rand(1, 10) <= 4) {
                $count = rand(1, 2);
                TicketIssue::factory()
                    ->count($count)
                    ->create(['ticket_id' => $ticketId]);
                $total += $count;
            }
        }

        // Alguns problemas abertos (sem resolução)
        TicketIssue::factory()->open()->count(10)->create();
        $total += 10;

        $this->command->info("TicketIssueSeeder: {$total} problemas criados.");
    }
}
