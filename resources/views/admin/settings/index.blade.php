@extends('admin.layouts.master')

@section('title', 'Configurações de Conta')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ loading: false }">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
            <div class="p-2 bg-gray-100 text-gray-600 rounded-xl shadow-sm border border-gray-200">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            Configurações da Conta
        </h1>
        <p class="mt-1 text-sm text-gray-500">Gerencie suas credenciais de acesso e preferências do painel.</p>
    </div>

    {{-- Flash messages --}}
    @if($errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3">
            <svg class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <div class="flex-1">
                <p class="text-sm font-bold text-rose-800">Erro ao salvar:</p>
                <ul class="list-disc pl-4 mt-1 text-sm text-rose-700">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            <button @click="show = false" class="text-rose-400 hover:text-rose-600 p-1 rounded-full"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    @endif

    {{-- Formulário --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <form action="{{ route('admin.settings.update') }}" method="POST" autocomplete="off" @submit="loading = true">
            @csrf
            @method('PUT')

            <div class="divide-y divide-gray-100">

                {{-- Bloco 1: Perfil --}}
                <div class="p-6 sm:p-8">
                    <h2 class="text-sm font-bold text-gray-900 mb-5 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Perfil e Credenciais
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Usuário</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <input type="text" value="{{ mb_strtoupper($user->name) }}" disabled
                                       class="block w-full pl-9 pr-4 py-2.5 bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-xl cursor-not-allowed">
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">E-mail</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                                       class="block w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloco 2: Segurança --}}
                <div class="p-6 sm:p-8">
                    <h2 class="text-sm font-bold text-gray-900 mb-5 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Segurança — Troca de Senha
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label for="old_password" class="block text-sm font-semibold text-gray-700 mb-1.5">Senha Atual</label>
                            <input type="password" name="old_password" id="old_password" placeholder="••••••••"
                                   class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all">
                            <p class="mt-1 text-xs text-gray-400">Necessária para confirmar alteração de senha.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Nova Senha</label>
                                <input type="password" name="password" id="password" placeholder="Nova senha"
                                       class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all">
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmar Nova Senha</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Repita a senha"
                                       class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloco 3: Preferências --}}
                <div class="p-6 sm:p-8">
                    <h2 class="text-sm font-bold text-gray-900 mb-5 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/></svg>
                        Preferências do Painel
                    </h2>
                    <div class="space-y-4">

                        {{-- Refresh rate --}}
                        <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <div>
                                <p class="text-sm font-bold text-gray-800">Auto-Atualização</p>
                                <p class="text-xs text-gray-500 mt-0.5">Intervalo em segundos para busca de novos chamados ("Live Feed").</p>
                            </div>
                            <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                                <input type="number" name="refresh_rate" id="refresh_rate" value="{{ $user->refresh_rate }}" min="5"
                                       class="w-20 px-3 py-2 text-center font-mono font-bold text-sm bg-white border border-gray-300 text-blue-600 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none">
                                <span class="text-sm text-gray-500">seg</span>
                            </div>
                        </div>

                        {{-- Target blank --}}
                        <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <div>
                                <p class="text-sm font-bold text-gray-800">Abrir Chamado em Nova Aba</p>
                                <p class="text-xs text-gray-500 mt-0.5">Força abertura de chamados em nova aba do navegador.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer ml-4 flex-shrink-0">
                                <input type="checkbox" name="open_ticket_new_tab" id="open_ticket_new_tab" class="sr-only peer"
                                       @checked((bool) ($settings['open_ticket_new_tab'] ?? true))>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        {{-- Coluna categoria --}}
                        <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 border border-gray-100">
                            <div>
                                <p class="text-sm font-bold text-gray-800">Coluna Categoria na Lista de Chamados</p>
                                <p class="text-xs text-gray-500 mt-0.5">Exibe a árvore de categorias na masterlist de chamados.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer ml-4 flex-shrink-0">
                                <input type="checkbox" name="show_ticket_category" id="show_ticket_category" class="sr-only peer"
                                       @checked(isset($settings['show_ticket_category']) && $settings['show_ticket_category'])>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 sm:px-8 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="submit" :disabled="loading"
                        class="inline-flex items-center gap-2 px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                    <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg x-show="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <span x-text="loading ? 'Salvando...' : 'Salvar Preferências'"></span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
