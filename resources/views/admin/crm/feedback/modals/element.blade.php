{{-- Modal Create/Edit Element --}}
<div x-show="modal.open"
     class="fixed inset-0 z-50 overflow-y-auto"
     x-cloak
     @keydown.escape.window="modal.open = false">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {{-- Overlay --}}
        <div x-show="modal.open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
             @click="modal.open = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- Modal Content --}}
        <div x-show="modal.open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6 border-b border-gray-200 pb-4">
                <h3 class="text-xl font-bold text-gray-900" x-text="modal.isEdit ? 'Editar Elemento' : 'Novo Elemento'"></h3>
                <button @click="modal.open = false" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form @submit.prevent="submitForm()" class="space-y-4">
                @csrf
                <input type="hidden" name="form_id" :value="modal.form.form_id">
                <input type="hidden" name="id" x-model="modal.form.id">

                {{-- Label --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="label">
                        Rótulo (Label)
                    </label>
                    <input type="text"
                           id="label"
                           name="label"
                           x-model="modal.form.label"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Ex: Nome do Usuário"
                           required>
                </div>

                {{-- Nome Técnico --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="name">
                        Nome Técnico (Name)
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           x-model="modal.form.name"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono"
                           placeholder="Ex: user_name"
                           required>
                </div>

                {{-- Tipo de Campo --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="type">
                        Tipo de Campo
                    </label>
                    <select id="type"
                            name="type"
                            x-model="modal.form.type"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            required>
                        <option value="">Selecione um tipo...</option>
                        <option value="text">Texto Curto</option>
                        <option value="textarea">Texto Longo</option>
                        <option value="email">E-mail</option>
                        <option value="phone">Telefone</option>
                        <option value="number">Número</option>
                        <option value="checkbox">Caixa de Seleção</option>
                        <option value="radio">Botão de Opção</option>
                        <option value="select">Seleção (Dropdown)</option>
                        <option value="date">Data</option>
                    </select>
                </div>

                {{-- Dados Extras --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="data">
                        Dados Extras (JSON ou CSV)
                    </label>
                    <textarea id="data"
                              name="data"
                              x-model="modal.form.data"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono"
                              placeholder='Ex: ["opção1","opção2"] ou option1,option2'
                              rows="3"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Deixe em branco se não precisar de dados extras</p>
                </div>

                {{-- Ordem de Exibição --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="sort_order">
                        Ordem de Exibição
                    </label>
                    <input type="number"
                           id="sort_order"
                           name="sort_order"
                           x-model.number="modal.form.sort_order"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           min="1"
                           value="1"
                           required>
                </div>

                {{-- Footer --}}
                <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
                    <button type="button"
                            @click="modal.open = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            :disabled="modal.submitting"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-bold text-sm text-white hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="!modal.submitting" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg x-show="modal.submitting" class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span x-text="modal.isEdit ? 'Atualizar Elemento' : 'Criar Elemento'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
