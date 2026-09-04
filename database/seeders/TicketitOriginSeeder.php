<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketitOriginSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ticketit_origin')->insertOrIgnore([
            ['id' => 1, 'name' => 'Sistema Interno'],
            ['id' => 2, 'name' => 'Portal do Cliente'],
            ['id' => 3, 'name' => 'Email'],
            ['id' => 4, 'name' => 'Telefone'],
            ['id' => 5, 'name' => 'WhatsApp'],
        ]);

        $this->command->info('Origens de tickets criadas/atualizadas com sucesso!');
    }
}
