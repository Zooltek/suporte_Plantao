{{-- Partial: Histórico da Empresa (sem layout, para uso via AJAX/fetch no formulário de ticket) --}}
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Histórico da Empresa</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $company->trade_name ?? $company->name }}</p>
        </div>
    </div>

<div class="px-4" x-data="{ companyId: '{{ $company->id }}' }">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-0 border border-gray-200 bg-white rounded-2xl shadow-sm text-[14px] overflow-hidden transition-colors">
        
        {{-- Coluna Esquerda: Informações de Contato --}}
        <div class="p-5 border-b md:border-b-0 relative">
            
            <h3 class="text-blue-600 font-extrabold mb-4 text-[16px] tracking-tight flex items-center gap-2">
                <span class="p-1.5 bg-blue-50 text-blue-500 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </span>
                {{ $company->trade_name }}
                @if($company->software)
                    <span class="text-gray-400 text-xs font-semibold ml-1 py-0.5 px-2 bg-gray-100 rounded-full border border-gray-200 shadow-sm leading-none">
                        {{ $company->software->name }}
                    </span>
                @endif
            </h3>

            <div class="space-y-2.5">
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    <p class="leading-snug">
                        <span class="text-gray-900 font-medium">{{ $company->phone ?: '---' }}</span>
                        <span class="text-gray-300 px-1">/</span>
                        <span class="text-gray-900 font-medium">{{ $company->telephone_2 ?: '---' }}</span>
                    </p>
                </div>

                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    <p class="lowercase text-gray-700 font-medium truncate">{{ $company->contact_email ?: '---' }}</p>
                </div>
                
                <div class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <div>
                        <p class="text-gray-700 font-medium leading-snug">{{ $company->address ?: '---' }}</p>
                        <p class="text-gray-500 text-xs mt-0.5">{{ $company->city ?? '' }}@if($company->city && $company->state) / @endif{{ $company->state?->abbr }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Coluna Direita: Dados Jurídicos --}}
        <div class="p-5 border-t md:border-t-0 md:border-l border-gray-200 relative">
            <div class="space-y-2 mb-6">
                <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                    <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Razão Social</span>
                    <span class="text-gray-900 font-bold text-right pl-2">{{ $company->name }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                    <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">CNPJ</span>
                    <span class="mask-cnpj text-gray-800 font-mono font-medium">{{ $company->cnpj }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                    <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Inscr. Estadual</span>
                    <span class="text-gray-800 font-mono font-medium">{{ $company->state_registration ?: 'Isento' }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                    <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Cód. Municipal (IBGE)</span>
                    <span class="text-gray-800 font-mono font-medium">{{ $company->city_registration ?: '—' }}</span>
                </div>
            </div>

            {{-- Módulos Contratados --}}
            @if($company->moduleTypes && $company->moduleTypes->isNotEmpty())
                <div class="mt-2">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Módulos Contratados</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($company->moduleTypes as $mod)
                            <span class="inline-flex items-center px-2 py-0.5 bg-indigo-50 border border-indigo-200 text-indigo-700 text-[11px] font-semibold rounded-full">
                                {{ $mod->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Últimos Chamados Finalizados --}}
@if(isset($finalized_tickets) && $finalized_tickets->isNotEmpty())
    <div class="mt-4 px-4">
        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Últimos Chamados Finalizados</p>
        <div class="space-y-1.5">
            @foreach($finalized_tickets as $ticket)
                <div x-data="{ open: false }" class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-3 py-2 text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="font-black text-gray-400">#{{ $ticket->id }}</span>
                            <span class="text-gray-700 font-medium truncate">{{ $ticket->subject }}</span>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                            @if($ticket->agent)
                                <span class="text-gray-400 text-[10px]">{{ $ticket->agent->name }}</span>
                            @endif
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                  style="background-color: {{ ($ticket->status->color ?? '#6366f1') }}22; color: {{ $ticket->status->color ?? '#6366f1' }}">
                                {{ $ticket->status->name ?? 'Finalizado' }}
                            </span>
                            <span class="text-gray-400 text-[10px]">{{ $ticket->completed_at?->format('d/m/Y') }}</span>
                            <button type="button" @click="open = !open"
                                    class="p-1 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                    :title="open ? 'Fechar' : 'Ver detalhes'">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div x-show="open" x-collapse class="border-t border-gray-100 px-3 py-2.5 bg-gray-50 text-xs space-y-1">
                        @if($ticket->category)
                            <div class="flex items-center gap-1.5">
                                <span class="text-gray-400 font-semibold w-20 shrink-0">Categoria</span>
                                <span class="text-gray-700">{{ $ticket->category->display_name }}</span>
                            </div>
                        @endif
                        @if($ticket->agent)
                            <div class="flex items-center gap-1.5">
                                <span class="text-gray-400 font-semibold w-20 shrink-0">Agente</span>
                                <span class="text-gray-700">{{ $ticket->agent->name }}</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-400 font-semibold w-20 shrink-0">Finalizado</span>
                            <span class="text-gray-700">{{ $ticket->completed_at?->format('d/m/Y H:i') ?? '—' }}</span>
                        </div>

                        @include('agent.company.partials.ticket-issues', ['ticket' => $ticket])
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

</div>
