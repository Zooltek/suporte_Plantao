@extends('admin.layouts.master')

@section('title', 'Helpdesk — Painel Administrativo')

@section('content')
{{--
    Dashboard Admin do Helpdesk
    Design: Tailwind CSS + Alpine.js | Mobile-first | Sem Bootstrap
--}}
<div class="space-y-6">

    {{-- ══ BREADCRUMB ══════════════════════════════════════════════════════ --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium" aria-label="Breadcrumb">
        <span class="text-gray-600 font-semibold">Helpdesk</span>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-400">Dashboard</span>
    </nav>

    {{-- ══ HERO HEADER ═════════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden bg-white rounded-2xl p-8 border border-gray-100 shadow-xl shadow-gray-200/50">
        {{-- Blobs decorativos com cores do Helpdesk (indigo/violet) --}}
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-gradient-to-br from-indigo-400/30 to-violet-400/30 blur-3xl rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-gradient-to-tr from-violet-400/20 to-indigo-400/20 blur-3xl rounded-full pointer-events-none"></div>

        <div class="relative z-10">
            {{-- Badge Helpdesk Ativo --}}
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 text-sm font-medium mb-4 border border-indigo-100">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                Helpdesk Ativo
            </div>

            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight mb-2">
                Olá, {{ explode(' ', auth('admin')->user()->name)[0] }}! 👋
            </h1>
            <p class="text-lg text-gray-600 max-w-xl">
                Bem-vindo ao <span class="font-semibold text-indigo-600">Painel Helpdesk</span>. Monitore os chamados e gerencie as configurações do suporte.
            </p>
        </div>
    </div>

    {{-- ══ MÉTRICAS TOTALIZADORAS ═══════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $cards = [
                ['label' => 'Total de Chamados', 'value' => $total,  'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'bg' => 'bg-indigo-50', 'icon_color' => 'text-indigo-500'],
                ['label' => 'Em Aberto',         'value' => $open,   'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'bg' => 'bg-amber-50', 'icon_color' => 'text-amber-500'],
                ['label' => 'Encerrados',         'value' => $closed, 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'bg' => 'bg-emerald-50', 'icon_color' => 'text-emerald-500'],
                ['label' => 'Abertos Hoje',      'value' => $today,  'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'bg' => 'bg-blue-50', 'icon_color' => 'text-blue-500'],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl {{ $card['bg'] }} flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 {{ $card['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $card['label'] }}</p>
                    <p class="text-2xl font-black text-gray-900">{{ number_format($card['value']) }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ══ GRID INFERIOR ════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Chamados por Status --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-black text-gray-700 uppercase tracking-widest">Por Status</h2>
                <a href="{{ route('admin.helpdesk.status.index') }}"
                   class="text-xs font-bold text-indigo-600 hover:underline">Gerenciar →</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($byStatus as $s)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $s['color'] ?? '#6366f1' }}"></div>
                        <span class="flex-1 text-sm font-semibold text-gray-700">{{ $s['name'] }}</span>
                        <span class="text-sm font-black text-gray-900">{{ $s['count'] }}</span>
                        @if($total > 0)
                            <span class="text-[10px] text-gray-400 w-10 text-right">{{ round($s['count'] / $total * 100) }}%</span>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-400 text-center">Nenhum status cadastrado.</p>
                @endforelse
            </div>
        </div>

        {{-- Top Categorias --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-black text-gray-700 uppercase tracking-widest">Top Categorias</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($topCategories as $cat)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <span class="flex-1 text-sm font-semibold text-gray-700 truncate">{{ $cat['name'] }}</span>
                        <span class="text-sm font-black text-gray-900">{{ $cat['total'] }}</span>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-400 text-center">Nenhuma categoria utilizada ainda.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ══ AÇÕES RÁPIDAS ════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
        <h2 class="text-xs font-black text-gray-500 uppercase tracking-widest mb-4">Ações Rápidas</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.helpdesk.status.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Novo Status
            </a>
            <a href="{{ route('admin.helpdesk.priority.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nova Prioridade
            </a>
            <a href="{{ route('admin.helpdesk.origins.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                Gerenciar Origens
            </a>
            <a href="{{ route('admin.helpdesk.modules.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Módulos Contratados
            </a>
            <a href="{{ route('agent.ticket.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold text-sm transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Abrir Chamado
            </a>
        </div>
    </div>
</div>
@endsection
