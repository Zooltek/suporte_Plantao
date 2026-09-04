{{--
    Atendimentos — Timeline com busca, filtros avançados e formulário de novo atendimento.
    Alpine: attendanceManager (resources/js/agent/tickets/attendances.js)
    O componente Alpine foi movido do inline para arquivo separado (SRP).
--}}
@php
    $attendanceAgents = ($agents ?? $agentsList ?? collect())->map(fn($a) => [
        'id' => $a->id,
        'name' => $a->name,
    ])->values();
@endphp
<div x-data="attendanceManager({{ $ticket->id }}, { agents: @js($attendanceAgents) })" x-cloak class="w-full space-y-5">
    {{-- Estilos para o input datetime-local nativo (WebKit) --}}
    <style>
        /* Esconde o placeholder feio padrão do HTML5 */
        input[type="datetime-local"]::-webkit-datetime-edit {
            color: transparent;
        }
        /* Mostra a data real APENAS quando tem valor (esconde o dd/mm/aaaa --:-- mesmo no clique) */
        input[type="datetime-local"].has-value::-webkit-datetime-edit {
            color: inherit;
        }
        
        /* Cria nosso próprio placeholder bonito e usável persistente */
        input[type="datetime-local"]:not(.has-value)::before {
            content: attr(data-placeholder);
            color: #9ca3af; /* text-gray-400 equivalent */
            width: 100%;
            position: absolute;
            pointer-events: none;
            /* centraliza verticalmente para não dar pulos no focus */
            top: 50%;
            transform: translateY(-50%);
        }
        
        /* Suporta dark mode explicitamente no custom placeholder */
        .dark input[type="datetime-local"]:not(.has-value)::before {
            color: #9ca3af; /* text-gray-400 para garantir leitura */
        }
        
        /* Torna o calendário clicável invisível sobre o nosso SVG customizado novo */
        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            position: absolute;
            right: 0;
            top: 0;
            width: 40px;
            height: 100%;
            opacity: 0; /* Invisível mas 100% clicável */
            cursor: pointer;
        }
    </style>

    @include('agent.company.partials.ticket-issues', ['ticket' => $ticket])

    {{-- ══ BARRA DE BUSCA E FILTROS ════════════════════════════════════════ --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 flex flex-col sm:flex-row gap-3 items-start sm:items-center">

            {{-- Busca por texto --}}
            <div class="relative flex-1 min-w-0">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
                <input type="text" x-model="searchText" placeholder="Buscar nas notas de atendimento..."
                       class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl
                              outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>

            {{-- Toggle filtros avançados --}}
            <button @click="showFilters = !showFilters"
                    :class="showFilters || hasActiveFilters ? 'text-indigo-600 bg-indigo-50 border-indigo-200' : 'text-gray-600 bg-gray-50 border-gray-200'"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold rounded-xl border transition-all whitespace-nowrap flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.586a1 1 0 00-.293-.707L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                Filtros
                <template x-if="hasActiveFilters">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 inline-block"></span>
                </template>
            </button>
        </div>

        {{-- Filtros avançados (colapsável) --}}
        <div x-show="showFilters" x-collapse
             class="px-5 pb-4 border-t border-gray-100 pt-3">
            <div class="flex flex-wrap gap-x-6 gap-y-3 items-end">

                {{-- Canal de retorno --}}
                <div class="space-y-1.5">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Canal de Retorno</p>
                    <div class="flex gap-3">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="filterZap" class="w-3.5 h-3.5 accent-emerald-500">
                            <span class="text-xs font-semibold text-emerald-700">WhatsApp</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="filterTel" class="w-3.5 h-3.5 accent-blue-500">
                            <span class="text-xs font-semibold text-blue-700">Telefone</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="filterCel" class="w-3.5 h-3.5 accent-orange-500">
                            <span class="text-xs font-semibold text-orange-700">Celular</span>
                        </label>
                    </div>
                </div>

                {{-- Período --}}
                <div class="space-y-1.5">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Período</p>
                    <div class="flex items-center gap-2">
                        <input type="date" x-model="filterFrom"
                               class="text-xs bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5
                                      outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        <span class="text-xs text-gray-400">até</span>
                        <input type="date" x-model="filterTo"
                               class="text-xs bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5
                                      outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                </div>

                {{-- Limpar filtros --}}
                <button @click="clearFilters" x-show="hasActiveFilters" style="display:none"
                        class="text-xs font-semibold text-red-500 hover:text-red-700 transition-colors underline">
                    Limpar filtros
                </button>
            </div>
        </div>
    </div>

    {{-- ══ CONTADOR ════════════════════════════════════════════════════════ --}}
    <div x-show="!loading" class="flex items-center text-xs text-gray-500 px-1">
        <span class="font-bold text-gray-800" x-text="filtered.length"></span>
        <span x-text="filtered.length === 1 ? '&nbsp;atendimento' : '&nbsp;atendimentos'"></span>
        <template x-if="filtered.length < attendances.length">
            <span class="ml-1 text-gray-400">(filtrado de <span x-text="attendances.length"></span>)</span>
        </template>
    </div>

    {{-- ══ TIMELINE ════════════════════════════════════════════════════════ --}}
    <div class="space-y-4">
        {{-- Loading --}}
        <div x-show="loading" class="flex flex-col items-center justify-center py-8 text-gray-400">
            <svg class="animate-spin h-8 w-8 text-indigo-500 mb-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            <span class="text-sm font-medium tracking-widest uppercase">Carregando atendimentos...</span>
        </div>

        {{-- Vazio --}}
        <div x-show="!loading && filtered.length === 0" style="display:none"
             class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-sm font-semibold text-gray-500">Nenhum atendimento encontrado</p>
            <p class="text-xs text-gray-400 mt-1"
               x-text="hasActiveFilters ? 'Tente remover ou ajustar os filtros.' : 'Nenhum atendimento registrado ainda.'">
            </p>
        </div>

        {{-- Cards --}}
        <template x-for="attendance in filtered" :key="attendance.id">
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow relative group">
                <div class="absolute left-9 top-14 bottom-[-1rem] w-px bg-gray-200 group-last:hidden"></div>
                <div class="flex items-start gap-4 relative z-10">
                    {{-- Avatar --}}
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 border-2 border-white shadow-sm">
                        <span class="text-indigo-700 font-black text-sm uppercase"
                              x-text="attendance.user?.name?.charAt(0) ?? 'S'"></span>
                    </div>

                    <div class="flex-grow min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-gray-800"
                                      x-text="attendance.user?.name ?? 'Ação de Sistema'"></span>
                                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full font-semibold"
                                      x-text="formatDate(attendance.created_at)"></span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-if="attendance.return_zap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 ring-1 ring-emerald-500/30">Zap</span>
                                </template>
                                <template x-if="attendance.return_tel">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 ring-1 ring-blue-500/30">Tel</span>
                                </template>
                                <template x-if="attendance.return_cel">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-orange-50 text-orange-700 ring-1 ring-orange-500/30">Cel</span>
                                </template>
                            </div>
                        </div>

                        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap" x-text="attendance.notes"></div>

                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                            <template x-if="attendance.return_scheduled_at">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-bold">Retorno agendado</span>
                                    <span class="text-indigo-500" x-text="formatDate(attendance.return_scheduled_at)"></span>
                                    <template x-if="attendance.return_assignee">
                                        <span class="text-indigo-500">com <span class="font-semibold" x-text="attendance.return_assignee.name"></span></span>
                                    </template>
                                </span>
                            </template>

                            <template x-if="attendance.returned_at">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-lg">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-bold">Retorno realizado</span>
                                    <span class="text-emerald-500" x-text="formatDate(attendance.returned_at)"></span>
                                    <template x-if="attendance.returned_by_user">
                                        <span class="text-emerald-500">por <span class="font-semibold" x-text="attendance.returned_by_user.name"></span></span>
                                    </template>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- ══ FORMULÁRIO DE NOVO ATENDIMENTO ═════════════════════════════════ --}}
    <form @submit.prevent="submit"
          class="bg-gray-50 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-5 sm:p-6 shadow-inner">
        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                Despacho / Observações Técnicas
            </label>
            <textarea x-model="form.notes" rows="3"
                      placeholder="Descreva a interação com o cliente..."
                      class="w-full bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100 text-sm rounded-xl px-4 py-3
                             font-medium focus:ring-4 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/30 focus:border-indigo-500 dark:focus:border-indigo-500
                             outline-none transition-all placeholder-gray-400 dark:placeholder-gray-500 resize-y" required></textarea>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-4 bg-white dark:bg-slate-800 py-2 px-4 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Via de Retorno:</span>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="form.returnZap" class="sr-only peer">
                    <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer peer-checked:bg-emerald-500 relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4 transition-colors"></div>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200 select-none">Zap</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="form.returnTel" class="sr-only peer">
                    <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer peer-checked:bg-blue-500 relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4 transition-colors"></div>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200 select-none">Tel</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="form.returnCel" class="sr-only peer">
                    <div class="w-9 h-5 bg-gray-200 dark:bg-slate-600 rounded-full peer peer-checked:bg-orange-500 relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4 transition-colors"></div>
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200 select-none">Cel</span>
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-slate-800 py-2 px-4 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm flex-1">
                <!-- TÉCNICO -->
                <div class="flex flex-col flex-1 min-w-[150px]">
                    <label for="returnUserId" class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest pl-1 mb-1">
                        Técnico
                    </label>
                    <div class="relative">
                        <select id="returnUserId" x-model="form.returnUserId"
                                class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg py-2 pl-3 pr-4 text-sm font-semibold text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-500/50 cursor-pointer hover:bg-white dark:hover:bg-slate-800 transition-colors shadow-sm outline-none">
                            <option value="">Selecione...</option>
                            <template x-for="agent in agents" :key="agent.id">
                                <option :value="agent.id" x-text="agent.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
                
                <!-- DATA E HORA DO RETORNO -->
                <div class="flex flex-col flex-1 min-w-[200px]">
                    <label for="returnAt" class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest pl-1 mb-1">
                        Data e hora do retorno
                    </label>
                    <div class="relative group flex items-center">
                        <input type="datetime-local" id="returnAt"
                               x-model="form.returnAt"
                               data-placeholder="Selecione uma data..."
                               :class="{ 'has-value': form.returnAt }"
                               class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg py-2 pl-3 pr-10 text-sm font-semibold text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-500/50 hover:bg-white dark:hover:bg-slate-800 transition-colors shadow-sm outline-none cursor-pointer"
                               style="color-scheme: light dark;">
                        <!-- Ícone SVG customizado garantido para Light e Dark em qualquer browser -->
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 dark:text-gray-500 group-focus-within:text-indigo-500 dark:group-focus-within:text-indigo-400 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" :disabled="submitting || form.notes.trim() === ''"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700
                           text-white rounded-xl font-bold shadow-md shadow-indigo-600/20 active:scale-95 transition-all
                           focus:ring-4 focus:ring-indigo-500/30 disabled:opacity-70 disabled:cursor-not-allowed">
                <svg x-show="!submitting" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <svg x-show="submitting" style="display:none" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                <span x-text="submitting ? 'Registrando...' : 'Registrar Atendimento'"></span>
            </button>
        </div>
    </form>
</div>
