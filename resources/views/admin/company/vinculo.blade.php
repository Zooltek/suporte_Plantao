@extends('layouts.master')

@section('content')
<div class="max-w-4xl mx-auto my-8 p-6 bg-white border border-gray-200 rounded-lg shadow-sm"
     x-data="{ submitting: false }">

    @include('shared.errors')

    <nav aria-label="Breadcrumb" class="mb-6">
        <a href="{{ route('company.index') }}"
           class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors group">
            <i class="fa fa-arrow-left mr-2 text-gray-400 group-hover:text-blue-600"></i>
            Voltar para Painel de Empresas
        </a>
    </nav>

    <h1 class="text-2xl font-bold text-gray-800 mb-8 border-b border-gray-100 pb-4">Vincular Usuário à Empresa</h1>

    <form method="POST" action="{{ route('company.vincular') }}" @submit="submitting = true" class="space-y-6">
        @csrf

        {{-- Seleção de Usuário --}}
        <div class="space-y-1">
            <label for="user_select" class="block text-sm font-bold text-gray-700">Usuário:</label>
            <select id="user_select"
                    name="user"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all"
                    required>
                <option value="" selected disabled>Selecione um usuário...</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">
                        {{ $user->name }} {{ $user->company ? '('.$user->company->name.')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Seleção de Empresa --}}
        <div class="space-y-1">
            <label for="company_select" class="block text-sm font-bold text-gray-700">Empresa Destino:</label>
            <select id="company_select"
                    name="company"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all"
                    required>
                <option value="" selected disabled>Selecione a empresa...</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Checkbox de Responsável --}}
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="responsible_check"
                           name="responsible"
                           type="checkbox"
                           value="1"
                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                    <label for="responsible_check" class="font-bold text-gray-700 leading-none">Definir como Responsável</label>
                    <p class="text-gray-500 mt-1">Usuários responsáveis recebem notificações administrativas da empresa.</p>
                </div>
            </div>
        </div>

        {{-- Botões de Ação --}}
        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
            <button type="submit"
                    class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-md shadow-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50 disabled:cursor-wait"
                    :disabled="submitting">
                <span x-show="!submitting" class="flex items-center">
                    <i class="fa fa-link mr-2 text-blue-200"></i> Vincular Usuário
                </span>
                <span x-show="submitting" class="flex items-center" x-cloak>
                    <i class="fas fa-circle-notch fa-spin mr-2"></i> Processando...
                </span>
            </button>

            <a href="{{ route('company.index') }}"
               class="inline-flex items-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none shadow-sm transition-all">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
