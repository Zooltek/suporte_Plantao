@extends('admin.layouts.master')

@section('content')
<div x-data="departmentManager()" class="py-8">
    {{-- Cabeçalho --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Departamentos</h1>
            <p class="text-gray-500 font-light mt-1">Gerencie os departamentos da organização.</p>
        </div>

        <button @click="openCreateModal()"
                class="inline-flex items-center px-5 py-2.5 bg-blue-600 border border-transparent rounded-lg font-bold text-sm text-white shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Departamento
        </button>
    </div>

    {{-- Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</div>
            <div class="text-sm text-gray-500">Total de Departamentos</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="text-2xl font-bold text-gray-800">{{ $stats['withModules'] ?? 0 }}</div>
            <div class="text-sm text-gray-500">Com Módulos Vinculados</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-400">
            <div class="text-2xl font-bold text-gray-800">{{ $stats['empty'] ?? 0 }}</div>
            <div class="text-sm text-gray-500">Sem Módulos</div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Módulos</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($departments as $dept)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-400">
                                #{{ $dept->id }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-800">{{ $dept->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $dept->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($dept->labels->count() > 0)
                                    <button
                                        @click="toggle({{ $dept->id }})"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200 transition"
                                        type="button">
                                        <svg :class="{'rotate-90': expanded[{{ $dept->id }}]}"
                                             class="w-3 h-3 transition-transform duration-200"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        {{ $dept->labels->count() }}
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        0
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="inline-flex items-center gap-2">
                                    <button @click="openEditModal(@js([
                                        'id' => $dept->id,
                                        'name' => $dept->name,
                                        'description' => $dept->description,
                                    ]))"
                                            class="p-2 rounded transition-colors text-blue-600 hover:bg-blue-100"
                                            title="Editar departamento">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="deleteDepartment({{ $dept->id }}, @js($dept->name))"
                                            class="p-2 rounded transition-colors text-red-600 hover:bg-red-100"
                                            title="Excluir departamento">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        {{-- Linha Expansível com Módulos --}}
                        <tr x-show="expanded[{{ $dept->id }}]" x-transition class="bg-gray-50/50 dark:bg-slate-800/50">
                            <td colspan="5" class="px-6 py-4">
                                <div class="space-y-3">
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                        Módulos Vinculados a "{{ $dept->name }}"
                                    </h3>
                                    
                                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-800">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">ID</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Nome do Módulo</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                @foreach($dept->labels as $module)
                                                    <tr class="hover:bg-blue-50/30 dark:hover:bg-blue-900/20 transition-colors">
                                                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 font-mono">#{{ $module->id }}</td>
                                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $module->name }}</td>
                                                        <td class="px-4 py-3">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $module->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                                                {{ $module->is_active ? 'Ativo' : 'Inativo' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <p class="text-sm text-gray-500 font-medium">Nenhum departamento cadastrado</p>
                                    <p class="text-xs text-gray-400 mt-1">Clique em "Novo Departamento" para começar</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modals --}}
    @include('admin.departments.modals.create')
    @include('admin.departments.modals.edit')

</div>
@endsection

@vite(['resources/js/admin/departments.js'])
