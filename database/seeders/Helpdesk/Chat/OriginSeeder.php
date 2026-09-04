<?php

namespace Database\Seeders\Helpdesk\Chat;

use App\Models\Ticket\Origin;
use Illuminate\Database\Seeder;

class OriginSeeder extends Seeder
{
    public function run(): void
    {
        $origins = ['Sistema Interno', 'Portal do Cliente', 'Email', 'Telefone', 'WhatsApp'];

        foreach ($origins as $origin) {
            Origin::firstOrCreate(['name' => $origin]);
        }

        Origin::factory()->count(3)->create();

        $this->command->info('Origens criadas com sucesso!');
    }
}
