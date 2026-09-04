<div x-show="showDeleteModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
     style="display: none;"
     x-cloak>

    <div @click.away="if (!loadingDelete) showDeleteModal = false"
         class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100">

        {{-- Header com Alerta --}}
        <div class="px-6 py-5 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100/80 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center flex-shrink-0 mt-0.5 border border-amber-200/50">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-bold text-gray-900 leading-tight">Exclusão e Transferência de Vínculos</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Verificando pendências do usuário <span class="font-semibold text-gray-800" x-text="deletePreviewData?.user?.name"></span>
                </p>
            </div>
            <button type="button"
                    @click="if (!loadingDelete) showDeleteModal = false"
                    :disabled="loadingDelete"
                    class="text-gray-400 hover:text-gray-600 text-lg p-1 rounded-lg transition-colors">✕</button>
        </div>

        {{-- Corpo do Modal --}}
        <div class="p-6 space-y-5">

            {{-- Cards de Resumo de Vínculos --}}
            <template x-if="deletePreviewData">
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="p-3 bg-orange-50/80 rounded-xl border border-orange-100 text-center">
                            <span class="block text-xl font-extrabold text-orange-600" x-text="deletePreviewData.active_tickets_count"></span>
                            <span class="text-[11px] font-semibold text-orange-800 leading-tight">Chamados Ativos</span>
                        </div>
                        <div class="p-3 bg-blue-50/80 rounded-xl border border-blue-100 text-center">
                            <span class="block text-xl font-extrabold text-blue-600" x-text="deletePreviewData.pending_schedules_count"></span>
                            <span class="text-[11px] font-semibold text-blue-800 leading-tight">Agendamentos</span>
                        </div>
                        <div class="p-3 bg-purple-50/80 rounded-xl border border-purple-100 text-center">
                            <span class="block text-xl font-extrabold text-purple-600" x-text="deletePreviewData.active_tasks_count"></span>
                            <span class="text-[11px] font-semibold text-purple-800 leading-tight">Tarefas Ativas</span>
                        </div>
                    </div>

                    {{-- Seletor de Novo Responsável (se houver itens ativos) --}}
                    <template x-if="deletePreviewData.total_active_items > 0">
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200/80 space-y-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                    Transferir pendências ativas para:
                                </label>
                                <select x-model="selectedTransferAgentId"
                                        :disabled="loadingDelete"
                                        class="w-full text-xs bg-white border border-gray-300 rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-amber-500 font-medium text-gray-800 shadow-sm cursor-pointer">
                                    <template x-for="agent in deletePreviewData.eligible_agents" :key="agent.id">
                                        <option :value="agent.id" x-text="`${agent.name} ${agent.id === 1 ? '(Administrador Principal)' : (agent.ticketit_admin ? '(Admin)' : '(Agente)')}`"></option>
                                    </template>
                                </select>
                            </div>
                            <p class="text-[11px] text-gray-500 leading-relaxed">
                                Os chamados em andamento, visitas técnicas e tarefas pendentes serão imediatamente atribuídos ao novo responsável selecionado com registro na auditoria.
                            </p>
                        </div>
                    </template>

                    {{-- Sem itens ativos --}}
                    <template x-if="deletePreviewData.total_active_items === 0">
                        <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center gap-2.5 text-emerald-800 text-xs font-medium">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Este usuário não possui chamados ativos ou pendências em andamento.
                        </div>
                    </template>

                    {{-- Nota de Preservação Histórica --}}
                    <div class="flex items-start gap-2.5 text-[11px] text-gray-500 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <span>Histórico preservado:</span>
                            <span class="font-bold text-gray-700" x-text="`${deletePreviewData.closed_tickets_count} chamado(s) já finalizado(s)`"></span>
                            <span>permanecerão salvos intactos para fins de relatórios e auditoria.</span>
                        </div>
                    </div>
                </div>
            </template>

        </div>

        {{-- Footer de Ações --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2.5">
            <button type="button"
                    @click="showDeleteModal = false"
                    :disabled="loadingDelete"
                    class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors shadow-sm disabled:opacity-50">
                Cancelar
            </button>
            <button type="button"
                    @click="confirmDeleteUser()"
                    :disabled="loadingDelete"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-sm shadow-red-200 disabled:opacity-50">
                <template x-if="loadingDelete">
                    <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <span x-text="loadingDelete ? 'Processando...' : (deletePreviewData?.total_active_items > 0 ? 'Transferir e Excluir' : 'Confirmar Exclusão')"></span>
            </button>
        </div>

    </div>
</div>
