@extends('admin.layouts.master')

@section('page-title', 'Resumo Diário por Problema')
@section('title', 'Resumo Diário por Problema')

@push('styles')
<style>
    @include('admin.reports.partials.period-filter-styles')

    /* ─── Tokens de cor ─── */
    .rpt-card    { background-color: #ffffff; border-color: #e2e8f0; }
    .rpt-thead   { background-color: #f1f5f9; color: #475569; }
    .rpt-tbody   { background-color: #ffffff; }
    .rpt-row:hover { background-color: #f0f9ff; }
    .rpt-tfoot   { background-color: #f8fafc; border-color: #cbd5e1; }
    .rpt-title   { color: #0f172a; }
    .rpt-sub     { color: #64748b; }
    .rpt-cell    { color: #1e293b; }
    .rpt-divider { border-color: #e2e8f0; }
    .rpt-input   { background-color: #f8fafc; border-color: #e2e8f0; color: #334155; }
    .rpt-input:focus { outline: none; box-shadow: 0 0 0 2px #0ea5e9; }
    .rpt-btn     { background-color: #f1f5f9; border-color: #e2e8f0; color: #334155; }
    .rpt-btn:hover { background-color: #e2e8f0; }
    .rpt-badge-soft { background-color: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
    .rpt-total-badge { background-color: #f1f5f9; color: #1e293b; border-color: #e2e8f0; }

    /* ─── Ocean / Dark ─── */
    html.ocean .rpt-card    { background-color: #1e293b; border-color: #334155; }
    html.ocean .rpt-thead   { background-color: #0f172a; color: #94a3b8; }
    html.ocean .rpt-tbody   { background-color: #1e293b; }
    html.ocean .rpt-row:hover { background-color: #0f172a; }
    html.ocean .rpt-tfoot   { background-color: #0f172a; border-color: #334155; }
    html.ocean .rpt-title   { color: #f1f5f9; }
    html.ocean .rpt-sub     { color: #94a3b8; }
    html.ocean .rpt-cell    { color: #cbd5e1; }
    html.ocean .rpt-divider { border-color: #334155; }
    html.ocean .rpt-input   { background-color: #0f172a; border-color: #334155; color: #e2e8f0; }
    html.ocean .rpt-btn     { background-color: #0f172a; border-color: #334155; color: #94a3b8; }
    html.ocean .rpt-btn:hover { background-color: #1e293b; }
    html.ocean .rpt-badge-soft { background-color: #0f172a; color: #94a3b8; border-color: #334155; }
    html.ocean .rpt-total-badge { background-color: #0f172a; color: #e2e8f0; border-color: #334155; }

    /* ─── Ícones SVG: força stroke/fill explícitos no Ocean ─── */
    html.ocean .rpt-btn svg,
    html.ocean .rpt-btn svg path {
        stroke: #94a3b8 !important;
        fill: none !important;
    }
    html.ocean .btn-print svg,
    html.ocean .btn-print svg path {
        stroke: #ffffff !important;
        fill: none !important;
    }

    /* ─── Impressão ─── */
    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        html, body { background: #fff !important; }
        body > * > header, body > * > footer, body > * aside { display: none !important; }
        .no-print { display: none !important; }
        .rpt-card, .rpt-thead, .rpt-tbody, .rpt-tfoot, .rpt-row:hover {
            background-color: #fff !important; border-color: #e2e8f0 !important;
        }
        .rpt-title, .rpt-cell { color: #000 !important; }
        table { page-break-inside: auto; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    }
</style>
@endpush

@section('content')

@php
    $totalPendings     = collect($data)->sum('pendings');
    $totalPendingsDate = collect($data)->sum('pendings_date');
    $totalCompleted    = collect($data)->sum('completed');
    $totalGeral        = collect($data)->sum('total');
@endphp

<div class="max-w-6xl mx-auto space-y-5">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="rpt-card border rounded-2xl px-5 py-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <span class="inline-block text-[10px] font-bold uppercase tracking-[.15em] rpt-sub mb-1">Relatório Operacional</span>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight rpt-title">Resumo Diário por Problema</h1>
                <p class="text-sm rpt-sub mt-0.5">
                    Chamados estratificados por categoria de problema · Período: <strong class="rpt-cell">{{ $displayPeriod }}</strong>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                @include('admin.reports.partials.period-filter', [
                    'filterRouteName' => $periodFilterActionRoute,
                ])

                <span class="rpt-badge-soft border text-xs font-semibold px-3 py-1.5 rounded-lg"
                      title="Quantidade de tipos de problema (subcategorias) com chamados no período">
                    {{ count($data) }} tipo(s) de problema
                </span>

                <button onclick="window.print()" type="button"
                        class="btn-print inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sky-700 active:scale-95">
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

    {{-- ── KPI Cards ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 no-print">
        <div class="rpt-card border rounded-xl p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest rpt-sub">Pendentes Acumulados</p>
            <p class="text-2xl font-black text-amber-500 mt-1">{{ $totalPendings }}</p>
        </div>
        <div class="rpt-card border rounded-xl p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest rpt-sub">Abertos Hoje</p>
            <p class="text-2xl font-black text-sky-500 mt-1">{{ $totalPendingsDate }}</p>
        </div>
        <div class="rpt-card border rounded-xl p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest rpt-sub">Finalizados Hoje</p>
            <p class="text-2xl font-black text-emerald-500 mt-1">{{ $totalCompleted }}</p>
        </div>
        <div class="rpt-card border rounded-xl p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest rpt-sub">Total Acumulado</p>
            <p class="text-2xl font-black rpt-title mt-1">{{ $totalGeral }}</p>
        </div>
    </div>

    {{-- ── Table ──────────────────────────────────────────────── --}}
    <div class="rpt-card border rounded-xl overflow-hidden shadow-sm">
        @if(empty($data))
            <div class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 rpt-sub mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-base font-semibold rpt-title">Nenhum dado encontrado para esta data.</p>
                <p class="text-sm rpt-sub mt-1">Tente ajustar o período no filtro acima.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-left text-[13px] sm:text-[14px]">
                    <thead>
                        <tr class="rpt-thead border-b rpt-divider text-xs font-bold uppercase tracking-wider">
                            <th class="px-5 py-3">Problema / Subcategoria</th>
                            <th class="w-32 px-5 py-3 text-center">Pendentes</th>
                            <th class="w-32 px-5 py-3 text-center">Abertos Hoje</th>
                            <th class="w-32 px-5 py-3 text-center">Finalizados</th>
                            <th class="w-28 px-5 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="rpt-tbody divide-y rpt-divider">
                        @foreach($data as $row)
                            <tr class="rpt-row transition-colors">
                                <td class="px-5 py-3 font-semibold rpt-title">{{ $row['name'] }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if($row['pendings'] > 0)
                                        <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                                            {{ $row['pendings'] }}
                                        </span>
                                    @else
                                        <span class="rpt-sub text-xs">0</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center text-sky-500 text-sm font-semibold">{{ $row['pendings_date'] }}</td>
                                <td class="px-5 py-3 text-center text-emerald-500 font-extrabold text-sm">{{ $row['completed'] }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span class="rpt-total-badge inline-flex items-center justify-center px-3 py-1 rounded-lg text-sm font-black border">
                                        {{ $row['total'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="rpt-tfoot border-t-2 rpt-divider text-sm font-black">
                            <td class="px-5 py-3 rpt-cell">Totais Consolidados</td>
                            <td class="px-5 py-3 text-center text-amber-500">{{ $totalPendings }}</td>
                            <td class="px-5 py-3 text-center text-sky-500">{{ $totalPendingsDate }}</td>
                            <td class="px-5 py-3 text-center text-emerald-500">{{ $totalCompleted }}</td>
                            <td class="px-5 py-3 text-right rpt-title">{{ $totalGeral }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- Print footer --}}
    <div class="hidden print:flex justify-between items-center text-[10px] rpt-sub font-bold uppercase tracking-tighter border-t pt-3 rpt-divider">
        <span>Amura — Resumo Diário por Problema</span>
        <span>Emitido em: {{ now()->format('d/m/Y H:i:s') }}</span>
    </div>

</div>

@endsection
