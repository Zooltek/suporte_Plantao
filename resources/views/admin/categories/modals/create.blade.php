<div
    x-show="showCreateModal"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    @keydown.escape.window="closeCreateModal()">

    <div
        @click="closeCreateModal()"
        class="fixed inset-0 bg-black bg-opacity-50 transition-opacity">
    </div>

    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div
            x-data="createCategoryForm()"
            @click.away="$dispatch('modal-close-create')"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span x-text="modalTitle"></span>
                        </h3>
                        <p class="mt-1 text-xs text-blue-100" x-text="modeDescription"></p>
                    </div>
                    <button
                        @click="$dispatch('modal-close-create')"
                        type="button"
                        class="text-white hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form @submit.prevent="submitForm()" class="px-6 py-6 space-y-4">
                <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2">
                    <p class="text-xs font-semibold text-blue-700" x-text="modeBadgeLabel"></p>
                </div>

                <div>
                    <label for="create-name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nome <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="create-name"
                        x-model="formData.name"
                        required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        :class="errors.name ? 'border-red-500' : 'border-gray-300'"
                        placeholder="Digite o nome">
                    <p x-show="errors.name" x-text="errors.name" class="mt-1 text-sm text-red-500"></p>
                </div>

                <div x-show="isSubcategoryMode">
                    <label for="create-parent" class="block text-sm font-medium text-gray-700 mb-1">
                        Categoria <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="create-parent"
                        x-ref="parentSelect"
                        x-model="formData.parent_id"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        :class="errors.parent_id ? 'border-red-500' : 'border-gray-300'">
                        <option value="">Selecione uma categoria</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->category_id }}" data-priority="{{ $parent->priority }}">
                                {{ $parent->description->name ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Subcategorias não podem ter outras subcategorias.
                    </p>
                    <p x-show="errors.parent_id" x-text="errors.parent_id" class="mt-1 text-sm text-red-500"></p>
                </div>

                <div>
                    <label for="create-priority" class="block text-sm font-medium text-gray-700 mb-1">
                        Prioridade <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="create-priority"
                        x-model="formData.priority"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="low">Baixa (peso 1)</option>
                        <option value="high">Alta (peso 3)</option>
                        <option value="urgent">Urgente (peso 5)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500" x-show="isSubcategoryMode">
                        Ao selecionar uma categoria, a prioridade inicial pode ser herdada dela.
                    </p>
                </div>

                <div>
                    <label for="create-department" class="block text-sm font-medium text-gray-700 mb-1">
                        Departamento responsável
                    </label>
                    <select
                        id="create-department"
                        x-model="formData.department_id"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        :class="errors.department_id ? 'border-red-500' : 'border-gray-300'">
                        <option value="">Nenhum (chamados ficam visíveis a todos os setores)</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Define para qual setor os chamados desta categoria serão roteados.
                    </p>
                    <p x-show="errors.department_id" x-text="errors.department_id" class="mt-1 text-sm text-red-500"></p>
                </div>

                <div>
                    <label for="create-permalink" class="block text-sm font-medium text-gray-700 mb-1">
                        Permalink (opcional)
                    </label>
                    <input
                        type="text"
                        id="create-permalink"
                        x-model="formData.permalink"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono"
                        :class="errors.permalink ? 'border-red-500' : 'border-gray-300'"
                        placeholder="ex: suporte-tecnico">
                    <p class="mt-1 text-xs text-gray-500">Se vazio, será gerado automaticamente com base no nome.</p>
                    <p x-show="errors.permalink" x-text="errors.permalink" class="mt-1 text-sm text-red-500"></p>
                </div>

                <div>
                    <label for="create-description" class="block text-sm font-medium text-gray-700 mb-1">
                        Descrição (opcional)
                    </label>
                    <textarea
                        id="create-description"
                        x-model="formData.description"
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Contexto da categoria"></textarea>
                    <p x-show="errors.description" x-text="errors.description" class="mt-1 text-sm text-red-500"></p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        @click="$dispatch('modal-close-create')"
                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200">
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="loading"
                        :class="loading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                        class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg transition-colors duration-200 shadow-sm">
                        <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="submitLabel"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
