@extends('admin.layouts.master')

@section('page-title', 'Clientes sem Atendimento')
@section('title', 'Clientes sem Atendimento')

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
    .rpt-badge-soft { background-color: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

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
    html.ocean .rpt-badge-soft { background-color: #0f172a; color: #94a3b8; border-color: #334155; }

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

<div class="max-w-6xl mx-auto space-y-5">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="rpt-card border rounded-2xl px-5 py-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <span class="inline-block text-[10px] font-bold uppercase tracking-[.15em] rpt-sub mb-1">Relatório Operacional</span>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight rpt-title">Clientes sem Atendimento</h1>
                <p class="text-sm rpt-sub mt-0.5">
                    Clientes ativos sem qualquer chamado no período · Período: <strong class="rpt-cell">{{ $displayPeriod }}</strong>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 no-print">
                @include('admin.reports.partials.period-filter', [
                    'filterRouteName' => $periodFilterActionRoute,
                ])

                <span class="rpt-badge-soft border text-xs font-semibold px-3 py-1.5 rounded-lg"
                      title="Quantidade total de clientes listados sem atendimento no período">
                    {{ count($clients) }} cliente(s)
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

    {{-- ── Table ──────────────────────────────────────────────── --}}
    <div class="rpt-card border rounded-xl overflow-hidden shadow-sm">
        @if($clients->isEmpty())
            <div class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 rpt-sub mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-base font-semibold rpt-title">Todos os clientes ativos tiveram atendimento no período.</p>
                <p class="text-sm rpt-sub mt-1">Selecione outro intervalo ou filtro.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-left text-[13px] sm:text-[14px]">
                    <thead>
                        <tr class="rpt-thead border-b rpt-divider text-xs font-bold uppercase tracking-wider">
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">CNPJ</th>
                            <th class="px-5 py-3">Contato</th>
                            <th class="px-5 py-3">Telefone</th>
                        </tr>
                    </thead>
                    <tbody class="rpt-tbody divide-y rpt-divider">
                        @foreach($clients as $client)
                            @php
                                $cnpjDigits = preg_replace('/\D/', '', $client->cnpj ?? '');
                                $cnpjFormatted = strlen($cnpjDigits) === 14 
                                    ? preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpjDigits)
                                    : ($client->cnpj ?? '—');
                            @endphp
                            <tr class="rpt-row transition-colors">
                                <td class="px-5 py-3 font-semibold rpt-title">
                                    {{ $client->name }}
                                    @if($client->trade_name && $client->trade_name !== $client->name)
                                        <span class="block text-xs font-normal rpt-sub mt-0.5">({{ $client->trade_name }})</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-mono rpt-cell">{{ $cnpjFormatted }}</td>
                                <td class="px-5 py-3 rpt-cell">
                                    <div class="font-medium">{{ $client->contact_name ?? '—' }}</div>
                                    @if($client->contact_email)
                                        <span class="block text-xs rpt-sub mt-0.5">{{ $client->contact_email }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 rpt-cell">
                                    <span>{{ $client->phone ?? '—' }}</span>
                                    @if($client->telephone_2)
                                        <span class="block text-xs rpt-sub mt-0.5">{{ $client->telephone_2 }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Print footer --}}
    <div class="hidden print:flex justify-between items-center text-[10px] rpt-sub font-bold uppercase tracking-tighter border-t pt-3 rpt-divider">
        <span>Amura — Clientes sem Atendimento</span>
        <span>Emitido em: {{ now()->format('d/m/Y H:i:s') }}</span>
    </div>

</div>

@endsection
