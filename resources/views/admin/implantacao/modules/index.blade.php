@extends('admin.layouts.master')

@section('title', 'Implantação — Módulos por Cliente')

@section('content')
<div class="space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <span class="text-gray-600 font-semibold">Implantação</span>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">Módulos por Cliente</span>
    </nav>

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-black text-gray-900">Módulos de Implantação por Cliente</h1>
        <p class="text-sm text-gray-500 mt-1">
            Configure quais módulos técnicos cada cliente pode utilizar nos agendamentos e RATs.
            <strong>Sem configuração</strong>, todos os módulos ficam disponíveis (padrão).
        </p>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        @if($companies->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                <p class="text-sm font-semibold text-gray-500">Nenhum cliente cadastrado.</p>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Cliente</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Módulos configurados</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($companies as $company)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-800">{{ $company->trade_name }}</span>
                                @if($company->name && $company->name !== $company->trade_name)
                                    <span class="block text-xs text-gray-400">{{ $company->name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($company->scheduleModules->isEmpty())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Todos (padrão)
                                    </span>
                                @else
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($company->scheduleModules->sortBy('name') as $mod)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                {{ Str::upper($mod->name) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.implantacao.modules.edit', $company->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Configurar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
