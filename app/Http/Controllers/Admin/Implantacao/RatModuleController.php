<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Implantacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Implantacao\RatModuleRequest;
use App\Models\Schedule\Module;
use App\Services\Admin\Implantacao\RatModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RatModuleController extends Controller
{
    public function __construct(
        private readonly RatModuleService $service
    ) {}

    public function index(): View
    {
        return view('admin.implantacao.rat-modules.index', [
            'modules' => $this->service->getAllWithCounts(),
        ]);
    }

    public function create(): View
    {
        return view('admin.implantacao.rat-modules.form', [
            'module'   => null,
            'projects' => $this->service->getDistinctProjects(),
        ]);
    }

    public function store(RatModuleRequest $request): RedirectResponse
    {
        $module = $this->service->createModule($request->validated());

        return redirect()
            ->route('admin.implantacao.rat-modules.index')
            ->with('status', "Módulo \"{$module->name}\" criado com sucesso.");
    }

    public function edit(Module $module): View
    {
        return view('admin.implantacao.rat-modules.form', [
            'module'   => $module,
            'projects' => $this->service->getDistinctProjects(),
        ]);
    }

    public function update(RatModuleRequest $request, Module $module): RedirectResponse
    {
        $module = $this->service->updateModule($module, $request->validated());

        return redirect()
            ->route('admin.implantacao.rat-modules.index')
            ->with('status', "Módulo \"{$module->name}\" atualizado com sucesso.");
    }

    public function destroy(Module $module): RedirectResponse
    {
        try {
            $this->service->deleteModule($module);

            return redirect()
                ->route('admin.implantacao.rat-modules.index')
                ->with('status', 'Módulo removido com sucesso.');
        } catch (\DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
