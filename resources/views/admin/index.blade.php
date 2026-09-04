@extends('admin.layouts.master')

@section('content')
<main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Cabeçalho Semântico --}}
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Gerenciamento de Categorias</h1>
            <p class="text-sm text-gray-500 mt-1">Ajuste prioridades e permalinks diretamente na tabela.</p>
        </div>

        <button type="button"
                class="inline-flex items-center px-5 py-2.5 bg-blue-600 border border-transparent rounded-lg font-bold text-sm text-white shadow-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
                data-bs-toggle="modal"
                data-bs-target="#exampleModal">
            <i class="fa fa-plus mr-2" aria-hidden="true"></i> Nova Categoria
        </button>
    </header>

    {{-- Container da Tabela --}}
    <section class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            {{-- Cabeçalho da Tabela --}}
            <div class="grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-widest">
                <div class="col-span-1">#</div>
                <div class="col-span-3">Nome / Caminho</div>
                <div class="col-span-2">Prioridade</div>
                <div class="col-span-4">Permalink</div>
                <div class="col-span-2 text-center">Ações</div>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($categories as $category)
                <form class="category-update-form group"
                      data-id="{{ $category->category_id }}"
                      x-data="{ submitting: false, success: false }"
                      @submit.prevent="
                        submitting = true;
                        globalThis.CategoryActions.update($el)
                            .then(() => { success = true; setTimeout(() => success = false, 2000); })
                            .finally(() => submitting = false);
                      ">
                    @csrf
                    <div class="grid grid-cols-12 gap-4 px-6 py-3 items-center hover:bg-gray-50/50 transition-colors">
                        <div class="col-span-1 text-sm font-mono text-gray-400">
                            #{{ $category->category_id }}
                        </div>
                        
                        <div class="col-span-3">
                            <input type="text" readonly
                                   class="block w-full text-xs font-medium border-transparent bg-gray-100 rounded-md text-gray-600 focus:ring-0 cursor-default"
                                   value="{{ $category->getParentName() . ' - ' .  $category->description->name}}">
                        </div>

                        <div class="col-span-2">
                            <select name="priority"
                                    class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors">
                                <option value="low" @selected($category->priority == 'low')>Baixa</option>
                                <option value="high" @selected($category->priority == 'high')>Alta</option>
                                <option value="urgent" @selected($category->priority == 'urgent')>Urgente</option>
                            </select>
                        </div>

                        <div class="col-span-4">
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-400 text-xs italic">/</span>
                                </div>
                                <input type="text" name="permalink"
                                       class="block w-full pl-6 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                       value="{{ $category->description->permalink }}">
                            </div>
                        </div>

                        <nav class="col-span-2 flex justify-center" aria-label="Controles de categoria">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 rounded-md shadow-sm text-white transition-all focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    :class="success ? 'bg-green-600 focus:ring-green-500' : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'"
                                    :disabled="submitting">
                                <i class="fa" :class="submitting ? 'fa-spinner fa-spin' : (success ? 'fa-check' : 'fa-save')" aria-hidden="true"></i>
                                <span class="sr-only">Salvar alterações da categoria #{{ $category->category_id }}</span>
                            </button>
                        </nav>
                    </div>
                </form>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Modal Categoria (Refatorado para Tailwind) --}}
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true" x-data="{ saving: false }">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-xl shadow-2xl overflow-hidden">
                <header class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h5 class="text-lg font-bold text-gray-800">Nova Categoria</h5>
                    <button type="button" class="text-gray-400 hover:text-gray-600 focus:outline-none" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </header>
                
                <main class="p-6">
                    <form id="new-category-form" class="space-y-4">
                        @csrf
                        <div>
                            <label for="new-category-name" class="block text-sm font-bold text-gray-700 mb-1">Nome da Categoria</label>
                            <input type="text" id="new-category-name" name="name"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="new-category-parent" class="block text-sm font-bold text-gray-700 mb-1">Categoria Pai</label>
                            <select id="new-category-parent" name="parent_id"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                                <option value="" selected disabled>Selecione uma categoria...</option>
                                @foreach($parents as $parent)
                                    <option value="{{ $parent->category_id }}">{{ $parent->description->name }}</option>
                                @endforeach
                                <option value="0">-- Criar como Categoria Principal --</option>
                            </select>
                        </div>
                    </form>
                </main>

                <footer class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button"
                            class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent rounded-lg font-bold text-sm text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all disabled:opacity-50"
                            :disabled="saving"
                            @click="
                                saving = true;
                                globalThis.CategoryActions.create(document.getElementById('new-category-form'))
                                    .finally(() => saving = false);
                            ">
                        <span x-show="!saving">Salvar</span>
                        <span x-show="saving" x-cloak class="flex items-center">
                            <i class="fa fa-circle-notch fa-spin mr-2"></i> Salvando...
                        </span>
                    </button>
                </footer>
            </div>
        </div>
    </div>
</main>
@endsection

@section('footer')
    <script>
        globalThis.CategoryConfig = {
            apiUrl: "{{ url('admin/api/v1/categories') }}"
        };
    </script>
    @vite(['resources/js/admin/category/category-manager.js'])
@endsection
