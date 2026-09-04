<?php

namespace Database\Seeders;

use App\Models\Tasks\Label;
use App\Models\Tasks\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Popula os projetos de tarefas com os produtos reais da empresa.
 * Idempotente via firstOrCreate (por nome).
 */
class TasksProjectSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')
            ->where('ticketit_admin', 1)
            ->value('id') ?? 1;

        $projects = [
            ['name' => 'EasyMaster',  'color' => '#6366f1'],
            ['name' => 'EasyControl', 'color' => '#10b981'],
            ['name' => 'AmuraWeb',    'color' => '#f59e0b'],
            ['name' => 'Plenus',      'color' => '#ef4444'],
        ];

        $projectModuleMap = [
            'EasyMaster' => ['Helpdesk', 'Financeiro'],
            'EasyControl' => ['Helpdesk', 'CRM'],
            'AmuraWeb' => ['CRM'],
            'Plenus' => ['Financeiro'],
        ];

        foreach ($projects as $projectData) {
            $project = Project::query()->firstOrCreate(
                ['name' => $projectData['name']],
                [
                    'color' => $projectData['color'],
                    'user_id' => $adminId,
                    'status' => 1,
                ]
            );

            $moduleIds = Label::query()
                ->active()
                ->whereNull('parent_id')
                ->whereIn('name', $projectModuleMap[$project->name] ?? [])
                ->pluck('id');

            if ($moduleIds->isNotEmpty()) {
                $project->modules()->syncWithoutDetaching($moduleIds->all());
            }
        }

        $this->command->info('TasksProjectSeeder: projetos e módulos de tarefas cadastrados.');
    }
}
