<div
    x-show="showViewModal"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    @keydown.escape.window="closeViewModal()">
    
    {{-- Backdrop --}}
    <div
        @click="closeViewModal()"
        class="fixed inset-0 bg-black bg-opacity-70 transition-opacity">
    </div>

    {{-- Modal Container --}}
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- Modal Content --}}
        <div
            @click.away="closeViewModal()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-[#0f172a] rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-700">
            
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">
                    Detalhes da Categoria
                </h3>
                <button
                    @click="closeViewModal()"
                    type="button"
                    class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-6 font-sans">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- ID --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">ID</label>
                        <p class="text-sm font-medium text-white" x-text="'#' + (viewData.id || 'N/A')"></p>
                    </div>

                    {{-- Nome --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nome</label>
                        <p class="text-sm font-medium text-white" x-text="viewData.name || 'N/A'"></p>
                    </div>

                    {{-- Prioridade --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Prioridade</label>
                        <span :class="{
                            'bg-red-500/20 text-red-400 border-red-500/30': viewData.priority === 'urgent',
                            'bg-yellow-500/20 text-yellow-400 border-yellow-500/30': viewData.priority === 'high',
                            'bg-green-500/20 text-green-400 border-green-500/30': viewData.priority === 'low'
                        }" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wide"
                           x-text="viewData.priority_label || 'Baixa'"></span>
                    </div>

                    {{-- Categoria --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Categoria</label>
                        <p class="text-sm font-medium">
                            <span x-show="!viewData.parent_id || viewData.parent_id == 0" class="text-white">—</span>
                            <span x-show="viewData.parent_id && viewData.parent_id != 0" class="text-blue-400" x-text="'ID: #' + viewData.parent_id"></span>
                        </p>
                    </div>

                    {{-- Descrição (Full Width) --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Descrição</label>
                        <div class="w-full px-3 py-3 bg-[#1e293b] rounded border border-slate-700 text-sm text-gray-300">
                            <span x-text="viewData.description || 'Sem descrição'"></span>
                        </div>
                    </div>

                    {{-- Permalink (Full Width if needed, or col-span-2) --}}
                    <div class="md:col-span-2" x-show="viewData.permalink">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Permalink</label>
                        <p class="text-sm font-mono text-gray-300" x-text="viewData.permalink || 'N/A'"></p>
                    </div>

                    {{-- Subcategorias --}}
                    <div class="md:col-span-2" x-show="viewData.children && viewData.children.length > 0">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                            Subcategorias (<span x-text="viewData.children ? viewData.children.length : 0"></span>)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="child in viewData.children" :key="child.category_id">
                                <div class="bg-[#1e293b] rounded px-3 py-2 border border-slate-700 flex items-center justify-between">
                                    <span class="text-sm text-gray-200" x-text="child.description?.name || 'Sem nome'"></span>
                                    <span class="text-xs text-gray-500 font-mono" x-text="'#' + child.category_id"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-[#0f172a] border-t border-slate-700 flex justify-end gap-3">
                <button
                    type="button"
                    @click="closeViewModal()"
                    class="px-4 py-2 bg-[#334155] text-white rounded hover:bg-[#475569] transition-colors font-medium text-sm">
                    Fechar
                </button>
                <button
                    type="button"
                    @click="closeViewModal(); openEditModal(viewData)"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors font-medium text-sm">
                    Editar Dados
                </button>
            </div>
        </div>
    </div>
</div>
