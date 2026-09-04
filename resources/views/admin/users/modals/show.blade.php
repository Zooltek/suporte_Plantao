<div x-show="showUserModal" 
     x-transition 
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
     style="display: none;" 
     x-cloak>
    
    <div @click.away="showUserModal = false" 
         class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4" 
         x-show="selectedUser">
        
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Detalhes do Usuário</h3>
            <button @click="showUserModal = false" class="text-gray-500 hover:text-gray-700 text-xl">✕</button>
        </div>

        <div class="px-6 py-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-bold uppercase text-gray-400">Nome</label>
                    <p class="mt-1 text-sm text-gray-700 font-medium" x-text="selectedUser?.name"></p>
                </div>

                <div>
                    <label class="text-xs font-bold uppercase text-gray-400">E-mail</label>
                    <p class="mt-1 text-sm text-gray-700" x-text="selectedUser?.email"></p>
                </div>

                <div>
                    <label class="text-xs font-bold uppercase text-gray-400">Acesso</label>
                    <p class="mt-1 text-sm text-gray-700" x-text="selectedUser?.role_display"></p>
                </div>

                <div>
                    <label class="text-xs font-bold uppercase text-gray-400">Departamento</label>
                    <p class="mt-1 text-sm text-blue-600 font-semibold" x-text="selectedUser?.department_display"></p>
                </div>

                <div class="sm:col-span-2">
                    <label class="text-xs font-bold uppercase text-gray-400">Observações</label>
                    <div class="mt-1 p-3 bg-gray-50 rounded border border-gray-100">
                        <p class="text-sm text-gray-600 italic" x-text="selectedUser?.obs || 'Sem observações'"></p>
                    </div>
                </div>

                <div class="sm:col-span-2 pt-3 border-t border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Permissões de Implantação</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-if="selectedUser?.deployment_admin">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-purple-50 text-purple-700 text-xs font-bold rounded-full border border-purple-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Adm. Implantação
                            </span>
                        </template>
                        <template x-if="selectedUser?.can_manage_implementation">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-50 text-orange-700 text-xs font-bold rounded-full border border-orange-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                                Ger. Implementação
                            </span>
                        </template>
                        <template x-if="!selectedUser?.deployment_admin && !selectedUser?.can_manage_implementation">
                            <span class="text-sm text-gray-400 italic">Nenhuma permissão de implantação</span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t flex justify-end gap-2 bg-gray-50">
            <button @click="showUserModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">Fechar</button>
            
            <template x-if="selectedUser">
                <button @click="showUserModal = false; $dispatch('edit-user', { formData: selectedUser })" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    Editar Dados
                </button>
            </template>
        </div>
    </div>
</div>