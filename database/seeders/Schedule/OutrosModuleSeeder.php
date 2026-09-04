<?php

namespace Database\Seeders\Schedule;

use App\Models\Schedule\Module;
use Illuminate\Database\Seeder;

/**
 * Cria o módulo genérico "Outros" para atendimentos que não se
 * enquadram nos módulos específicos do checklist.
 *
 * Idempotente via firstOrCreate.
 */
class OutrosModuleSeeder extends Seeder
{
    public function run(): void
    {
        Module::firstOrCreate(
            ['name' => 'Outros'],
            ['project' => null]
        );

        $this->command->info('OutrosModuleSeeder: módulo "Outros" cadastrado.');
    }
}
