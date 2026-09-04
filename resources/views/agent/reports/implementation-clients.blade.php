@extends('layouts.agent')

@section('title', 'Relatório de Implantações - Agente')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Clientes em Implantação</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $totalClients }} cliente(s) com atividades em andamento</p>
        </div>
        <a href="{{ route('agent.implantacao.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar para Implantação
        </a>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-500 shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Total de Clientes</p>
                <p class="text-2xl font-black text-gray-900">{{ $totalClients }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500 shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Tickets Abertos</p>
                <p class="text-2xl font-black text-gray-900">{{ $totalOpenTickets }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center text-purple-500 shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Agendamentos Ativos</p>
                <p class="text-2xl font-black text-gray-900">{{ $totalSchedules }}</p>
            </div>
        </div>
    </div>

    {{-- Clients Table --}}
    <div class="overflow-hidden bg-white rounded-2xl border border-gray-200 shadow-sm">
        @if($clients->isEmpty())
            <div class="py-20 text-center">
                <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-gray-700 font-semibold">Nenhum cliente em implantação</p>
                <p class="text-gray-400 text-sm mt-1">Todos os clientes estão com atividades concluídas.</p>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Cliente</th>
                        <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 hidden md:table-cell">Software</th>
                        <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 hidden lg:table-cell">UF</th>
                        <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Tickets Abertos</th>
                        <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Agendamentos</th>
                        <th class="px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-gray-400 w-24">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($clients as $client)
                        <tr class="group hover:bg-orange-50/30 transition-colors duration-150">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900 group-hover:text-orange-700 transition-colors">
                                    {{ $client->trade_name ?? $client->name }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $client->cnpj }}</p>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell">
                                @if($client->software)
                                    <span class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                                        {{ $client->software->name }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 hidden lg:table-cell text-gray-500 text-sm font-semibold">
                                {{ $client->state?->abbr ?? '—' }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($client->open_tickets > 0)
                                    <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-amber-100 text-amber-700 text-xs font-bold rounded-full">
                                        {{ $client->open_tickets }}
                                    </span>
                                @else
                                    <span class="text-gray-300 text-xs">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($client->active_schedules > 0)
                                    <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">
                                        {{ $client->active_schedules }}
                                    </span>
                                @else
                                    <span class="text-gray-300 text-xs">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('agent.company.history', $client->id) }}"
                                   class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors inline-flex"
                                   title="Ver histórico">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr class="font-bold text-sm">
                        <td class="px-5 py-3.5 text-gray-700" colspan="3">Total</td>
                        <td class="px-4 py-3.5 text-center text-amber-700">{{ $totalOpenTickets }}</td>
                        <td class="px-4 py-3.5 text-center text-purple-700">{{ $totalSchedules }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

</div>
@endsection
