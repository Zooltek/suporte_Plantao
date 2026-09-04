@extends('layouts.agent')

@section('title', 'Busca de Empresas - Agente')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Busca Rápida</h1>
            <p class="text-sm text-gray-500 mt-0.5">Encontre uma empresa pelo nome ou CNPJ</p>
        </div>
        <a href="{{ route('agent.companies.manage.index') }}"
           class="text-sm text-gray-500 hover:text-orange-600 transition-colors flex items-center gap-1.5">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            Ver todas
        </a>
    </div>

    {{-- Search Box --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6"
         x-data="{ query: '', results: [], loading: false, searched: false }"
         x-init="">

        <div class="relative">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                   x-model="query"
                   @input.debounce.400ms="
                       if (query.length >= 2) {
                           loading = true;
                           fetch('{{ route('agent.api.v1.companies.search') }}?q=' + encodeURIComponent(query))
                               .then(r => r.json())
                               .then(data => { results = data; loading = false; searched = true; })
                               .catch(() => { loading = false; });
                       } else {
                           results = [];
                           searched = false;
                       }
                   "
                   placeholder="Digite o nome ou CNPJ da empresa..."
                   class="w-full pl-12 pr-12 py-4 text-base border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
            <div x-show="loading" class="absolute right-4 top-1/2 -translate-y-1/2">
                <svg class="animate-spin h-5 w-5 text-orange-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        </div>

        {{-- Results --}}
        <div x-show="searched && results.length === 0 && !loading" class="mt-6 py-10 text-center text-gray-400">
            Nenhuma empresa encontrada para "<span x-text="query" class="font-semibold"></span>"
        </div>

        <div x-show="results.length > 0" class="mt-4 divide-y divide-gray-100 rounded-xl border border-gray-100 overflow-hidden">
            <template x-for="company in results" :key="company.id">
                <a :href="'{{ url('support/company') }}/' + company.id + '/history'"
                   class="flex items-center justify-between px-4 py-3.5 hover:bg-orange-50/50 transition-colors group">
                    <div>
                        <p class="font-semibold text-gray-900 group-hover:text-orange-700 transition-colors" x-text="company.trade_name ?? company.name"></p>
                        <p class="text-xs text-gray-400 font-mono mt-0.5" x-text="company.cnpj"></p>
                    </div>
                    <svg class="h-4 w-4 text-gray-300 group-hover:text-orange-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </template>
        </div>

        <p x-show="!searched && !loading" class="mt-4 text-center text-sm text-gray-400 py-4">
            Digite ao menos 2 caracteres para buscar
        </p>
    </div>

</div>
@endsection
