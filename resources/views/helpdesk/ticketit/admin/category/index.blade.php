@extends('admin.layouts.master')

@section('content')
<div x-data="categoryManagerHelpdesk()" class="py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Categorias - Painel de Suporte</h1>
        <p class="text-gray-500">Gerencie departamentos e categorias do helpdesk</p>
    </div>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('v2.tasks.tickets.admin.category.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded shadow-sm hover:bg-green-700">Nova Categoria</a>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cor</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($categories as $category)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $category->id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-800">{{ $category->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="inline-flex items-center gap-2">
                                    <span class="inline-block w-3 h-3 rounded-full border" style="background-color: {{ $category->color ?? '#3B82F6' }}"></span>
                                    <span>{{ $category->color ?? '#3B82F6' }}</span>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('v2.tasks.tickets.admin.category.edit', $category->id) }}" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-600 rounded mr-2">Editar</a>
                                <button @click="deleteCategory({{ $category->id }})" class="inline-flex items-center px-3 py-1 bg-red-100 text-red-600 rounded">Remover</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
