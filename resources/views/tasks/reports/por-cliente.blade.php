@extends('admin.layouts.master')

@section('page-title', 'Suporte - Lista de Solicitações por Cliente')
@section('title', 'Suporte - Lista de Solicitações por Cliente')

@push('styles')
<style>
    .report-shell {
        max-width: 72rem;
    }

    /* ── Print dialog ──────────────────────────────────────── */
    .print-module-dialog {
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }

    .print-module-item {
        border: 1px solid #e2e8f0;
        background: #ffffff;
    }

    .print-dialog-secondary {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
    }

    .print-dialog-chip {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
    }

    /* ── Dark mode (ocean) ─────────────────────────────────── */
    html.ocean .print-module-dialog {
        background: #0f172a !important;
        border-color: #334155 !important;
    }

    html.ocean .print-module-item {
        background: #111b30 !important;
        border-color: #334155 !important;
    }

    html.ocean .print-dialog-secondary {
        background: #111b30 !important;
        border-color: #475569 !important;
        color: #cbd5e1 !important;
    }

    html.ocean .print-dialog-secondary:hover {
        background: #1e293b !important;
    }

    html.ocean .print-dialog-chip {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #cbd5e1 !important;
    }

    html.ocean .print-module-dialog-title  { color: #f8fafc !important; }
    html.ocean .print-module-dialog-subtext { color: #94a3b8 !important; }
    html.ocean .print-module-item-name     { color: #e2e8f0 !important; }
    html.ocean .print-module-item-meta     { color: #94a3b8 !important; }

    html.ocean .report-card {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        box-shadow: 0 6px 16px rgba(2, 6, 23, 0.45) !important;
    }

    html.ocean .report-card > button {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }

    html.ocean .report-card > button:hover {
        background-color: #334155 !important;
    }

    html.ocean .report-card h2             { color: #f8fafc !important; }
    html.ocean .report-card .module-count  {
        background-color: #0b1220 !important;
        border-color: #475569 !important;
        color: #cbd5e1 !important;
    }

    html.ocean .report-card .report-toggle-icon { color: #94a3b8 !important; }

    html.ocean .report-card thead tr {
        background-color: #172033 !important;
        border-bottom-color: #334155 !important;
        color: #cbd5e1 !important;
    }

    html.ocean .report-card tbody         { border-color: #334155 !important; }
    html.ocean .report-card tbody tr      { border-color: #334155 !important; }
    html.ocean .report-card tbody tr:hover { background-color: #1e293b !important; }
    html.ocean .report-card td            { color: #e2e8f0 !important; }

    html.ocean .report-card td.text-slate-500,
    html.ocean .report-card td.text-center.text-slate-500 {
        color: #94a3b8 !important;
    }

    html.ocean .report-card .status-badge { border-color: transparent !important; }

    /* ── Subtarefas ────────────────────────────────────────── */
    html.ocean .subtask-row { background-color: #0d1525 !important; }
    html.ocean .subtask-row td { color: #94a3b8 !important; }

    html.ocean .customer-heading {
        color: #cbd5e1 !important;
        border-color: #3b82f6 !important;
    }

    /* ── Print ─────────────────────────────────────────────── */
    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        html, body {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        body > .min-h-screen { display: block !important; min-height: auto !important; }

        body > .min-h-screen > header,
        body > .min-h-screen > footer,
        body > .min-h-screen aside,
        body > .min-h-screen .fixed,
        .fixed {
            display: none !important;
        }

        body > .min-h-screen > .flex-grow,
        body > .min-h-screen > .flex-grow > main {
            display: block !important;
            overflow: visible !important;
            min-height: auto !important;
        }

        body > .min-h-screen > .flex-grow > main { padding: 0 !important; }

        .report-shell {
            max-width: 100% !important;
            margin: 0 !important;
        }

        .report-card {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            break-inside: avoid;
            page-break-inside: avoid;
            margin-bottom: 10mm !important;
        }

        .report-card__body {
            display: block !important;
            height: auto !important;
            overflow: visible !important;
        }

        .report-toggle-icon, .no-print { display: none !important; }
        .print-hidden-by-filter         { display: none !important; }

        table      { page-break-inside: auto; }
        thead      { display: table-header-group; }
        tr         { page-break-inside: avoid; page-break-after: auto; }

        .status-badge {
            background: transparent !important;
            border: 1px solid #0f172a !important;
            color: #0f172a !important;
        }
    }
</style>
@endpush

@section('content')

@php
    $totalProjects = collect($data)->count();
    $totalTasks    = collect($data)->sum(fn($p) =>
        collect($p['customers'])->sum(fn($c) => count($c['tasks']))
    );

    /* Mapeia projetos para o dialog de impressão */
    $printableGroups = collect($data)
        ->filter(fn($p) => collect($p['customers'])->some(fn($c) => count($c['tasks']) > 0))
        ->values()
        ->map(fn($p, $i) => [
            'id'    => "project-{$i}",
            'name'  => $p['project_name'],
            'count' => collect($p['customers'])->sum(fn($c) => count($c['tasks'])),
        ])
        ->values()
        ->all();

    $getStatusBadge = function (string $status): array {
        return match ($status) {
            'new' => ['label' => 'NOVA',    'css' => 'bg-sky-100 text-sky-700 border border-sky-200'],
            'pen' => ['label' => 'PEND.',   'css' => 'bg-amber-100 text-amber-700 border border-amber-200'],
            'pro' => ['label' => 'EM AND.', 'css' => 'bg-indigo-100 text-indigo-700 border border-indigo-200'],
            'sto' => ['label' => 'PARADA',  'css' => 'bg-rose-100 text-rose-700 border border-rose-200'],
            'tdo' => ['label' => 'TESTAD.', 'css' => 'bg-emerald-100 text-emerald-700 border border-emerald-200'],
            'don' => ['label' => 'CONC.',   'css' => 'bg-emerald-100 text-emerald-700 border border-emerald-200'],
            'can' => ['label' => 'CANC.',   'css' => 'bg-slate-100 text-slate-700 border border-slate-200'],
            'rej' => ['label' => 'REJE.',   'css' => 'bg-slate-200 text-slate-800 border border-slate-300'],
            default => ['label' => strtoupper($status), 'css' => 'bg-slate-100 text-slate-700 border border-slate-200'],
        };
    };
@endphp

<div x-data="taskReportPrint(@js($printableGroups))" x-init="init()" @keydown.escape.window="closePrintDialog" class="report-shell mx-auto space-y-6">

    {{-- ── Header ──────────────────────────────────────────── --}}
    <header class="bg-white border border-slate-200 rounded-2xl shadow-sm px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Relatório Operacional</p>
                <h1 class="text-2xl sm:text-3xl font-semibold text-slate-900 tracking-tight">Lista de Solicitações por Cliente</h1>
                <p class="text-sm text-slate-500 mt-1">Visão consolidada das tarefas ativas por projeto e cliente.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">
                    {{ $totalProjects }} {{ Str::plural('projeto', $totalProjects) }}
                </span>
                <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">
                    {{ $totalTasks }} solicitações
                </span>
                <button @click="openPrintDialog" type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z" />
                    </svg>
                    Imprimir
                </button>
            </div>
        </div>

        <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
            Emitido em {{ now()->format('d/m/Y \à\s H:i') }}
        </div>
    </header>

    {{-- ── Print Dialog ─────────────────────────────────────── --}}
    <div x-show="showPrintDialog" x-cloak class="fixed inset-0 z-[80] no-print" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-sm" @click="closePrintDialog"></div>

        <div class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-hidden rounded-t-2xl shadow-2xl sm:inset-x-auto sm:bottom-auto sm:left-1/2 sm:top-1/2 sm:w-[38rem] sm:max-w-[96vw] sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-2xl">
            <div class="print-module-dialog flex h-full max-h-[85vh] flex-col">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="print-module-dialog-title text-base font-semibold text-slate-900">Selecionar projetos para impressão</h3>
                    <p class="print-module-dialog-subtext mt-1 text-sm text-slate-500">Escolha um, vários ou todos os projetos.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 px-5 py-3">
                    <button @click="selectAllModules" type="button" class="print-dialog-secondary rounded-md px-3 py-1.5 text-xs font-semibold transition-colors">Selecionar todos</button>
                    <button @click="clearSelection"   type="button" class="print-dialog-secondary rounded-md px-3 py-1.5 text-xs font-semibold transition-colors">Limpar seleção</button>
                    <span class="print-dialog-chip ml-auto rounded-md px-2 py-1 text-xs font-semibold">
                        <span x-text="selectedModuleIds.length"></span> de <span x-text="modules.length"></span> selecionados
                    </span>
                </div>

                <div class="space-y-2 overflow-y-auto px-5 py-4">
                    <template x-for="module in modules" :key="module.id">
                        <label class="print-module-item flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5">
                            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" :value="module.id" x-model="selectedModuleIds">
                            <div class="min-w-0 flex-1">
                                <p class="print-module-item-name truncate text-sm font-semibold text-slate-800" x-text="module.name"></p>
                                <p class="print-module-item-meta text-xs text-slate-500">
                                    <span x-text="module.count"></span> solicitações
                                </p>
                            </div>
                        </label>
                    </template>
                </div>

                <div class="flex flex-col gap-2 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-end">
                    <button @click="closePrintDialog" type="button" class="print-dialog-secondary w-full rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors sm:w-auto">Cancelar</button>
                    <button @click="printSelectedModules" type="button"
                        class="w-full rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                        :disabled="selectedModuleIds.length === 0">
                        <span x-text="selectedModuleIds.length === modules.length ? 'Imprimir todos' : 'Imprimir seleção'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Estado vazio ─────────────────────────────────────── --}}
    @if($totalTasks === 0)
        <section class="bg-white border border-slate-200 rounded-2xl shadow-sm px-6 py-10 text-center">
            <p class="text-base font-medium text-slate-700">Nenhuma solicitação ativa encontrada.</p>
            <p class="mt-1 text-sm text-slate-500">Ajuste os filtros das tarefas para gerar um relatório com dados.</p>
        </section>
    @endif

    {{-- ── Blocos por Projeto ───────────────────────────────── --}}
    <div class="space-y-5">
        @foreach($data as $projectIndex => $project)
            @php
                $projectId    = "project-{$projectIndex}";
                $projectTotal = collect($project['customers'])->sum(fn($c) => count($c['tasks']));
            @endphp

            @if($projectTotal > 0)
                <section
                    x-data="{ open: true }"
                    data-report-module-id="{{ $projectId }}"
                    class="report-card overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                    {{-- Cabeçalho do projeto --}}
                    <button @click="open = !open" class="w-full border-b border-slate-200 bg-slate-50 px-5 py-3 text-left transition-colors hover:bg-slate-100">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="flex items-center gap-2 text-base font-semibold tracking-wide text-slate-800 sm:text-lg">
                                <svg class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
                                </svg>
                                {{ $project['project_name'] }}
                            </h2>
                            <div class="flex items-center gap-2">
                                <span class="module-count rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-600">
                                    {{ $projectTotal }} {{ Str::plural('item', $projectTotal) }}
                                </span>
                                <svg class="report-toggle-icon h-4 w-4 text-slate-500 transition-transform duration-200 no-print"
                                    :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </button>

                    {{-- Corpo: clientes --}}
                    <div x-show="open" x-collapse x-cloak class="report-card__body divide-y divide-slate-100">

                        @foreach($project['customers'] as $row)
                            @if(count($row['tasks']) > 0)
                                <div class="p-4 sm:p-5 space-y-3">

                                    {{-- Sub-cabeçalho do cliente --}}
                                    <h3 class="customer-heading flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-700 border-l-4 border-sky-500 pl-3">
                                        <svg class="h-4 w-4 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0H5m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4" />
                                        </svg>
                                        {{ $row['customer_name'] }}
                                        <span class="ml-auto text-xs font-semibold text-slate-400 normal-case tracking-normal">
                                            {{ count($row['tasks']) }} {{ Str::plural('tarefa', count($row['tasks'])) }}
                                        </span>
                                    </h3>

                                    {{-- Tabela de tarefas --}}
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border-collapse text-left text-[13px] sm:text-[14px]">
                                            <thead>
                                                <tr class="border-b border-slate-200 bg-slate-50 text-slate-600">
                                                    <th class="w-[110px] px-3 py-2 font-semibold">Solicitação</th>
                                                    <th class="px-3 py-2 font-semibold">Conteúdo</th>
                                                    <th class="w-[100px] px-3 py-2 text-center font-semibold">Conclusão</th>
                                                    <th class="w-[100px] px-3 py-2 text-center font-semibold">Prazo</th>
                                                    <th class="w-[100px] px-3 py-2 text-right font-semibold">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($row['tasks'] as $task)
                                                    {{-- Tarefa pai --}}
                                                    <tr class="transition-colors hover:bg-sky-50/50">
                                                        <td class="px-3 py-2 text-slate-500">
                                                            {{ $task->request_at?->format('d/m/Y') ?? $task->created_at?->format('d/m/Y') ?? '--/--/----' }}
                                                        </td>
                                                        <td class="px-3 py-2 font-medium text-slate-800">{{ $task->title }}</td>
                                                        <td class="px-3 py-2 text-center text-slate-500">
                                                            {{ $task->completed_at?->format('d/m/Y') ?? '--/--/----' }}
                                                        </td>
                                                        <td class="px-3 py-2 text-center text-slate-500">
                                                            {{ $task->delivery_at?->format('d/m/Y') ?? '--/--/----' }}
                                                        </td>
                                                        <td class="px-3 py-2 text-right">
                                                            @php $badge = $getStatusBadge($task->status); @endphp
                                                            <span class="status-badge inline-block rounded-md px-2 py-1 text-[11px] font-semibold tracking-wide {{ $badge['css'] }}">
                                                                {{ $badge['label'] }}
                                                            </span>
                                                        </td>
                                                    </tr>

                                                    {{-- Subtarefas --}}
                                                    @foreach($task->childs as $child)
                                                        <tr class="subtask-row bg-slate-50/60 transition-colors hover:bg-slate-100/60">
                                                            <td class="px-3 py-1.5 text-center text-slate-400">
                                                                <svg class="inline h-3.5 w-3.5 rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </td>
                                                            <td class="px-3 py-1.5 text-sm text-slate-600 italic">{{ $child->title }}</td>
                                                            <td class="px-3 py-1.5 text-center text-sm text-slate-500">
                                                                {{ $child->completed_at?->format('d/m/Y') ?? '--/--/----' }}
                                                            </td>
                                                            <td class="px-3 py-1.5 text-center text-sm text-slate-500">
                                                                {{ $child->delivery_at?->format('d/m/Y') ?? '--/--/----' }}
                                                            </td>
                                                            <td class="px-3 py-1.5 text-right">
                                                                @php $childBadge = $getStatusBadge($child->status); @endphp
                                                                <span class="status-badge inline-block rounded-md px-2 py-1 text-[11px] font-semibold tracking-wide {{ $childBadge['css'] }}">
                                                                    {{ $childBadge['label'] }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('taskReportPrint', (modules) => ({
            modules: [],
            selectedModuleIds: [],
            showPrintDialog: false,
            hasAppliedPrintFilter: false,

            init() {
                this.modules = Array.isArray(modules)
                    ? modules.map((m) => ({ id: String(m.id), name: m.name, count: Number(m.count || 0) }))
                    : [];

                this.selectAllModules();
                window.addEventListener('afterprint', () => this.resetPrintFilter());
            },

            openPrintDialog() {
                this.showPrintDialog = true;
                document.body.classList.add('overflow-hidden');
            },

            closePrintDialog() {
                this.showPrintDialog = false;
                document.body.classList.remove('overflow-hidden');
            },

            selectAllModules() {
                this.selectedModuleIds = this.modules.map((m) => m.id);
            },

            clearSelection() {
                this.selectedModuleIds = [];
            },

            applyPrintFilter() {
                const selected = new Set(this.selectedModuleIds.map((id) => String(id)));
                document.querySelectorAll('[data-report-module-id]').forEach((section) => {
                    section.classList.toggle(
                        'print-hidden-by-filter',
                        !selected.has(String(section.dataset.reportModuleId || ''))
                    );
                });
                this.hasAppliedPrintFilter = true;
            },

            resetPrintFilter() {
                if (!this.hasAppliedPrintFilter) return;
                document.querySelectorAll('.print-hidden-by-filter').forEach((s) => {
                    s.classList.remove('print-hidden-by-filter');
                });
                this.hasAppliedPrintFilter = false;
                document.body.classList.remove('overflow-hidden');
            },

            printSelectedModules() {
                if (this.selectedModuleIds.length === 0) return;
                this.applyPrintFilter();
                this.closePrintDialog();
                this.$nextTick(() => window.print());
            },
        }));
    });
</script>
@endpush