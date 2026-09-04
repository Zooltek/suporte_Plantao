<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Tasks\Label;
use App\Models\User;
use Illuminate\Database\Seeder;

class LabelsSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::where('ticketit_admin', 1)->value('id')
            ?? User::orderBy('id')->value('id')
            ?? User::factory()->create()->id;

        $departmentId = Department::query()->value('id') ??
            Department::query()->create(['name' => 'Suporte Técnico'])->id;

        $modules = [
            'Helpdesk'   => ['Tickets', 'Relatórios', 'Indicadores'],
            'CRM'        => ['Clientes', 'Campanhas', 'Follow-ups'],
            'Financeiro' => ['Faturamento', 'Cobrança'],
        ];

        foreach ($modules as $parentName => $children) {
            $parent = Label::query()->firstOrCreate(
                ['name' => $parentName, 'parent_id' => null],
                ['user_id' => $userId, 'department_id' => $departmentId, 'is_active' => true]
            );

            foreach ($children as $childName) {
                Label::query()->firstOrCreate(
                    ['name' => $childName, 'parent_id' => $parent->id],
                    ['user_id' => $userId, 'department_id' => $departmentId, 'is_active' => true]
                );
            }
        }
    }
}
