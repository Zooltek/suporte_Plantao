@extends('admin.layouts.master')

@section('page-title', 'Relatórios de Plantão & Sobreaviso')
@section('title', 'Relatórios de Plantão & Sobreaviso')

@push('styles')
<style>
    /* ─── Tokens de cor & Layout ─── */
    .rpt-card        { background-color: #ffffff; border-color: #e2e8f0; }
    .rpt-card-inner  { background-color: #f8fafc; border-color: #e2e8f0; }
    .rpt-thead       { background-color: #f1f5f9; color: #475569; }
    .rpt-tbody       { background-color: #ffffff; }
    .rpt-row:hover   { background-color: #f0f9ff; }
    .rpt-tfoot       { background-color: #f8fafc; border-color: #cbd5e1; }
    .rpt-title       { color: #0f172a; }
    .rpt-sub         { color: #64748b; }
    .rpt-cell        { color: #1e293b; }
    .rpt-divider     { border-color: #e2e8f0; }
    .rpt-input       { background-color: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
    .rpt-input:focus { outline: none; border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2); }
    .rpt-btn         { background-color: #f1f5f9; border-color: #cbd5e1; color: #334155; }
    .rpt-btn:hover   { background-color: #e2e8f0; color: #0f172a; }
    .rpt-tab-active  { background-color: #0d6efd; color: #ffffff !important; }
    .rpt-badge-soft  { background-color: #f1f5f9; color: #475569; border-color: #e2e8f0; }

    /* ─── Ocean / Dark Mode ─── */
    html.ocean .rpt-card        { background-color: #131f37; border-color: #1e293b; }
    html.ocean .rpt-card-inner  { background-color: #0b1329; border-color: #1e293b; }
    html.ocean .rpt-thead       { background-color: #0b1329; color: #94a3b8; }
    html.ocean .rpt-tbody       { background-color: #131f37; }
    html.ocean .rpt-row:hover   { background-color: #1e293b; }
    html.ocean .rpt-tfoot       { background-color: #0b1329; border-color: #1e293b; }
    html.ocean .rpt-title       { color: #f1f5f9; }
    html.ocean .rpt-sub         { color: #94a3b8; }
    html.ocean .rpt-cell        { color: #cbd5e1; }
    html.ocean .rpt-divider     { border-color: #1e293b; }
    html.ocean .rpt-input       { background-color: #0b1329; border-color: #334155; color: #e2e8f0; }
    html.ocean .rpt-btn         { background-color: #1e293b; border-color: #334155; color: #94a3b8; }
    html.ocean .rpt-btn:hover   { background-color: #334155; color: #f8fafc; }
    html.ocean .rpt-tab-active  { background-color: #0d6efd; color: #ffffff !important; }
    html.ocean .rpt-badge-soft  { background-color: #0b1329; color: #94a3b8; border-color: #1e293b; }

    /* ─── Impressão ─── */
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        html, body { background: #fff !important; }
        body > * > header, body > * > footer, body > * aside { display: none !important; }
        .no-print { display: none !important; }
        .rpt-card, .rpt-thead, .rpt-tbody, .rpt-tfoot, .rpt-row:hover {
            background-color: #fff !important; border-color: #cbd5e1 !important;
        }
        .rpt-title, .rpt-cell { color: #000 !important; }
        table { page-break-inside: auto; width: 100% !important; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-12" x-data="{ 
    tab: 'labor',
    hourlyRate: 30.00,
    editModal: false,
    editingAttendance: {
        id: null,
        started_at: '',
        ended_at: '',
        duration_minutes: 0,
        adjusted_duration_minutes: '',
        is_approved: true,
        admin_notes: '',
        trouble: '',
        solution: '',
        action_url: ''
    },
    openEditModal(att, actionUrl) {
        this.editingAttendance = {
            id: att.id,
            started_at: att.started_at ? att.started_at.substring(0, 16) : '',
            ended_at: att.ended_at ? att.ended_at.substring(0, 16) : '',
            duration_minutes: att.duration_minutes,
            adjusted_duration_minutes: att.adjusted_duration_minutes !== null ? att.adjusted_duration_minutes : '',
            is_approved: att.is_approved !== false && att.is_approved !== 0,
            admin_notes: att.admin_notes || '',
            trouble: att.trouble || '',
            solution: att.solution || '',
            action_url: actionUrl
        };
        this.editModal = true;
    },
    quickFilter(days) {
        let end = new Date();
        let start = new Date();
        if (days === 'month') {
            start = new Date(end.getFullYear(), end.getMonth(), 1);
        } else if (days === 'last_month') {
            start = new Date(end.getFullYear(), end.getMonth() - 1, 1);
            end = new Date(end.getFullYear(), end.getMonth(), 0);
        } else {
            start.setDate(end.getDate() - parseInt(days));
        }
        document.getElementById('input_start_date').value = start.toISOString().split('T')[0];
        document.getElementById('input_end_date').value = end.toISOString().split('T')[0];
        document.getElementById('filter_form').submit();
    }
}">

    {{-- Notificações de Sucesso --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-xs font-semibold flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <span class="text-sm">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">✕</button>
        </div>
    @endif

    {{-- ── CABEÇALHO & FILTRO ───────────────────────────────────────── --}}
    <div class="rpt-card border rounded-2xl p-5 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                        Plantão & Gestão Trabalhista
                    </span>
                    <span class="text-xs rpt-sub">SLA & Apuração Legal</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight rpt-title">Relatórios do Plantão</h1>
                <p class="text-xs sm:text-sm rpt-sub mt-0.5">
                    Filtro por período e agente, ranking de clientes, histórico de atendimentos e cálculo trabalhista mensal.
                </p>
            </div>

            {{-- Ações Rápidas (Exportar e Imprimir) --}}
            <div class="flex flex-wrap items-center gap-2 no-print">
                <a href="{{ route('admin.oncall.reports.export', ['start_date' => $startDate, 'end_date' => $endDate, 'agent_id' => $agentId]) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold rpt-btn border shadow-sm transition-all">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Exportar CSV
                </a>

                <button type="button"
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold rpt-btn border shadow-sm transition-all">
                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </button>
            </div>
        </div>

        {{-- Barra de Formulário de Filtro --}}
        <form id="filter_form" method="GET" action="{{ route('admin.oncall.reports') }}" class="mt-5 pt-4 border-t rpt-divider">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                
                {{-- Data Início --}}
                <div class="lg:col-span-3">
                    <label class="block text-xs font-semibold rpt-sub mb-1">Data Início</label>
                    <input type="date"
                           id="input_start_date"
                           name="start_date"
                           value="{{ $startDate }}"
                           class="w-full text-xs font-medium px-3 py-2 rounded-xl border rpt-input shadow-sm">
                </div>

                {{-- Data Fim --}}
                <div class="lg:col-span-3">
                    <label class="block text-xs font-semibold rpt-sub mb-1">Data Fim</label>
                    <input type="date"
                           id="input_end_date"
                           name="end_date"
                           value="{{ $endDate }}"
                           class="w-full text-xs font-medium px-3 py-2 rounded-xl border rpt-input shadow-sm">
                </div>

                {{-- Agente --}}
                <div class="lg:col-span-4">
                    <label class="block text-xs font-semibold rpt-sub mb-1">Plantonista / Agente</label>
                    <select name="agent_id" class="w-full text-xs font-medium px-3 py-2 rounded-xl border rpt-input shadow-sm">
                        <option value="">-- Todos os Agentes Plantonistas --</option>
                        @foreach($agents as $ag)
                            <option value="{{ $ag->id }}" {{ $agentId == $ag->id ? 'selected' : '' }}>
                                {{ $ag->name }} {{ $ag->is_oncall ? '★ (Plantonista)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Botões de Submissão --}}
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit"
                            class="flex-1 inline-flex justify-center items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Filtrar
                    </button>

                    <a href="{{ route('admin.oncall.reports') }}"
                       class="px-3 py-2 text-xs font-semibold rpt-btn border rounded-xl hover:text-red-600 shadow-sm"
                       title="Limpar filtros">
                        ✕
                    </a>
                </div>
            </div>

            {{-- Atalhos Rápidos de Data --}}
            <div class="flex flex-wrap items-center gap-1.5 mt-3 no-print">
                <span class="text-[11px] font-semibold rpt-sub mr-1">Atalhos:</span>
                <button type="button" @click="quickFilter('month')" class="px-2.5 py-1 text-[11px] font-medium rpt-btn border rounded-lg">Mês Atual</button>
                <button type="button" @click="quickFilter('last_month')" class="px-2.5 py-1 text-[11px] font-medium rpt-btn border rounded-lg">Mês Anterior</button>
                <button type="button" @click="quickFilter('15')" class="px-2.5 py-1 text-[11px] font-medium rpt-btn border rounded-lg">Últimos 15 Dias</button>
                <button type="button" @click="quickFilter('7')" class="px-2.5 py-1 text-[11px] font-medium rpt-btn border rounded-lg">Últimos 7 Dias</button>
            </div>
        </form>
    </div>

    {{-- ── CARDS DE RESUMO (KPIS) ───────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        {{-- Total Chamados --}}
        <div class="rpt-card border rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold rpt-sub uppercase tracking-wider">Total Chamados</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold rpt-title">{{ $totals['total_calls'] }}</div>
                <p class="text-[11px] rpt-sub mt-0.5">Atendimentos no plantão</p>
            </div>
        </div>

        {{-- Clientes Únicos --}}
        <div class="rpt-card border rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold rpt-sub uppercase tracking-wider">Clientes Únicos</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold rpt-title">{{ $totals['distinct_clients'] }}</div>
                <p class="text-[11px] rpt-sub mt-0.5">Empresas atendidas</p>
            </div>
        </div>

        {{-- Sobreaviso a Pagar (0,333) --}}
        <div class="rpt-card border rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold rpt-sub uppercase tracking-wider">Sobreaviso Líq.</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-amber-600">{{ number_format($totals['standby_hours_payable'], 2, ',', '.') }}h</div>
                <p class="text-[11px] rpt-sub mt-0.5">Equiv. CLT (Fator 0,333x)</p>
            </div>
        </div>

        {{-- Horas Extras Efetivas --}}
        <div class="rpt-card border rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold rpt-sub uppercase tracking-wider">Horas Extras</span>
                <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-purple-600">{{ number_format($totals['extra_hours_payable'], 2, ',', '.') }}h</div>
                <p class="text-[11px] rpt-sub mt-0.5">HE Fatorada (1.5 / 1.75 / 2.0)</p>
            </div>
        </div>

        {{-- Total Geral a Pagar --}}
        <div class="rpt-card border-2 border-blue-500/30 bg-gradient-to-br from-blue-50/50 to-indigo-50/50 dark:from-blue-950/20 dark:to-indigo-950/20 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">Total a Pagar</span>
                <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
                    ∑
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ number_format($totals['total_payable_hours'], 2, ',', '.') }}h</div>
                <p class="text-[11px] font-semibold text-blue-700 dark:text-blue-300 mt-0.5">Horas Equivalentes Totais</p>
            </div>
        </div>

    </div>

    {{-- ── NAVEGAÇÃO POR ABAS ───────────────────────────────────────── --}}
    <div class="rpt-card border rounded-2xl p-2 shadow-sm flex flex-wrap gap-1.5 no-print">
        <button type="button"
                @click="tab = 'labor'"
                :class="tab === 'labor' ? 'rpt-tab-active shadow-sm font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            1. Cálculo Trabalhista Mensal
        </button>

        <button type="button"
                @click="tab = 'by_agent'"
                :class="tab === 'by_agent' ? 'rpt-tab-active shadow-sm font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            2. Clientes Atendidos por Agente
        </button>

        <button type="button"
                @click="tab = 'top_clients'"
                :class="tab === 'top_clients' ? 'rpt-tab-active shadow-sm font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            3. Ranking de Clientes que Mais Utilizam
        </button>

        <button type="button"
                @click="tab = 'attendances'"
                :class="tab === 'attendances' ? 'rpt-tab-active shadow-sm font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            4. Todos os Atendimentos ({{ $attendances->count() }})
        </button>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- ABA 1: CÁLCULO TRABALHISTA MENSAL POR AGENTE                   --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'labor'" x-cloak class="space-y-4">
        
        {{-- Card de Regras e Simulador de Valor --}}
        <div class="rpt-card border rounded-2xl p-5 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold rpt-title flex items-center gap-2">
                        <span>⚖️</span> Regras de Apuração de Horas da Empresa
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2 text-xs">
                        <div class="p-2 rounded-xl rpt-card-inner border">
                            <span class="font-bold text-amber-600">Sobreaviso:</span> 0,333x (1/3 da hr normal)
                        </div>
                        <div class="p-2 rounded-xl rpt-card-inner border">
                            <span class="font-bold text-blue-600">HE Seg-Sex:</span> 1,50x (18:00 às 21:00)
                        </div>
                        <div class="p-2 rounded-xl rpt-card-inner border">
                            <span class="font-bold text-indigo-600">HE Sábado:</span> 1,75x (09:00 às 21:00)
                        </div>
                        <div class="p-2 rounded-xl rpt-card-inner border">
                            <span class="font-bold text-rose-600">HE Dom/Fer:</span> 2,00x (Conforme Escala)
                        </div>
                    </div>
                </div>

                {{-- Simulador Interativo de Custo --}}
                <div class="p-3 rounded-xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 flex items-center gap-3 no-print">
                    <div>
                        <label class="block text-[11px] font-bold text-blue-900 dark:text-blue-300">Simulador de Valor/Hora (R$):</label>
                        <div class="relative mt-1">
                            <span class="absolute left-2.5 top-1.5 text-xs text-gray-500 font-bold">R$</span>
                            <input type="number"
                                   step="0.50"
                                   min="1"
                                   x-model="hourlyRate"
                                   class="w-28 text-xs font-bold pl-8 pr-2 py-1.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 shadow-sm">
                        </div>
                    </div>
                    <div class="border-l border-blue-200 dark:border-blue-800 pl-3">
                        <span class="block text-[10px] uppercase font-bold text-blue-700 dark:text-blue-400 tracking-wider">Custo Total Folha</span>
                        <span class="text-lg font-black text-blue-700 dark:text-blue-300"
                              x-text="'R$ ' + ({{ $totals['total_payable_hours'] }} * hourlyRate).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabela de Apuração por Agente --}}
        <div class="rpt-card border rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="rpt-thead border-b rpt-divider text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-3 px-4">Agente Plantonista</th>
                            <th class="py-3 px-3 text-center">Atendimentos</th>
                            <th class="py-3 px-3 text-right">Escala Bruta</th>
                            <th class="py-3 px-3 text-right text-blue-600">HE Seg-Sex (1.5x)</th>
                            <th class="py-3 px-3 text-right text-indigo-600">HE Sábado (1.75x)</th>
                            <th class="py-3 px-3 text-right text-rose-600">HE Dom/Fer (2.0x)</th>
                            <th class="py-3 px-3 text-right">Total Trab.</th>
                            <th class="py-3 px-3 text-right text-amber-600">Sobreaviso Líq. (0.333x)</th>
                            <th class="py-3 px-4 text-right bg-blue-50/50 dark:bg-blue-900/20 font-black text-blue-700 dark:text-blue-400">Total Horas a Pagar</th>
                            <th class="py-3 px-4 text-right bg-blue-50/80 dark:bg-blue-900/40 font-black text-blue-700 dark:text-blue-300 no-print">Valor Estimado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y rpt-divider rpt-tbody">
                        @forelse($laborReportsByAgent as $row)
                            <tr class="rpt-row transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold rpt-cell text-sm">{{ $row['agent']->name }}</div>
                                    <div class="text-[11px] rpt-sub">{{ $row['agent']->email }}</div>
                                </td>
                                <td class="py-3 px-3 text-center font-semibold rpt-cell">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                        {{ $row['attendances_count'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right font-medium rpt-sub">
                                    {{ $row['gross_standby_formatted'] }}
                                </td>
                                <td class="py-3 px-3 text-right font-medium text-blue-600 dark:text-blue-400">
                                    <div>{{ $row['weekday_worked_formatted'] }}</div>
                                    <div class="text-[10px] font-semibold text-blue-500">= {{ number_format($row['eq_weekday_hours'], 2, ',', '.') }}h</div>
                                </td>
                                <td class="py-3 px-3 text-right font-medium text-indigo-600 dark:text-indigo-400">
                                    <div>{{ $row['saturday_worked_formatted'] }}</div>
                                    <div class="text-[10px] font-semibold text-indigo-500">= {{ number_format($row['eq_saturday_hours'], 2, ',', '.') }}h</div>
                                </td>
                                <td class="py-3 px-3 text-right font-medium text-rose-600 dark:text-rose-400">
                                    <div>{{ $row['sunday_worked_formatted'] }}</div>
                                    <div class="text-[10px] font-semibold text-rose-500">= {{ number_format($row['eq_sunday_hours'], 2, ',', '.') }}h</div>
                                </td>
                                <td class="py-3 px-3 text-right font-semibold rpt-cell">
                                    {{ $row['worked_formatted'] }}
                                </td>
                                <td class="py-3 px-3 text-right font-medium text-amber-600 dark:text-amber-400">
                                    <div>{{ $row['liquid_standby_formatted'] }}</div>
                                    <div class="text-[10px] font-semibold text-amber-500">= {{ number_format($row['eq_standby_hours'], 2, ',', '.') }}h</div>
                                </td>
                                <td class="py-3 px-4 text-right bg-blue-50/50 dark:bg-blue-900/20 font-black text-sm text-blue-700 dark:text-blue-300">
                                    {{ number_format($row['total_payable_hours'], 2, ',', '.') }} h
                                </td>
                                <td class="py-3 px-4 text-right bg-blue-50/80 dark:bg-blue-900/40 font-black text-sm text-emerald-600 dark:text-emerald-400 no-print"
                                    x-text="'R$ ' + ({{ $row['total_payable_hours'] }} * hourlyRate).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center rpt-sub text-xs">
                                    Nenhum plantão ou atendimento registrado no período selecionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($laborReportsByAgent) > 0)
                    <tfoot>
                        <tr class="rpt-tfoot border-t-2 font-black text-xs">
                            <td class="py-3 px-4 rpt-title">TOTAIS CONSOLIDADOS</td>
                            <td class="py-3 px-3 text-center rpt-title">{{ $totals['total_calls'] }}</td>
                            <td class="py-3 px-3 text-right rpt-sub">-</td>
                            <td class="py-3 px-3 text-right text-blue-600 font-bold">
                                {{ number_format(array_sum(array_column($laborReportsByAgent, 'eq_weekday_hours')), 2, ',', '.') }}h
                            </td>
                            <td class="py-3 px-3 text-right text-indigo-600 font-bold">
                                {{ number_format(array_sum(array_column($laborReportsByAgent, 'eq_saturday_hours')), 2, ',', '.') }}h
                            </td>
                            <td class="py-3 px-3 text-right text-rose-600 font-bold">
                                {{ number_format(array_sum(array_column($laborReportsByAgent, 'eq_sunday_hours')), 2, ',', '.') }}h
                            </td>
                            <td class="py-3 px-3 text-right rpt-cell">
                                @php
                                    $totWorkedMins = array_sum(array_column($laborReportsByAgent, 'worked_minutes'));
                                @endphp
                                {{ sprintf('%02dh %02dmin', floor($totWorkedMins / 60), $totWorkedMins % 60) }}
                            </td>
                            <td class="py-3 px-3 text-right text-amber-600 font-bold">
                                {{ number_format($totals['standby_hours_payable'], 2, ',', '.') }}h
                            </td>
                            <td class="py-3 px-4 text-right text-base text-blue-700 dark:text-blue-300 font-black bg-blue-100/60 dark:bg-blue-900/40">
                                {{ number_format($totals['total_payable_hours'], 2, ',', '.') }} h
                            </td>
                            <td class="py-3 px-4 text-right text-base text-emerald-600 dark:text-emerald-400 font-black bg-blue-100/80 dark:bg-blue-900/60 no-print"
                                x-text="'R$ ' + ({{ $totals['total_payable_hours'] }} * hourlyRate).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- ABA 2: CLIENTES ATENDIDOS POR AGENTE                           --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'by_agent'" x-cloak class="space-y-6">
        @forelse($clientsByAgent as $agentGroup)
            <div class="rpt-card border rounded-2xl p-5 shadow-sm space-y-4">
                {{-- Topo do Agente --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b rpt-divider gap-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-sm">
                            {{ strtoupper(substr($agentGroup['agent']->name, 0, 2)) }}
                        </div>
                        <div>
                            <h2 class="text-base font-bold rpt-title">{{ $agentGroup['agent']->name }}</h2>
                            <p class="text-xs rpt-sub">{{ $agentGroup['agent']->email }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <span class="block text-[11px] rpt-sub">Total de Clientes</span>
                            <span class="text-sm font-bold rpt-cell">{{ count($agentGroup['clients']) }} empresas</span>
                        </div>
                        <div class="text-right border-l rpt-divider pl-3">
                            <span class="block text-[11px] rpt-sub">Chamados Realizados</span>
                            <span class="text-sm font-bold text-blue-600">{{ $agentGroup['total_calls'] }} atendimentos</span>
                        </div>
                        <div class="text-right border-l rpt-divider pl-3">
                            <span class="block text-[11px] rpt-sub">Tempo Total em Atendimento</span>
                            <span class="text-sm font-bold text-indigo-600">
                                {{ sprintf('%02dh %02dmin', floor($agentGroup['total_minutes'] / 60), $agentGroup['total_minutes'] % 60) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Tabela de Clientes do Agente --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="rpt-thead border-b rpt-divider text-[11px] uppercase tracking-wider font-bold">
                                <th class="py-2.5 px-3">Código</th>
                                <th class="py-2.5 px-4">Cliente</th>
                                <th class="py-2.5 px-3 text-center">Atendimentos</th>
                                <th class="py-2.5 px-3 text-right">Tempo Total</th>
                                <th class="py-2.5 px-4 text-right">Último Atendimento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y rpt-divider rpt-tbody">
                            @foreach($agentGroup['clients'] as $c)
                                <tr class="rpt-row transition-colors">
                                    <td class="py-2.5 px-3 font-mono text-[11px] text-gray-500 font-bold">
                                        {{ $c['code'] ?: '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 font-bold rpt-cell text-sm">
                                        {{ $c['name'] }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                            {{ $c['calls_count'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ $c['total_formatted'] }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right rpt-sub">
                                        {{ $c['last_call'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rpt-card border rounded-2xl p-8 text-center rpt-sub text-xs">
                Nenhum atendimento registrado por agente no período.
            </div>
        @endforelse
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- ABA 3: RANKING DE CLIENTES QUE MAIS UTILIZAM O PLANTÃO        --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'top_clients'" x-cloak class="space-y-4">
        <div class="rpt-card border rounded-2xl p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b rpt-divider gap-2">
                <div>
                    <h2 class="text-base font-bold rpt-title flex items-center gap-2">
                        <span>🏆</span> Clientes que Mais Utilizam o Suporte de Plantão
                    </h2>
                    <p class="text-xs rpt-sub mt-0.5">
                        Identifique as empresas mais demandantes fora do expediente comercial para gestão de contratos e SLA.
                    </p>
                </div>
                <div class="text-xs font-semibold rpt-sub">
                    Total: <strong class="rpt-cell">{{ count($topClients) }}</strong> empresas atendidas
                </div>
            </div>

            <div class="overflow-x-auto mt-3">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="rpt-thead border-b rpt-divider text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-3 px-3 text-center w-12">Pos.</th>
                            <th class="py-3 px-3 w-20">Código</th>
                            <th class="py-3 px-4">Nome do Cliente</th>
                            <th class="py-3 px-3 text-center">Chamados</th>
                            <th class="py-3 px-4 w-48">% Participação</th>
                            <th class="py-3 px-3 text-right">Tempo Total Demandado</th>
                            <th class="py-3 px-3 text-center">Resolvidos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y rpt-divider rpt-tbody">
                        @forelse($topClients as $idx => $client)
                            <tr class="rpt-row transition-colors">
                                <td class="py-3 px-3 text-center font-black text-xs">
                                    @if($idx === 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-400 text-white shadow-sm">1</span>
                                    @elseif($idx === 1)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-300 text-slate-800 shadow-sm">2</span>
                                    @elseif($idx === 2)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-600 text-white shadow-sm">3</span>
                                    @else
                                        <span class="text-gray-500">#{{ $idx + 1 }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 font-mono text-[11px] text-gray-500 font-bold">
                                    {{ $client['code'] ?: '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold rpt-cell text-sm">{{ $client['name'] }}</div>
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-sm text-blue-600">
                                    {{ $client['calls_count'] }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                                            <div class="bg-blue-600 h-full rounded-full" style="width: {{ min(100, $client['percentage']) }}%"></div>
                                        </div>
                                        <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400 w-10 text-right">{{ $client['percentage'] }}%</span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-right font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $client['total_formatted'] }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600">
                                        ✓ {{ $client['resolved_count'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center rpt-sub text-xs">
                                    Nenhum dado de cliente para exibir no período selecionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- ABA 4: TODOS OS ATENDIMENTOS REGISTRADOS NO PERÍODO            --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'attendances'" x-cloak class="space-y-4">
        <div class="rpt-card border rounded-2xl p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b rpt-divider gap-2">
                <div>
                    <h2 class="text-base font-bold rpt-title flex items-center gap-2">
                        <span>📋</span> Extrato Analítico dos Atendimentos de Plantão
                    </h2>
                    <p class="text-xs rpt-sub mt-0.5">
                        Lista detalhada de cada chamado realizado pelos agentes no aplicativo móvel com sincronização.
                    </p>
                </div>
                <div class="text-xs font-semibold rpt-sub">
                    Exibindo <strong class="rpt-cell">{{ $attendances->count() }}</strong> atendimentos
                </div>
            </div>

            <div class="overflow-x-auto mt-3">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="rpt-thead border-b rpt-divider text-[11px] uppercase tracking-wider font-bold">
                            <th class="py-3 px-3">Ticket</th>
                            <th class="py-3 px-3">Data / Horário</th>
                            <th class="py-3 px-3 text-center">Duração Efetiva</th>
                            <th class="py-3 px-3">Plantonista</th>
                            <th class="py-3 px-4">Cliente / Contato</th>
                            <th class="py-3 px-4">Problema, Solução & Auditoria</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3 text-center no-print w-28">Ações (Gestão)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y rpt-divider rpt-tbody">
                        @forelse($attendances as $att)
                            @php
                                $start = \Carbon\Carbon::parse($att->started_at);
                                $end = \Carbon\Carbon::parse($att->ended_at);
                                $days = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                                $clientDisplayName = $att->customer
                                    ? ($att->customer->trade_name ?: $att->customer->name)
                                    : ($att->customer_name_fallback ?: 'Avulso');
                            @endphp
                            <tr class="rpt-row transition-colors {{ !$att->is_approved ? 'opacity-60 bg-rose-50/30 dark:bg-rose-950/10' : '' }}">
                                <td class="py-3 px-3 font-mono font-bold">
                                    @if($att->ticket_id)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            #{{ $att->ticket_id }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            Pendente
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-bold rpt-cell">{{ $start->format('d/m/Y') }} ({{ $days[$start->dayOfWeek] }})</div>
                                    <div class="text-[11px] rpt-sub">{{ $start->format('H:i') }} às {{ $end->format('H:i') }}</div>
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @if(! $att->is_approved)
                                        <span class="inline-block px-2 py-0.5 rounded-lg text-xs font-black bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-200" title="Horas reprovadas/glosadas pela diretoria">
                                            🚫 Glosado (0m)
                                        </span>
                                        <div class="text-[10px] line-through text-gray-400">Orig: {{ $att->duration_minutes }} min</div>
                                    @elseif($att->adjusted_duration_minutes !== null)
                                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200" title="Duração ajustada manualmente pelo gestor">
                                            ⚠️ {{ $att->adjusted_duration_minutes }} min
                                        </span>
                                        <div class="text-[10px] text-gray-400">Orig: {{ $att->duration_minutes }} min</div>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-extrabold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800">
                                            {{ $att->duration_minutes }} min
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 font-semibold rpt-cell">
                                    {{ $att->agent?->name ?: 'N/A' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold rpt-cell text-sm">{{ $clientDisplayName }}</div>
                                    @if($att->contact_name)
                                        <div class="text-[11px] rpt-sub">Contato: {{ $att->contact_name }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 max-w-sm">
                                    <div class="font-medium rpt-cell line-clamp-2" title="{{ $att->trouble }}">
                                        <strong>P:</strong> {{ $att->trouble }}
                                    </div>
                                    @if($att->solution)
                                        <div class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-1 line-clamp-2" title="{{ $att->solution }}">
                                            <strong>S:</strong> {{ $att->solution }}
                                        </div>
                                    @endif
                                    @if($att->admin_notes)
                                        <div class="text-[10px] font-medium text-purple-700 dark:text-purple-300 mt-1 p-1 rounded bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800">
                                            <strong>⚖️ Justificativa Gestor:</strong> {{ $att->admin_notes }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @if($att->is_resolved)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            ✓ Resolvido
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            Pendente
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap no-print">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- Botão Editar / Ajustar --}}
                                        <button type="button"
                                                @click='openEditModal(@json($att), "{{ route('admin.oncall.attendances.update', $att->id) }}")'
                                                class="px-2 py-1 text-[11px] font-bold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 transition-colors"
                                                title="Ajustar horas, glosar ou editar">
                                            ✏️ Ajustar
                                        </button>

                                        {{-- Botão Excluir --}}
                                        <form method="POST"
                                              action="{{ route('admin.oncall.attendances.destroy', $att->id) }}"
                                              onsubmit="return confirm('Tem certeza que deseja excluir permanentemente este atendimento de plantão? As horas serão recalculadas imediatamente.');"
                                              class="inline-block m-0 p-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2 py-1 text-[11px] font-bold rounded-lg bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 border border-red-200 transition-colors"
                                                    title="Excluir lançamento">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center rpt-sub text-xs">
                                    Nenhum atendimento registrado no período selecionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL DE EDIÇÃO, AJUSTE E GLOSA DE HORAS PELO GESTOR            --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div x-show="editModal"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div class="rpt-card border rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-4 relative"
             @click.outside="editModal = false">
            
            {{-- Cabeçalho do Modal --}}
            <div class="flex items-center justify-between border-b rpt-divider pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">⚖️</span>
                    <div>
                        <h3 class="text-base font-bold rpt-title">Ajuste de Atendimento & Horas Trabalhistas</h3>
                        <p class="text-xs rpt-sub">Manutenção pelo gestor para correção de falhas, divergências ou glosa de horas.</p>
                    </div>
                </div>
                <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg">✕</button>
            </div>

            {{-- Formulário de Ajuste --}}
            <form :action="editingAttendance.action_url" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Status de Aprovação / Glosa --}}
                <div class="p-3.5 rounded-xl border rpt-card-inner">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_approved"
                               value="1"
                               x-model="editingAttendance.is_approved"
                               class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                        <div>
                            <span class="text-xs font-bold rpt-title">Aprovar Horas Deste Atendimento para Pagamento</span>
                            <p class="text-[11px] rpt-sub">
                                Se desmarcado (glosa), o atendimento permanece no extrato mas computa <strong>0 horas extras</strong> na folha.
                            </p>
                        </div>
                    </label>
                </div>

                {{-- Horários de Início e Fim --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold rpt-sub mb-1">Hora de Início:</label>
                        <input type="datetime-local"
                               name="started_at"
                               x-model="editingAttendance.started_at"
                               required
                               class="w-full text-xs font-medium px-3 py-2 rounded-xl border rpt-input shadow-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold rpt-sub mb-1">Hora de Fim:</label>
                        <input type="datetime-local"
                               name="ended_at"
                               x-model="editingAttendance.ended_at"
                               required
                               class="w-full text-xs font-medium px-3 py-2 rounded-xl border rpt-input shadow-sm">
                    </div>
                </div>

                {{-- Duração Original vs Duração Ajustada --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold rpt-sub mb-1">Duração Lançada (minutos):</label>
                        <input type="number"
                               name="duration_minutes"
                               min="0"
                               x-model="editingAttendance.duration_minutes"
                               required
                               class="w-full text-xs font-medium px-3 py-2 rounded-xl border rpt-input shadow-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-amber-600 mb-1">Duração Ajustada/Autorizada (minutos):</label>
                        <input type="number"
                               name="adjusted_duration_minutes"
                               min="0"
                               placeholder="Deixe vazio para manter lançada"
                               x-model="editingAttendance.adjusted_duration_minutes"
                               class="w-full text-xs font-bold px-3 py-2 rounded-xl border border-amber-300 dark:border-amber-700 rpt-input shadow-sm">
                        <p class="text-[10px] rpt-sub mt-1">Preencha se o gestor acordou uma duração menor que a lançada.</p>
                    </div>
                </div>

                {{-- Observações do Gestor / Justificativa da Glosa --}}
                <div>
                    <label class="block text-xs font-semibold rpt-sub mb-1">Justificativa do Gestor / Diretoria:</label>
                    <textarea name="admin_notes"
                              rows="2"
                              x-model="editingAttendance.admin_notes"
                              placeholder="Ex: Tempo corrigido de comum acordo devido a intervalo / Atendimento glosado por não conformidade com a política..."
                              class="w-full text-xs font-medium px-3 py-2 rounded-xl border rpt-input shadow-sm"></textarea>
                </div>

                {{-- Problema e Solução --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold rpt-sub mb-1">Problema Relatado:</label>
                        <textarea name="trouble"
                                  rows="2"
                                  x-model="editingAttendance.trouble"
                                  class="w-full text-xs font-medium px-3 py-2 rounded-xl border rpt-input shadow-sm"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold rpt-sub mb-1">Solução Aplicada:</label>
                        <textarea name="solution"
                                  rows="2"
                                  x-model="editingAttendance.solution"
                                  class="w-full text-xs font-medium px-3 py-2 rounded-xl border rpt-input shadow-sm"></textarea>
                    </div>
                </div>

                {{-- Botões de Ação do Modal --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t rpt-divider">
                    <button type="button"
                            @click="editModal = false"
                            class="px-4 py-2 text-xs font-semibold rpt-btn border rounded-xl">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md transition-colors">
                        Salvar Ajuste & Recalcular
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
