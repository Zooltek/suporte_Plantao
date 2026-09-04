<!-- Modal Edit User -->
<div x-show="showEditModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
    <div @click.away="showEditModal = false" class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold">Editar Usuário</h3>
            <button @click="showEditModal = false" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>

        <form @submit.prevent="submitEditForm()" class="px-6 py-6 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit_name" class="block text-sm font-medium text-gray-700">Nome *</label>
                    <input type="text" id="edit_name" x-model="editForm.name" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <label for="edit_email" class="block text-sm font-medium text-gray-700">E-mail *</label>
                    <input type="email" id="edit_email" x-model="editForm.email" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <label for="edit_role" class="block text-sm font-medium text-gray-700">Perfil de Acesso *</label>
                    <select id="edit_role" x-model="editForm.role" @change="syncRoleSelection('editForm')" required
                            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="" disabled>Selecione um perfil</option>
                        <option value="1">Agente — Painel de atendimento</option>
                        <option value="2">Administrador — Admin + agente + configurações</option>
                        <option value="3">CRM / Comercial — Painel CRM</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Define quais módulos o usuário pode acessar após login.</p>
                </div>

                <div>
                    <label for="edit_dept" class="block text-sm font-medium text-gray-700">Departamento *</label>
                    <select id="edit_dept" x-model="editForm.department_id" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <p x-show="editForm.role === '3'" x-cloak class="mt-1 text-xs text-teal-700">
                        Perfil CRM: usamos departamentos marcados como CRM/Feedback para liberar o módulo comercial. Selecione o setor adequado.
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label for="edit_password" class="block text-sm font-medium text-gray-700">Nova Senha Temporária (opcional)</label>
                    <input type="password" id="edit_password" x-model="editForm.password"
                           autocomplete="new-password" minlength="6"
                           placeholder="Deixe em branco para manter a senha atual"
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <p class="mt-1 text-xs text-amber-600 font-medium">
                        💡 Caso o usuário não tenha acesso ao e-mail de recuperação, digite uma senha temporária aqui. Ao fazer login com ela, será solicitado que cadastre uma nova senha imediatamente.
                    </p>
                </div>

                <div class="sm:col-span-2 space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="editForm.active" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Usuário Ativo</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer pt-1">
                        <input type="checkbox" x-model="editForm.is_oncall" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                        <div>
                            <p class="text-sm font-medium text-gray-700">⏱️ Agente de Plantão (Sobreaviso)</p>
                            <p class="text-xs text-gray-400">Habilita o usuário no App Mobile de Plantão para sincronização e registro de atendimentos.</p>
                        </div>
                    </label>
                </div>

                <div class="sm:col-span-2 pt-3 border-t border-gray-100">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Permissões de Implantação</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" x-model="editForm.deployment_admin" class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Adm. Implantação</p>
                                <p class="text-xs text-gray-400">Gerencia agendamentos e registros de implantação</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" x-model="editForm.can_manage_implementation" class="w-4 h-4 text-orange-500 rounded focus:ring-orange-400">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Ger. Implementação</p>
                                <p class="text-xs text-gray-400">Acesso ao controle de implantação</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </form>

        <div class="px-6 py-4 border-t flex justify-end gap-2">
            <button @click="showEditModal = false" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</button>
            <button @click="submitEditForm()" :disabled="loadingEdit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                <span x-show="!loadingEdit">Salvar</span>
                <span x-show="loadingEdit">Salvando...</span>
            </button>
        </div>
    </div>
</div>
