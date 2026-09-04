@extends('layouts.master')

@section('page')
    {{ trans('ticketit::admin.administrator-index-title') }} - Notificações
@endsection

@section('content')
<main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="{ submitting: false }">
    @include('shared.errors')

    {{-- Cabeçalho Semântico --}}
    <header class="mb-8">
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Central de Notificações</h1>
        <p class="text-sm text-gray-500 mt-1 font-light">Disparo de mensagens para grupos ou usuários específicos do sistema.</p>
    </header>

    {{-- Container do Formulário --}}
    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 md:p-8">
        <form method="POST" action="{{ url('notification/send') }}" @submit="submitting = true" class="space-y-6">
            @csrf

            {{-- Destinatários --}}
            <div class="space-y-1">
                <label for="group" class="block text-sm font-bold text-gray-700">Grupo de Destino:</label>
                <select id="group" name="group" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all">
                    <option value="" disabled selected>Selecione quem receberá a notificação...</option>
                    <optgroup label="Grupos do Sistema" class="font-bold text-gray-900">
                        <option value="users">Usuários (Clientes)</option>
                        <option value="agents">Agentes de Suporte</option>
                        <option value="admins">Administradores</option>
                        <option value="all">Todos os usuários registrados</option>
                    </optgroup>
                    <optgroup label="Envio Individual" class="font-bold text-gray-900">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            {{-- Mensagem --}}
            <div class="space-y-1">
                <label for="content" class="block text-sm font-bold text-gray-700">Conteúdo da Mensagem:</label>
                <textarea id="content" name="content" rows="3" required
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                          placeholder="Digite o texto da notificação..."></textarea>
            </div>

            {{-- Grid de Ação e Imagem --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                    <label for="action" class="block text-sm font-bold text-gray-700">Link de Ação (URL):</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-400 sm:text-sm">/</span>
                        </div>
                        <input type="text" id="action" name="action" required
                               class="block w-full pl-7 rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="Ex: solution/category/1">
                    </div>
                    <p class="text-xs text-gray-500 italic">Destino do usuário ao clicar na notificação.</p>
                </div>

                <div class="space-y-1">
                    <label for="image" class="block text-sm font-bold text-gray-700">Ícone/Imagem:</label>
                    <input type="text" id="image" name="image" required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="Ex: notification/alert.png">
                    <p class="text-xs text-gray-500 italic">Caminho relativo para a imagem da notificação.</p>
                </div>
            </div>

            {{-- Botão de Disparo --}}
            <footer class="pt-6 border-t border-gray-100">
                <button type="submit"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-lg shadow-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50 disabled:cursor-wait"
                        :disabled="submitting">
                    <span x-show="!submitting" class="flex items-center">
                        <i class="fa fa-paper-plane mr-2" aria-hidden="true"></i> Disparar Notificação
                    </span>
                    <span x-show="submitting" x-cloak class="flex items-center">
                        <i class="fa fa-circle-notch fa-spin mr-2" aria-hidden="true"></i> Processando envio...
                    </span>
                </button>
            </footer>
        </form>
    </section>
</main>
@endsection
