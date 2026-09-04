@extends('admin.layouts.master')

@section('content')
<div x-data="feedbackManager()" class="py-8">
    {{-- Cabeçalho --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Feedback - CRM</h1>
        <p class="text-gray-500 font-light mt-1">Gerenciamento de Formulários e Elementos de Feedback</p>
    </div>

    {{-- Estatísticas --}}
    @if($stats)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="text-2xl font-bold text-gray-800">{{ $stats['total_forms'] ?? 0 }}</div>
            <div class="text-sm text-gray-500">Formulários Totais</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="text-2xl font-bold text-gray-800">{{ $stats['active_forms'] ?? 0 }}</div>
            <div class="text-sm text-gray-500">Formulários Ativos</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="text-2xl font-bold text-gray-800">{{ $stats['total_elements'] ?? 0 }}</div>
            <div class="text-sm text-gray-500">Elementos Totais</div>
        </div>
    </div>
    @endif

    {{-- Seleção de Formulário --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Formulários Disponíveis</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-16">#</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nome do Formulário</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($forms as $form)
                        <tr class="hover:bg-gray-50 transition-colors {{ ($selectedFormId ?? null) === $form->id ? 'bg-blue-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">{{ $form->id }}</td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-800">{{ $form->name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <a href="?form_id={{ $form->id }}"
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Selecionar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center">
                                <p class="text-gray-500 text-sm">Nenhum formulário cadastrado</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Elementos do Formulário Selecionado --}}
    <div class="space-y-4">
        {{-- Header com Botão --}}
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Elementos do Formulário</h3>
            <button @click="openCreateModal()"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-bold rounded hover:bg-green-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Novo Elemento
            </button>
        </div>

        {{-- Tabela de Elementos --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">#</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Label</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ordem</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($element_types as $element)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">{{ $element->id }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-700">{{ $element->label }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 font-mono">{{ $element->name }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $element->type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $element->sort_order }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button @click='openEditModal(@json($element))'
                                                class="admin-icon-btn admin-icon-btn--edit"
                                                title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button @click="deleteElement({{ $element->id }})"
                                                class="admin-icon-btn admin-icon-btn--delete"
                                                title="Remover">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Nenhum elemento cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Create/Edit --}}
    @include('admin.crm.feedback.modals.element')

</div>
@endsection

@vite(['resources/js/admin/crm/feedback.js'])
