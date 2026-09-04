@extends('admin.layouts.master')

@section('page-title', 'Clientes em Implantação')
@section('title', 'Clientes em Implantação')

@push('styles')
<style>
    @include('admin.reports.partials.period-filter-styles')

    /* ─── Tokens de cor base ─── */
    .rpt-bg      { background-color: #f8fafc; }
    .rpt-card    { background-color: #ffffff; border-color: #e2e8f0; }
    .rpt-thead   { background-color: #f1f5f9; color: #475569; }
    .rpt-tbody   { background-color: #ffffff; }
    .rpt-row:hover { background-color: #f0f9ff; }
    .rpt-tfoot   { background-color: #f8fafc; border-color: #cbd5e1; }
    .rpt-title   { color: #0f172a; }
    .rpt-sub     { color: #64748b; }
    .rpt-cnpj    { color: #94a3b8; font-family: monospace; }
    .rpt-cell    { color: #1e293b; }
    .rpt-divider { border-color: #e2e8f0; }
    .rpt-badge-soft { background-color: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
    .rpt-input   { background-color: #f8fafc; border-color: #e2e8f0; color: #334155; }
    .rpt-input:focus { outline: none; box-shadow: 0 0 0 2px #0ea5e9; }
    .rpt-btn     { background-color: #f1f5f9; border-color: #e2e8f0; color: #334155; }
    .rpt-btn:hover { background-color: #e2e8f0; }

    /* ─── Ocean / Dark Mode ─── */
    html.ocean .rpt-bg      { background-color: transparent; }
    html.ocean .rpt-card    { background-color: #1e293b; border-color: #334155; }
    html.ocean .rpt-thead   { background-color: #0f172a; color: #94a3b8; }
    html.ocean .rpt-tbody   { background-color: #1e293b; }
    html.ocean .rpt-row:hover { background-color: #0f172a; }
    html.ocean .rpt-tfoot   { background-color: #0f172a; border-color: #334155; }
    html.ocean .rpt-title   { color: #f1f5f9; }
    html.ocean .rpt-sub     { color: #94a3b8; }
    html.ocean .rpt-cnpj    { color: #64748b; }
    html.ocean .rpt-cell    { color: #cbd5e1; }
    html.ocean .rpt-divider { border-color: #334155; }
    html.ocean .rpt-badge-soft { background-color: #0f172a; color: #94a3b8; border-color: #334155; }
    html.ocean .rpt-input   { background-color: #0f172a; border-color: #334155; color: #e2e8f0; }
    html.ocean .rpt-btn     { background-color: #0f172a; border-color: #334155; color: #94a3b8; }
    html.ocean .rpt-btn:hover { background-color: #1e293b; }

    /* ─── KPI Icon boxes ─── */
    .kpi-box-indigo { background-color: #eef2ff; color: #6366f1; }
    .kpi-box-amber  { background-color: #fffbeb; color: #f59e0b; }
    .kpi-box-purple { background-color: #f5f3ff; color: #8b5cf6; }
    html.ocean .kpi-box-indigo { background-color: #1e1b4b; color: #818cf8; }
    html.ocean .kpi-box-amber  { background-color: #1c1209; color: #fbbf24; }
    html.ocean .kpi-box-purple { background-color: #1a0f2e; color: #a78bfa; }

    /* ─── Estado ─── */
    html.ocean .text-indigo-700 { color: #818cf8 !important; }
    html.ocean .text-amber-700  { color: #fbbf24 !important; }
    html.ocean .text-purple-500 { color: #a78bfa !important; }

    /* ─── Impressão ─── */
    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        html, body { background: #fff !important; }
        body > * > header,
        body > * > footer,
        body > * aside { display: none !important; }
        .no-print { display: none !important; }
        .rpt-card, .rpt-thead, .rpt-tbody, .rpt-tfoot, .rpt-row:hover {
            background-color: #fff !important;
            border-color: #e2e8f0 !important;
        }
        .rpt-title, .rpt-cell { color: #000 !important; }
        table { page-break-inside: auto; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    }
</style>
@endpush

@section('content')

<div class="max-w-6xl mx-auto space-y-5">

    {{-- ── Header ─────────────────────────────────────────────────── --}}
    <div class="rpt-card border rounded-2xl px-5 py-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <span class="inline-block text-[10px] font-bold uppercase tracking-[.15em] rpt-sub mb-1">Relatório Operacional</span>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight rpt-title">Clientes em Implantação</h1>
                <p class="text-sm rpt-sub mt-0.5">
                    Clientes com tickets em aberto ou agendamentos ativos · Período: <strong class="rpt-cell">{{ $displayPeriod }}</strong>
                </p>
                <p class="text-xs rpt-sub mt-1">
                    Filtro disponível: período de atividade, considerando a abertura do ticket e o início do agendamento.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                @include('admin.reports.partials.period-filter', [
                    'filterRouteName' => $periodFilterActionRoute,
                ])

                <span class="rpt-badge-soft border text-xs font-semibold px-3 py-1.5 rounded-lg">
                    {{ $totalClients }} cliente(s)
                </span>
                <button onclick="window.print()" type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sky-700 active:scale-95">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </button>
            </div>
        </div>
        <p class="text-[11px] rpt-sub mt-3 pt-3 border-t rpt-divider">
            Emitido em {{ now()->format('d/m/Y \à\s H:i') }}
        </p>
    </div>

    {{-- ── KPI Cards ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 no-print">
        <div class="rpt-card border rounded-xl p-5 flex items-center gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl kpi-box-indigo flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs rpt-sub font-medium">Total de Clientes</p>
                <p class="text-2xl font-black rpt-title">{{ $totalClients }}</p>
            </div>
        </div>
        <div class="rpt-card border rounded-xl p-5 flex items-center gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl kpi-box-amber flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <p class="text-xs rpt-sub font-medium">Tickets Abertos</p>
                <p class="text-2xl font-black text-amber-600">{{ $totalOpenTickets }}</p>
            </div>
        </div>
        <div class="rpt-card border rounded-xl p-5 flex items-center gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl kpi-box-purple flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs rpt-sub font-medium">Tempo de Implantação</p>
                <p class="text-2xl font-black text-purple-500">{{ $totalImplementationFormatted }}</p>
            </div>
        </div>
    </div>

    {{-- ── Table ────────────────────────────────────────────────────── --}}
    <div class="rpt-card border rounded-xl overflow-hidden shadow-sm">
        @if($clients->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3 kpi-box-indigo">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="rpt-title font-semibold">Nenhum cliente em implantação</p>
                <p class="rpt-sub text-sm mt-1">Todos os clientes estão com atividades concluídas.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-left text-[13px] sm:text-[14px]">
                    <thead>
                        <tr class="rpt-thead border-b rpt-divider text-xs font-bold uppercase tracking-wider">
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3 hidden md:table-cell">Software</th>
                            <th class="px-5 py-3 hidden lg:table-cell text-center">UF</th>
                            <th class="px-5 py-3 text-center">Tickets Abertos</th>
                            <th class="px-5 py-3 text-center">Agendamentos</th>
                            <th class="px-5 py-3 text-right hidden xl:table-cell">Tempo Total</th>
                        </tr>
                    </thead>
                    <tbody class="rpt-tbody divide-y rpt-divider">
                        @foreach($clients as $client)
                            <tr class="rpt-row transition-colors">
                                {{-- Cliente: nome em destaque, CNPJ abaixo menor ─── --}}
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold rpt-title leading-tight">
                                        {{ $client->trade_name ?? $client->name }}
                                    </p>
                                    @if($client->trade_name && $client->name !== $client->trade_name)
                                        <p class="text-[11px] rpt-sub mt-0.5 truncate max-w-[220px]">{{ $client->name }}</p>
                                    @endif
                                    <p class="text-[11px] rpt-cnpj tracking-tight mt-0.5">{{ $client->cnpj }}</p>
                                </td>
                                <td class="px-5 py-3.5 hidden md:table-cell">
                                    @if($client->software)
                                        <span class="rpt-badge-soft border text-xs font-semibold px-2.5 py-1 rounded-full">
                                            {{ $client->software->name }}
                                        </span>
                                    @else
                                        <span class="rpt-sub text-sm">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 hidden lg:table-cell text-center">
                                    <span class="text-xs font-bold rpt-cell">{{ $client->state?->abbr ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if($client->open_tickets > 0)
                                        <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-amber-100 text-amber-700 text-xs font-bold rounded-full ring-1 ring-amber-200">
                                            {{ $client->open_tickets }}
                                        </span>
                                    @else
                                        <span class="rpt-sub text-xs">0</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if($client->active_schedules > 0)
                                        <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-violet-100 text-violet-700 text-xs font-bold rounded-full ring-1 ring-violet-200">
                                            {{ $client->active_schedules }}
                                        </span>
                                    @else
                                        <span class="rpt-sub text-xs">0</span>
                                    @endif
                                </td>
                                @php
                                    $mins = $client->total_implementation_minutes;
                                    $h    = intdiv($mins, 60);
                                    $m    = $mins % 60;
                                    $fmt  = $mins > 0 ? ($h > 0 ? "{$h}h " . sprintf('%02d', $m) . 'min' : "{$m}min") : '—';
                                @endphp
                                <td class="px-5 py-3.5 text-right hidden xl:table-cell">
                                    <span class="text-xs font-bold {{ $mins > 0 ? 'text-purple-600' : 'rpt-sub' }}">
                                        {{ $fmt }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="rpt-tfoot border-t-2 rpt-divider text-sm font-black">
                            <td class="px-5 py-3 rpt-cell" colspan="3">Total</td>
                            <td class="px-5 py-3 text-center text-amber-600">{{ $totalOpenTickets }}</td>
                            <td class="px-5 py-3 text-center text-violet-600">{{ $totalSchedules }}</td>
                            <td class="px-5 py-3 text-right text-purple-600 hidden xl:table-cell">{{ $totalImplementationFormatted }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- Print footer --}}
    <div class="hidden print:flex justify-between items-center text-[10px] rpt-sub font-bold uppercase tracking-tighter border-t pt-3 rpt-divider">
        <span>Amura — Clientes em Implantação</span>
        <span>Emitido em: {{ now()->format('d/m/Y H:i:s') }}</span>
    </div>
</div>

@endsection
