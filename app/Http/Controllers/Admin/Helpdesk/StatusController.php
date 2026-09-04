<?php

namespace App\Http\Controllers\Admin\Helpdesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Helpdesk\SaveStatusRequest;
use App\Services\Admin\Helpdesk\HelpdeskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD de Status do Helpdesk.
 * Controller magro — delega toda lógica à HelpdeskService (SRP).
 */
class StatusController extends Controller
{
    public function __construct(
        private readonly HelpdeskService $service
    ) {}

    public function index(): View
    {
        return view('admin.helpdesk.status.index', [
            'statuses' => $this->service->getAllStatuses(),
        ]);
    }

    public function create(): View
    {
        return view('admin.helpdesk.status.form', [
            'status' => null,
        ]);
    }

    public function store(SaveStatusRequest $request): RedirectResponse
    {
        $status = $this->service->createStatus($request->validated());

        return redirect()
            ->route('admin.helpdesk.status.index')
            ->with('status', "Status \"{$status->name}\" criado com sucesso.");
    }

    public function edit(int $id): View
    {
        return view('admin.helpdesk.status.form', [
            'status' => $this->service->findStatus($id),
        ]);
    }

    public function update(SaveStatusRequest $request, int $id): RedirectResponse
    {
        $status = $this->service->updateStatus($id, $request->validated());

        return redirect()
            ->route('admin.helpdesk.status.index')
            ->with('status', "Status \"{$status->name}\" atualizado com sucesso.");
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->service->deleteStatus($id);
            return redirect()
                ->route('admin.helpdesk.status.index')
                ->with('status', 'Status removido com sucesso.');
        } catch (\DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
