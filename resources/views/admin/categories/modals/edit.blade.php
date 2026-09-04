<div
    x-show="showEditModal"
    x-transition
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    @keydown.escape.window="closeEditModal()">

    <div @click="closeEditModal()" class="fixed inset-0 bg-black bg-opacity-50"></div>

    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg relative">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Editar Categoria</h3>
                <button @click="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitEditForm()" class="px-6 py-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <input type="text" x-model="editForm.name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500" required>
                </div>

                <div x-show="!editForm.parent_id || editForm.parent_id == 0">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridade</label>
                    <select x-model="editForm.priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500">
                        <option value="low">Baixa (peso 1)</option>
                        <option value="high">Alta (peso 3)</option>
                        <option value="urgent">Urgente (peso 5)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria (opcional)</label>
                    <select x-model="editForm.parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500">
                        <option value="0">Nenhuma (sem categoria associada)</option>
                        @foreach($parents as $parent)
                            <option
                                value="{{ $parent->category_id }}"
                                x-show="String(editForm.category_id) !== '{{ $parent->category_id }}'">
                                {{ $parent->description->name ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departamento responsável</label>
                    <select x-model="editForm.department_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500">
                        <option value="">Nenhum (chamados ficam visíveis a todos os setores)</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Os chamados criados nesta categoria serão direcionados a esse setor.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Permalink</label>
                    <input type="text" x-model="editForm.permalink" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 font-mono" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea x-model="editForm.description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
            </form>

            <div class="px-6 py-4 border-t flex justify-end gap-2">
                <button @click="closeEditModal()" type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md">Cancelar</button>
                <button @click="submitEditForm()" :disabled="loadingEdit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md disabled:opacity-50">
                    <span x-show="!loadingEdit">Salvar Alterações</span>
                    <span x-show="loadingEdit">Salvando...</span>
                </button>
            </div>
        </div>
    </div>
</div>
