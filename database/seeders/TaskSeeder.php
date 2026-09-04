<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tasks\Label;
use App\Models\Tasks\Project;
use App\Models\Tasks\Task;
use App\Models\User;
use App\Services\Admin\Tasks\TaskModuleCatalogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();
        $customer = Customer::query()->first() ?? Customer::factory()->create();
        $taskModuleCatalog = app(TaskModuleCatalogService::class);

        $projects = Project::query()
            ->active()
            ->orderBy('name')
            ->get();

        if ($projects->isEmpty()) {
            $this->backfillMissingDeadlines();

            return;
        }

        foreach ($projects as $project) {
            $availableLabels = $taskModuleCatalog->getModulesForProject($project->id)
                ->flatMap(fn (Label $module) => $module->childs->isNotEmpty() ? $module->childs : collect([$module]))
                ->values();

            if ($availableLabels->isEmpty()) {
                continue;
            }

            foreach ($availableLabels as $label) {
                $tasks = Task::factory()
                    ->count(3)
                    ->withProject($project)
                    ->create([
                        'user_id' => $user->id,
                        'author_id' => $user->id,
                        'customer_id' => $customer->id,
                    ]);

                foreach ($tasks as $task) {
                    $task->labels()->syncWithoutDetaching([$label->id]);
                }
            }
        }

        $this->backfillMissingDeadlines();
    }

    private function backfillMissingDeadlines(): void
    {
        Task::query()
            ->active()
            ->whereNull('delivery_at')
            ->chunkById(200, function ($tasks): void {
                foreach ($tasks as $task) {
                    $baseDate = $task->request_at ?? $task->created_at ?? now();
                    $requestAt = Carbon::parse($baseDate)->startOfMinute();

                    $task->request_at = $task->request_at ?? $requestAt;
                    $task->delivery_at = (clone $requestAt)->addDays(random_int(2, 15));

                    if ($task->status === 'don' && ! $task->completed_at) {
                        $task->completed_at = (clone $task->delivery_at)->subDays(random_int(0, 2));
                    }

                    $task->save();
                }
            });
    }
}
