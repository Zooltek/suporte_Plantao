@extends('layouts.agent')

@section('content')
<div class="w-full min-h-[calc(100vh-10rem)] flex items-center justify-center p-4">
    
    <!-- Card Premium de Filtros Centrado -->
    <div class="w-full max-w-3xl bg-white  rounded-3xl shadow-2xl shadow-gray-200/50  border border-gray-200  overflow-hidden relative">
        
        <!-- Decoração Top Banner Gradiente -->
        <div class="h-2 bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-600"></div>
        
        <div class="p-8 sm:p-10">
            
            <div class="flex items-center gap-5 mb-10">
                <div class="p-4 bg-indigo-50  rounded-2xl border border-indigo-100  shadow-sm">
                    <svg class="w-8 h-8 text-indigo-600 " fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900  tracking-tight">Central de Relatórios Totais</h1>
                    <p class="mt-2 text-gray-500  text-sm">Selecione o arquétipo analítico e a data base para gerar o balanço volumétrico.</p>
                </div>
            </div>

            <!-- Formulário Embutido Sem Bootstrap -->
            <form action="{{ route('agent.report.generate') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50  p-6 md:p-8 rounded-2xl border border-gray-100  mb-8">
                    
                    <!-- Campo: Tipo de Relatório -->
                    <div>
                        <label for="report_type" class="block text-sm font-bold text-gray-700  mb-2">Qual tipo de balanço?</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                            <select name="type" id="report_type" 
                                    class="block w-full pl-10 pr-10 py-3 bg-white border border-gray-300 text-gray-900 text-sm font-semibold rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 appearance-none outline-none transition-all      shadow-sm cursor-pointer">
                                <option value="1">Volumetria por Suporte / Equipe</option>
                                <option value="2">Volumetria por Clientes Cadastrados</option>
                                <option value="3">Volumetria por Categoria de Problemas</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Campo: Data do Relatório -->
                    <div>
                        <label for="report_date" class="block text-sm font-bold text-gray-700  mb-2">Base temporal (Data limite)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <input type="date" name="date" id="report_date" value="{{ now()->format('Y-m-d') }}"
                                   class="block w-full pl-10 pr-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm font-semibold rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all      shadow-sm cursor-text [&::-webkit-calendar-picker-indicator]:">
                        </div>
                    </div>

                </div>

                <!-- Footer do Card (Ação) -->
                <div class="flex items-center justify-end border-t border-gray-100  pt-6">
                    <button type="submit" :disabled="loading"
                            class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-10 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold tracking-wide shadow-md shadow-blue-600/20 active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed transition-all outline-none focus:ring-4 focus:ring-blue-500/30">
                        
                        <svg x-show="!loading" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>

                        <svg x-show="loading" class="animate-spin -ml-1 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span x-text="loading ? 'Processando dados...' : 'Gerar Relatório Analítico'"></span>
                    </button>
                </div>
            </form>

            <div id="resultado" class="hidden"></div>
        </div>
    </div>
</div>
@endsection
