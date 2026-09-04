@extends('layouts.agent')

@section('title', 'EasyWiki — Editar Artigo #' . $article->id)

@section('content')
<script>
function expandableText(initial, label) {
    return {
        value: initial || '',
        expanded: false,
        label: label,
        autoResize(el) {
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = Math.max(el.scrollHeight, 120) + 'px';
        },
        open() {
            this.expanded = true;
            this.$nextTick(() => { this.$refs.modal?.focus(); });
        },
        close() {
            this.expanded = false;
            this.$nextTick(() => { this.autoResize(this.$refs.inline); });
        },
        format(prefix, suffix = '') {
            const textarea = this.expanded ? this.$refs.modal : this.$refs.inline;
            if (!textarea) return;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = this.value || '';
            const selected = text.substring(start, end);
            const replacement = prefix + (selected || 'texto') + suffix;

            this.value = text.substring(0, start) + replacement + text.substring(end);

            this.$nextTick(() => {
                textarea.focus();
                const cursorStart = start + prefix.length;
                const cursorEnd = cursorStart + (selected ? selected.length : 5);
                textarea.setSelectionRange(cursorStart, cursorEnd);
                if (!this.expanded) this.autoResize(textarea);
            });
        },
        insertImage() {
            const url = prompt('Cole a URL da imagem:');
            if (url) {
                this.format('![Imagem](' + url + ')', '');
            }
        },
        insertLink() {
            const url = prompt('Cole a URL do link:', 'https://');
            if (url) {
                this.format('[', '](' + url + ')');
            }
        }
    };
}
</script>

<div class="max-w-3xl mx-auto space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <a href="{{ route('agent.knowledge.index') }}" class="hover:text-indigo-600 transition-colors">EasyWiki</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="{{ route('agent.knowledge.show', $article) }}" class="hover:text-indigo-600 transition-colors truncate max-w-xs">{{ $article->title }}</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-600">Editar</span>
    </nav>

    <form method="POST" action="{{ route('agent.knowledge.update', $article) }}"
          class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        @csrf
        @method('PUT')

        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
            <h1 class="text-lg font-extrabold text-gray-900">Editar Artigo #{{ $article->id }}</h1>
            <p class="text-xs text-gray-500 mt-0.5">Atualize o problema e a solução documentados</p>
        </div>

        <div class="p-6 space-y-5">

            {{-- Título --}}
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">
                    Título do Artigo <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title"
                       value="{{ old('title', $article->title) }}"
                       placeholder="Ex: Erro ao gerar nota fiscal no módulo Fiscal"
                       class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl
                              outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                       required>
                @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Categoria --}}
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">
                    Categoria
                </label>
                <select name="category_id"
                        class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl
                               outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    <option value="">Sem categoria</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->category_id }}"
                                @selected(old('category_id', $article->category_id) == $cat->category_id)>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Problema --}}
            <div x-data="expandableText({{ json_encode(old('problem', $article->problem ?? '')) }}, 'Descrição do Problema')">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest">
                        Descrição do Problema <span class="text-red-500">*</span>
                    </label>
                    <button type="button" @click="open()"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-orange-500 hover:text-orange-600 transition-opacity hover:opacity-80">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                        Expandir
                    </button>
                </div>

                {{-- Barra de Ferramentas de Formatação --}}
                <div class="flex items-center flex-wrap gap-1 p-1.5 bg-gray-100 dark:bg-slate-800 border border-b-0 border-gray-200 dark:border-slate-700 rounded-t-xl text-xs">
                    <button type="button" @click="format('**', '**')" title="Negrito" class="px-2 py-1 font-bold bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">B</button>
                    <button type="button" @click="format('*', '*')" title="Itálico" class="px-2 py-1 italic bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">I</button>
                    <button type="button" @click="format('<u>', '</u>')" title="Sublinhado" class="px-2 py-1 underline bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">U</button>
                    <button type="button" @click="format('## ', '')" title="Título H2" class="px-2 py-1 font-bold bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">H2</button>
                    <button type="button" @click="format('- ', '')" title="Lista com Marcadores" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">• Lista</button>
                    <button type="button" @click="format('1. ', '')" title="Lista Numerada" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">1. Lista</button>
                    <button type="button" @click="format('- [ ] ', '')" title="Checklist / Tarefa" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">☑ Tarefa</button>
                    <button type="button" @click="insertLink()" title="Inserir Link" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">🔗 Link</button>
                    <button type="button" @click="insertImage()" title="Inserir Imagem" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">🖼️ Imagem</button>
                    <button type="button" @click="format('```\n', '\n```')" title="Bloco de Código" class="px-2 py-1 font-mono bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">&lt;/&gt;</button>
                    <button type="button" @click="format('> ', '')" title="Citação" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">” Citação</button>
                </div>

                <textarea name="problem" x-ref="inline" x-model="value"
                          @input="autoResize($refs.inline)" x-init="autoResize($refs.inline)"
                          placeholder="Descreva o problema, o contexto e as condições em que ocorre..."
                          style="min-height:120px"
                          class="w-full px-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-b-xl
                                 outline-none focus:ring-2 focus:ring-indigo-500 transition-all resize-none overflow-hidden"
                          required></textarea>
                <p class="text-right text-[11px] mt-1 text-gray-400" x-text="value.length + ' caracteres'"></p>
                @error('problem') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror

                {{-- Modal fullscreen --}}
                <div x-show="expanded"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="fixed inset-0 z-50 flex flex-col"
                     style="background:rgba(10,18,30,0.97)"
                     @keydown.escape.window="close()"
                     x-cloak>
                    <div class="flex items-center justify-between px-5 py-3 shrink-0" style="border-bottom:1px solid #1e3a5f">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wider" style="color:#94a3b8" x-text="label"></span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs text-slate-500" x-text="value.length + ' caracteres'"></span>
                            <button type="button" @click="close()"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-opacity hover:opacity-80">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Fechar (Esc)
                            </button>
                        </div>
                    </div>

                    {{-- Toolbar no modo tela cheia --}}
                    <div class="flex items-center flex-wrap gap-1 px-5 py-2 shrink-0" style="background:#1e293b;border-bottom:1px solid #334155">
                        <button type="button" @click="format('**', '**')" title="Negrito" class="px-2 py-1 font-bold bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">B</button>
                        <button type="button" @click="format('*', '*')" title="Itálico" class="px-2 py-1 italic bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">I</button>
                        <button type="button" @click="format('<u>', '</u>')" title="Sublinhado" class="px-2 py-1 underline bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">U</button>
                        <button type="button" @click="format('## ', '')" title="Título H2" class="px-2 py-1 font-bold bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">H2</button>
                        <button type="button" @click="format('- ', '')" title="Lista" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">• Lista</button>
                        <button type="button" @click="format('1. ', '')" title="Lista Numerada" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">1. Lista</button>
                        <button type="button" @click="format('- [ ] ', '')" title="Tarefa" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">☑ Tarefa</button>
                        <button type="button" @click="insertLink()" title="Link" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">🔗 Link</button>
                        <button type="button" @click="insertImage()" title="Imagem" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">🖼️ Imagem</button>
                        <button type="button" @click="format('```\n', '\n```')" title="Código" class="px-2 py-1 font-mono bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">&lt;/&gt;</button>
                        <button type="button" @click="format('> ', '')" title="Citação" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">” Citação</button>
                    </div>

                    <div class="flex-1 p-4 overflow-hidden">
                        <textarea x-ref="modal" x-model="value"
                                  placeholder="Descreva o problema, o contexto e as condições em que ocorre..."
                                  class="w-full h-full px-5 py-4 rounded-xl text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                                  style="background:#0f172a;color:#e2e8f0;border:1px solid #1e3a5f;font-family:inherit"></textarea>
                    </div>
                </div>
            </div>

            {{-- Solução --}}
            <div x-data="expandableText({{ json_encode(old('solution', $article->solution ?? '')) }}, 'Solução')">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest">
                        Solução <span class="text-red-500">*</span>
                    </label>
                    <button type="button" @click="open()"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-orange-500 hover:text-orange-600 transition-opacity hover:opacity-80">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                        Expandir
                    </button>
                </div>

                {{-- Barra de Ferramentas de Formatação --}}
                <div class="flex items-center flex-wrap gap-1 p-1.5 bg-gray-100 dark:bg-slate-800 border border-b-0 border-gray-200 dark:border-slate-700 rounded-t-xl text-xs">
                    <button type="button" @click="format('**', '**')" title="Negrito" class="px-2 py-1 font-bold bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">B</button>
                    <button type="button" @click="format('*', '*')" title="Itálico" class="px-2 py-1 italic bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">I</button>
                    <button type="button" @click="format('<u>', '</u>')" title="Sublinhado" class="px-2 py-1 underline bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">U</button>
                    <button type="button" @click="format('## ', '')" title="Título H2" class="px-2 py-1 font-bold bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">H2</button>
                    <button type="button" @click="format('- ', '')" title="Lista com Marcadores" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">• Lista</button>
                    <button type="button" @click="format('1. ', '')" title="Lista Numerada" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">1. Lista</button>
                    <button type="button" @click="format('- [ ] ', '')" title="Checklist / Tarefa" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">☑ Tarefa</button>
                    <button type="button" @click="insertLink()" title="Inserir Link" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">🔗 Link</button>
                    <button type="button" @click="insertImage()" title="Inserir Imagem" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">🖼️ Imagem</button>
                    <button type="button" @click="format('```\n', '\n```')" title="Bloco de Código" class="px-2 py-1 font-mono bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">&lt;/&gt;</button>
                    <button type="button" @click="format('> ', '')" title="Citação" class="px-2 py-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded hover:bg-gray-50 text-gray-700 dark:text-gray-200">” Citação</button>
                </div>

                <textarea name="solution" x-ref="inline" x-model="value"
                          @input="autoResize($refs.inline)" x-init="autoResize($refs.inline)"
                          placeholder="Descreva passo a passo como o problema foi resolvido..."
                          style="min-height:140px"
                          class="w-full px-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-b-xl
                                 outline-none focus:ring-2 focus:ring-indigo-500 transition-all resize-none overflow-hidden"
                          required></textarea>
                <p class="text-right text-[11px] mt-1 text-gray-400" x-text="value.length + ' caracteres'"></p>
                @error('solution') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror

                {{-- Modal fullscreen --}}
                <div x-show="expanded"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="fixed inset-0 z-50 flex flex-col"
                     style="background:rgba(10,18,30,0.97)"
                     @keydown.escape.window="close()"
                     x-cloak>
                    <div class="flex items-center justify-between px-5 py-3 shrink-0" style="border-bottom:1px solid #1e3a5f">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wider" style="color:#94a3b8" x-text="label"></span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs text-slate-500" x-text="value.length + ' caracteres'"></span>
                            <button type="button" @click="close()"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-opacity hover:opacity-80">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Fechar (Esc)
                            </button>
                        </div>
                    </div>

                    {{-- Toolbar no modo tela cheia --}}
                    <div class="flex items-center flex-wrap gap-1 px-5 py-2 shrink-0" style="background:#1e293b;border-bottom:1px solid #334155">
                        <button type="button" @click="format('**', '**')" title="Negrito" class="px-2 py-1 font-bold bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">B</button>
                        <button type="button" @click="format('*', '*')" title="Itálico" class="px-2 py-1 italic bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">I</button>
                        <button type="button" @click="format('<u>', '</u>')" title="Sublinhado" class="px-2 py-1 underline bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">U</button>
                        <button type="button" @click="format('## ', '')" title="Título H2" class="px-2 py-1 font-bold bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">H2</button>
                        <button type="button" @click="format('- ', '')" title="Lista" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">• Lista</button>
                        <button type="button" @click="format('1. ', '')" title="Lista Numerada" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">1. Lista</button>
                        <button type="button" @click="format('- [ ] ', '')" title="Tarefa" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">☑ Tarefa</button>
                        <button type="button" @click="insertLink()" title="Link" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">🔗 Link</button>
                        <button type="button" @click="insertImage()" title="Imagem" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">🖼️ Imagem</button>
                        <button type="button" @click="format('```\n', '\n```')" title="Código" class="px-2 py-1 font-mono bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">&lt;/&gt;</button>
                        <button type="button" @click="format('> ', '')" title="Citação" class="px-2 py-1 bg-slate-700 border border-slate-600 rounded hover:bg-slate-600 text-gray-200 text-xs">” Citação</button>
                    </div>

                    <div class="flex-1 p-4 overflow-hidden">
                        <textarea x-ref="modal" x-model="value"
                                  placeholder="Descreva passo a passo como o problema foi resolvido..."
                                  class="w-full h-full px-5 py-4 rounded-xl text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                                  style="background:#0f172a;color:#e2e8f0;border:1px solid #1e3a5f;font-family:inherit"></textarea>
                    </div>
                </div>
            </div>

            {{-- Tags e visibilidade --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">
                        Tags
                    </label>
                    <input type="text" name="tags" value="{{ old('tags', $article->tags) }}"
                           placeholder="fiscal, nfe, erro (separadas por vírgula)"
                           class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl
                                  outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    <p class="text-[10px] text-gray-400 mt-1">Separe as tags com vírgula</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-1.5">
                        Visibilidade
                    </label>
                    <select name="visibility"
                            class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl
                                   outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="internal" @selected(old('visibility', $article->visibility) === 'internal')>
                            Interno (somente equipe)
                        </option>
                        <option value="public" @selected(old('visibility', $article->visibility) === 'public')>
                            Público (clientes podem ver)
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center gap-3">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700
                           text-white rounded-xl font-bold text-sm shadow-md transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 13l4 4L19 7"/>
                </svg>
                Salvar Alterações
            </button>
            <a href="{{ route('agent.knowledge.show', $article) }}"
               class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
