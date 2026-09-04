<?php

namespace Database\Seeders\Helpdesk\Ticketit;

use App\Models\Helpdesk\Ticketit\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    public function run(): void
    {
        Priority::firstOrCreate(['name' => 'Baixa'], ['color' => '#17a2b8']);
        Priority::firstOrCreate(['name' => 'Normal'], ['color' => '#007bff']);
        Priority::firstOrCreate(['name' => 'Alta'], ['color' => '#ffc107']);
        Priority::firstOrCreate(['name' => 'Crítica'], ['color' => '#dc3545']);

        Priority::factory()->count(2)->create();

        $this->command->info('Prioridades criadas com sucesso!');
    }
}
