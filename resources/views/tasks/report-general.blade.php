@extends('tasks.layouts.master')

@section('title', 'Suporte - Lista de Solicitações')

@push('styles')
<style>
    @media print {
        body { background: white; }
        .no-print { display: none !important; }
        [x-show] { display: block !important; height: auto !important; }
        section { border: none !important; box-shadow: none !important; margin-bottom: 2rem; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
    }
</style>
@endpush

@section('content')

@php
    $getStatusBadge = function ($status) {
        return match ($status) {
            'pen' => ['label' => 'PENDE.',  'css' => 'bg-orange-400/90 text-white'],
            'don' => ['label' => 'CONCL.',  'css' => 'bg-green-500/90 text-white'],
            'can' => ['label' => 'CANCEL.', 'css' => 'bg-gray-400/90 text-white'],
            'rej' => ['label' => 'REJEIT.', 'css' => 'bg-gray-900/90 text-white'],
            'pro' => ['label' => 'EM AND.', 'css' => 'bg-blue-600/80 text-white'],
            'sto' => ['label' => 'PARADO',  'css' => 'bg-red-500/90 text-white'],
            default => ['label' => strtoupper($status), 'css' => 'bg-gray-300 text-gray-800'],
        };
    };
@endphp

<div class="max-w-[1100px] mx-auto">
    
    <header class="mb-8 border-b border-gray-300 pb-4 flex justify-between items-end">
        <h1 class="text-3xl font-light text-gray-800 uppercase tracking-wider">
            Lista de Solicitações
        </h1>
        <button onclick="window.print()" class="no-print flex items-center gap-2 text-sm text-gray-500 hover:text-indigo-600 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg> Imprimir
        </button>
    </header>

    <div class="space-y-6">
        @foreach($data as $row)
            @if(count($row['tasks']))
                
                <section x-data="{ open: true }" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    
                    <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-3 bg-gray-100 hover:bg-gray-200 transition-colors border-b border-gray-300">
                        <h2 class="text-lg font-bold text-gray-800 tracking-wide flex items-center">
                            @if(isset($row['obj']))
                                <svg class="w-5 h-5 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/></svg>
                                {{ $row['name'] }}
                            @else
                                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                Solicitações
                            @endif
                        </h2>
                        <svg class="w-4 h-4 text-gray-500 transition-transform duration-200 no-print" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-collapse x-cloak class="p-0 sm:p-2">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-[13px] sm:text-[14px] border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600">
                                        <th class="py-2 px-3 w-[100px] font-semibold">D. Criação</th>
                                        <th class="py-2 px-3 w-[250px] font-semibold">Cliente</th>
                                        <th class="py-2 px-3 font-semibold">Conteúdo</th>
                                        <th class="py-2 px-3 w-[90px] font-semibold text-center">Prazo</th>
                                        <th class="py-2 px-3 w-[90px] font-semibold text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($row['tasks'] as $task)
                                        <tr class="hover:bg-indigo-50/50 transition-colors">
                                            <td class="py-2 px-3 text-gray-500">{{ $task->created_at?->format('d/m/Y') }}</td>
                                            
                                            <td class="py-2 px-3">
                                                @if(isset($task->customer))
                                                    <span class="font-medium text-gray-800">{{ $task->customer->trade_name }}</span>
                                                @else
                                                    <span class="text-gray-400 italic text-sm">&laquo; Cliente não definido &raquo;</span>
                                                @endif
                                            </td>
                                            
                                            <td class="py-2 px-3 text-gray-800">{{ $task->title }}</td>
                                            <td class="py-2 px-3 text-center text-gray-500">{{ $task->delivery_at?->format('d/m/Y') }}</td>
                                            
                                            <td class="py-2 px-3 text-right">
                                                @php $badge = $getStatusBadge($task->status); @endphp
                                                <span class="inline-block px-2 py-1 text-[11px] font-bold rounded-sm tracking-wide {{ $badge['css'] }}">
                                                    {{ $badge['label'] }}
                                                </span>
                                            </td>
                                        </tr>

                                        @foreach($task->childs as $childTask)
                                            <tr class="bg-gray-50/40 hover:bg-gray-50 transition-colors text-gray-600">
                                                <td class="py-1.5 px-3 text-gray-400 text-sm flex items-center">
                                                    <svg class="w-3 h-3 rotate-90 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg> {{ $childTask->created_at?->format('d/m/Y') }}
                                                </td>
                                                <td class="py-1.5 px-3"></td> <td class="py-1.5 px-3">{{ $childTask->title }}</td>
                                                <td class="py-1.5 px-3 text-center">{{ $childTask->delivery_at?->format('d/m/Y') }}</td>
                                                
                                                <td class="py-1.5 px-3 text-right">
                                                    @php $childBadge = $getStatusBadge($childTask->status); @endphp
                                                    <span class="inline-block px-2 py-1 text-[11px] font-bold rounded-sm tracking-wide {{ $childBadge['css'] }}">
                                                        {{ $childBadge['label'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

            @endif
        @endforeach
    </div>
</div>

@endsection