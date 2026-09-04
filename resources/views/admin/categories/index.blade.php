@extends('admin.layouts.master')

@section('content')
<div x-data="categoryManager()" class="py-8">
    {{-- Cabeçalho com Breadcrumb Dinâmico --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            {{-- Breadcrumb aparece quando está visualizando subcategorias --}}
            <div x-show="Object.keys(expanded).some(key => expanded[key])" class="mb-2">
                <nav class="flex items-center gap-2 text-sm">
                    <a href="/admin/categories" class="text-blue-600 hover:text-blue-800 font-medium">
                        Categorias
                    </a>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <template x-for="(isExpanded, categoryId) in expanded" :key="categoryId">
                        <span x-show="isExpanded" class="text-gray-700 font-medium" x-text="getCategoryName(categoryId)"></span>
                    </template>
                </nav>
            </div>

            <h1 class="text-3xl font-bold text-gray-800 tracking-tight">
                <span x-show="!Object.keys(expanded).some(key => expanded[key])">Categorias</span>
                <template x-for="(isExpanded, categoryId) in expanded" :key="categoryId">
                    <span x-show="isExpanded" x-text="'Subcategorias de ' + getCategoryName(categoryId)"></span>
                </template>
            </h1>
            <p class="text-gray-500 font-light mt-1">
                <span x-show="!Object.keys(expanded).some(key => expanded[key])">Gerencie as categorias do sistema.</span>
                <span x-show="Object.keys(expanded).some(key => expanded[key])">Gerencie as subcategorias desta categoria.</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                @click="openCreateRootModal()"
                class="inline-flex items-center px-4 py-2.5 bg-blue-600 border border-transparent rounded-lg font-bold text-sm text-white shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nova Categoria
            </button>

            <button
                @click="openCreateSubcategoryModal(currentParentId, categoriesData[currentParentId]?.priority || 'low')"
                class="inline-flex items-center px-4 py-2.5 bg-white border border-blue-200 rounded-lg font-bold text-sm text-blue-700 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 12h8m-8 5h5"/>
                </svg>
                Nova Subcategoria
            </button>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50 px-5 py-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-bold text-indigo-800">Regras de Peso e SLA</p>
                <p class="text-xs text-indigo-700 mt-1">
                    Peso da prioridade: <strong>Urgente = 5</strong>, <strong>Alta = 3</strong>, <strong>Baixa = 1</strong>.
                    O peso final do ticket é <strong>Categoria + Subcategoria</strong>.
                </p>
                <p class="text-xs text-indigo-700 mt-1">
                    Nesta listagem: <strong>Peso da árvore = Categoria + soma das subcategorias</strong>. No atendimento:
                    <strong>Peso final do ticket = Categoria + 1 subcategoria selecionada</strong>.
                </p>
                <p class="text-xs text-indigo-700 mt-1">
                    Faixas SLA por peso: <strong>&ge; 8</strong> (alta), <strong>&ge; 5</strong> (média), <strong>&lt; 5</strong> (baixa).
                </p>
            </div>
            <a href="{{ route('admin.sla.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition-colors">
                Ver tempos de SLA (minutos)
            </a>
        </div>
    </div>

    {{-- Tabela de Categorias --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nome da Categoria</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Departamento</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Prioridade</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Subcategorias</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($categories as $category)
                        @php($ticketWeightRange = $category->getTicketWeightRange())
                        @php($treeWeight = $category->getPriorityWeight() + $category->getSubcategoriesWeightTotal())
                        <tr class="hover:bg-gray-50 transition-colors" 
                            data-category-id="{{ $category->category_id }}" 
                            data-category-name="{{ $category->description?->name ?? 'Sem nome' }}"
                            data-category-priority="{{ $category->priority ?? 'low' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-400">
                                #{{ $category->category_id }}
                            </td>
                            
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-800 tracking-tight">
                                    {{ $category->description?->name ?? 'Sem nome' }}
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($category->department)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                        {{ $category->department->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 italic">Não atribuído</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap" x-data="categoryRow(@js([
                                'category_id' => $category->category_id,
                                'priority' => $category->priority ?? 'low',
                                'description' => [
                                    'name' => $category->description?->name,
                                    'permalink' => $category->description?->permalink,
                                    'description' => $category->description?->description,
                                ],
                                'parent_id' => $category->parent_id,
                                'department_id' => $category->department_id,
                                'parent' => $category->parent ? [
                                    'description' => ['name' => $category->parent->description?->name]
                                ] : null
                            ]))">
                                <select 
                                    x-model="formData.priority"
                                    @change="saved = false"
                                    :disabled="loading"
                                    class="text-xs font-medium rounded-full border-0 py-1 px-3 focus:ring-2 focus:ring-blue-500 transition-all w-full"
                                    :class="{
                                        'bg-red-100 text-red-700': formData.priority === 'urgent',
                                        'bg-yellow-100 text-yellow-700': formData.priority === 'high',
                                        'bg-green-100 text-green-700': formData.priority === 'low',
                                        'opacity-50': loading
                                    }">
                                    <option value="urgent">🔴 Urgente (peso 5)</option>
                                    <option value="high">🟡 Alta (peso 3)</option>
                                    <option value="low">🟢 Baixa (peso 1)</option>
                                </select>
                                <p class="mt-1 text-[11px] text-gray-500">
                                    Peso categoria: <strong>{{ $category->getPriorityWeight() }}</strong> •
                                    Peso da árvore: <strong>{{ $treeWeight }}</strong> •
                                    Peso final por ticket:
                                    <strong>{{ $ticketWeightRange['min'] }}{{ $ticketWeightRange['min'] !== $ticketWeightRange['max'] ? ' a '.$ticketWeightRange['max'] : '' }}</strong>
                                </p>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    @click="toggleChildren({{ $category->category_id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200 transition"
                                    type="button">
                                    {{-- Ícone de seta que rotaciona quando expandido --}}
                                    <svg :class="{'rotate-90': expanded[{{ $category->category_id }}]}"
                                         class="w-3 h-3 transition-transform duration-200"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    {{ $category->children->count() }} subcategoria{{ $category->children->count() !== 1 ? 's' : '' }}
                                </button>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" x-data="categoryRow(@js([
                                'category_id' => $category->category_id,
                                'priority' => $category->priority ?? 'low',
                                'description' => [
                                    'name' => $category->description?->name,
                                    'permalink' => $category->description?->permalink,
                                    'description' => $category->description?->description,
                                ],
                                'parent_id' => $category->parent_id,
                                'department_id' => $category->department_id,
                                'parent' => $category->parent ? [
                                    'description' => ['name' => $category->parent->description?->name]
                                ] : null
                                ]))">
                                <div class="inline-flex items-center gap-2">
                                    <button @click="openEditModal(@js([
                                        'id' => $category->category_id,
                                        'name' => $category->description?->name,
                                        'description' => $category->description?->description,
                                        'parent_id' => $category->parent_id,
                                        'priority' => $category->priority,
                                        'department_id' => $category->department_id,
                                    ]))"
                                            class="admin-icon-btn admin-icon-btn--edit"
                                            title="Editar categoria">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="openViewModal(@js([
                                        'id' => $category->category_id,
                                        'name' => $category->description?->name,
                                        'description' => $category->description?->description,
                                        'parent_id' => $category->parent_id,
                                        'priority' => $category->priority,
                                        'priority_label' => $category->getPriorityLabel(),
                                        'permalink' => $category->description?->permalink,
                                        'children' => $category->children->map->only(['category_id', 'description'])->values(),
                                    ]))"
                                            class="admin-icon-btn admin-icon-btn--view"
                                            title="Ver detalhes">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button @click="deleteCategory({{ $category->category_id }}, '{{ $category->description?->name }}')"
                                            class="admin-icon-btn admin-icon-btn--delete"
                                            title="Excluir categoria">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- Seção de Subcategorias Expandida --}}
                        <tr x-show="expanded[{{ $category->category_id }}]" x-transition class="bg-gray-50/50">
                            <td colspan="6" class="px-6 py-4">
                                <div class="space-y-3">
                                    {{-- Cabeçalho da seção de subcategorias --}}
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-sm font-semibold text-gray-700">
                                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            Subcategorias de "{{ $category->description?->name }}"
                                        </h3>
                                        <button @click="openCreateSubcategoryModal({{ $category->category_id }}, '{{ $category->priority }}')"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700 transition">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Nova Subcategoria
                                        </button>
                                    </div>

                                    {{-- Lista de subcategorias --}}
                                    @if($category->children->count() > 0)
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nome</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Prioridade / Peso</th>
                                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach($category->children as $subcategory)
                                                        <tr class="hover:bg-blue-50/30 transition-colors">
                                                            <td class="px-4 py-3 text-xs text-gray-500 font-mono">#{{ $subcategory->category_id }}</td>
                                                            <td class="px-4 py-3 text-sm font-medium text-gray-700">
                                                                {{ $subcategory->description?->name ?? 'Sem nome' }}
                                                                @if($subcategory->department)
                                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                                                                        {{ $subcategory->department->name }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $subcategory->getPriorityBgColor() }} {{ $subcategory->getPriorityColor() }}">
                                                                    {{ $subcategory->getPriorityLabel() }} • Peso {{ $subcategory->getPriorityWeight() }}
                                                                </span>
                                                                <p class="mt-1 text-[11px] text-gray-500">
                                                                    Peso final do ticket:
                                                                    <strong>{{ $category->calculateTicketWeightWith($subcategory) }}</strong>
                                                                </p>
                                                            </td>
                                                            <td class="px-4 py-3 text-right" x-data="categoryRow(@js([
                                                                'category_id' => $subcategory->category_id,
                                                                'priority' => $subcategory->priority ?? 'low',
                                                                'description' => [
                                                                    'name' => $subcategory->description?->name,
                                                                    'permalink' => $subcategory->description?->permalink,
                                                                    'description' => $subcategory->description?->description,
                                                                ],
                                                                'parent_id' => $subcategory->parent_id,
                                                                'department_id' => $subcategory->department_id,
                                                                'parent' => $subcategory->parent ? [
                                                                    'description' => ['name' => $subcategory->parent->description?->name]
                                                                ] : null
                                                            ]))">
                                                                <div class="inline-flex items-center gap-1">

                                                                    <button @click="openEditModal(@js([
                                                                        'id' => $subcategory->category_id,
                                                                        'name' => $subcategory->description?->name,
                                                                        'description' => $subcategory->description?->description,
                                                                        'parent_id' => $subcategory->parent_id,
                                                                        'priority' => $subcategory->priority,
                                                                        'department_id' => $subcategory->department_id,
                                                                    ]))"
                                                                            class="p-1.5 text-blue-600 hover:bg-blue-100 rounded transition"
                                                                            title="Editar">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                        </svg>
                                                                    </button>
                                                                    <button @click="deleteCategory({{ $subcategory->category_id }}, '{{ $subcategory->description?->name }}')"
                                                                            class="p-1.5 text-red-600 hover:bg-red-100 rounded transition"
                                                                            title="Excluir">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-6 text-sm text-gray-500">
                                            Nenhuma subcategoria cadastrada.
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                    </svg>
                                    <p class="text-sm text-gray-500 font-medium">Nenhuma categoria cadastrada</p>
                                    <p class="text-xs text-gray-400 mt-1">Clique em "Nova Categoria" para começar</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modals --}}
    @include('admin.categories.modals.create')
    @include('admin.categories.modals.edit')
    @include('admin.categories.modals.view')

</div>
@endsection
