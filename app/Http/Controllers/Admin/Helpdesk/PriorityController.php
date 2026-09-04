<?php

namespace App\Http\Controllers\Admin\Helpdesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Helpdesk\SavePriorityRequest;
use App\Services\Admin\Helpdesk\HelpdeskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD de Prioridades do Helpdesk.
 * Controller magro — delega toda lógica à HelpdeskService (SRP).
 */
class PriorityController extends Controller
{
    public function __construct(
        private readonly HelpdeskService $service
    ) {}

    public function index(): View
    {
        return view('admin.helpdesk.priority.index', [
            'priorities' => $this->service->getAllPriorities(),
        ]);
    }

    public function create(): View
    {
        return view('admin.helpdesk.priority.form', [
            'priority' => null,
        ]);
    }

    public function store(SavePriorityRequest $request): RedirectResponse
    {
        $priority = $this->service->createPriority($request->validated());

        return redirect()
            ->route('admin.helpdesk.priority.index')
            ->with('status', "Prioridade \"{$priority->name}\" criada com sucesso.");
    }

    public function edit(int $id): View
    {
        return view('admin.helpdesk.priority.form', [
            'priority' => $this->service->findPriority($id),
        ]);
    }

    public function update(SavePriorityRequest $request, int $id): RedirectResponse
    {
        $priority = $this->service->updatePriority($id, $request->validated());

        return redirect()
            ->route('admin.helpdesk.priority.index')
            ->with('status', "Prioridade \"{$priority->name}\" atualizada com sucesso.");
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->service->deletePriority($id);
            return redirect()
                ->route('admin.helpdesk.priority.index')
                ->with('status', 'Prioridade removida com sucesso.');
        } catch (\DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
