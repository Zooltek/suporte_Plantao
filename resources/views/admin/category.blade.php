@extends('layouts.master')

@section('page')
    {{ trans('ticketit::admin.administrator-index-title') }} - Categorias
@endsection

@section('content')
<main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900"
      x-data="{ baseUrl: '{{ url('admin/category') }}' }">

    @include('shared.errors')

    {{-- Cabeçalho --}}
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 tracking-tight">
                Central de Categorias
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Gerencie a classificação e ordem das categorias por setor.
            </p>
        </div>
        <a href="{{ url('admin/category/create/' . $id) }}"
           class="inline-flex items-center px-5 py-2.5 bg-blue-600 dark:bg-blue-500 border border-transparent rounded-lg font-bold text-sm text-white shadow-md hover:bg-blue-700 dark:hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
            <i class="fa fa-plus-circle mr-2" aria-hidden="true"></i> Criar nova Categoria
        </a>
    </header>

    {{-- Área de Filtro --}}
    <section class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-8 flex flex-col sm:flex-row sm:items-center gap-4">
        <label for="setor" class="text-sm font-bold text-gray-700 dark:text-gray-300">Filtrar por Setor:</label>
        <div class="relative w-full sm:max-w-xs">
            <select id="setor"
                    name="setor"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-all dark:bg-gray-900 dark:text-gray-100"
                    @change="globalThis.location.href = `${baseUrl}/${$event.target.value}`"
                    required>
                @foreach($setores as $setor)
                    <option value="{{ $setor->id }}" @selected($id == $setor->id)>
                        {{ $setor->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </section>

    {{-- Tabela de Categorias --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-widest w-24">ID</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Nome da Categoria</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Ordem de Exibição</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-widest pr-10">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-700">
                @forelse((array)$data as $category)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-400 dark:text-gray-300">
                            #{{ $category['id'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $category['name'] }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                {{ $category['order'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right pr-10">
                            <a href="{{ url('admin/category/edit/' . $category['id']) }}"
                               class="inline-flex items-center px-3 py-1.5 border border-blue-200 dark:border-blue-500 text-xs font-bold rounded-md text-blue-600 dark:text-blue-300 bg-blue-50 dark:bg-blue-900 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-700 transition-all shadow-sm group-hover:shadow-md">
                                <i class="fa fa-pencil mr-1.5" aria-hidden="true"></i> Editar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fa fa-folder-open text-gray-200 dark:text-gray-600 text-5xl mb-4" aria-hidden="true"></i>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">Nenhuma categoria encontrada para este setor.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection
