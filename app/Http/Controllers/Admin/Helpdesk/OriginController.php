<?php

namespace App\Http\Controllers\Admin\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Admin\Helpdesk\HelpdeskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OriginController extends Controller
{
    public function __construct(
        private readonly HelpdeskService $service
    ) {}

    public function index(): View
    {
        return view('admin.helpdesk.origins.index', [
            'origins' => $this->service->getAllOrigins(),
        ]);
    }

    public function create(): View
    {
        return view('admin.helpdesk.origins.form', ['origin' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'status'      => ['boolean'],
        ]);

        $origin = $this->service->createOrigin($data);

        return redirect()
            ->route('admin.helpdesk.origins.index')
            ->with('status', "Origem \"{$origin->name}\" criada com sucesso.");
    }

    public function edit(int $id): View
    {
        return view('admin.helpdesk.origins.form', [
            'origin' => $this->service->findOrigin($id),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'status'      => ['boolean'],
        ]);

        $origin = $this->service->updateOrigin($id, $data);

        return redirect()
            ->route('admin.helpdesk.origins.index')
            ->with('status', "Origem \"{$origin->name}\" atualizada com sucesso.");
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->service->deleteOrigin($id);
            return redirect()
                ->route('admin.helpdesk.origins.index')
                ->with('status', 'Origem removida com sucesso.');
        } catch (\DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
