@extends('layouts.agent')

@section('title', 'Busca de Cliente')

@section('content')
<div class="px-4 animate-fade-in-up"
     x-data="{
        submitting: false,
        isFinalized: {{ (isset($schedule) && $schedule->isFinalized()) ? 'true' : 'false' }},
        customerId: '{{ $schedule->customer_id ?? '' }}'
     }"
     x-init="if(customerId) globalThis.ScheduleActions.loadHistory(customerId)">

    <div class="max-w-7xl mx-auto space-y-6">
        
        {{-- Header Título (Opcional, mas dá peso visual) --}}
        <div class="pb-2 border-b border-gray-200 ">
            <h2 class="text-2xl font-extrabold tracking-tight text-gray-900  flex items-center gap-3">
                <span class="p-2 bg-orange-50  text-orange-500 rounded-xl shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </span>
                Localizar Cliente
            </h2>
            <p class="mt-1 text-sm text-gray-500 ">Busque um cliente cadastrado para visualizar seu histórico ou agendar um ticket.</p>
        </div>

        {{-- Alertas e Erros --}}
        @include('shared.errors')

        @php
            $formId = isset($schedule) ? 'form-update' : 'form-create';
            $action = isset($schedule) ? route('agent.schedules.update', $schedule->id) : route('agent.schedules.store');
        @endphp

        <form method="POST" id="{{ $formId }}" action="{{ $action }}">
            @csrf
            @isset($schedule) @method('PUT') @endisset

            <fieldset :disabled="isFinalized" class="group disabled:opacity-75">
                <div class="flex flex-col gap-6">
                    
                    {{-- Barra de Pesquisa --}}
                    <div class="flex flex-col sm:flex-row items-end gap-3 bg-white  p-5 rounded-2xl border border-gray-200  shadow-sm transition-shadow hover:shadow-md">
                        
                        {{-- Seleção de Cliente --}}
                        <div class="flex-grow w-full">
                            <label for="customer_id_main" class="block text-sm font-bold text-gray-700  mb-1.5 flex items-center gap-1.5 uppercase tracking-wider text-xs">
                                Selecione a Empresa
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M19 4a1 1 0 01-1 1h-2.376l2.76 2.76a1 1 0 01-1.414 1.414l-4.576-4.576a1 1 0 01.707-1.707H17V1.5A.5.5 0 0117.5 1h1a.5.5 0 01.5.5V4zM4.624 3H7a1 1 0 110 2H2a1 1 0 01-1-1V1.5A.5.5 0 011.5 1h1a.5.5 0 01.5.5v1.293l4.576-4.576a1 1 0 011.414 1.414L4.624 3z" clip-rule="evenodd" /></svg>
                                </div>
                                <select name="customer_id"
                                        id="customer_id_main"
                                        class="block w-full pl-10 pr-10 py-3 rounded-xl border-gray-300  bg-gray-50  text-gray-900  shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors cursor-pointer appearance-none"
                                        x-model="customerId"
                                        @change="globalThis.ScheduleActions.loadHistory($el.value)"
                                        autofocus>
                                    <option value="" disabled selected class="text-gray-400">Clique para selecionar um cliente da base de dados...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" data-cnpj="{{ $customer->cnpj }}" class="text-gray-800  bg-white ">
                                            {{ $customer->trade_name . ' - ' . $customer->id }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </div>
                            </div>
                        </div>

                        {{-- Botão de Busca por CNPJ --}}
                        <div class="flex-shrink-0 w-full sm:w-auto">
                            <button type="button"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl border border-transparent bg-orange-500 text-white font-bold shadow-md shadow-orange-500/30 hover:bg-orange-600 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500  transition-all active:scale-95"
                                    title="Buscar Avançada / CNPJ"
                                    @click="globalThis.ScheduleActions.searchByCnpj()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                <span class="sm:hidden">Buscar</span>
                            </button>
                        </div>
                    </div>

                    {{-- Container do Histórico --}}
                    <div id="history-container"
                         class="w-full rounded-2xl border-2 border-dashed border-gray-200  bg-gray-50/50  p-6 min-h-[250px] transition-all duration-500 flex flex-col relative overflow-hidden"
                         :class="customerId ? 'opacity-100 border-solid border-gray-200  bg-white  shadow-sm' : 'opacity-70 justify-center items-center'">
                        
                        {{-- Empty State (Visível apenas quando não há cliente selecionado e Alpine não injetou HTML) --}}
                        <div x-show="!customerId" class="absolute inset-0 flex flex-col items-center justify-center text-center p-6 text-gray-400 ">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            <p class="text-lg font-medium">Nenhum cliente selecionado</p>
                            <p class="text-sm mt-1 max-w-sm">Use a barra de pesquisa acima para selecionar uma empresa e visualizar seu respectivo histórico de atendimentos e tickets abertos.</p>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.4s ease-out forwards;
    }
</style>
@endsection

@section('footer')
    @vite(['resources/js/agent/customer/schedule-manager.js'])
@endsection
