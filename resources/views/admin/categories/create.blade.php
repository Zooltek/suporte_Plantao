@extends('admin.layouts.master')

@section('content')
<div x-data="categoryCreateForm()" class="py-8">
    {{-- Header Moderno --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Criar Nova Categoria</h1>
            <p class="text-gray-500 font-light mt-1">Preencha os campos abaixo para criar uma nova categoria</p>
        </div>

        <a href="{{ url('admin/category/' . $setor->id) }}" 
           class="inline-flex items-center px-5 py-2.5 bg-gray-100 border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-all">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar para Categorias
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        @include('shared.errors')

        <form method="POST" action="{{ url('admin/category/store') }}" class="p-6">
            @csrf
            <input type="hidden" name="setor" value="{{ $setor->id }}">

            <div class="space-y-6">
                {{-- Linha 1: Nome e Setor --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Nome --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome da Categoria <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                               placeholder="Ex: Vue.js">
                    </div>

                    {{-- Setor (disabled) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Setor
                        </label>
                        <input type="text" 
                               class="w-full px-4 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-500 cursor-not-allowed text-sm" 
                               placeholder="{{ $setor->name }}" 
                               disabled>
                    </div>
                </div>

                {{-- Linha 2: Parent e Sort Order --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Parent --}}
                    <div>
                        <label for="parent" class="block text-sm font-medium text-gray-700 mb-2">
                            Categoria <span class="text-red-500">*</span>
                        </label>
                        <select name="parent"
                                id="parent"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all">
                            <option value="" @selected(! old('parent')) disabled>Selecione a categoria</option>
                            @foreach((array) $data as $category)
                                <option value="{{ $category['id'] }}" @selected(old('parent') == $category['id'])>
                                    {{ $category['name'] }}
                                </option>
                            @endforeach
                            <option value="0" @selected(old('parent') === '0')>Nova categoria</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Selecione "Nova categoria" para criar uma categoria sem vínculo.
                        </p>
                    </div>

                    {{-- Sort Order --}}
                    <div>
                        <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">
                            Ordem de Exibição <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="sort" 
                               name="sort" 
                               value="{{ old('sort', 0) }}" 
                               required
                               min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                               placeholder="0">
                        <p class="mt-1 text-xs text-gray-500">
                            Número menor = aparece primeiro
                        </p>
                    </div>
                </div>

                {{-- Descrição --}}
                <div>
                    <label for="desc" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="desc" 
                           name="desc" 
                           value="{{ old('desc') }}" 
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                           placeholder="Breve descrição da categoria">
                </div>

                {{-- Imagem --}}
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                        Caminho da Imagem <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="image" 
                           name="image" 
                           value="{{ old('image') }}" 
                           required
                           placeholder="category/image.png" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm font-mono transition-all">
                    <p class="mt-1 text-xs text-gray-500">
                        Ex: category/tecnologia.png ou https://exemplo.com/imagem.jpg
                    </p>
                </div>

                {{-- Checkboxes --}}
                <div class="border-t border-gray-100 pt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Configurações de Visibilidade
                    </label>
                    <div class="flex flex-wrap gap-6">
                        <label class="inline-flex items-center cursor-pointer group">
                            <input type="checkbox" 
                                   name="visible" 
                                   value="1" 
                                   @checked(old('visible', true)) 
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition-all">
                            <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">Visível</span>
                        </label>

                        <label class="inline-flex items-center cursor-pointer group">
                            <input type="checkbox" 
                                   name="status" 
                                   value="1" 
                                   @checked(old('status', true)) 
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition-all">
                            <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">Ativo</span>
                        </label>

                        <label class="inline-flex items-center cursor-pointer group">
                            <input type="checkbox" 
                                   name="header" 
                                   value="1" 
                                   @checked(old('header', true)) 
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition-all">
                            <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">Exibir no Header</span>
                        </label>
                    </div>
                </div>

                {{-- HTML Customizado --}}
                <div class="border-t border-gray-100 pt-6">
                    <label for="html_textarea" class="block text-sm font-medium text-gray-700 mb-2">
                        HTML Customizado <span class="text-gray-400 text-xs">(opcional)</span>
                    </label>
                    <textarea id="html_textarea" 
                              name="html" 
                              rows="6" 
                              x-model="htmlContent"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm bg-slate-50 focus:bg-white transition-all"
                              placeholder="<div>...</div>">{{ old('html') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Código HTML que será renderizado na página da categoria
                    </p>
                </div>

                {{-- Botões de Ação --}}
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <button type="button" 
                            @click="previewHTML()"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Pré-visualizar HTML
                    </button>

                    <button type="submit" 
                            class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md transition-all font-bold text-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Criar Categoria
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function categoryCreateForm() {
    return {
        htmlContent: '{{ old("html") }}',

        previewHTML() {
            if (!this.htmlContent || this.htmlContent.trim() === '') {
                alert('⚠️ Digite algum HTML primeiro!');
                return;
            }

            const win = window.open("", "_blank", "toolbar=no,scrollbars=yes,resizable=yes,width=900,height=700");
            win.document.write(`
                <!DOCTYPE html>
                <html lang="pt-BR">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Preview HTML - Categoria</title>
                        <style>
                            * { margin: 0; padding: 0; box-sizing: border-box; }
                            body { 
                                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                                padding: 20px; 
                                background: #f9fafb;
                            }
                        </style>
                    </head>
                    <body>
                        ${this.htmlContent}
                    </body>
                </html>
            `);
            win.document.close();
        }
    };
}
</script>
@endpush
