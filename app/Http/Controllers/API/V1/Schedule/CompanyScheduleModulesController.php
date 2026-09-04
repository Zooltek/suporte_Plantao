<?php

namespace App\Http\Controllers\API\V1\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Admin\Implantacao\ScheduleModuleConfigService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint usado pelo formulário de agendamento para filtrar
 * os módulos disponíveis de acordo com o cliente selecionado.
 */
class CompanyScheduleModulesController extends Controller
{
    public function __construct(
        private readonly ScheduleModuleConfigService $service
    ) {}

    /**
     * GET /api/v1/companies/{company}/schedule-modules
     *
     * Retorna os módulos do cliente agrupados por projeto.
     * Se o cliente não tiver módulos configurados, retorna todos (fallback).
     *
     * @return JsonResponse  { "Geral": [{id, name, project}], "ERP": [...] }
     */
    public function index(Company $company): JsonResponse
    {
        $grouped = $this->service
            ->getForCompany($company)
            ->groupBy(fn ($m) => $m->project ?? 'Geral')
            ->map(fn ($modules) => $modules->map(fn ($m) => [
                'id'      => $m->id,
                'name'    => $m->name,
                'project' => $m->project ?? 'Geral',
            ])->values());

        return response()->json($grouped);
    }
}
