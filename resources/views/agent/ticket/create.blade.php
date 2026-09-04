@extends('layouts.agent')

@section('content')
<script>
function expandableText(initial, label) {
    const normalizedValue = typeof initial === 'string'
        ? initial
        : (initial == null ? '' : String(initial));

    return {
        value: normalizedValue,
        expanded: false,
        label: label,
        // Replica selectedStatus do ticketForm para uso local (sem $parent)
        currentStatus: String(window.__ticketFormConfig?.selectedStatus ?? 4),
        isResolved() { return this.currentStatus == 3; },
        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.max(el.scrollHeight, 120) + 'px';
        },
        open() {
            this.expanded = true;
            this.$nextTick(() => { this.$refs.modal.focus(); });
        },
        close() {
            this.expanded = false;
            this.$nextTick(() => { this.autoResize(this.$refs.inline); });
        }
    };
}
</script>
<div class="w-full relative" x-data="ticketForm" x-cloak>
    {{-- Alertas de Erro (Estilizado Tailwind Premium) --}}
    @if($errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms class="mb-6 bg-red-50  border-l-4 border-red-500 rounded-r-xl p-4 shadow-sm flex items-start justify-between">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500 " viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800 ">Existem pendências no formulário:</h3>
                    <div class="mt-2 text-sm text-red-700 ">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <button @click="show = false" class="text-red-500 hover:text-red-700  transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    {{-- Resumo do Chamado (Hero Banner - Visível apenas em Edição) --}}
    @if(isset($ticket))
    @php
        $statusColor = $ticket->status?->color ?? '#6366f1';
    @endphp
    <div class="mb-8 p-6 lg:p-8 bg-white dark:bg-slate-800 rounded-3xl shadow-lg border border-gray-200 dark:border-slate-700 overflow-hidden relative group">
        <!-- Decorativo de Fundo -->
        <div class="absolute right-0 top-0 w-64 h-full opacity-5 dark:opacity-10 pointer-events-none transition-transform duration-1000 group-hover:scale-110 flex items-center justify-end">
            <svg class="h-48 w-48 -mr-8 text-gray-900 dark:text-white fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        </div>
        
        <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-gray-100 dark:border-slate-600">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Ticket ID</p>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-black text-gray-900 dark:text-white">#{{ $ticket->id }}</span>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-gray-100 dark:border-slate-600">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Cliente</p>
                <div class="flex items-center gap-2">
                    <span class="text-lg font-bold text-gray-900 dark:text-white truncate" title="{{ $ticket->company?->trade_name ?? 'N/A' }}">{{ $ticket->company?->trade_name ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-gray-100 dark:border-slate-600 flex flex-col justify-center items-start">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Status Operacional</p>
                <span class="px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-wider shadow-sm border"
                      style="background-color: {{ $statusColor }}22; color: {{ $statusColor }}; border-color: {{ $statusColor }}33;">
                    {{ $ticket->status->name ?? 'Aberto' }}
                </span>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-2xl p-4 border border-gray-100 dark:border-slate-600">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Contato/Solicitante</p>
                <div class="flex items-center gap-2">
                    <span class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ $ticket->contact ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Formulary Card --}}
    <div class="bg-white  rounded-3xl shadow-lg border border-gray-200  overflow-hidden"
         id="ticket-container">

        <div class="p-6 sm:p-10">
            <form method="POST" id="form" enctype="multipart/form-data" action="{{ isset($ticket) ? route('agent.ticket.update', $ticket->id) : route('agent.ticket.store') }}">
                @csrf
                @if(isset($ticket))
                    @method('PUT')
                @else
                    <input type="hidden" name="created_at" value="{{ $now }}">
                    <input type="hidden" name="hash" value="{{ $now_c }}">
                    <input type="hidden" id="elapsed_time" name="elapsed_time" value="0">
                @endif

                {{-- Bloco 1: Metadados Iniciais --}}
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8">
                    <!-- Data -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="date_display" class="block text-sm font-bold text-gray-700  mb-2">Data</label>
                        <input type="text" id="date_display" class="w-full bg-gray-100  border border-gray-200  text-gray-500  text-sm rounded-xl px-4 py-3 font-semibold cursor-not-allowed outline-none" value="{{ isset($ticket) ? $ticket->created_at->format('d/m/Y') : now()->format('d/m/Y') }}" disabled>
                    </div>
                    
                    <!-- Hora -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="hour_display" class="block text-sm font-bold text-gray-700  mb-2">Hora</label>
                        <input type="text" id="hour_display" class="w-full bg-gray-100  border border-gray-200  text-gray-500  text-sm rounded-xl px-4 py-3 font-semibold cursor-not-allowed outline-none" value="{{ isset($ticket) ? $ticket->created_at->format('H:i') : now()->format('H:i') }}" disabled>
                    </div>
                    
                    {{-- Setor: editável só para Admin (Policy::changeDepartment).
                         Variável $canChooseDepartment é definida pelo controller. --}}
                    @if($canChooseDepartment ?? false)
                    <div class="col-span-1 md:col-span-2">
                        <label for="department_id" class="block text-sm font-bold text-gray-700 mb-2">Setor</label>
                        <select name="department_id" id="department_id"
                                x-model="selectedDepartment"
                                @change="handleDepartmentChange()"
                                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">(Auto — definido pela categoria)</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(old('department_id', $ticket->department_id ?? '') == $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Deixe "(Auto)" para que o setor seja resolvido pela categoria/subcategoria do chamado.
                        </p>
                    </div>
                    @endif

                    <!-- Agente -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="agent_id" class="block text-sm font-bold text-gray-700  mb-2">Técnico/Suporte</label>
                        <select name="agent_id" id="agent_id" x-model="selectedAgent"
                                @change="handleAgentChange()"
                                class="w-full bg-gray-50  border border-gray-300  text-gray-900  text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500  outline-none transition-all appearance-none cursor-pointer">
                            <option value="">(Não Atribuído)</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}"
                                        data-department-id="{{ $agent->department_id }}"
                                        @selected(old('agent_id', $ticket->agent_id ?? (optional($prefillTicket)->agent_id ?? (Auth::guard('admin')->user()->id ?? ''))) == $agent->id)>
                                    {{ Str::upper($agent->name) }}@if($agent->department) — {{ $agent->department->name }}@endif
                                </option>
                            @endforeach
                        </select>
                        <p x-show="selectedDepartment" x-cloak class="mt-1.5 text-[11px] font-semibold text-indigo-600">
                            Encaminhar para: <span x-text="resolvedDepartmentName()"></span>
                        </p>
                    </div>

                    <!-- Cliente (Company) - Dropdown com Busca Integrada -->
                    <div class="col-span-1 md:col-span-4 flex flex-col gap-3">
                        <div class="flex items-center justify-between pb-1">
                            <label for="company_id" class="block text-sm font-bold text-gray-700">Empresa/Cliente <span class="text-red-500">*</span></label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" x-model="showInactiveClients" class="w-4 h-4 text-indigo-600 bg-gray-50 border-gray-300 rounded focus:ring-indigo-500 transition-colors cursor-pointer group-hover:border-indigo-400">
                                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider group-hover:text-indigo-600 transition-colors">Exibir Inativos</span>
                            </label>
                        </div>

                        <!-- Dropdown customizado com busca integrada -->
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <!-- Campo com dropdown de busca -->
                            <div class="relative">
                                <input
                                    type="text"
                                    x-model="searchClient"
                                    @input.debounce.300ms="filterCompaniesRemote(); open = true"
                                    @focus="open = true"
                                    placeholder="Buscar por nome, CNPJ, contato ..."
                                    class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all pr-10"
                                    autocomplete="off"
                                >
                                <svg class="absolute right-9 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <svg x-show="isLoadingRemoteCompanies" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 animate-spin h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <button x-show="searchClient.length > 0" x-cloak @click="searchClient = ''" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 transition-colors p-1" title="Limpar filtro">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <!-- Dropdown de resultados -->
                            <div x-show="open && filteredCompanies.length > 0" x-cloak class="absolute z-50 mt-1 w-full bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl shadow-lg max-h-60 overflow-y-auto" style="top: 100%;">
                                <template x-for="company in filteredCompanies" :key="company.id">
                                    <div
                                        @click="selectedCompany = String(company.id); searchClient = company.trade_name; open = false"
                                        class="px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-slate-700 cursor-pointer border-b border-gray-100 dark:border-slate-700/60 last:border-0 transition-colors"
                                    >
                                        <div class="font-medium text-gray-900 dark:text-slate-100" x-text="company.trade_name"></div>
                                        <div class="text-xs text-gray-500 dark:text-slate-400" x-text="'CNPJ: ' + (company.cnpj || '---') + ' | ' + (company.city || '')"></div>
                                    </div>
                                </template>
                                <div x-show="filteredCompanies.length === 0 && searchClient.length >= 2 && !isLoadingRemoteCompanies" class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400">
                                    Nenhuma empresa encontrada
                                </div>
                            </div>

                            <!-- Select hidden para form submission -->
                            <select name="company_id" id="company_id" x-model="selectedCompany" class="sr-only" required>
                                <option value="">Selecione o Cliente na lista...</option>
                                <template x-for="company in filteredCompanies" :key="company.id">
                                    <option :value="company.id"></option>
                                </template>
                            </select>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <template x-if="selectedCompanyObj()?.group_hash">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wide bg-amber-50 text-amber-700 border border-amber-200">
                                    Grupo: <span x-text="selectedCompanyObj().group_hash"></span>
                                </span>
                            </template>
                            <template x-if="selectedCompanyObj()?.financial_irregular">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wide bg-red-50 text-red-700 border border-red-200">
                                    Bloqueado financeiro
                                </span>
                            </template>
                        </div>

                        <template x-if="isFinancialBlocked()">
                            <div class="mt-2 p-3 rounded-xl border border-red-200 bg-red-50 text-sm text-red-700">
                                <p class="font-bold">Cliente inadimplente — abertura de chamado bloqueada.</p>
                                <p class="text-xs mt-1" x-text="selectedCompanyObj()?.observations || 'Regularizar no Financeiro para liberar.'"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="h-px bg-gray-200  w-full my-6"></div>

                <!-- Histórico via Alpine fetch -->
                <div id="history" class="mb-6">
                    <div id="resultado-historico" class="w-full" x-html="historyHtml"></div>
                </div>

                <!-- Tarefa Vinculada (Engenharia UI para Datagrid/Card) -->
                @if(isset($ticket) && $ticket->task)
                    <div class="mb-8 overflow-hidden rounded-2xl border border-orange-200  shadow-sm transition-all focus-within:ring-4 focus-within:ring-orange-500/20">
                        <div class="bg-gradient-to-r from-orange-500 to-amber-500 text-white px-4 py-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <h3 class="font-bold text-sm tracking-wide">Tarefa Vinculada ao Chamado</h3>
                            </div>
                        </div>
                        <div class="overflow-x-auto bg-white ">
                            <table class="min-w-full text-left whitespace-nowrap table-fixed">
                                <thead>
                                    <tr class="bg-orange-50/50  border-b border-orange-100 ">
                                        <th class="px-4 py-3 text-xs font-bold text-gray-500  uppercase">Data Abert.</th>
                                        <th class="px-4 py-3 text-xs font-bold text-gray-500  uppercase">Requisitante</th>
                                        <th class="px-4 py-3 text-xs font-bold text-gray-500  uppercase w-1/3">Título</th>
                                        <th class="px-4 py-3 text-xs font-bold text-gray-500  uppercase">Projeto</th>
                                        <th class="px-4 py-3 text-xs font-bold text-gray-500  uppercase">Entrega</th>
                                        <th class="px-4 py-3 text-xs font-bold text-gray-500  uppercase text-center">Status</th>
                                        <th class="px-4 py-3 text-xs font-bold text-gray-500  uppercase">Autor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Colors adaptados para Tailwind Classes
                                        $badgesClasses = [
                                            'pen' => 'bg-orange-100 text-orange-800   border-orange-200 ',
                                            'don' => 'bg-green-100 text-green-800   border-green-200 ',
                                            'can' => 'bg-gray-100 text-gray-800   border-gray-200 ',
                                            'rej' => 'bg-gray-800 text-white   border-gray-600 ',
                                            'pro' => 'bg-blue-100 text-blue-800   border-blue-200 ',
                                            'sto' => 'bg-red-100 text-red-800   border-red-200 ',
                                        ];
                                        $badgeClass = $badgesClasses[$ticket->task->status] ?? $badgesClasses['can'];
                                    @endphp
                                    <tr class="hover:bg-orange-50/80  transition-colors cursor-pointer outline-none focus:bg-orange-50  group"
                                        tabindex="0"
                                        onclick="popupTarefa('{{ $ticket->task_id }}')"
                                        onkeydown="if(event.key === 'Enter' || event.key === ' '){ popupTarefa('{{ $ticket->task_id }}'); event.preventDefault(); }">
                                        <td class="px-4 py-3 text-sm text-gray-600  group-hover:text-orange-700 ">{{ $ticket->task->request_at->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 ">{{ $ticket->task->user->name ?? '' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700  font-medium truncate">{{ $ticket->task->title }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 ">{{ $ticket->task->project->name ?? '' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 ">{{ $ticket->task->delivery_at?->format('d/m/Y') ?? '' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block px-3 py-1 text-xs font-black uppercase rounded-full border {{ $badgeClass }}">
                                                {{ $ticket->task->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 ">{{ $ticket->task->author->name ?? '' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Bloco 2: Informações do Sistema & Contexto Técnico --}}
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8">
                    <!-- Observação -->
                    <div class="col-span-1 md:col-span-8">
                        <label for="obs" class="block text-sm font-bold text-gray-700  mb-2">Observação Interna</label>
                        <input type="text" name="obs" id="obs" value="{{ old('obs', $ticket->obs ?? '') }}" placeholder="Notas adicionais sobre o contexto da solicitação..." class="w-full bg-gray-50  border border-gray-300  text-gray-900  text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500  outline-none transition-all placeholder-gray-400 ">
                    </div>
                    
                    <div class="col-span-1 md:col-span-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2" x-cloak>
                            <div>
                                <label for="version" class="block text-sm font-bold text-gray-700 mb-2">Versão Base</label>
                                <div class="relative">
                                    <input type="text"
                                           name="version"
                                           id="version"
                                           x-model="versionInput"
                                           list="ticket-version-options"
                                           placeholder="Ex: 01.32.01"
                                           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all placeholder-gray-400">
                                    <datalist id="ticket-version-options">
                                        <template x-for="version in availableVersionOptions()" :key="version">
                                            <option :value="version" x-text="version"></option>
                                        </template>
                                    </datalist>
                                </div>
                            </div>

                            <div>
                                <label for="release" class="block text-sm font-bold text-gray-700 mb-2">Release</label>
                                <input type="text"
                                       name="release"
                                       id="release"
                                       x-model="releaseInput"
                                       placeholder="Ex: R4"
                                       class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all placeholder-gray-400">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8">
                    <!-- Contato -->
                    <div class="col-span-1 md:col-span-4">
                        <label for="contact" class="block text-sm font-bold text-gray-700  mb-2">Nome do Contato <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <input type="text" name="contact" id="contact" value="{{ old('contact', $ticket->contact ?? (optional($prefillTicket)->contact ?? '')) }}" required placeholder="Pessoa de referência" class="w-full pl-10 pr-4 py-3 bg-gray-50  border border-gray-300  text-gray-900  text-sm rounded-xl font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500  outline-none transition-all placeholder-gray-400 ">
                        </div>
                    </div>

                    <!-- Categoria e Priority Badge Alpine.js -->
                    <div class="col-span-1 md:col-span-4">
                        <div class="flex items-center justify-between mb-2">
                            <label for="category_id" class="block text-sm font-bold text-gray-700 ">Categoria <span class="text-red-500">*</span></label>
                            <span x-show="selectedCategoryPriority" x-transition class="text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded-full" :class="getPriorityColor(selectedCategoryPriority)" x-text="getPriorityLabel(selectedCategoryPriority)"></span>
                        </div>
                        <div class="relative">
                            <select name="category_id" id="category_id" required
                                    class="w-full bg-gray-50  border border-gray-300  text-gray-900  text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500  outline-none transition-all cursor-pointer"
                                    @change="categoryChanged($event.target.value)"
                                    x-model="selectedCategory">
                                <option value="">Selecione...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}"
                                            data-priority="{{ $category->priority }}"
                                            data-icon="{{ $category->getPriorityIcon() }}"
                                            @selected(isset($ticket) && $ticket->category_id == $category->category_id)>
                                        {{ $category->getPriorityIcon() }} {{ $category->description->name ?? 'Catálogo Base' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Problema Principal (Sub-category Dynamic via Alpine) -->
                    <div class="col-span-1 md:col-span-4">
                        <label for="sub_category_id" class="block text-sm font-bold text-gray-700  mb-2">Problema Central <span class="text-red-500">*</span></label>
                        <select name="sub_category_id" id="sub_category_id" x-model="selectedSubCategory" required class="w-full bg-gray-50  border border-gray-300  text-gray-900  text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500  outline-none transition-all cursor-pointer">
                            <option value=""
                                    x-text="isLoadingSub ? 'Carregando subcategorias...' : (subCategories.length === 0 ? 'Aguardando seleção de categoria...' : '(Selecione o alvo técnico)')">
                            </option>
                            <template x-for="sub in subCategories" :key="sub.id">
                                <option :value="sub.id" x-text="sub.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Módulo Contratado (visível apenas quando a empresa tem módulos cadastrados) --}}
                <div x-show="selectedCompanyModules().length > 0" x-cloak class="mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <div class="col-span-1 md:col-span-5">
                            <label for="module_id" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                Módulo Contratado
                                <span class="ml-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">(opcional)</span>
                            </label>
                            <select name="module_id" id="module_id" x-model="selectedModule"
                                    class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-gray-100 text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/30 focus:border-indigo-500 dark:focus:border-indigo-500 outline-none transition-all cursor-pointer">
                                <option value="">Selecione o módulo...</option>
                                <template x-for="mod in selectedCompanyModules()" :key="mod.id">
                                    <option :value="mod.id" x-text="mod.name"></option>
                                </template>
                            </select>
                            <p class="mt-2 text-xs text-gray-500" x-show="selectedModuleRatTemplateName()">
                                Checklist padrão vinculado:
                                <span class="font-semibold text-gray-700" x-text="selectedModuleRatTemplateName()"></span>
                            </p>
                            <p class="mt-2 text-xs text-gray-500" x-show="showRatTemplateSelector()">
                                Este módulo não possui checklist padrão. Selecione abaixo o template RAT técnico do atendimento.
                            </p>
                            <input type="hidden" name="rat_module_id" :value="selectedRatModule || ''">
                        </div>

                        <div class="col-span-1 md:col-span-7" x-show="selectedModule && showRatTemplateSelector()" x-cloak>
                            <label for="rat_module_id_select" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                Checklist RAT Técnico
                            </label>
                            <select id="rat_module_id_select"
                                    x-model="selectedRatModule"
                                    @change="handleSelectedRatTemplateChange($el.value)"
                                    class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-gray-100 text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/30 focus:border-indigo-500 dark:focus:border-indigo-500 outline-none transition-all cursor-pointer">
                                <option value="">Selecione o checklist técnico...</option>
                                <template x-for="rat in selectedCompanyRatModules()" :key="rat.id">
                                    <option :value="rat.id" x-text="formatRatModuleOption(rat)"></option>
                                </template>
                            </select>
                            <p class="mt-2 text-xs text-gray-500">
                                O catálogo segue a mesma configuração técnica usada em agendamentos e RATs do cliente.
                            </p>
                        </div>
                    </div>
                </div>

                <div x-show="shouldShowRatFeedback()" x-cloak x-transition class="mb-6">
                    <div class="rounded-2xl border px-4 py-3"
                         :class="ratFeedbackTone()">
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0"
                                 :class="ratFeedbackIconTone()"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold" :class="ratFeedbackTextTone()" x-text="ratFeedbackMessage"></p>
                                <p class="mt-1 text-xs" :class="ratFeedbackSubtextTone()" x-show="selectedRatModuleName()" x-text="'Template atual: ' + selectedRatModuleName()"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RAT: checklist dinâmico por módulo --}}
                <div x-show="ratHasItems() || isLoadingRat" x-cloak x-transition class="mb-8">
                    <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50/60 dark:bg-amber-900/10 overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-3 border-b border-amber-200 dark:border-amber-800">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span class="text-sm font-bold text-amber-800 dark:text-amber-300">Checklist RAT — Relatório de Atendimento Técnico</span>
                            </div>
                            <div x-show="isLoadingRat" class="flex items-center gap-1 text-xs text-amber-500">
                                <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Carregando...
                            </div>
                        </div>

                        <div class="px-5 py-4 space-y-6">
                            <template x-for="group in ratGroups" :key="group.name">
                                <div>
                                    <p class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-widest mb-3" x-text="group.name"></p>
                                    <div class="space-y-2">
                                        <template x-for="item in group.items" :key="item.id">
                                            <div>
                                                {{-- Checkbox type --}}
                                                <template x-if="item.type === 'checkbox'">
                                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-amber-100 dark:border-amber-800 bg-white dark:bg-slate-800 hover:bg-amber-50/50 dark:hover:bg-slate-700/60 cursor-pointer transition-colors">
                                                        <input type="checkbox"
                                                               :name="'rat_responses[' + item.id + ']'"
                                                               :value="'1'"
                                                               :checked="ratResponses[item.id] == '1'"
                                                               :aria-label="item.label || item.name"
                                                               @change="ratResponses[item.id] = $event.target.checked ? '1' : '0'"
                                                               class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"/>
                                                        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="item.label || item.name">Item do checklist RAT</span>
                                                    </label>
                                                </template>

                                                {{-- Text / default type --}}
                                                <template x-if="item.type !== 'checkbox'">
                                                    <label class="block p-3 rounded-xl border border-amber-100 dark:border-amber-800 bg-white dark:bg-slate-800">
                                                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" x-text="item.label || item.name">Campo do checklist RAT</span>
                                                        <input type="text"
                                                               :name="'rat_responses[' + item.id + ']'"
                                                               :value="ratResponses[item.id] || ''"
                                                               :aria-label="item.label || item.name"
                                                               @input="ratResponses[item.id] = $event.target.value"
                                                               class="w-full bg-gray-50 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 outline-none transition-all"
                                                               placeholder="Informe..."/>
                                                    </label>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- EasyWiki: artigos relacionados à categoria selecionada --}}
                <div x-show="wikiArticles.length > 0 || isLoadingWiki" x-transition class="mb-8">
                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-3 border-b border-indigo-100">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <span class="text-sm font-bold text-indigo-700">EasyWiki — Artigos desta categoria</span>
                                <span x-show="!isLoadingWiki" class="text-xs font-semibold text-indigo-500 bg-indigo-100 px-2 py-0.5 rounded-full" x-text="wikiArticles.length + (wikiArticles.length === 1 ? ' artigo' : ' artigos')"></span>
                            </div>
                            <div x-show="isLoadingWiki" class="flex items-center gap-1 text-xs text-indigo-400">
                                <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                Carregando...
                            </div>
                        </div>
                        <div class="divide-y divide-indigo-100">
                            <template x-for="article in wikiArticles" :key="article.id">
                                <div class="px-5 py-3">
                                    <button type="button"
                                            @click="toggleWikiArticle(article.id)"
                                            class="w-full flex items-center justify-between text-left gap-3 group">
                                        <span class="text-sm font-semibold text-indigo-800 group-hover:text-indigo-600 transition-colors" x-text="article.title"></span>
                                        <svg class="w-4 h-4 text-indigo-400 flex-shrink-0 transition-transform"
                                             :class="{ 'rotate-180': wikiExpanded === article.id }"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div x-show="wikiExpanded === article.id" x-transition class="mt-3 space-y-3">
                                        <div x-show="article.problem">
                                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Problema</p>
                                            <p class="text-sm text-gray-700 whitespace-pre-line" x-text="article.problem"></p>
                                        </div>
                                        <div x-show="article.solution">
                                            <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1">Solução</p>
                                            <p class="text-sm text-gray-700 whitespace-pre-line" x-text="article.solution"></p>
                                        </div>
                                        <div x-show="article.tags">
                                            <p class="text-xs text-indigo-400" x-text="'Tags: ' + article.tags"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Bloco 3: Textareas (Problema e Solução) --}}
                <div class="space-y-6 mb-8">

                    {{-- Detalhamento do Problema --}}
                    <div x-data="expandableText({{ json_encode(old('trouble', $ticket->trouble ?? '')) }}, 'Detalhamento do Problema')"
                         @ticket-status-changed.window="currentStatus = $event.detail.status">
                        <div class="flex items-center justify-between mb-2">
                            <label for="trouble" class="block text-sm font-bold text-gray-700 ">Detalhamento do Problema</label>
                            <button type="button" @click="open()"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-500 hover:text-indigo-600 transition-opacity hover:opacity-80">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                                Expandir
                            </button>
                        </div>
                        <textarea name="trouble" id="trouble" x-ref="inline" x-model="value"
                                  @input="autoResize($refs.inline)" x-init="autoResize($refs.inline)"
                                  placeholder="Descreva os processos que levaram ao problema..."
                                  :required="isResolved()"
                                  style="min-height:120px"
                                  class="w-full bg-gray-50  border border-gray-300  text-gray-900  text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500  outline-none transition-all placeholder-gray-400  resize-none overflow-hidden"></textarea>
                        <p class="text-right text-[11px] mt-1 text-gray-400" x-text="value.length + ' caracteres'"></p>

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
                             @keydown.escape.window="close()"
                             x-cloak>
                            <div class="flex items-center justify-between px-5 py-3 shrink-0" style="border-bottom:1px solid #1e3a5f">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="text-xs font-bold uppercase tracking-wider" style="color:#94a3b8" x-text="label"></span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-xs text-slate-500" x-text="value.length + ' caracteres'"></span>
                                    <button type="button" @click="close()"
                                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-opacity hover:opacity-80">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Fechar (Esc)
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 p-4 overflow-hidden">
                                <textarea x-ref="modal" x-model="value"
                                          :placeholder="'Descreva os processos que levaram ao problema...'"
                                          class="w-full h-full px-5 py-4 rounded-xl text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                                          style="background:#0f172a;color:#e2e8f0;border:1px solid #1e3a5f;font-family:inherit"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Escopo da Solução Aplicada --}}
                    <div x-data="expandableText({{ json_encode(old('solution', $ticket->solution ?? '')) }}, 'Escopo da Solução Aplicada')"
                         @ticket-status-changed.window="currentStatus = $event.detail.status">
                        <div class="flex items-center justify-between mb-2">
                            <label for="solution" class="block text-sm font-bold text-gray-700 ">Escopo da Solução Aplicada</label>
                            <button type="button" @click="open()"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-500 hover:text-indigo-600 transition-opacity hover:opacity-80">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                                Expandir
                            </button>
                        </div>
                        <textarea name="solution" id="solution" x-ref="inline" x-model="value"
                                  @input="autoResize($refs.inline)" x-init="autoResize($refs.inline)"
                                  placeholder="Descreva tecnicamente como o caso foi resolvido..."
                                  :required="isResolved()"
                                  style="min-height:120px"
                                  class="w-full bg-gray-50  border border-gray-300  text-gray-900  text-sm rounded-xl px-4 py-3 font-medium focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500  outline-none transition-all placeholder-gray-400  resize-none overflow-hidden"></textarea>
                        <p class="text-right text-[11px] mt-1 text-gray-400" x-text="value.length + ' caracteres'"></p>

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
                             @keydown.escape.window="close()"
                             x-cloak>
                            <div class="flex items-center justify-between px-5 py-3 shrink-0" style="border-bottom:1px solid #1e3a5f">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="text-xs font-bold uppercase tracking-wider" style="color:#94a3b8" x-text="label"></span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-xs text-slate-500" x-text="value.length + ' caracteres'"></span>
                                    <button type="button" @click="close()"
                                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-opacity hover:opacity-80">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Fechar (Esc)
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 p-4 overflow-hidden">
                                <textarea x-ref="modal" x-model="value"
                                          :placeholder="'Descreva tecnicamente como o caso foi resolvido...'"
                                          class="w-full h-full px-5 py-4 rounded-xl text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                                          style="background:#0f172a;color:#e2e8f0;border:1px solid #1e3a5f;font-family:inherit"></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Bloco 4: Zona de Uploads e Anexos --}}
                <div class="mb-8 p-6 rounded-2xl border transition-colors duration-200"
                     :class="isDragging ? 'bg-indigo-50 border-indigo-400' : 'bg-gray-50 border-gray-200'"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="handleDrop($event)">
                    <div class="flex items-center justify-between mb-4">
                        <label for="fileInput" class="block text-sm font-bold text-gray-700">Evidências e Anexos</label>
                        <span x-show="selectedFiles.length > 0" x-transition class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full" x-text="selectedFiles.length + ' arquivo(s) selecionado(s)'"></span>
                    </div>
                    <div id="attachments-wrapper" class="flex flex-wrap gap-4 items-start">

                        <!-- Botão adicionar -->
                        <button type="button" @click="$refs.fileInput.click()"
                                class="flex flex-col items-center justify-center w-36 h-32 border-2 border-dashed border-indigo-300 rounded-2xl bg-white hover:bg-indigo-50 transition-colors group focus:outline-none focus:ring-4 focus:ring-indigo-500/20 flex-shrink-0 cursor-pointer">
                            <div class="p-3 bg-indigo-50 rounded-full mb-2 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-indigo-600">Adicionar Files</span>
                            <input type="file" id="fileInput" name="arquivo[]" x-ref="fileInput" class="hidden" multiple @change="handleFileSelect($event)">
                        </button>

                        <!-- Arquivos novos selecionados (Preview Alpine) -->
                        <template x-for="(item, index) in selectedFiles" :key="index">
                            <div class="flex flex-col w-36 h-32 bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow group flex-shrink-0 relative">

                                <!-- Área de preview -->
                                <div class="flex-grow flex items-center justify-center overflow-hidden relative">
                                    {{-- Imagem: thumbnail real --}}
                                    <template x-if="item.fileType === 'image'">
                                        <img :src="item.previewUrl" :alt="item.file.name" class="w-full h-full object-cover">
                                    </template>
                                    {{-- PDF --}}
                                    <template x-if="item.fileType === 'pdf'">
                                        <div class="flex flex-col items-center justify-center w-full h-full bg-red-50 p-3">
                                            <svg class="w-10 h-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <span class="text-[10px] font-bold text-red-500 mt-1">PDF</span>
                                        </div>
                                    </template>
                                    {{-- Vídeo --}}
                                    <template x-if="item.fileType === 'video'">
                                        <div class="flex flex-col items-center justify-center w-full h-full bg-purple-50 p-3">
                                            <svg class="w-10 h-10 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.723v6.554a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            <span class="text-[10px] font-bold text-purple-500 mt-1">VÍD</span>
                                        </div>
                                    </template>
                                    {{-- Planilha --}}
                                    <template x-if="item.fileType === 'spreadsheet'">
                                        <div class="flex flex-col items-center justify-center w-full h-full bg-emerald-50 p-3">
                                            <svg class="w-10 h-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18M10 3v18M14 3v18M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                                            <span class="text-[10px] font-bold text-emerald-600 mt-1">XLS</span>
                                        </div>
                                    </template>
                                    {{-- Word --}}
                                    <template x-if="item.fileType === 'word'">
                                        <div class="flex flex-col items-center justify-center w-full h-full bg-blue-50 p-3">
                                            <svg class="w-10 h-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="text-[10px] font-bold text-blue-600 mt-1">DOC</span>
                                        </div>
                                    </template>
                                    {{-- Arquivo compactado --}}
                                    <template x-if="item.fileType === 'archive'">
                                        <div class="flex flex-col items-center justify-center w-full h-full bg-amber-50 p-3">
                                            <svg class="w-10 h-10 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                            <span class="text-[10px] font-bold text-amber-500 mt-1">ZIP</span>
                                        </div>
                                    </template>
                                    {{-- Genérico --}}
                                    <template x-if="item.fileType === 'generic'">
                                        <div class="flex flex-col items-center justify-center w-full h-full bg-gray-50 p-3">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        </div>
                                    </template>
                                </div>

                                <!-- Info rodapé -->
                                <div class="p-2 border-t border-gray-100 bg-white text-center">
                                    <p class="text-[10px] font-bold text-gray-600 truncate" x-text="item.file.name" :title="item.file.name"></p>
                                    <p class="text-[9px] text-gray-400" x-text="formatFileSize(item.file.size)"></p>
                                </div>

                                <!-- Overlay hover: Visualizar + Remover -->
                                <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <template x-if="item.previewUrl">
                                        <a :href="item.previewUrl" target="_blank" rel="noopener" title="Visualizar"
                                           class="p-2 bg-white text-indigo-600 rounded-full hover:scale-110 hover:bg-indigo-50 transition-all shadow-sm">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    </template>
                                    <button type="button" @click="removeFile(index)" title="Remover arquivo"
                                            class="p-2 bg-white text-red-600 rounded-full hover:scale-110 hover:bg-red-50 transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Attachments existentes (Edição) -->
                        @if(isset($attachments) && count($attachments) > 0)
                            @foreach($attachments as $at)
                                <div id="attachment-{{ $at->id }}" class="flex flex-col w-36 h-32 bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow group flex-shrink-0 relative">
                                    <div class="flex-grow flex items-center justify-center bg-gray-50 p-3">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="p-2 border-t border-gray-100 bg-white text-center">
                                        <p class="text-[10px] font-bold text-gray-600 truncate" title="{{ $at->name }}">{{ $at->name }}</p>
                                    </div>
                                    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                                        <a href="{{ route('agent.tickets.attachments.download', $at->id) }}" target="_blank" title="Baixar anexo" class="p-2 bg-white text-indigo-600 rounded-full hover:scale-110 hover:bg-indigo-50 transition-all shadow-sm">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        </a>
                                        <button type="button" @click="removeAttachment('{{ $at->id }}')" title="Remover" class="p-2 bg-white text-red-600 rounded-full hover:scale-110 hover:bg-red-50 transition-all shadow-sm">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        <!-- Placeholder quando vazio -->
                        <div x-show="selectedFiles.length === 0 && !{{ isset($attachments) && count($attachments) > 0 ? 'true' : 'false' }}"
                             class="flex-grow h-32 flex items-center justify-center border-2 border-dashed rounded-2xl transition-colors duration-200"
                             :class="isDragging ? 'border-indigo-400 bg-indigo-50/60' : 'border-gray-200 bg-gray-50/50'">
                            <p class="text-xs font-semibold uppercase tracking-widest transition-colors duration-200"
                               :class="isDragging ? 'text-indigo-500' : 'text-gray-400'">
                                <span x-show="isDragging">Solte os arquivos aqui</span>
                                <span x-show="!isDragging">Nenhum anexo vinculado</span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Bloco 5: Finalização (Status, Origem, Recorrente) --}}
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8">
                    <!-- Decisão de Status -->
                    <div class="col-span-1 md:col-span-4">
                        <label for="status_id" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Decisão de Status</label>
                        <div class="relative group">
                            @php
                                $visitaTecnicaStatus = $statuses->firstWhere('requires_schedule', true);
                                $solicitacaoStatus   = $statuses->first(fn ($status) => $status->name === 'Solicitação');
                                $currentStatusId     = (int) old('status_id', $ticket->status_id ?? 4);
                                $prioritizedStatusIds = array_values(array_filter([
                                    4,
                                    2,
                                    $visitaTecnicaStatus?->id,
                                    3,
                                    5,
                                    $solicitacaoStatus?->id,
                                ]));
                                $statusOptions = collect($prioritizedStatusIds)
                                    ->map(fn ($statusId) => $statuses->firstWhere('id', $statusId))
                                    ->filter()
                                    ->merge(
                                        $statuses
                                            ->reject(fn ($status) => in_array($status->id, $prioritizedStatusIds, true))
                                            ->sortBy('name')
                                    )
                                    ->values();
                            @endphp
                            <select name="status_id" id="status_id" x-model="selectedStatus" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-gray-100 text-sm rounded-xl px-4 py-3.5 font-black uppercase tracking-wider focus:ring-4 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/30 focus:border-indigo-500 dark:focus:border-indigo-500 hover:border-indigo-300 dark:hover:border-slate-500 outline-none transition-all cursor-pointer shadow-sm">
                                @foreach($statusOptions as $status)
                                    <option value="{{ $status->id }}"
                                            @selected($currentStatusId === (int) $status->id)
                                            @if($status->requires_agent) :disabled="!selectedAgent" @endif
                                            style="color: {{ $status->color ?? '#374151' }}">
                                        {{ Str::upper($status->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Canal de Origem -->
                    <div class="col-span-1 md:col-span-4">
                        <label for="origin_id" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Canal de Origem</label>
                        <div class="relative group">
                            <select name="origin_id" id="origin_id" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-gray-900 dark:text-gray-100 text-sm rounded-xl px-4 py-3.5 font-bold uppercase focus:ring-4 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/30 focus:border-indigo-500 dark:focus:border-indigo-500 hover:border-indigo-300 dark:hover:border-slate-500 outline-none transition-all cursor-pointer shadow-sm">
                                @foreach($origins as $origin)
                                    <option value="{{ $origin->id }}" @selected(old('origin_id', $ticket->origin_id ?? (optional($prefillTicket)->origin_id ?? '')) == $origin->id)>{{ Str::upper($origin->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Problema Recorrente -->
                    <div class="col-span-1 md:col-span-4 flex flex-col justify-end">
                        <label class="relative flex items-center p-3.5 gap-4 cursor-pointer rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-orange-50/50 dark:hover:bg-slate-700/80 transition-all duration-300 group overflow-hidden shadow-sm">
                            <input type="hidden" name="is_recurring" value="0">
                            <input type="checkbox" name="is_recurring" value="1"
                                   @checked(old('is_recurring', $ticket->is_recurring ?? false))
                                   class="peer sr-only">
                                   
                            <!-- Active Background Overlay -->
                            <div class="absolute inset-0 bg-orange-50/80 dark:bg-orange-900/10 opacity-0 peer-checked:opacity-100 peer-checked:border-orange-400 dark:peer-checked:border-orange-500 border-2 border-transparent transition-all pointer-events-none rounded-xl"></div>
                            
                            <!-- Custom Toggle Switch Base -->
                            <div class="relative z-10 w-12 h-6 bg-gray-200 dark:bg-slate-600 peer-checked:bg-orange-500 dark:peer-checked:bg-orange-500 rounded-full transition-colors duration-300 flex-shrink-0 shadow-inner
                                        after:content-[''] after:absolute after:top-[2px] after:left-[2.5px] after:w-5 after:h-5 after:bg-white after:rounded-full after:shadow after:transition-transform after:duration-300 peer-checked:after:translate-x-6">
                            </div>
                            
                            <!-- Text Content -->
                            <div class="relative z-10 flex flex-col transition-colors">
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-200 peer-checked:text-orange-700 dark:peer-checked:text-orange-400 group-hover:text-gray-900 dark:group-hover:text-white">Problema Recorrente</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 peer-checked:text-orange-600/90 dark:peer-checked:text-orange-300/90 font-medium">Sinaliza reincidência para este cliente</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Bloco 5.5: Chamado Referente --}}
                <div class="mb-8 p-5 rounded-2xl bg-gray-50/60 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 group focus-within:ring-2 focus-within:ring-indigo-500/20 dark:focus-within:ring-indigo-500/30 focus-within:border-indigo-400 dark:focus-within:border-indigo-500 focus-within:bg-white dark:focus-within:bg-slate-800 transition-all shadow-sm">
                    <label for="referenced_ticket_id" class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-white dark:bg-slate-700 rounded-lg shadow-sm border border-gray-100 dark:border-slate-600 group-focus-within:border-indigo-100 dark:group-focus-within:border-indigo-500/30 transition-colors">
                           <svg class="h-4 w-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Chamado Referente</span>
                                <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-200/70 dark:bg-slate-700 px-2.5 py-0.5 rounded-full">Opcional</span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Vincule a um número de chamado anterior para manter o histórico unificado.</p>
                        </div>
                    </label>
                    <div class="flex items-center gap-4">
                        <div class="relative max-w-sm w-full">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 dark:text-gray-500 font-black">#</div>
                            <input
                                type="number"
                                name="referenced_ticket_id"
                                id="referenced_ticket_id"
                                value="{{ old('referenced_ticket_id', $ticket->referenced_ticket_id ?? '') }}"
                                min="1"
                                placeholder="Nº do chamado (ex: 1042)"
                                class="w-full bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100 text-sm rounded-xl pl-9 pr-4 py-3 font-semibold focus:ring-4 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/30 focus:border-indigo-500 dark:focus:border-indigo-500 hover:border-gray-400 dark:hover:border-slate-500 outline-none transition-all placeholder-gray-400 dark:placeholder-gray-500 shadow-sm"
                            >
                        </div>
                    </div>
                </div>

                {{-- Rodapé / Ações --}}
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-4 pt-6 mt-6 border-t border-gray-200 dark:border-slate-700/50">
                    {{-- Ao editar volta para o chamado; ao criar volta para a tela principal --}}
                    <a href="{{ (isset($ticket) && $ticket->exists) ? route('agent.ticket.show', $ticket->id) : route('agent.index') }}"
                       class="w-full sm:w-auto px-6 py-3.5 rounded-xl font-bold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-slate-600 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white transition-all text-center flex items-center justify-center gap-2 group">
                        <svg class="h-5 w-5 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Descartar e Voltar
                    </a>
                    
                    @if(isset($ticket) && ! $ticket->hasAssignedAgent())
                        <button type="button" onclick="capturarChamado()" class="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-bold shadow-lg shadow-orange-500/30 dark:shadow-orange-900/30 active:scale-95 hover:-translate-y-0.5 transition-all text-center flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" /></svg>
                            Puxar para mim (Capturar)
                        </button>
                    @endif

                    <button type="submit" :disabled="isFinancialBlocked()" class="w-full sm:w-auto px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white rounded-xl font-black tracking-wide shadow-lg shadow-indigo-600/30 dark:shadow-indigo-900/30 hover:shadow-indigo-600/40 dark:hover:shadow-indigo-900/40 hover:-translate-y-0.5 active:scale-95 transition-all text-center disabled:opacity-60 disabled:cursor-not-allowed group flex items-center justify-center gap-2">
                        <span x-text="'Salvar Protocolo Oficial'"></span>
                        <svg class="h-5 w-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </button>
                </div>
            </form>

            @isset($ticket)
            <div class="mt-12 pt-8 border-t-2 border-dashed border-gray-200 ">
                <div class="mb-6 flex items-center gap-3">
                    <div class="p-2 bg-indigo-100  rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600 " fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 ">Interações Técnicas</h2>
                </div>
                <!-- Sub-view Attendances refatorada anteriormente -->
                @include('agent.partials.attendances', ['ticket' => $ticket, 'agents' => $task_agents ?? $agents ?? []])
            </div>
            @endisset
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Injeta config do backend para o Alpine sem poluir markup --}}
<script>
    @php
        $companiesData = $companies->map(function ($c) {
            return [
                'id'           => $c->id,
                'trade_name'   => $c->trade_name,
                'name'         => $c->name,
                'cnpj'         => $c->cnpj,
                'city'         => $c->city,
                'contact_name' => $c->contact_name,
                'is_active'    => (bool) $c->is_active,
                'financial_irregular' => (bool) $c->financial_irregular,
                'observations' => $c->observations,
                'group_hash'   => $c->group?->hash,
                'module_types' => $c->moduleTypes->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'rat_template_id' => $m->rat_module_id,
                    'rat_template_name' => $m->ratModule?->name,
                    'rat_template_project' => $m->ratModule?->project,
                    'rat_template_item_count' => (int) ($m->ratModule?->element_types_count ?? 0),
                ])->values(),
                'schedule_rat_modules' => $c->scheduleModules
                    ->filter(fn ($m) => (int) ($m->element_types_count ?? 0) > 0)
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                        'project' => $m->project,
                        'element_count' => (int) ($m->element_types_count ?? 0),
                    ])->values(),
            ];
        })->values();

        $ratModulesData = $ratModules->map(fn ($module) => [
            'id' => $module->id,
            'name' => $module->name,
            'project' => $module->project,
            'element_count' => (int) $module->element_types_count,
        ])->values();
    @endphp
    @php
        $allCatsForJs = \App\Models\Category::where('parent_id', 0)->get()->map(fn($c) => [
            'category_id' => $c->category_id,
            'name'        => $c->description->name ?? $c->name,
        ])->values();

        $existingExtras = [];
        if (isset($ticket)) {
            $existingExtras = $ticket->extraCategories()->get()->map(fn($e) => [
                'category_id'     => $e->category_id,
                'sub_category_id' => $e->sub_category_id,
            ])->values()->toArray();
        }
    @endphp
    window.__ticketFormConfig = {
        ticketId:            @json($ticket->id ?? null),
        csrf:                @json(csrf_token()),
        urlHistory:          @json(url('support/company')),
        urlCategory:         @json(url('support/settings/categories')),
        selectedCompany:     @json(old('company_id',      $ticket->company_id ?? (optional($prefillTicket)->company_id ?? ''))),
        selectedCategory:    @json(old('category_id',     $ticket->category_id ?? (optional($prefillTicket)->category_id ?? ''))),
        selectedSubCategory: @json(old('sub_category_id', $ticket->sub_category_id ?? (optional($prefillTicket)->sub_category_id ?? ''))),
        selectedStatus:      @json(old('status_id',       $ticket->status_id ?? 4)),
        requiresScheduleIds: @json($statuses->where('requires_schedule', true)->pluck('id')->values()),
        requiresAgentIds:    @json($statuses->where('requires_agent', true)->pluck('id')->values()),
        selectedAgent:       @json(old('agent_id',        $ticket->agent_id ?? (optional($prefillTicket)->agent_id ?? (Auth::guard('admin')->user()->id ?? '')))),
        selectedDepartment:  @json(old('department_id',   $ticket->department_id ?? (optional($prefillTicket)->department_id ?? (Auth::guard('admin')->user()->department_id ?? '')))),
        departments:         @json($departments ?? []),
        initialVersion:      @json(old('version', $ticket->version ?? '')),
        initialRelease:      @json(old('release', $ticket->release ?? '')),
        companies:           @json($companiesData),
        technicalContexts:   @json($companyTechnicalContexts ?? []),
        versionCatalog:      @json($ticketVersionCatalog ?? []),
        selectedModule:      @json(old('module_id', $ticket->module_id ?? '')),
        selectedRatModule:   @json(old('rat_module_id', $ticket->rat_module_id ?? '')),
        urlRatElements:      @json(url('api/v1/records/elements')),
        ratResponses:        @json(old('rat_responses', $ticket->rat_responses ?? [])),
        availableRatModules: @json($ratModulesData),
        isEdit:              @json(isset($ticket)),
        allCategories:       @json($allCatsForJs),
        existingExtras:         @json($existingExtras),
        urlCompanySearch:  @json(route('agent.api.v1.companies.search')),
    };

    // ── Extra Categories Manager ──────────────────────────────────────────
    function extraCategoriesManager() {
        return {
            pairs: [],
            allCategories: window.__ticketFormConfig.allCategories || [],
            _uid: 0,

            init() {
                const existing = window.__ticketFormConfig.existingExtras || [];
                existing.forEach(e => this._addPairWithData(e.category_id, e.sub_category_id));
            },

            addPair() {
                this.pairs.push({ uid: ++this._uid, category_id: '', sub_category_id: '', subs: [], loadingSubs: false });
            },

            _addPairWithData(catId, subId) {
                const pair = { uid: ++this._uid, category_id: String(catId), sub_category_id: String(subId), subs: [], loadingSubs: false };
                this.pairs.push(pair);
                if (catId) this.loadExtraSubs(pair, subId);
            },

            removePair(idx) {
                this.pairs.splice(idx, 1);
            },

            loadExtraSubs(pair, preSelectSubId = null) {
                if (!pair.category_id) { pair.subs = []; pair.sub_category_id = ''; return; }
                pair.loadingSubs = true;
                pair.subs = [];
                pair.sub_category_id = '';
                const url = window.__ticketFormConfig.urlCategory + '/' + pair.category_id + '/children';
                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        pair.subs = data || [];
                        if (preSelectSubId) pair.sub_category_id = String(preSelectSubId);
                        pair.loadingSubs = false;
                    })
                    .catch(() => { pair.loadingSubs = false; });
            },
        };
    }
</script>
@endpush
