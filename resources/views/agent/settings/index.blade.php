@extends('layouts.agent')

@section('title', 'Configurações de Conta - Suporte')

@section('content')
<div class="w-full" x-data="agentSettings()" x-cloak>

    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Header Hero Estilizado -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900  flex items-center gap-3">
                <div class="p-2 bg-gray-100  text-gray-600  rounded-xl shadow-sm border border-gray-200 ">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                Configurações da Conta
            </h1>
            <p class="mt-3 text-gray-500  max-w-2xl">
                Personalize suas credenciais de acesso, e-mail e preferências do painel (como atualização automática e abas ativas).
            </p>
        </div>

        {{-- Toasts Interativos de Erros, Warnings e Sucesso --}}
        @if($errors->any())
            <div x-data="{ show: true }" x-show="show" x-transition.duration.400ms class="mb-6 p-4 bg-rose-50  border border-rose-200  rounded-2xl flex items-start shadow-sm" role="alert">
                <div class="flex-shrink-0 mt-0.5">
                    <svg class="w-6 h-6 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-bold text-rose-800 ">Autenticação Falhou ou Erro nos Campos:</h3>
                    <ul class="list-disc pl-5 mt-1 text-sm text-rose-700 ">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" class="ml-auto text-rose-400 hover:text-rose-600  hover:bg-rose-100  p-1.5 rounded-full transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        @endif

        <!-- Card Container de Formulário -->
        <div class="bg-white  rounded-3xl shadow-xl shadow-gray-200/50  border border-gray-200  overflow-hidden relative">
            
            <form action="{{ route('agent.settings.update') }}" method="POST" autocomplete="off" @submit="loading = true" class="flex flex-col">
                @csrf
                @method('PUT')

                <div class="p-6 sm:p-10 divide-y divide-gray-100 ">

                    <!-- Bloco 1: Perfil (Usuário e Email) -->
                    <div class="py-6 first:pt-0">
                        <h2 class="text-base font-bold text-gray-900  mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 " fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Perfil e Credenciais
                        </h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Nome Visualização -->
                            <div>
                                <label for="username" class="block text-sm font-semibold text-gray-700  mb-2">Usuário</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <input type="text" id="username" value="{{ mb_strtoupper($user->name) }}" disabled
                                           class="block w-full pl-10 pr-4 py-2.5 bg-gray-100 border border-gray-200 text-gray-500 text-sm font-medium rounded-xl cursor-not-allowed   ">
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700  mb-2">Email Autorizado</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input type="email" name="email" id="email" value="{{ $user->email }}"
                                           class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all     ">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bloco 2: Segurança -->
                    <div class="py-6">
                        <h2 class="text-base font-bold text-gray-900  mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 " fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            Segurança (Troca de Senha)
                        </h2>
                        
                        <div class="space-y-5">
                            <div>
                                <label for="old_password" class="block text-sm font-semibold text-gray-700  mb-2">Senha Atual de Autenticação</label>
                                <input type="password" name="old_password" id="old_password"
                                       class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all     "
                                       placeholder="••••••••">
                                <p class="mt-1 text-xs text-gray-500 ">Exigida para homologar alteração de dados sensíveis.</p>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label for="password" class="block text-sm font-semibold text-gray-700  mb-2">Nova Chave (Senha)</label>
                                    <input type="password" name="password" id="password"
                                           class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all     "
                                           placeholder="Nova senha">
                                </div>
                                <div>
                                    <label for="password-confirm" class="block text-sm font-semibold text-gray-700  mb-2">Confirmação da Nova Chave</label>
                                    <input type="password" name="password_confirmation" id="password-confirm"
                                           class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all     "
                                           placeholder="Repita a senha">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bloco 3: Preferências UI do Painel -->
                    <div class="py-6">
                        <h2 class="text-base font-bold text-gray-900  mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 " fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Preferências de Operação e UI
                        </h2>
                        
                        <div class="space-y-6">
                            
                            <!-- Timer -->
                            <div class="sm:flex sm:items-center sm:justify-between p-4 rounded-xl bg-gray-50  border border-gray-100 ">
                                <div>
                                    <label for="refresh_rate" class="block text-sm font-bold text-gray-800 ">Cronômetro de Auto-Atualização</label>
                                    <p class="text-xs text-gray-500 max-w-sm mt-1">Tempo em segundos em que o painel buscará novos aberturas de chamados no banco ("Live Feed").</p>
                                </div>
                                <div class="mt-3 sm:mt-0 flex items-center gap-2">
                                    <input type="number" name="refresh_rate" id="refresh_rate" value="{{ $user->refresh_rate }}" min="5"
                                           class="w-24 px-3 py-2 text-center font-mono font-bold text-lg bg-white border border-gray-300 text-blue-600 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none   ">
                                    <span class="text-sm font-semibold text-gray-500 ">segs</span>
                                </div>
                            </div>

                            <!-- iOS Toggle: Tab Nova -->
                            <div class="sm:flex sm:items-center sm:justify-between p-4 rounded-xl bg-gray-50  border border-gray-100 ">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800 ">Target Blank em Protocolos</h4>
                                    <p class="text-xs text-gray-500 max-w-sm mt-1">Forçar a "abertura de chamado" em uma aba completamente nova do seu Navegador Web.</p>
                                </div>
                                <div class="mt-3 sm:mt-0 flex items-center">
                                    <label for="open_ticket_new_tab" class="relative inline-flex items-center cursor-pointer">
                                        <!-- Checkbox Oculto para Formulário -->
                                        <input type="checkbox" name="open_ticket_new_tab" id="open_ticket_new_tab" class="sr-only peer"
                                               @checked(isset($settings['open_ticket_new_tab']) && $settings['open_ticket_new_tab'])>
                                        <!-- Fundo do Switch e Bolinha (Peg animada) -->
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300  rounded-full peer  peer-checked:after:trangray-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all  peer-checked:bg-blue-600"></div>
                                        <span class="ml-3 text-sm font-bold text-gray-700 " x-text="$el.previousElementSibling.previousElementSibling.checked ? 'Ativado' : 'Inativo'">Inativo</span>
                                    </label>
                                </div>
                            </div>

                            <!-- iOS Toggle: Mostrar Categoria -->
                            <div class="sm:flex sm:items-center sm:justify-between p-4 rounded-xl bg-gray-50  border border-gray-100 ">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800 ">Coluna Categoria na Masterlist</h4>
                                    <p class="text-xs text-gray-500 max-w-sm mt-1">Inserir a arvore hierárquica (Subnível Organizacional Pai/Filho) visualmente na listagem de chamados.</p>
                                </div>
                                <div class="mt-3 sm:mt-0 flex items-center">
                                    <label for="show_ticket_category" class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="show_ticket_category" id="show_ticket_category" class="sr-only peer"
                                               @checked(isset($settings['show_ticket_category']) && $settings['show_ticket_category'])>
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300  rounded-full peer  peer-checked:after:trangray-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all  peer-checked:bg-emerald-500"></div>
                                        <span class="ml-3 text-sm font-bold text-gray-700 " x-text="$el.previousElementSibling.previousElementSibling.checked ? 'Ativado' : 'Inativo'">Inativo</span>
                                    </label>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div> <!-- Fim de blocos de inputs -->

                <!-- Footer de Contenção de Botões -->
                <div class="p-6 sm:px-10 bg-gray-50/80  border-t border-gray-200  flex justify-end">
                    <button type="submit" :disabled="loading"
                            class="w-full sm:w-auto inline-flex justify-center items-center px-10 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold tracking-wide shadow-md shadow-blue-600/20 transition-all active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed border border-transparent focus:outline-none focus:ring-4 focus:ring-blue-500/30">
                        <svg x-show="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-100" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        
                        <svg x-show="!loading" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>

                        <span x-text="loading ? 'Aplicando Configurações...' : 'Salvar Preferências'"></span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- Micro-Lógica UI Alpine.js --}}
<script>
    function agentSettings() {
        return {
            loading: false
        };
    }
</script>
@endsection
