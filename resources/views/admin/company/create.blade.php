@extends('layouts.master')

@section('content')
<section class="max-w-4xl mx-auto my-8 p-6 bg-white border border-gray-200 rounded-lg shadow-sm"
         x-data="{ loadingCities: false, submitting: false }">

    @include('shared.errors')

    <nav aria-label="Breadcrumb" class="mb-6">
        <a href="{{ route('company.index') }}"
           class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors">
            <i class="fa fa-arrow-left mr-2"></i> Voltar para Company Center
        </a>
    </nav>

    <header class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Incluir Nova Empresa</h1>
    </header>

    <form method="POST" action="{{ route('company.store') }}" @submit="submitting = true" class="space-y-6">
        @csrf

        {{-- Grid para Estado e Cidade --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Seleção de Estado --}}
            <div class="space-y-1">
                <label for="state" class="block text-sm font-bold text-gray-700">Estado:</label>
                <select id="state"
                        name="state"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all"
                        required
                        @change="loadingCities = true">
                    <option value="" selected disabled>Selecione um estado</option>
                    @foreach($states as $state)
                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Seleção de Cidade --}}
            <div class="space-y-1">
                <label for="cities" class="flex items-center gap-2 text-sm font-bold text-gray-700">
                    Cidade:
                    <i class="fas fa-spinner fa-spin text-blue-600" x-show="loadingCities" x-cloak aria-hidden="true"></i>
                    <span class="sr-only" x-show="loadingCities">Carregando cidades...</span>
                </label>
                <select id="cities"
                        name="city"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:bg-gray-50 disabled:text-gray-400 transition-all"
                        required
                        :disabled="loadingCities">
                    <option value="" selected disabled>Selecione uma Cidade</option>
                </select>
            </div>
        </div>

        {{-- Ações do Formulário --}}
        <footer class="flex items-center gap-4 pt-6 border-t border-gray-100">
            <button type="submit"
                    class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-md shadow-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50 disabled:cursor-wait"
                    :disabled="submitting">
                <span x-show="!submitting" class="flex items-center">
                    <i class="fa fa-save mr-2" aria-hidden="true"></i> Salvar Empresa
                </span>
                <span x-show="submitting" class="flex items-center" x-cloak>
                    <i class="fa fa-circle-notch fa-spin mr-2" aria-hidden="true"></i> Salvando...
                </span>
            </button>

            <a href="{{ route('company.index') }}"
               class="inline-flex items-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none shadow-sm transition-all">
                Cancelar
            </a>
        </footer>
    </form>
</section>

<script>
    globalThis.CompanyConfig = {
        citiesRoute: @json(url('admin/company/cities'))
    };
</script>
@endsection
