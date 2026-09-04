<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Reports\ImplementationClientSituation;
use App\Enums\Reports\ReportPeriodPreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportPeriodFilterRequest;
use App\Models\Software;
use App\Services\Agent\ReportService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Resumo Diário por Problema (subcategoria).
     */
    public function dailyProblems(ReportPeriodFilterRequest $request): View
    {
        [$start, $end, $selectedPreset] = $this->resolveReportPeriod(
            $request,
            ReportPeriodPreset::LAST_30_DAYS,
        );
        $selectedSoftwareId = $request->softwareId();
        $softwareFilterQuery = $this->buildSoftwareFilterQuery($selectedSoftwareId);

        $data = $this->reportService->generateProblemasData($start, $end, $selectedSoftwareId);

        return view('admin.reports.daily-problems', array_merge(
            [
                'data' => $data,
            ],
            $this->buildPeriodFilterViewData(
                $request,
                'admin.reports.daily-problems',
                ReportPeriodPreset::LAST_30_DAYS,
                $selectedPreset,
                $start,
                $end,
                $softwareFilterQuery,
                $this->buildDailyProblemSecondaryFilters($selectedSoftwareId),
                $softwareFilterQuery,
                $selectedSoftwareId !== null,
            ),
        ));
    }

    /**
     * Relatório de Clientes em Implantação.
     */
    public function implementationClients(ReportPeriodFilterRequest $request): View
    {
        [$start, $end, $selectedPreset] = $this->resolveReportPeriod(
            $request,
            ReportPeriodPreset::ALL_TIME,
        );
        $selectedSituation = $request->implementationStatus();
        $statusFilterQuery = $this->buildImplementationStatusFilterQuery($selectedSituation);

        $result = $this->reportService->getImplementationClientsData($start, $end, $selectedSituation);

        return view('admin.reports.implementation-clients', array_merge(
            $result,
            $this->buildPeriodFilterViewData(
                $request,
                'admin.reports.implementation-clients',
                ReportPeriodPreset::ALL_TIME,
                $selectedPreset,
                $start,
                $end,
                $statusFilterQuery,
                $this->buildImplementationSecondaryFilters($selectedSituation),
                $statusFilterQuery,
                $selectedSituation !== ImplementationClientSituation::ALL,
            ),
        ));
    }

    /**
     * Relatório de Clientes sem Atendimento no período.
     */
    public function clientsWithoutAttendance(ReportPeriodFilterRequest $request): View
    {
        [$start, $end, $selectedPreset] = $this->resolveReportPeriod(
            $request,
            ReportPeriodPreset::LAST_30_DAYS,
        );

        $data = $this->reportService->getClientsWithoutAttendanceData($start, $end);

        return view('admin.reports.clients-without-attendance', array_merge(
            $data,
            $this->buildPeriodFilterViewData(
                $request,
                'admin.reports.clients-without-attendance',
                ReportPeriodPreset::LAST_30_DAYS,
                $selectedPreset,
                $start,
                $end,
            ),
        ));
    }

    /**
     * Relatório de Atualização de Clientes.
     */
    public function clientUpdates(ReportPeriodFilterRequest $request): View
    {
        $selectedSoftwareId = $request->softwareId();
        $softwareFilterQuery = $this->buildSoftwareFilterQuery($selectedSoftwareId);

        $clients = $this->reportService->getClientUpdatesData($selectedSoftwareId);

        return view('admin.reports.client-updates', [
            'clients' => $clients,
            'selectedSoftwareId' => $selectedSoftwareId,
            'softwareFilterQuery' => $softwareFilterQuery,
            'secondaryFilters' => $this->buildDailyProblemSecondaryFilters($selectedSoftwareId),
            'hasActiveReportFilters' => $selectedSoftwareId !== null,
        ]);
    }

    /**
     * Exporta o Relatório de Atualização de Clientes para CSV com UTF-8 BOM.
     */
    public function exportClientUpdates(ReportPeriodFilterRequest $request)
    {
        $selectedSoftwareId = $request->softwareId();
        $clients = $this->reportService->getClientUpdatesData($selectedSoftwareId);

        $sortedClients = $clients->sortBy([
            [fn ($client) => mb_strtolower($client->group?->name ?? 'Sem Grupo'), 'asc'],
            [fn ($client) => mb_strtolower($client->name), 'asc'],
        ]);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="atualizacao_de_clientes_' . now()->format('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($sortedClients) {
            $file = fopen('php://output', 'w');

            // Grava UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Cabeçalho CSV
            fputcsv($file, [
                'Grupo Empresarial',
                'Cliente',
                'CNPJ',
                'Contato',
                'Telefone',
                'Telefone 2',
                'Sistema',
                'Versão',
                'Ativo',
            ], ';');

            // Dados CSV
            foreach ($sortedClients as $client) {
                fputcsv($file, [
                    $client->group?->name ?? 'Sem Grupo',
                    $client->name,
                    $client->cnpj ?? '—',
                    $client->contact_name ?? '—',
                    $client->phone ?? '—',
                    $client->telephone_2 ?? '—',
                    $client->software?->name ?? '—',
                    $client->software?->version ?? '—',
                    $client->is_active ? 'Sim' : 'Não',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function resolveReportPeriod(

        ReportPeriodFilterRequest $request,
        ReportPeriodPreset $defaultPreset,
    ): array {
        $selectedPreset = $request->selectedPreset($defaultPreset);

        if ($selectedPreset === ReportPeriodPreset::CUSTOM) {
            return [
                $request->periodStart(),
                $request->periodEnd(),
                $selectedPreset,
            ];
        }

        ['start' => $start, 'end' => $end] = $selectedPreset->resolveBounds();

        return [$start, $end, $selectedPreset];
    }

    private function buildPeriodFilterViewData(
        ReportPeriodFilterRequest $request,
        string $routeName,
        ReportPeriodPreset $defaultPreset,
        ReportPeriodPreset $selectedPreset,
        ?Carbon $start,
        ?Carbon $end,
        array $preservedQuery = [],
        array $secondaryFilters = [],
        array $secondaryHiddenFields = [],
        bool $hasActiveSecondaryFilters = false,
    ): array {
        return [
            'dateFrom' => $request->inputDate('date_from', $start),
            'dateTo' => $request->inputDate('date_to', $end),
            'displayPeriod' => $selectedPreset === ReportPeriodPreset::CUSTOM
                ? $this->formatDisplayPeriod($start, $end, $defaultPreset->displayPeriod())
                : $selectedPreset->displayPeriod(),
            'selectedPeriodPreset' => $selectedPreset->value,
            'selectedPeriodPresetLabel' => $selectedPreset->label(),
            'periodPresetOptions' => $this->buildPeriodPresetOptions($routeName, $selectedPreset, $preservedQuery),
            'periodFilterActionRoute' => $routeName,
            'currentPeriodHiddenFields' => $this->buildCurrentPeriodHiddenFields($request, $selectedPreset),
            'secondaryFilters' => $secondaryFilters,
            'secondaryHiddenFields' => $secondaryHiddenFields,
            'hasActiveReportFilters' => $request->hasActivePeriodFilter($defaultPreset) || $hasActiveSecondaryFilters,
        ];
    }

    private function buildPeriodPresetOptions(
        string $routeName,
        ReportPeriodPreset $selectedPreset,
        array $preservedQuery = [],
    ): array {
        return array_map(
            function (ReportPeriodPreset $preset) use ($routeName, $selectedPreset, $preservedQuery): array {
                $query = array_filter(
                    array_merge($preservedQuery, ['period_preset' => $preset->value]),
                    static fn ($value): bool => $value !== null && $value !== '',
                );

                return [
                    'value' => $preset->value,
                    'label' => $preset->label(),
                    'description' => $preset->description(),
                    'url' => route($routeName, $query),
                    'active' => $selectedPreset === $preset,
                ];
            },
            ReportPeriodPreset::filterableCases(),
        );
    }

    private function buildCurrentPeriodHiddenFields(
        ReportPeriodFilterRequest $request,
        ReportPeriodPreset $selectedPreset,
    ): array {
        if ($selectedPreset !== ReportPeriodPreset::CUSTOM) {
            return ['period_preset' => $selectedPreset->value];
        }

        return array_filter(
            [
                'period_preset' => ReportPeriodPreset::CUSTOM->value,
                'date_from' => $request->inputDate('date_from'),
                'date_to' => $request->inputDate('date_to'),
            ],
            static fn ($value): bool => $value !== null && $value !== '',
        );
    }

    private function buildDailyProblemSecondaryFilters(?int $selectedSoftwareId): array
    {
        return [[
            'name' => 'software_id',
            'label' => 'Software',
            'value' => (string) ($selectedSoftwareId ?? ''),
            'placeholder' => 'Todos os sistemas',
            'options' => Software::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (Software $software): array => [
                    'value' => (string) $software->id,
                    'label' => $software->name,
                ])
                ->all(),
        ]];
    }

    private function buildImplementationSecondaryFilters(
        ImplementationClientSituation $selectedSituation,
    ): array {
        return [[
            'name' => 'implementation_status',
            'label' => 'Situação',
            'value' => $selectedSituation === ImplementationClientSituation::ALL
                ? ''
                : $selectedSituation->value,
            'placeholder' => ImplementationClientSituation::ALL->label(),
            'options' => array_map(
                static fn (ImplementationClientSituation $situation): array => [
                    'value' => $situation->value,
                    'label' => $situation->label(),
                ],
                ImplementationClientSituation::selectableCases(),
            ),
        ]];
    }

    private function buildSoftwareFilterQuery(?int $selectedSoftwareId): array
    {
        return $selectedSoftwareId !== null
            ? ['software_id' => $selectedSoftwareId]
            : [];
    }

    private function buildImplementationStatusFilterQuery(
        ImplementationClientSituation $selectedSituation,
    ): array {
        return $selectedSituation !== ImplementationClientSituation::ALL
            ? ['implementation_status' => $selectedSituation->value]
            : [];
    }

    private function formatDisplayPeriod(?Carbon $start, ?Carbon $end, string $fallback): string
    {
        if (! $start && ! $end) {
            return $fallback;
        }

        if ($start && $end) {
            return $start->isSameDay($end)
                ? $start->format('d/m/Y')
                : $start->format('d/m/Y').' até '.$end->format('d/m/Y');
        }

        if ($start) {
            return 'A partir de '.$start->format('d/m/Y');
        }

        return 'Até '.$end->format('d/m/Y');
    }
}
