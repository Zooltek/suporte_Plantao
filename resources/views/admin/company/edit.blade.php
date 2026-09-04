@extends('layouts.master')

@section('content')
<div class="max-w-4xl mx-auto my-8 p-6 bg-white border border-gray-200 rounded-lg shadow-sm"
     x-data="{ submitting: false }">

    @include('shared.errors')

    <nav aria-label="Breadcrumb" class="mb-6">
        <a href="{{ url('admin/category/' . $category->ticket_category_id) }}"
           class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors">
            <i class="fa fa-arrow-left mr-2"></i> Voltar para Categorias
        </a>
    </nav>

    <h1 class="text-2xl font-bold text-gray-800 mb-8">
        Editar Categoria: <span class="text-blue-600">{{ $category->getName() }}</span>
        <span class="text-sm font-normal text-gray-400 ml-2">({{ $id }})</span>
    </h1>

    <form method="POST" action="{{ url('admin/category/update') }}" @submit="submitting = true" class="space-y-6">
        @csrf
        <input type="hidden" name="setor" value="{{ $category->ticket_category_id }}">
        <input type="hidden" name="id" value="{{ $category->category_id }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1">
                <label for="name" class="block text-sm font-bold text-gray-700">Nome:</label>
                <input type="text" name="name" id="name" value="{{ $category->getName() }}"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
        </div>

        {{-- seção de Checkboxes --}}
        <div class="flex flex-col space-y-3 p-4 bg-gray-50 rounded-md border border-gray-100">
            <label class="inline-flex items-center cursor-pointer group">
                <input type="checkbox" name="visible" value="1" id="visible"
                       {{ $category->visible == 1 ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 h-4 w-4">
                <span class="ml-2 text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors">Visible</span>
            </label>

            <label class="inline-flex items-center cursor-pointer group">
                <input type="checkbox" name="status" value="1" id="status_check"
                       {{ $category->status == 1 ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 h-4 w-4">
                <span class="ml-2 text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors">Status</span>
            </label>

            <label class="inline-flex items-center cursor-pointer group">
                <input type="checkbox" name="header" value="1" id="header"
                       {{ $category->header == 1 ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 h-4 w-4">
                <span class="ml-2 text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors">Header</span>
            </label>
        </div>

        <div class="space-y-1">
            <label for="html_textarea" class="block text-sm font-bold text-gray-700">Conteúdo HTML Cabeçalho:</label>
            <textarea id="html_textarea" rows="5" name="html"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono text-gray-600"
                      placeholder="<div>...</div>">{{ $category->description->html_header }}</textarea>
        </div>

        <div class="flex items-center gap-3 pt-6 border-t border-gray-100">
            <button type="submit"
                    class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-md shadow-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50"
                    :disabled="submitting">
                <span x-show="!submitting" class="flex items-center">
                    <i class="fa fa-paper-plane mr-2"></i> Enviar
                </span>
                <span x-show="submitting" class="flex items-center" x-cloak>
                    <i class="fa fa-circle-notch fa-spin mr-2"></i> Enviando...
                </span>
            </button>

            <button type="button" id="pre-vis"
                    class="inline-flex items-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none shadow-sm transition-all group">
                <i class="fa fa-eye mr-2 text-gray-400 group-hover:text-blue-600 transition-colors"></i>
                Pré visualizar HTML
            </button>
        </div>
    </form>
</div>
@endsection

@push('footer')
    @vite(['resources/js/category-edit.js'])
@endpush
