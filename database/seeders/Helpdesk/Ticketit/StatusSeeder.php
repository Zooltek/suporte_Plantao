<?php

namespace Database\Seeders\Helpdesk\Ticketit;

use App\Models\Ticket\Status;
use App\Support\Tickets\TicketStatusCatalog;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TicketStatusCatalog::definitions() as $status) {
            Status::query()->updateOrCreate(
                ['id' => $status['id']],
                [
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'is_terminal' => $status['is_terminal'],
                    'requires_schedule' => $status['requires_schedule'],
                    'requires_solution' => $status['requires_solution'],
                    'requires_agent' => $status['requires_agent'],
                ]
            );
        }

        $this->command->info('Status canônicos do helpdesk sincronizados com sucesso.');
    }
}
