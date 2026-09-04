@extends('layouts.agent')

@section('title', isset($schedule) ? 'Editar Agendamento' : 'Novo Agendamento')

@php
    $editingSchedule = $schedule ?? null;
    $ticketId = old('ticket_id', $sourceTicket?->id ?? $editingSchedule?->ticket_id);
    $isTicketOrigin = !empty($ticketId);
    $scheduleTypes = $scheduleTypes ?? collect();
    $ticketTypeLabel = optional($scheduleTypes->firstWhere('slug', \App\Models\Schedule::KIND_TICKET))->label ?? 'Ticket';
    $defaultKind = $scheduleTypes->where('slug', '!=', \App\Models\Schedule::KIND_TICKET)->where('requires_customer', true)->first()->slug
        ?? ($scheduleTypes->where('slug', '!=', \App\Models\Schedule::KIND_TICKET)->first()->slug ?? \App\Models\Schedule::KIND_CLIENT);
    $selectedKind = old('kind', $isTicketOrigin ? \App\Models\Schedule::KIND_TICKET : ($editingSchedule?->kind ?? $defaultKind));
    $selectedCustomerId = old('customer_id', $sourceTicket?->company_id ?? $editingSchedule?->customer_id ?? '');
    $selectedModuleId = old('module_id', $editingSchedule?->module_id ?? '');
    $selectedAgentId = old('agent_id', $editingSchedule?->agent_id ?? $currentUser?->id);
    $titleValue = old('title', $editingSchedule?->title ?? ($sourceTicket ? "Visita tecnica - Ticket #{$sourceTicket->id}" : ''));
    $contactValue = old('contact', $editingSchedule?->contact ?? $sourceTicket?->contact ?? '');
    $obsValue = old('obs', $editingSchedule?->obs ?? ($sourceTicket ? "Origem do ticket #{$sourceTicket->id}: {$sourceTicket->subject}" : ''));
    $dateValue = old('date', $editingSchedule?->start_at?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $timeValue = old('start_hour', $editingSchedule?->start_at?->format('H:i') ?? '');
    $kindsRequiringCustomer = $scheduleTypes->where('requires_customer', true)->pluck('slug')->values();
@endphp

@push('styles')
<style>
    html.ocean .schedule-card {
        background: #0b1220 !important;
        border-color: #334155 !important;
        color: #e2e8f0;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
    }
    html.ocean .schedule-card input,
    html.ocean .schedule-card select,
    html.ocean .schedule-card textarea {
        background: #111827 !important;
        border-color: #334155 !important;
        color: #e5e7eb !important;
    }
    html.ocean .schedule-card option {
        color: #0f172a;
    }
    html.ocean .schedule-card h2,
    html.ocean .schedule-card h3,
    html.ocean .schedule-card label {
        color: #e2e8f0 !important;
    }
    html.ocean .schedule-card .text-gray-500 {
        color: #94a3b8 !important;
    }
    html.ocean .schedule-card .border-gray-200,
    html.ocean .schedule-card .border-gray-100 {
        border-color: #334155 !important;
    }
    html.ocean .schedule-card .bg-gray-50,
    html.ocean .schedule-card .bg-orange-50,
    html.ocean .schedule-card .bg-slate-50 {
        background: #111827 !important;
    }
    html.ocean .schedule-header {
        border-color: #334155 !important;
    }
    html.ocean .schedule-pill {
        background: #1e293b !important;
        color: #e2e8f0 !important;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.4s ease-out forwards;
    }
</style>
@endpush

@section('content')
<div
    class="px-4 animate-fade-in-up"
    x-data="{
        submitting: false,
        isFinalized: {{ isset($schedule) && $schedule->isFinalized() ? 'true' : 'false' }},
        customerId: '{{ $selectedCustomerId }}',
        kind: '{{ $selectedKind }}',
        isTicketOrigin: {{ $isTicketOrigin ? 'true' : 'false' }},
        kindsRequiringCustomer: {{ $kindsRequiringCustomer->toJson() }},
        showClientFields() {
            return this.isTicketOrigin || this.kindsRequiringCustomer.includes(this.kind);
        },
        onKindChange() {
            if (this.showClientFields()) return;

            this.customerId = '';
            const moduleSelect = document.getElementById('module_id');
            if (moduleSelect) moduleSelect.value = '';
            const contactInput = document.getElementById('contact');
            if (contactInput) contactInput.value = '';
            const history = document.getElementById('history-container');
            if (history) history.replaceChildren();
        },
        loadHistoryIfNeeded() {
            if (this.customerId) {
                globalThis.ScheduleActions.loadHistory(this.customerId);
                globalThis.ScheduleActions.loadModules(this.customerId);
            }
        }
    }"
    x-init="loadHistoryIfNeeded()"
>
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="pb-2 border-b border-gray-200 schedule-header">
            <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 flex items-center gap-3">
                <span class="p-2 bg-orange-50 text-orange-500 rounded-xl shadow-sm schedule-pill">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 2 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
                    </svg>
                </span>
                {{ isset($schedule) ? 'Editar Agendamento #' . $schedule->id : 'Novo Agendamento' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">Use esta tela para lançar visitas por ticket e compromissos internos do agente.</p>
        </div>

        @include('shared.errors')

        <template x-if="isFinalized">
            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-xl">
                <p class="text-sm text-amber-700 font-bold">
                    Este agendamento ja foi finalizado e os campos estao bloqueados.
                </p>
            </div>
        </template>

        <form
            method="POST"
            id="main-schedule-form"
            class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-gray-200 relative overflow-hidden schedule-card"
            action="{{ isset($schedule) ? route('agent.schedules.update', $schedule->id) : route('agent.schedules.store') }}"
        >
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-orange-400 to-rose-500"></div>

            @csrf
            @isset($schedule)
                @method('PUT')
            @endisset

            <input type="hidden" name="ticket_id" value="{{ $ticketId }}">
            @if(!$isAdmin)
                <input type="hidden" name="agent_id" value="{{ $selectedAgentId }}">
            @endif

            <fieldset :disabled="isFinalized" class="group disabled:opacity-75 transition-opacity space-y-8">
                @if($isTicketOrigin)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-col gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-amber-800 border border-amber-200">
                                Ticket #{{ $ticketId }} - aguardando admin
                            </span>
                            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-600 border border-slate-200">
                                {{ $ticketTypeLabel }}
                            </span>
                        </div>
                        @if($sourceTicket)
                            <div class="grid gap-3 md:grid-cols-3 text-sm text-slate-700">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Cliente</p>
                                    <p class="font-semibold">{{ $sourceTicket->company?->trade_name ?? 'Nao informado' }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Contato</p>
                                    <p class="font-semibold">{{ $sourceTicket->contact ?: 'Nao informado' }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Assunto</p>
                                    <p class="font-semibold">{{ $sourceTicket->subject }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Tipo e resumo
                    </h3>

                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
                        <div class="xl:col-span-4">
                            <label for="kind" class="block text-sm font-bold text-gray-700 mb-1.5 uppercase tracking-wider text-xs">Tipo de agendamento</label>
                            @if($isTicketOrigin)
                                <input type="hidden" name="kind" value="{{ \App\Models\Schedule::KIND_TICKET }}">
                                <div class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                                    {{ $ticketTypeLabel }}
                                </div>
                            @else
                                <div class="relative">
                                    <select
                                        name="kind"
                                        id="kind"
                                        x-model="kind"
                                        @change="onKindChange()"
                                        class="block w-full pl-4 pr-10 py-3 rounded-xl border-gray-300 bg-gray-50 text-gray-900 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors cursor-pointer appearance-none"
                                    >
                                        @foreach($scheduleTypes as $scheduleType)
                                            @continue($scheduleType->slug === \App\Models\Schedule::KIND_TICKET)
                                            <option value="{{ $scheduleType->slug }}" @selected($selectedKind === $scheduleType->slug)>{{ $scheduleType->label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="xl:col-span-8">
                            <label for="title" class="block text-sm font-bold text-gray-700 mb-1.5 uppercase tracking-wider text-xs">Titulo do agendamento</label>
                            <input
                                type="text"
                                name="title"
                                id="title"
                                value="{{ $titleValue }}"
                                class="block w-full py-3 px-4 rounded-xl border-gray-300 bg-gray-50 text-gray-900 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors"
                                placeholder="Ex.: Consulta medica, Reuniao semanal, Visita tecnica"
                                required
                            >
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4" />
                        </svg>
                        Cliente e contexto
                    </h3>

                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
                        <div class="xl:col-span-8" x-show="showClientFields()" x-cloak>
                            <label for="customer_id" class="block text-sm font-bold text-gray-700 mb-1.5 uppercase tracking-wider text-xs">
                                Cliente
                            </label>
                            @if($isTicketOrigin)
                                <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">
                            @endif
                            <div class="relative">
                                <select
                                    name="{{ $isTicketOrigin ? 'customer_locked' : 'customer_id' }}"
                                    id="customer_id"
                                    x-model="customerId"
                                    @change="globalThis.ScheduleActions.loadHistory($el.value); globalThis.ScheduleActions.loadModules($el.value)"
                                    class="block w-full pl-4 pr-10 py-3 rounded-xl border-gray-300 bg-gray-50 text-gray-900 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors cursor-pointer appearance-none disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    @disabled($isTicketOrigin)
                                >
                                    <option value="">Sem cliente</option>
                                    @foreach($customers as $customer)
                                        <option
                                            value="{{ $customer->id }}"
                                            data-cnpj="{{ $customer->cnpj }}"
                                            @selected((string) $selectedCustomerId === (string) $customer->id)
                                        >
                                            {{ $customer->trade_name }} - {{ $customer->id }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            @unless($isTicketOrigin)
                                <button
                                    type="button"
                                    class="mt-2 inline-flex items-center gap-2 rounded-xl border border-transparent bg-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-700 transition hover:bg-gray-300"
                                    @click="globalThis.ScheduleActions.searchByCnpj()"
                                    :disabled="isFinalized"
                                >
                                    Buscar por CNPJ
                                </button>
                            @endunless
                        </div>

                        <div class="xl:col-span-4" x-show="showClientFields()" x-cloak>
                            <label for="module_id" class="block text-sm font-bold text-gray-700 mb-1.5 uppercase tracking-wider text-xs">
                                Modulo (opcional)
                            </label>
                            <div class="relative">
                                <select
                                    name="module_id"
                                    id="module_id"
                                    class="block w-full pl-4 pr-10 py-3 rounded-xl border-gray-300 bg-gray-50 text-gray-900 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors cursor-pointer appearance-none"
                                >
                                    <option value="">Não informar</option>
                                    @foreach($modules as $project => $projectModules)
                                        <optgroup label="{{ $project }}">
                                            @foreach($projectModules as $module)
                                                <option value="{{ $module->id }}" @selected((string) $selectedModuleId === (string) $module->id)>
                                                    {{ Str::upper($module->name) }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="history-container" class="mt-4 transition-all duration-500" :class="customerId ? 'opacity-100 block' : 'opacity-0 hidden'"></div>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z" />
                        </svg>
                        Responsavel e horario
                    </h3>

                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
                        <div class="xl:col-span-4">
                            <label for="agent_id" class="block text-sm font-bold text-gray-700 mb-1.5 uppercase tracking-wider text-xs">Agente responsavel</label>
                            @if($isAdmin)
                                <div class="relative">
                                    <select
                                        name="agent_id"
                                        id="agent_id"
                                        class="block w-full pl-4 pr-10 py-3 rounded-xl border-gray-300 bg-gray-50 text-gray-900 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors cursor-pointer appearance-none"
                                    >
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}" @selected((string) $selectedAgentId === (string) $agent->id)>
                                                {{ Str::upper($agent->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-900">
                                    {{ $currentUser?->name ?? 'Agente atual' }}
                                </div>
                            @endif
                        </div>

                        <div class="xl:col-span-4">
                            <label for="date_input" class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider text-xs">Data</label>
                            <input
                                type="date"
                                name="date"
                                id="date_input"
                                value="{{ $dateValue }}"
                                class="block w-full py-3 px-4 rounded-xl border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-gray-100 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors dark:[color-scheme:dark]"
                                required
                            >
                        </div>

                        <div class="xl:col-span-4">
                            <label for="start_hour" class="block text-sm font-bold text-gray-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider text-xs">Horário</label>
                            <input
                                type="time"
                                name="start_hour"
                                id="start_hour"
                                value="{{ $timeValue }}"
                                class="block w-full py-3 px-4 rounded-xl border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-gray-100 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors dark:[color-scheme:dark]"
                                required
                            >
                        </div>

                        <div class="xl:col-span-12" x-show="showClientFields()" x-cloak>
                            <label for="contact" class="block text-sm font-bold text-gray-700 mb-1.5 uppercase tracking-wider text-xs">
                                Contato (opcional)
                            </label>
                            <input
                                type="text"
                                name="contact"
                                id="contact"
                                value="{{ $contactValue }}"
                                class="block w-full py-3 px-4 rounded-xl border-gray-300 bg-gray-50 text-gray-900 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors"
                                placeholder="Quem solicitou o atendimento"
                            >
                        </div>
                    </div>
                </div>

                <div>
                    <label for="obs" class="block text-sm font-bold text-gray-700 mb-1.5 uppercase tracking-wider text-xs">
                        Observacoes
                    </label>
                    <textarea
                        name="obs"
                        id="obs"
                        rows="4"
                        class="resize-y block w-full py-3 px-4 rounded-xl border-gray-300 bg-gray-50 text-gray-900 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors"
                        placeholder="Detalhes importantes para o agendamento"
                    >{{ $obsValue }}</textarea>
                </div>
            </fieldset>

            <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-between">
                <div class="hidden sm:flex text-xs text-gray-400 items-center justify-center gap-2">
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded border border-gray-200 bg-gray-50 font-mono text-[10px] leading-none text-gray-500 shadow-sm">CTRL</span>
                    <span>+</span>
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded border border-gray-200 bg-gray-50 font-mono text-[10px] leading-none text-gray-500 shadow-sm">S</span>
                    <span class="ml-1">Atalho salvar formulario</span>
                </div>

                <button
                    type="submit"
                    id="confirm-btn"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl border border-transparent bg-orange-500 text-white font-bold shadow-lg shadow-orange-500/30 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed group"
                    :disabled="submitting || isFinalized"
                    @click="submitting = true; $el.form.submit()"
                >
                    <span x-show="!submitting" class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Salvar agendamento
                    </span>
                    <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Salvando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('footer')
<script>
    document.addEventListener('alpine:init', () => {
        if (!globalThis.ScheduleActions) {
            globalThis.ScheduleActions = {};
        }

        globalThis.ScheduleActions.historyBaseUrl = "{{ url('support/company') }}";

        globalThis.ScheduleActions.loadHistory = function (customerId) {
            const container = document.getElementById('history-container');

            if (!customerId) {
                if (container) container.innerHTML = '';
                return;
            }

            if (container) {
                container.innerHTML = '<div class="py-4 flex justify-center items-center"><svg class="animate-spin h-6 w-6 text-orange-500 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="text-sm font-semibold text-gray-500">Carregando historico do cliente...</span></div>';
            }

            fetch(`${this.historyBaseUrl}/${customerId}/history`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(response => response.text())
                .then(html => {
                    if (container) container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Falha ao obter historico', error);
                    if (container) container.innerHTML = '<div class="p-3 text-red-500 text-sm font-bold border border-red-200 bg-red-50 rounded-lg">Erro ao carregar o historico.</div>';
                });
        };

        /**
         * Recarrega o dropdown de módulos filtrado pelos módulos do cliente.
         * Se o cliente não tiver módulos configurados, mantém todos visíveis (fallback).
         */
        globalThis.ScheduleActions.modulesApiUrl = "{{ url('api/v1/companies') }}";
        globalThis.ScheduleActions._selectedModuleId = '{{ $selectedModuleId }}';

        globalThis.ScheduleActions.loadModules = async function (customerId) {
            const select = document.getElementById('module_id');
            if (!select) return;

            // Sem cliente → restaura todos os módulos (server-side fallback)
            if (!customerId) {
                select.querySelectorAll('optgroup, option:not(:first-child)').forEach(el => el.remove());
                @foreach($modules as $project => $projectModules)
                    (() => {
                        const og = document.createElement('optgroup');
                        og.label = '{{ $project }}';
                        @foreach($projectModules as $module)
                            (() => {
                                const opt = document.createElement('option');
                                opt.value = '{{ $module->id }}';
                                opt.textContent = '{{ Str::upper($module->name) }}';
                                og.appendChild(opt);
                            })();
                        @endforeach
                        select.appendChild(og);
                    })();
                @endforeach
                return;
            }

            try {
                const res = await fetch(`${this.modulesApiUrl}/${customerId}/schedule-modules`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;

                const grouped = await res.json(); // { "Geral": [{id, name}], ... }
                const saved   = this._selectedModuleId || select.value;

                select.querySelectorAll('optgroup, option:not(:first-child)').forEach(el => el.remove());

                Object.entries(grouped).forEach(([project, modules]) => {
                    const og = document.createElement('optgroup');
                    og.label = project;
                    modules.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value  = m.id;
                        opt.textContent = m.name.toUpperCase();
                        if (String(m.id) === String(saved)) opt.selected = true;
                        og.appendChild(opt);
                    });
                    select.appendChild(og);
                });

                this._selectedModuleId = ''; // limpa após primeira restauração
            } catch (e) {
                console.error('[ScheduleActions] Falha ao carregar módulos', e);
            }
        };

        globalThis.ScheduleActions.searchByCnpj = function () {
            const promptCnpj = prompt('Buscar cliente na lista por CNPJ (somente numeros):');
            if (!promptCnpj) return;

            const cnpj = promptCnpj.replace(/\D/g, '');
            const select = document.getElementById('customer_id');
            let matches = 0;

            Array.from(select.options).forEach(option => {
                if ((option.dataset.cnpj || '').replace(/\D/g, '') === cnpj) {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change'));
                    matches++;
                }
            });

            if (matches > 1) {
                alert('Mais de um cliente foi encontrado com este CNPJ. Revise a selecao.');
            } else if (matches === 0) {
                alert('Nenhum cliente encontrado com o CNPJ informado.');
            }
        };
    });

    document.addEventListener('keydown', (event) => {
        if (event.ctrlKey || event.metaKey) {
            const key = event.key.toLowerCase();
            if (key === 's') {
                event.preventDefault();
                const button = document.getElementById('confirm-btn');
                if (button && !button.disabled) {
                    button.click();
                }
            }
        }
    });

    window.addEventListener('unload', () => {
        if (window.opener && typeof window.opener.refresh === 'function') {
            window.opener.refresh();
        }
    });
</script>
@endsection
