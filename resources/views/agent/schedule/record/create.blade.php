@extends('layouts.agent')

@section('title', isset($record) ? 'Editar Registro de Atendimento' : 'Novo Registro de Atendimento')

@section('content')
<div class="px-4 animate-fade-in-up"
     x-data="recordCreate"
     x-init="initData({
        recordId: '{{ $record->id ?? '' }}',
        scheduleId: '{{ $schedule->id ?? '' }}',
        isEdit: {{ isset($record) ? 'true' : 'false' }},
        ticketId: '{{ $ticket->id ?? '' }}',
        customerId: '{{ isset($schedule) && $schedule->customer ? $schedule->customer_id : '' }}',
        moduleId: '{{ isset($record) ? $record->module_id : (isset($schedule) ? $schedule->module_id : '') }}',
        baseHistoryUrl: '{{ url('support/company') }}',
        apiElementsUrl: '{{ route('api.records.elements.index') }}',
        apiRecordFetchUrl: '{{ url('api/v1/schedules/' . $schedule->id . '/records') }}'
     })">

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Header Título --}}
        <div class="pb-2 border-b border-gray-200 dark:border-slate-700">
            <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-slate-100 flex items-center gap-3">
                <span class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 dark:text-indigo-400 rounded-xl shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </span>
                {{ isset($record) ? 'Editar Registro do Atendimento' : 'Registrar Atendimento' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Lance as atividades executadas e horários referentes a esse chamado/agendamento.</p>
        </div>

        @include('shared.errors')

        <form method="POST" id="main-record-form"
              class="p-6 sm:p-8 rounded-2xl shadow-sm border relative overflow-hidden bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100"
              action="{{ isset($record) ? route('agent.record.update', ['schedule' => $schedule->id, 'record' => $record->id]) : route('agent.record.store', ['schedule' => $schedule->id]) }}">

            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 to-sky-400"></div>

            @csrf
            @if(isset($record))
                @method('PUT')
            @endif

            <fieldset class="group disabled:opacity-75 transition-opacity space-y-8 mt-2">

                {{-- Quadro de Informações do Agendamento (Se Existir) --}}
                @if($schedule->customer)
                    <div class="rounded-xl p-5 mb-8 shadow-sm border bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800/50">
                        <h3 class="text-sm font-bold uppercase tracking-wider mb-4 flex items-center gap-2 text-indigo-800 dark:text-indigo-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Detalhes do Agendamento Vinculado
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm text-gray-700 dark:text-slate-300">
                            <div class="space-y-3">
                                <p class="flex items-start gap-2">
                                    <span class="font-bold min-w-[90px] text-gray-900 dark:text-slate-100">Cliente:</span>
                                    <span>{{ $schedule->customer->trade_name }}</span>
                                </p>
                                <p class="flex items-start gap-2">
                                    <span class="font-bold min-w-[90px] text-gray-900 dark:text-slate-100">Módulo:</span>
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold shadow-sm bg-white dark:bg-slate-700 border border-indigo-300 dark:border-indigo-600 text-indigo-800 dark:text-indigo-300">{{ $schedule->module?->name ?? '—' }}</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <span class="font-bold min-w-[90px] text-gray-900 dark:text-slate-100">Data Prev.:</span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ $schedule->start_at->format('d/m/Y \à\s H:i') }}
                                    </span>
                                </p>
                            </div>
                            <div class="space-y-3 md:border-l md:pl-6 border-indigo-200 dark:border-indigo-700/50">
                                <p class="flex items-start gap-2">
                                    <span class="font-bold min-w-[90px] text-gray-900 dark:text-slate-100">Contato:</span>
                                    <span>{{ $schedule->contact }}</span>
                                </p>
                                <p class="flex items-start gap-2">
                                    <span class="font-bold min-w-[90px] text-gray-900 dark:text-slate-100">Obs:</span>
                                    <span class="italic opacity-90">{{ $schedule->obs ?: 'Nenhuma observação prévia.' }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Seleção Manual de Cliente (Se não for via Ticket/Schedule) --}}
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2 border-b border-gray-100 dark:border-slate-700 pb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            Seleção de Cliente
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                            <div class="md:col-span-8">
                                <label for="customer_id" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Empresa Cliente</label>
                                <div class="relative">
                                    <select name="customer_id" id="customer_id"
                                            class="block w-full pl-4 pr-10 py-3 rounded-xl shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors cursor-pointer appearance-none"
                                            x-model="config.customerId"
                                            @change="loadHistory($el.value)" required autofocus>
                                        <option value="" disabled selected class="text-gray-400">Selecione uma empresa...</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" data-cnpj="{{ $customer->cnpj }}" @selected(isset($ticket) && $customer->id == $ticket->customer_id)>
                                                {{ $customer->trade_name }} - {{ $customer->id }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 dark:text-slate-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Container do Histórico do Cliente Dinâmico --}}
                        <div id="history-container" class="mt-4 transition-all duration-500" :class="config.customerId ? 'opacity-100 block' : 'opacity-0 hidden'"></div>
                    </div>
                @endif


                {{-- Sessão: Dados Básicos do Registro --}}
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2 border-b border-gray-100 dark:border-slate-700 pb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Dados do Atendimento
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-5">
                        <div class="md:col-span-4">
                            <label for="module_id" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Sistema/Módulo Trabalhado</label>
                            <div class="relative">
                                <select name="module_id" id="module_id"
                                        class="block w-full pl-4 pr-10 py-3 rounded-xl shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors cursor-pointer appearance-none disabled:opacity-75 disabled:cursor-not-allowed"
                                        x-model="config.moduleId"
                                        @change="loadElements($el.value)" required @disabled(isset($record))>
                                    <option value="" disabled selected class="text-gray-400">Selecione...</option>
                                    @foreach($modules as $project => $projectModules)
                                        <optgroup label="{{ $project }}">
                                            @foreach($projectModules as $module)
                                                <option value="{{ $module->id }}" @selected(isset($record) ? $record->module_id == $module->id : $schedule->module_id == $module->id)>
                                                    {{ Str::upper($module->name) }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 dark:text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-4">
                            <label for="agent_id" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Suporte Executante</label>
                            <div class="relative">
                                <select name="agent_id" id="agent_id"
                                        class="block w-full pl-4 pr-10 py-3 rounded-xl shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors cursor-pointer appearance-none">
                                    <option value="0" class="text-gray-400">Nenhum Responsável Específico</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" @selected(isset($record) ? $record->agent_id == $agent->id : Auth::id() == $agent->id)>
                                            {{ Str::upper($agent->name) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 dark:text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-4">
                            <label for="contact" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Pessoas Atendidas</label>
                            <input type="text" name="contact" id="contact" value="{{ $record->contact ?? '' }}"
                                   class="block w-full py-3 px-4 rounded-xl shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors"
                                   required placeholder="João, Maria...">
                        </div>
                    </div>
                </div>

                {{-- Sessão: Ponto Mágico / Horários --}}
                <div class="p-5 rounded-2xl border bg-gray-50 dark:bg-slate-900 border-gray-100 dark:border-slate-700">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2 uppercase tracking-wide">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Tempos e Horários do Atendimento
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-5">
                        <div class="md:col-span-4">
                            <label for="date_input" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">Data de Execução</label>
                            <input type="date" name="date" id="date_input"
                                   class="block w-full py-2.5 px-4 rounded-xl shadow-sm border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors dark:[color-scheme:dark]"
                                   value="{{ isset($record) ? $record->created_at->format('Y-m-d') : $schedule->start_at->format('Y-m-d') }}" required>
                        </div>
                        <div class="md:col-span-2">
                            <label for="version" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">Versão ERP</label>
                            <input type="text" name="version" id="version" value="{{ $record->version ?? '' }}" placeholder="Ex: 5"
                                   class="block w-full py-2.5 px-4 rounded-xl shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm text-center">
                        </div>
                        <div class="md:col-span-2">
                            <label for="release" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">Release API</label>
                            <input type="text" name="release" id="release" value="{{ $record->release ?? '' }}" placeholder="Ex: 2.1"
                                   class="block w-full py-2.5 px-4 rounded-xl shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm text-center">
                        </div>

                        <div class="md:col-span-2">
                            <label for="start_hour" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">H. Início</label>
                            <input type="time" name="start_hour" id="start_hour"
                                   class="block w-full py-2.5 px-3 rounded-xl shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                   value="{{ isset($record) ? $record->start->format('H:i') : '' }}" required>
                        </div>
                        <div class="md:col-span-2">
                            <label for="end_hour" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">H. Fim</label>
                            <input type="time" name="end_hour" id="end_hour"
                                   class="block w-full py-2.5 px-3 rounded-xl shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                   value="{{ isset($record) ? $record->end->format('H:i') : '' }}" required>
                        </div>
                    </div>

                    {{-- Intervalos (Secundário) --}}
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 pt-3 border-t border-gray-200 dark:border-slate-700">
                        <div class="md:col-span-4 flex items-center">
                            <p class="text-xs text-gray-500 dark:text-slate-400">Preencha o intervalo abaixo apenas em caso de pausas (Ex: Almoço) durante o atendimento contínuo.</p>
                        </div>
                        <div class="md:col-span-2">
                            <label for="interval_start" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">Pausa Início</label>
                            <input type="time" name="interval_start" id="interval_start"
                                   class="block w-full py-2.5 px-3 rounded-xl shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                   value="{{ (isset($record) && $record->interval_start) ? $record->interval_start->format('H:i') : '' }}">
                        </div>
                        <div class="md:col-span-2">
                            <label for="interval_end" class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">Pausa Retorno</label>
                            <input type="time" name="interval_end" id="interval_end"
                                   class="block w-full py-2.5 px-3 rounded-xl shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                   value="{{ (isset($record) && $record->interval_end) ? $record->interval_end->format('H:i') : '' }}">
                        </div>

                        {{-- Resolvido --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Resolvido?</label>
                            <div class="flex items-center gap-3 h-10">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="resolved" value="1" class="w-3.5 h-3.5 accent-green-500"
                                           @if(isset($record) && $record->resolved === true) checked @endif>
                                    <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">Sim</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="resolved" value="0" class="w-3.5 h-3.5 accent-red-500"
                                           @if(isset($record) && $record->resolved === false) checked @endif>
                                    <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">Não</span>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Record Elements Inject (Vindo por Fetch API em JSON e montado via AlpineJS/JS) --}}
                <div id="elements-loading" style="display:none;" class="py-10 flex flex-col items-center justify-center">
                    <svg class="animate-spin h-8 w-8 text-orange-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span class="text-sm font-semibold text-gray-500 dark:text-slate-400">Puxando Checklists do Módulo...</span>
                </div>
                {{-- O JS preenche o container abaixo com Elementos Tailwind --}}
                <div id="record-elements-target" class="space-y-6"></div>

                {{-- Sessão Final: Observações Gerais --}}
                <div class="pt-4 border-t border-gray-100 dark:border-slate-700"
                     x-data="obsBlock" data-obs="{{ e($record->obs ?? '') }}">

                    <div class="flex items-center justify-between mb-2">
                        <label for="obs" class="flex items-center gap-1.5 text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Relatório Resumo da Atividade / Parecer
                        </label>
                        <button type="button" @click="openModal()"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-500 dark:text-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                            Expandir
                        </button>
                    </div>

                    {{-- Textarea inline --}}
                    <textarea name="obs" id="obs" x-ref="inline" x-model="value"
                              @input="autoResize($refs.inline)" x-init="autoResize($refs.inline)"
                              style="min-height:120px"
                              class="resize-none block w-full py-3 px-4 rounded-xl shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors overflow-hidden"
                              placeholder="Descreva aqui o que foi feito em detalhes, soluções aplicadas..."></textarea>
                    <p class="text-right text-[11px] mt-1 text-gray-400 dark:text-slate-500" x-text="value.length + ' caracteres'"></p>

                    {{-- Modal fullscreen --}}
                    <div x-show="expanded"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="fixed inset-0 z-50 flex flex-col"
                         style="background:rgba(10,18,30,0.97)"
                         @keydown.escape.window="closeModal()"
                         x-cloak>
                        <div class="flex items-center justify-between px-5 py-3 shrink-0" style="border-bottom:1px solid #1e3a5f">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Relatório Resumo da Atividade / Parecer</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-slate-500" x-text="value.length + ' caracteres'"></span>
                                <button type="button" @click="closeModal()"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Fechar (Esc)
                                </button>
                            </div>
                        </div>
                        <div class="flex-1 p-4 overflow-hidden">
                            <textarea x-ref="modal" x-model="value"
                                      placeholder="Descreva aqui o que foi feito em detalhes, soluções aplicadas..."
                                      class="w-full h-full px-5 py-4 rounded-xl text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                                      style="background:#0f172a;color:#e2e8f0;border:1px solid #1e3a5f;font-family:inherit">
                            </textarea>
                        </div>
                    </div>
                </div>

            </fieldset>

            {{-- Formulário Submit --}}
            <div class="mt-8 pt-6 border-t border-gray-100 dark:border-slate-700 flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                <div class="hidden sm:flex text-xs text-gray-400 dark:text-slate-500 items-center justify-center gap-2">
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 font-mono text-[10px] leading-none text-gray-500 dark:text-slate-400">CTRL</span>
                    <span>+</span>
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 font-mono text-[10px] leading-none text-gray-500 dark:text-slate-400">S</span>
                    <span class="ml-1">Atalho salvar registro</span>
                </div>

                <button type="submit" id="confirm-btn"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl border border-transparent bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed group"
                        :disabled="submitting"
                        @click="submitting = true; $el.form.submit()">
                    <span x-show="!submitting" class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Salvar Atendimento (Ficha)
                    </span>
                    <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Salvando Ficha...
                    </span>
                </button>
            </div>

        </form>
    </div>
</div>

<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.4s ease-out forwards;
    }
    /* Garante legibilidade do botão submit em qualquer tema */
    #confirm-btn,
    #confirm-btn span,
    #confirm-btn svg {
        color: #ffffff !important;
    }
</style>
@endsection

