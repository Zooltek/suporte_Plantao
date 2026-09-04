@extends('layouts.master')

@section('header')
    {{-- Mantendo os CSS específicos do plugin, mas agora via Vite ou CDN se preferir --}}
    <link rel="stylesheet" href="{{ asset('js/form-builder/form-builder.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/form-builder/form-render.min.css') }}">
@endsection

@section('content')
<div class="max-w-5xl mx-auto my-8 p-6 bg-white border border-gray-200 rounded-lg shadow-sm"
     x-data="{
        htmlPreview: @js($category->description->html_header),
        submitting: false
     }">

    @include('shared.errors')

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Editar Categoria: {{ $category->getName() }}</h1>
            <p class="text-xs text-gray-400 mt-1 uppercase tracking-wider">ID Interno: {{ $id }}</p>
        </div>
        <a href="{{ route('admin.category.show', $category->ticket_category_id) }}"
           class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors">
            <i class="fa-solid fa-chevron-left mr-2"></i> Voltar para Categorias
        </a>
    </div>

    <form method="POST" action="{{ route('admin.category.update') }}" @submit="submitting = true">
        @csrf
        <input type="hidden" name="setor" value="{{ $category->ticket_category_id }}">
        <input type="hidden" name="id" value="{{ $category->category_id }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Nome --}}
            <div class="space-y-1">
                <label for="name" class="block text-sm font-bold text-gray-700">Nome:</label>
                <input type="text" id="name" name="name" value="{{ $category->getName() }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
            </div>

            {{-- Imagem --}}
            <div class="space-y-1">
                <label for="image" class="block text-sm font-bold text-gray-700">Caminho da Imagem:</label>
                <input type="text" id="image" name="image" value="{{ $category->description->image }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
            </div>

            {{-- Descrição --}}
            <div class="space-y-1">
                <label for="desc" class="block text-sm font-bold text-gray-700">Descrição:</label>
                <input type="text" id="desc" name="desc" value="{{ $category->getDescription() }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>

            {{-- Sort Order --}}
            <div class="space-y-1">
                <label for="sort" class="block text-sm font-bold text-gray-700">Ordem (Sort):</label>
                <input type="number" id="sort" name="sort" value="{{ $category->sort_order }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
            </div>
        </div>

        {{-- Switches / Checkboxes --}}
        <div class="flex flex-wrap gap-6 my-8 p-4 bg-gray-50 rounded-lg border border-gray-100">
            <label class="inline-flex items-center cursor-pointer group">
                <input type="checkbox" name="visible" value="1" {{ $category->visible ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                <span class="ml-2 text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors">Visible</span>
            </label>

            <label class="inline-flex items-center cursor-pointer group">
                <input type="checkbox" name="status" value="1" {{ $category->status ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                <span class="ml-2 text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors">Status Ativo</span>
            </label>

            <label class="inline-flex items-center cursor-pointer group">
                <input type="checkbox" name="header" value="1" {{ $category->header ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                <span class="ml-2 text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors">Exibir no Header</span>
            </label>
        </div>

        {{-- HTML Editor --}}
        <div class="mb-8">
            <label for="html_textarea" class="block text-sm font-bold text-gray-700 mb-2">Cabeçalho HTML:</label>
            <textarea id="html_textarea" rows="5" name="html" x-model="htmlPreview"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono text-blue-800"></textarea>
            
            <button type="button"
                    class="mt-2 inline-flex items-center text-xs font-bold text-blue-600 hover:text-blue-800"
                    @click="globalThis.Utils.previewHtml(htmlPreview)">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Pré-visualizar Cabeçalho
            </button>
        </div>

        {{-- Form Builder Section --}}
        <div class="p-6 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 mb-8">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fa-solid fa-cubes-stacked mr-2 text-blue-600"></i> Editor de Formulário
            </h2>
            <div id="build-wrap" class="bg-white p-2 rounded shadow-inner"></div>
            <div id="render-wrap"></div>
        </div>

        {{-- Ações Finais --}}
        <div class="flex items-center gap-4 pt-6 border-t border-gray-200">
            <button type="submit"
                    class="inline-flex items-center px-8 py-2.5 border border-transparent text-sm font-bold rounded-md shadow-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50"
                    :disabled="submitting">
                <i class="fa-solid fa-check mr-2" x-show="!submitting"></i>
                <i class="fa-solid fa-circle-notch fa-spin mr-2" x-show="submitting"></i>
                Salvar Alterações
            </button>
        </div>
    </form>
</div>
@endsection

@section('footer')
    @vite(['resources/js/category/category-edit.js'])
@endsection
