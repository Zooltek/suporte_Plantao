<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['id' => 10, 'name' => 'Administração'],
            ['id' => 1, 'name' => 'Suporte Técnico'],
            ['id' => 2, 'name' => 'Financeiro'],
            ['id' => 3, 'name' => 'CRM / Comercial', 'is_crm' => true, 'is_feedback' => true],
        ];

        foreach ($departments as $dep) {
            // Usamos updateOrCreate para evitar erro de duplicidade se rodar 2x
            Department::updateOrCreate(['id' => $dep['id']], $dep);
        }
        $this->command->info('Departamentos (10, 1, 2, 3) criados.');
    }
}
