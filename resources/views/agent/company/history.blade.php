@extends('layouts.agent')

@section('title', ($company->trade_name ?? 'Empresa') . ' - Histórico')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Histórico da Empresa</h1>
                @if($company->is_active)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>Ativo
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-500 border border-gray-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>Inativo
                    </span>
                @endif
                @if($company->financial_irregular)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        Irregular Financeiro
                    </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-0.5">{{ $company->trade_name ?? $company->name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('agent.companies.manage.edit', $company->id) }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-orange-600 hover:text-orange-700 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
            <a href="{{ route('agent.companies.manage.index') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar às empresas
            </a>
        </div>
    </div>

<div class="px-4" x-data="{ companyId: '{{ $company->id }}' }">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-0 border border-gray-200  bg-white  rounded-2xl shadow-sm text-[14px] overflow-hidden transition-colors">
        
        {{-- Coluna Esquerda: Informações de Contato --}}
        <div class="p-5 border-b md:border-b-0 relative">
            
            <h3 class="text-blue-600  font-extrabold mb-4 text-[16px] tracking-tight flex items-center gap-2">
                <span class="p-1.5 bg-blue-50  text-blue-500 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </span>
                {{ $company->trade_name }}
                @if($company->software)
                    <span class="text-gray-400  text-xs font-semibold ml-1 py-0.5 px-2 bg-gray-100  rounded-full border border-gray-200  shadow-sm leading-none">
                        {{ $company->software->name }}
                    </span>
                @endif
            </h3>

            <div class="space-y-2.5">
                {{-- Telefones --}}
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400  mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    <p class="leading-snug">
                        <span class="text-gray-900  font-medium">{{ $company->phone ?: '---' }}</span>
                        @if($company->telephone_2)
                            <span class="text-gray-300  px-1">/</span>
                            <span class="text-gray-900  font-medium">{{ $company->telephone_2 }}</span>
                        @endif
                    </p>
                </div>

                {{-- E-mail --}}
                @if($company->contact_email)
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400  mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        <p class="lowercase text-gray-700  font-medium truncate">{{ $company->contact_email }}</p>
                    </div>
                @endif

                {{-- Endereço --}}
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400  mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <div>
                        @if($company->address)
                            <p class="text-gray-700  font-medium leading-snug">{{ $company->address }}</p>
                        @endif
                        <p class="text-gray-500  text-xs mt-0.5">
                            @if($company->bairro){{ $company->bairro }}@if($company->city), @endif @endif
                            {{ $company->city ?? '' }}@if($company->city && $company->state) / @endif{{ $company->state?->abbr }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Contatos Cadastrados --}}
            @if($company->contacts->isNotEmpty())
                <div class="mt-5">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Contatos</p>
                    <div class="space-y-2">
                        @foreach($company->contacts as $contact)
                            <div class="flex items-center justify-between py-1.5 px-3 rounded-lg bg-gray-50 border border-gray-100">
                                <div class="flex items-center gap-2 min-w-0">
                                    @if($contact->is_main)
                                        <svg class="h-3.5 w-3.5 text-orange-400 flex-shrink-0 fill-orange-400" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118L2.062 9.101c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    @else
                                        <svg class="h-3.5 w-3.5 text-gray-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    @endif
                                    <span class="text-sm font-medium text-gray-800 truncate">{{ $contact->name ?: '—' }}</span>
                                    @if($contact->origin === 'financeiro')
                                        <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-600">Financeiro</span>
                                    @endif
                                </div>
                                <div class="text-right ml-3 flex-shrink-0">
                                    @if($contact->phone)
                                        <span class="block text-xs text-gray-500 font-mono">{{ $contact->phone }}</span>
                                    @endif
                                    @if($contact->email)
                                        <span class="block text-xs text-gray-500">{{ $contact->email }}</span>
                                    @endif
                                    @if(!$contact->phone && !$contact->email)
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Observações --}}
            @if($company->observations)
                <div class="mt-5 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/40 rounded-xl shadow-sm">
                    <span class="font-bold text-blue-700 dark:text-blue-300 mb-1 text-xs uppercase tracking-wider flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Observações:
                    </span>
                    <div class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-200 leading-relaxed font-medium mt-2">{{ $company->observations }}</div>
                </div>
            @endif

            @if($company->notes_2)
                <div class="mt-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-xl shadow-sm">
                    <span class="font-bold text-amber-800 dark:text-amber-300 mb-1 text-xs uppercase tracking-wider flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Observação Interna:
                    </span>
                    <div class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-200 leading-relaxed font-medium mt-2">{{ $company->notes_2 }}</div>
                </div>
            @endif
        </div>

        {{-- Coluna Direita: Dados Jurídicos e Features --}}
        <div class="p-5 border-t md:border-t-0 md:border-l border-gray-200  relative">
            <div class="space-y-2 mb-6">
                <div class="flex justify-between items-center border-b border-gray-100  pb-2">
                    <span class="text-gray-500  text-xs font-semibold uppercase tracking-wider">Razão Social</span>
                    <span class="text-gray-900  font-bold text-right pl-2">{{ $company->name }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-gray-100  pb-2">
                    <span class="text-gray-500  text-xs font-semibold uppercase tracking-wider">CNPJ</span>
                    <span class="mask-cnpj text-gray-800  font-mono font-medium">{{ $company->cnpj ?: '—' }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-gray-100  pb-2">
                    <span class="text-gray-500  text-xs font-semibold uppercase tracking-wider">Inscr. Estadual</span>
                    <span class="text-gray-800  font-mono font-medium">{{ $company->state_registration ?: 'Isento' }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-gray-100  pb-2">
                    <span class="text-gray-500  text-xs font-semibold uppercase tracking-wider">Cód. Municipal (IBGE)</span>
                    <span class="text-gray-800  font-mono font-medium">{{ $company->city_registration ?: '—' }}</span>
                </div>
                @if($company->contact_name)
                    <div class="flex justify-between items-center border-b border-gray-100  pb-2">
                        <span class="text-gray-500  text-xs font-semibold uppercase tracking-wider">Responsável</span>
                        <span class="text-gray-800  font-medium text-right pl-2">{{ $company->contact_name }}</span>
                    </div>
                @endif

                <div class="flex items-center justify-between pt-1">
                    <span class="text-gray-500  text-xs font-semibold uppercase tracking-wider">Grupo Empresarial</span>
                    @if($company->group)
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-700  text-xs font-semibold text-right">{{ $company->group->name ?: ($company->group->financial_code ?: 'Não informado') }}</span>
                            @if($company->group->financial_code && $company->group->name)
                                <span class="px-2 py-0.5 bg-indigo-50  text-indigo-700  border border-indigo-200  rounded text-xs font-bold">
                                    {{ $company->group->financial_code }}
                                </span>
                            @endif
                        </div>
                    @else
                        <span class="text-gray-400  text-xs italic">Não possui</span>
                    @endif
                </div>
            </div>

            {{-- Features Section (Pills Modernos) --}}
            <div class="pt-4 border-t border-gray-200 ">
                <p class="text-xs font-bold text-gray-800  uppercase tracking-wider mb-3">Módulos Contratados:</p>
                <div class="flex flex-wrap gap-2">
                    @forelse($company->moduleTypes->sortBy('sort_order') as $module)
                        <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50  text-emerald-700  border border-emerald-200  shadow-sm transition-all hover:scale-105">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            {{ $module->name }}
                        </div>
                    @empty
                        <span class="text-xs text-gray-400 italic">Nenhum módulo contratado</span>
                    @endforelse
                </div>
            </div>

            {{-- Fila de Pendências (Alerta Premium) --}}
            @if(isset($pending) && $pending && empty($condensed))
                <div class="mt-6 p-4 border border-rose-200  rounded-xl bg-gradient-to-br from-rose-50 to-white   shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-rose-200  rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>

                    <p class="text-rose-700  font-bold text-xs flex items-center mb-3 relative z-10 uppercase tracking-wider">
                        <span class="flex h-2 w-2 relative mr-2">
                           <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                           <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                        </span>
                        Atenção! Fila de Pendências sem responsável:
                    </p>
                    <div class="flex flex-wrap gap-2 relative z-10">
                        @foreach($pendings as $ticket)
                            <button type="button"
                                    class="text-[11px] px-2.5 py-1 bg-white  border border-rose-300  text-rose-700  rounded-md hover:bg-rose-600 hover:text-white   transition-all duration-200 font-bold shadow-sm active:scale-95"
                                    @@click="globalThis.TicketActions.openPopup('{{ $ticket->id }}')">
                                #{{ $ticket->id }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

    {{-- ══ HISTÓRICO DE ATENDIMENTOS ══════════════════════════════════════ --}}
    <div class="space-y-4">

        {{-- Cabeçalho da seção --}}
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-extrabold text-gray-800 tracking-tight">Histórico de Atendimentos</h2>
            @if($tickets->total() > 0)
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-700">
                    {{ $tickets->total() }} chamado(s)
                </span>
            @endif
        </div>

        {{-- Filtros --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.586a1 1 0 00-.293-.707L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>

            <form method="GET" action="{{ request()->url() }}" class="p-5">
                <div class="flex flex-wrap gap-4 items-end">

                    {{-- Período: De --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">De</label>
                        <input type="date" name="start" value="{{ request('start') }}"
                               class="text-sm bg-gray-50 border border-gray-200 rounded-xl px-3 py-2
                                      outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>

                    {{-- Período: Até --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Até</label>
                        <input type="date" name="end" value="{{ request('end') }}"
                               class="text-sm bg-gray-50 border border-gray-200 rounded-xl px-3 py-2
                                      outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>

                    {{-- Agente --}}
                    <div class="flex flex-col gap-1 min-w-40">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Agente</label>
                        <select name="agent_id"
                                class="text-sm bg-gray-50 border border-gray-200 rounded-xl px-3 py-2
                                       outline-none focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer">
                            <option value="">Todos</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" @selected(request('agent_id') == $agent->id)>
                                    {{ $agent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Categoria --}}
                    <div class="flex flex-col gap-1 min-w-44">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Categoria</label>
                        <select name="category_id"
                                class="text-sm bg-gray-50 border border-gray-200 rounded-xl px-3 py-2
                                       outline-none focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer">
                                <option value="">Todas</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->category_id }}" @selected(request('category_id') == $cat->category_id)>
                                    {{ $cat->display_name }}
                                    </option>
                                @endforeach
                            </select>
                    </div>

                    {{-- Ações --}}
                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black
                                       uppercase tracking-wider rounded-xl transition-all shadow-md active:scale-95">
                            Filtrar
                        </button>
                        @if(request()->anyFilled(['start', 'end', 'agent_id', 'category_id']))
                            <a href="{{ request()->url() }}"
                               class="text-xs font-semibold text-gray-400 hover:text-red-500 transition-colors">
                                Limpar
                            </a>
                        @endif
                    </div>

                </div>
            </form>
        </div>

        {{-- Lista de Tickets --}}
        @forelse($tickets as $ticket)
            @php
                $slaLevel    = $ticket->sla_level ?? 'normal';
                $badgeSla    = match($slaLevel) {
                    'attention' => 'bg-blue-100 text-blue-700',
                    'warning'   => 'bg-yellow-100 text-yellow-700',
                    'critical'  => 'bg-red-100 text-red-700',
                    'resolved'  => 'bg-emerald-100 text-emerald-700',
                    default     => 'bg-emerald-100 text-emerald-700',
                };
                $labelSla    = match($slaLevel) {
                    'attention' => 'Atenção',
                    'warning'   => 'Aviso',
                    'critical'  => 'Crítico',
                    'resolved'  => 'Concluído',
                    default     => 'No Prazo',
                };
                $statusColor = $ticket->status?->color ?? '#6366f1';
            @endphp

            <article class="rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow duration-200"
                     style="border-left: 5px solid {{ $statusColor }}">
                <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-4">

                    {{-- ID --}}
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center
                                justify-center text-indigo-600 font-black text-xs text-center flex-shrink-0">
                        #{{ $ticket->id }}
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 mb-0.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            <span>{{ $ticket->category?->display_name ?? 'Sem categoria' }}</span>
                            @if($ticket->subCategory)
                                <span class="text-gray-300">›</span>
                                <span class="text-indigo-500">{{ $ticket->subCategory->display_name }}</span>
                            @endif
                        </div>

                        <h3 class="font-bold text-gray-900 truncate hover:text-indigo-600 transition-colors">
                            <a href="{{ route('agent.ticket.show', $ticket->id) }}">
                                {{ $ticket->subject }}
                            </a>
                        </h3>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs text-gray-500">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ $ticket->agent?->name ?? 'Sem agente' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $ticket->created_at?->format('d/m/Y') }}
                            </span>
                        </div>

                        @include('agent.company.partials.ticket-issues', ['ticket' => $ticket])
                    </div>

                    {{-- Badges --}}
                    <div class="flex-shrink-0 flex sm:flex-col items-center sm:items-end gap-1.5">
                        <div class="flex items-center gap-1.5 flex-wrap justify-end">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $badgeSla }}">
                                SLA: {{ $labelSla }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                  style="background-color: {{ $statusColor }}22; color: {{ $statusColor }}">
                                {{ $ticket->status?->name ?? 'Aberto' }}
                            </span>
                        </div>
                        <time class="text-[10px] text-gray-400 italic" datetime="{{ $ticket->created_at?->toIso8601String() }}">
                            {{ $ticket->created_at?->diffForHumans() }}
                        </time>
                    </div>

                </div>
            </article>

        @empty
            <div class="flex flex-col items-center justify-center py-16 text-center rounded-2xl border border-dashed border-gray-200 bg-white">
                <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500 font-semibold text-sm">Nenhum atendimento encontrado</p>
                @if(request()->anyFilled(['start', 'end', 'agent_id', 'category_id']))
                    <p class="text-gray-400 text-xs mt-1">Ajuste os filtros para ver mais resultados.</p>
                @endif
            </div>
        @endforelse

        {{-- Paginação --}}
        @if($tickets->hasPages())
            <div class="pt-1">
                {{ $tickets->links() }}
            </div>
        @endif

    </div>

</div>
@endsection
