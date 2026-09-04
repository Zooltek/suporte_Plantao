<?php

namespace App\Http\Controllers\Admin\Helpdesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Helpdesk\SaveCompanyModuleRequest;
use App\Services\Admin\Helpdesk\HelpdeskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyModuleController extends Controller
{
    public function __construct(
        private readonly HelpdeskService $service
    ) {}

    public function index(): View
    {
        return view('admin.helpdesk.modules.index', [
            'modules' => $this->service->getAllModuleTypes(),
        ]);
    }

    public function create(): View
    {
        return view('admin.helpdesk.modules.form', [
            'module'     => null,
            'ratModules' => $this->service->getAllRatModules(),
        ]);
    }

    public function store(SaveCompanyModuleRequest $request): RedirectResponse
    {
        $module = $this->service->createModuleType($request->validated());

        return redirect()
            ->route('admin.helpdesk.modules.index')
            ->with('status', "Módulo \"{$module->name}\" criado com sucesso.");
    }

    public function edit(int $id): View
    {
        return view('admin.helpdesk.modules.form', [
            'module'     => $this->service->findModuleType($id),
            'ratModules' => $this->service->getAllRatModules(),
        ]);
    }

    public function update(SaveCompanyModuleRequest $request, int $id): RedirectResponse
    {
        $module = $this->service->updateModuleType($id, $request->validated());

        return redirect()
            ->route('admin.helpdesk.modules.index')
            ->with('status', "Módulo \"{$module->name}\" atualizado com sucesso.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->service->deleteModuleType($id);

        return redirect()
            ->route('admin.helpdesk.modules.index')
            ->with('status', 'Módulo removido com sucesso.');
    }
}
