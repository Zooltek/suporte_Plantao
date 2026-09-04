@extends('crm.layouts.master-blank')

@section('content')
{{-- Container principal: Altura total da tela (min-h-screen), Flexbox centralizado --}}
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-slate-900 px-4 sm:px-6 lg:px-8 transition-colors duration-300">
    
    {{-- Card --}}
    <div class="max-w-lg w-full bg-white dark:bg-slate-800 rounded-xl shadow-md border border-gray-100 dark:border-slate-700 p-8 text-center transition-colors duration-300">
        
        {{-- Ícone de Sucesso --}}
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-6">
            <i class="fa-solid fa-check text-3xl text-green-600 dark:text-green-400"></i>
        </div>

        {{-- Título --}}
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            Tudo limpo por aqui!
        </h2>

        {{-- Mensagem --}}
        <p class="text-gray-600 dark:text-gray-400 text-lg mb-8">
            Não há mais demandas de feedback disponíveis para processar no momento.
        </p>

        {{-- Botão de Ação (Opcional: Voltar para home) --}}
        <a href="{{ route('crm.index') }}" 
           class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Voltar ao Painel
        </a>

    </div>
    
    {{-- Rodapé discreto (Opcional) --}}
    <p class="mt-8 text-center text-sm text-gray-400 dark:text-gray-600">
        &copy; {{ date('Y') }} CRM Consuldata
    </p>

</div>
@endsection