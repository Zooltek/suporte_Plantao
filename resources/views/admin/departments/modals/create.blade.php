{{-- Modal de Criação de Departamento --}}
<div x-show="showCreateModal" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        {{-- Overlay --}}
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showCreateModal = false"></div>

        {{-- Modal --}}
        <div class="inline-block w-full max-w-md overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Novo Departamento</h3>
                <button @click=" showCreateModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form @submit.prevent="submitCreateForm()" class="px-6 py-6 space-y-4">
                {{-- Nome --}}
                <div>
                    <label for="create_name" class="block text-sm font-medium text-gray-700">
                        Nome do Departamento <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="create_name" 
                           x-model="createForm.name" 
                           required 
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                           placeholder="Ex: Suporte Técnico">
                </div>

                {{-- Descrição --}}
                <div>
                    <label for="create_description" class="block text-sm font-medium text-gray-700">
                        Descrição <span class="text-gray-400 text-xs">(opcional)</span>
                    </label>
                    <textarea id="create_description" 
                              x-model="createForm.description" 
                              rows="3"
                              class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm resize-none"
                              placeholder="Descreva as responsabilidades deste departamento..."></textarea>
                </div>

                {{-- Botões --}}
                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="button" 
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" 
                            :disabled="loadingCreate"
                            :class="{'opacity-50 cursor-not-allowed': loadingCreate}"
                            class="px-4 py-2 text-sm font-bold text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <span x-show="!loadingCreate">Criar Departamento</span>
                        <span x-show="loadingCreate" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Criando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
