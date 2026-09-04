<div
    x-show="showViewModal"
    x-transition
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    @keydown.escape.window="closeViewModal()">

    <div @click="closeViewModal()" class="fixed inset-0 bg-black bg-opacity-50"></div>

    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg relative">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Visualizar Categoria</h3>
                <button @click="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-6 space-y-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">ID</p>
                    <p class="text-sm font-medium text-gray-800" x-text="selectedCategory?.category_id"></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Nome</p>
                    <p class="text-sm font-medium text-gray-800" x-text="selectedCategory?.name || '-'"></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Categoria</p>
                    <p class="text-sm text-gray-700" x-text="selectedCategory?.parent_name || '—'"></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Prioridade</p>
                    <p class="text-sm text-gray-700" x-text="selectedCategory?.priority || '-'"></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Permalink</p>
                    <p class="text-sm text-gray-700 font-mono break-all" x-text="selectedCategory?.permalink || '-'"></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Descrição</p>
                    <p class="text-sm text-gray-700" x-text="selectedCategory?.description || '-'"></p>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-2">
                <button @click="closeViewModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md">Fechar</button>
                <button @click="closeViewModal(); openEditModal(selectedCategory)" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md">Editar</button>
            </div>
        </div>
    </div>
</div>
