<!-- Modal Create User -->
<div x-show="showCreateModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
    <div @click.away="showCreateModal = false" class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold">Novo Usuário</h3>
            <button @click="showCreateModal = false" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>

        <form @submit.prevent="submitCreateForm()" class="px-6 py-6 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="create_name" class="block text-sm font-medium text-gray-700">Nome *</label>
                    <input type="text" id="create_name" x-model="createForm.name" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <label for="create_email" class="block text-sm font-medium text-gray-700">E-mail *</label>
                    <input type="email" id="create_email" x-model="createForm.email" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label for="create_role" class="block text-sm font-medium text-gray-700">Perfil de Acesso *</label>
                    <select id="create_role" x-model="createForm.role" @change="syncRoleSelection('createForm')" required
                            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="1">Agente — Painel de atendimento (suporte/tickets)</option>
                        <option value="2">Administrador — Painel admin + agente + configurações</option>
                        <option value="3">CRM / Comercial — Painel CRM (feedbacks e comercial)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Define quais módulos o usuário pode acessar após login.</p>
                </div>

                <div>
                    <label for="create_dept" class="block text-sm font-medium text-gray-700">Departamento *</label>
                    <select id="create_dept" x-model="createForm.department_id" required
                            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <p x-show="createForm.role === '3'" x-cloak class="mt-1 text-xs text-teal-700">
                        Perfil CRM: usamos departamentos marcados como CRM/Feedback para liberar o módulo comercial. Selecione o setor adequado.
                    </p>
                </div>

                {{-- Senha temporária --}}
                <div class="sm:col-span-2">
                    <label for="create_password" class="block text-sm font-medium text-gray-700">Senha temporária *</label>
                    <input type="password" id="create_password" x-model="createForm.password"
                           autocomplete="new-password" required minlength="6"
                           placeholder="Mínimo 6 caracteres"
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <p class="mt-1 text-xs text-gray-400">
                        O usuário fará login com esta senha e será obrigado a trocá-la no primeiro acesso.
                    </p>
                </div>

                {{-- Flag de Plantonista --}}
                <div class="sm:col-span-2 pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" x-model="createForm.is_oncall" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                        <div>
                            <p class="text-sm font-medium text-gray-700">⏱️ Agente de Plantão (Sobreaviso)</p>
                            <p class="text-xs text-gray-400">Habilita o usuário no App Mobile de Plantão para registro de chamados e sobreaviso.</p>
                        </div>
                    </label>
                </div>
            </div>
        </form>

        <div class="px-6 py-4 border-t flex justify-end gap-2">
            <button @click="showCreateModal = false" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</button>
            <button @click="submitCreateForm()" :disabled="loadingCreate" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                <span x-show="!loadingCreate">Criar</span>
                <span x-show="loadingCreate">Criando...</span>
            </button>
        </div>
    </div>
</div>
