<?php

namespace App\Http\Controllers\Admin\Implantacao;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Admin\Implantacao\ScheduleModuleConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Permite ao administrador configurar quais módulos técnicos de implantação
 * estão disponíveis para cada cliente.
 */
class ScheduleModuleController extends Controller
{
    public function __construct(
        private readonly ScheduleModuleConfigService $service
    ) {}

    /**
     * Lista todas as empresas com seus módulos de implantação configurados.
     */
    public function index(): View
    {
        $companies = Company::orderBy('trade_name')
            ->with('scheduleModules')
            ->get();

        return view('admin.implantacao.modules.index', compact('companies'));
    }

    /**
     * Exibe o formulário de configuração de módulos para um cliente.
     */
    public function edit(Company $company): View
    {
        $allModules = $this->service->getAllGrouped();
        $assigned   = $company->scheduleModules->pluck('id')->toArray();

        return view('admin.implantacao.modules.edit', compact('company', 'allModules', 'assigned'));
    }

    /**
     * Salva os módulos selecionados para o cliente.
     * Passar [] limpa a configuração → fallback para todos os módulos.
     */
    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'module_ids'   => ['nullable', 'array'],
            'module_ids.*' => ['integer', 'exists:schedule_record_module,id'],
        ]);

        $this->service->syncForCompany($company, $validated['module_ids'] ?? []);

        return redirect()
            ->route('admin.implantacao.modules.index')
            ->with('success', "Módulos de implantação de \"{$company->trade_name}\" atualizados com sucesso.");
    }
}
