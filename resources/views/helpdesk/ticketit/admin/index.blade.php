@extends('admin.layouts.master')

@section('content')
<div class="py-8" x-data="{ activeTab: 'overview' }" x-cloak>
    {{-- Cabeçalho --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Painel de Suporte</h1>
        <p class="text-gray-500 font-light mt-1">Dashboard de Administração do Sistema de Tickets</p>
    </div>

    {{-- Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="text-2xl font-bold text-gray-800">{{ $total_tickets ?? ($total ?? 0) }}</div>
            <div class="text-sm text-gray-500">Tickets Totais</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="text-2xl font-bold text-gray-800">{{ $open_tickets ?? ($open ?? 0) }}</div>
            <div class="text-sm text-gray-500">Tickets Abertos</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <div class="text-2xl font-bold text-gray-800">{{ $agents_count ?? 0 }}</div>
            <div class="text-sm text-gray-500">Agentes Ativos</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="text-2xl font-bold text-gray-800">{{ $categories_count ?? 0 }}</div>
            <div class="text-sm text-gray-500">Categorias</div>
        </div>
    </div>

    {{-- Conteúdo Principal - Tabs --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 bg-gray-50">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button class="py-4 px-1 border-b-2 font-medium text-sm hover:text-gray-700 hover:border-gray-300"
                        :class="{ 'border-blue-500 text-blue-600': activeTab === 'overview', 'border-transparent text-gray-500': activeTab !== 'overview' }"
                        @click="activeTab = 'overview'">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Visão Geral
                </button>
                <button class="py-4 px-1 border-b-2 font-medium text-sm hover:text-gray-700 hover:border-gray-300"
                        :class="{ 'border-blue-500 text-blue-600': activeTab === 'agents', 'border-transparent text-gray-500': activeTab !== 'agents' }"
                        @click="activeTab = 'agents'">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 0a2 2 0 11-4 0 2 2 0 014 0zM5 20a3 3 0 015.856-1.487M5 10a3 3 0 01-3-3 3 3 0 013-3 3 3 0 01-3 3"/>
                    </svg>
                    Agentes
                </button>
                <button class="py-4 px-1 border-b-2 font-medium text-sm hover:text-gray-700 hover:border-gray-300"
                        :class="{ 'border-blue-500 text-blue-600': activeTab === 'categories', 'border-transparent text-gray-500': activeTab !== 'categories' }"
                        @click="activeTab = 'categories'">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Categorias
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            {{-- Overview Tab --}}
            <div x-show="activeTab === 'overview'" class="space-y-6">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Bem-vindo ao Painel de Suporte</h3>
                    <p class="text-gray-600 mb-4">Painel de controle centralizado para gerenciar tickets, agentes e categorias do seu helpdesk.</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Monitore tickets em tempo real
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Gerencie agentes e suas permissões
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Organize categorias de tickets
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Agents Tab --}}
            <div x-show="activeTab === 'agents'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Agentes Ativos</h3>
                    <span class="text-sm text-gray-500">Total: {{ $agents->total() }}</span>
                </div>

                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">E-mail</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tickets</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Abertos</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($agents as $agent)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $agent->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $agent->email }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800 font-semibold">{{ $agent->agent_total_tickets_count ?? 0 }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $agent->agent_open_tickets_count ?? 0 }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold
                                            {{ $agent->ticketit_admin ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $agent->ticketit_admin ? 'Sim' : 'Não' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Nenhum agente encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($agents->hasPages())
                    <div class="pt-3">
                        {{ $agents->withQueryString()->links() }}
                    </div>
                @endif
            </div>

            {{-- Categories Tab --}}
            <div x-show="activeTab === 'categories'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Categorias</h3>
                    <span class="text-sm text-gray-500">Total: {{ $categories->total() }}</span>
                </div>

                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tickets</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($categories as $cat)
                                <tr>
                                    @php
                                        $ownTickets = $cat->ticketit_tickets_count ?? 0;
                                        $childrenTickets = $cat->children->sum('ticketit_tickets_count');
                                        $totalTickets = $ownTickets + $childrenTickets;
                                    @endphp
                                    <td class="px-4 py-3 text-sm text-gray-800">#{{ $cat->category_id }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800 flex items-center space-x-2">
                                        <span class="w-3 h-3 rounded-full {{ str_replace('text-', 'bg-', $cat->getPriorityColor()) }}"></span>
                                        <span>{{ $cat->getName() ?? 'Sem nome' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800 font-semibold">{{ $totalTickets }}</td>
                                </tr>
                                {{-- Subcategories loop --}}
                                @foreach($cat->children as $child)
                                    <tr class="bg-gray-50">
                                        <td class="px-4 py-3 text-xs text-gray-500 pl-8">↳ #{{ $child->category_id }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-600 flex items-center space-x-2 pl-8">
                                            <span class="w-2 h-2 rounded-full {{ str_replace('text-', 'bg-', $child->getPriorityColor()) }}"></span>
                                            <span>{{ $child->getName() ?? 'Sem nome' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600 font-medium">{{ $child->ticketit_tickets_count }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">Nenhuma categoria encontrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($categories->hasPages())
                    <div class="pt-3">
                        {{ $categories->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
