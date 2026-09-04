<?php

namespace App\Http\Controllers\Admin\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Tasks\TaskStoreRequest;
use App\Http\Requests\Tasks\TaskCatalogRequest;
use App\Http\Requests\Tasks\TaskInboxRequest;
use App\Http\Requests\Tasks\TaskStatusUpdateRequest;
use App\Http\Requests\Tasks\TaskUpdateRequest;
use App\Models\Company;
use App\Models\Tasks\Attachment;
use App\Models\Tasks\Project;
use App\Models\Tasks\Task;
use App\Models\User;
use App\Services\Admin\Tasks\TaskModuleCatalogService;
use App\Services\Admin\Tasks\TaskRatContextService;
use App\Services\API\V1\Tasks\TaskService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MobileTaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskModuleCatalogService $taskModuleCatalogService,
        private readonly TaskRatContextService $taskRatContextService,
    ) {}

    public function index(TaskInboxRequest $request): View
    {
        $filters = $request->validated();
        $userId = auth('admin')->id() ?? auth()->id();

        $tasks = $this->taskService->getMyTasks(array_merge($filters, ['user_id' => $userId]));

        return view('tasks.mobile.index', array_merge([
            'tasks' => $tasks,
            'statusFilter' => $filters['status'] ?? 'open',
            'search' => $filters['q'] ?? '',
            'taskIdFilter' => isset($filters['task_id']) ? (string) $filters['task_id'] : '',
            'classificationFilter' => $filters['classification'] ?? '',
            'selectedCustomerFilter' => isset($filters['customer_id']) ? (string) $filters['customer_id'] : '',
            'selectedProjectFilter' => isset($filters['project_id']) ? (string) $filters['project_id'] : '',
        ], $this->getFormData()));
    }

    public function store(TaskStoreRequest $request): RedirectResponse
    {
        $task = $this->taskService->createTask($request->validated());

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $path = $file->store('task-attachments', 'local');

            Attachment::create([
                'task_id' => $task->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Tarefa criada com sucesso.');
    }

    public function updateStatus(TaskStatusUpdateRequest $request, Task $task): RedirectResponse
    {
        try {
            $this->taskService->updateTaskStatus($task->id, $request->validated());

            return back()->with('success', 'Status da tarefa atualizado com sucesso.');
        } catch (Exception $e) {
            $message = ((int) $e->getCode() === 403)
                ? 'Você não tem permissão para alterar o status desta tarefa.'
                : 'Não foi possível atualizar o status da tarefa.';

            return back()->with('warning', $message);
        }
    }

    public function update(TaskUpdateRequest $request, Task $task): RedirectResponse
    {
        try {
            $this->taskService->updateTask($task->id, $request->validated());

            return redirect()
                ->route('tasks.index', ['task' => $task->id])
                ->with('success', 'Tarefa atualizada com sucesso.');
        } catch (Exception $e) {
            $message = ((int) $e->getCode() === 403)
                ? 'Você não tem permissão para editar esta tarefa.'
                : 'Não foi possível atualizar a tarefa.';

            return back()
                ->withInput()
                ->with('warning', $message);
        }
    }

    public function catalog(TaskCatalogRequest $request): JsonResponse
    {
        $customerId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;
        $catalog = $this->taskModuleCatalogService->getCatalog($customerId);

        return response()->json([
            'projects' => $catalog['projects']
                ->map(fn (Project $project) => [
                    'id' => (string) $project->id,
                    'name' => $project->name,
                ])
                ->values()
                ->all(),
            'projectModuleTree' => $catalog['projectModuleTree'],
            'ratRecords' => $this->taskRatContextService->getSerializedRecentRecords($customerId),
        ]);
    }

    private function getFormData(): array
    {
        $catalog = $this->taskModuleCatalogService->getCatalog();

        return [
            'users' => User::where('active', true)->orderBy('name')->get(['id', 'name']),
            'companies' => Company::orderBy('trade_name')->get(['id', 'trade_name']),
            'projects' => $catalog['projects'],
            'projectModuleTree' => $catalog['projectModuleTree'],
        ];
    }
}
