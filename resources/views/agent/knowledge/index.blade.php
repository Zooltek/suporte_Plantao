@extends('layouts.agent')

@section('title', 'EasyWiki — Base de Conhecimento')

@section('content')
<style>
    html.ocean .notion-guide-box {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    html.ocean .notion-guide-box p,
    html.ocean .notion-guide-box strong {
        color: #ffffff !important;
    }
    html.ocean .notion-guide-box li {
        color: #e2e8f0 !important;
    }
    html.ocean .notion-guide-box a {
        color: #818cf8 !important;
    }
</style>

<div x-data="{
    activeTab: '{{ request('tab', 'local') }}',
    setTab(t) { this.activeTab = t; },
    openNotionModal: false,
    notionApiKey: '{{ $notionSettings['api_key'] ?? '' }}',
    notionDatabaseId: '{{ $notionSettings['database_id'] ?? '' }}',
    testingConnection: false,
    testResult: null,
    async testNotion() {
        if (!this.notionApiKey.trim()) {
            this.testResult = { success: false, message: 'Digite o Token do Notion antes de testar.' };
            return;
        }
        this.testingConnection = true;
        this.testResult = null;
        try {
            const res = await fetch('{{ route('agent.knowledge.notion.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    api_key: this.notionApiKey,
                    database_id: this.notionDatabaseId
                })
            });
            this.testResult = await res.json();
        } catch (e) {
            this.testResult = { success: false, message: 'Erro ao conectar: ' + e.message };
        } finally {
            this.testingConnection = false;
        }
    }
}" class="space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <a href="{{ route('agent.index') }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-600">EasyWiki</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">EasyWiki & Artigos</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Base de conhecimento interna e artigos integrados ao Notion</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($notionConfigured)
                <a href="{{ route('agent.knowledge.index', ['tab' => 'notion', 'refresh' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-xl font-bold text-xs transition-all border border-indigo-200 dark:border-indigo-800 shadow-sm"
                   title="Atualizar e sincronizar páginas do Notion">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Sincronizar Artigos</span>
                </a>
            @endif
            <button type="button" @click="openNotionModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl font-bold text-xs transition-all border border-slate-200 dark:border-slate-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M4.459 4.208c.746.606 1.026.56 2.428.466l13.215-.793c.28 0 .047-.28-.046-.326L17.86 1.968c-.42-.326-.981-.7-2.055-.607L3.01 2.295c-.466.046-.56.28-.374.466zm.793 3.08v13.904c0 .747.373 1.027 1.214.98l14.523-.84c.84-.046.933-.56.933-1.167V6.354c0-.606-.233-.933-.746-.887l-15.177.887c-.56.047-.747.327-.747.934zm13.682.933c.093.42.093.84-.28 1.167l-.7.653c-.187.14-.374.373-.374.653v8.587c0 .42-.187.7-.653.793-.42.093-.747-.094-.98-.42l-4.76-7.468v6.908c.28.233.56.467.56.793 0 .42-.326.654-.793.654l-2.614.14c-.093-.373-.046-.746.28-1.026l.7-.654c.187-.14.373-.373.373-.653V8.873c0-.42.187-.7.654-.793.42-.094.746.093.98.42l4.853 7.608V9.106c-.28-.233-.56-.466-.56-.793 0-.42.327-.653.794-.653z"/>
                </svg>
                <span>Configurar Notion</span>
            </button>
            <a href="{{ route('agent.knowledge.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white
                      rounded-xl font-bold text-sm shadow-md transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Novo Artigo
            </a>
        </div>
    </div>

    {{-- Tabs seletoras --}}
    <div class="flex border-b border-gray-200 dark:border-slate-800">
        <button type="button" @click="setTab('local')"
                :class="activeTab === 'local' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200 font-medium'"
                class="py-3 px-5 border-b-2 text-sm flex items-center gap-2 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <span>EasyWiki Local</span>
            <span class="px-2 py-0.5 text-xs rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold">
                {{ $articles->total() }}
            </span>
        </button>

        <button type="button" @click="setTab('notion')"
                :class="activeTab === 'notion' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200 font-medium'"
                class="py-3 px-5 border-b-2 text-sm flex items-center gap-2 transition-all">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M4.459 4.208c.746.606 1.026.56 2.428.466l13.215-.793c.28 0 .047-.28-.046-.326L17.86 1.968c-.42-.326-.981-.7-2.055-.607L3.01 2.295c-.466.046-.56.28-.374.466zm.793 3.08v13.904c0 .747.373 1.027 1.214.98l14.523-.84c.84-.046.933-.56.933-1.167V6.354c0-.606-.233-.933-.746-.887l-15.177.887c-.56.047-.747.327-.747.934zm13.682.933c.093.42.093.84-.28 1.167l-.7.653c-.187.14-.374.373-.374.653v8.587c0 .42-.187.7-.653.793-.42.093-.747-.094-.98-.42l-4.76-7.468v6.908c.28.233.56.467.56.793 0 .42-.326.654-.793.654l-2.614.14c-.093-.373-.046-.746.28-1.026l.7-.654c.187-.14.373-.373.373-.653V8.873c0-.42.187-.7.654-.793.42-.094.746.093.98.42l4.853 7.608V9.106c-.28-.233-.56-.466-.56-.793 0-.42.327-.653.794-.653z"/>
            </svg>
            <span>Notion Workspace</span>
            @if($notionConfigured)
                <span class="px-2 py-0.5 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold">
                    {{ count($notionArticles) }}
                </span>
            @else
                <span class="px-2 py-0.5 text-[10px] rounded-full bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 font-semibold">
                    Configurar
                </span>
            @endif
        </button>
    </div>

    {{-- Busca --}}
    <form method="GET" action="{{ route('agent.knowledge.index') }}"
          class="flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="tab" :value="activeTab">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Buscar artigos..."
                   class="w-full pl-9 pr-4 py-2.5 text-sm bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl
                          text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
        </div>
        <div x-show="activeTab === 'local'">
            <select name="category_id"
                    class="text-sm bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl px-3 py-2.5
                           text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                <option value="">Todas as categorias</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->category_id }}" @selected(request('category_id') == $cat->category_id)>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm transition-all">
            Buscar
        </button>
        @if(request('q') || request('category_id'))
            <a href="{{ route('agent.knowledge.index', ['tab' => request('tab', 'local')]) }}"
               class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-gray-200 rounded-xl font-semibold text-sm transition-all">
                Limpar
            </a>
        @endif
    </form>

    {{-- CONTEÚDO DA ABA 1: EASYWIKI LOCAL --}}
    <div x-show="activeTab === 'local'" class="space-y-4">
        @if($articles->isEmpty())
            <div class="bg-white dark:bg-slate-900 border-2 border-dashed border-gray-200 dark:border-slate-800 rounded-2xl p-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-base font-bold text-gray-600 dark:text-slate-300">Nenhum artigo encontrado na EasyWiki</p>
                <p class="text-sm text-gray-400 mt-1">
                    @if(request('q'))
                        Nenhum resultado para "{{ request('q') }}". Tente outros termos.
                    @else
                        Crie o primeiro artigo para documentar soluções técnicas.
                    @endif
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($articles as $article)
                    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col justify-between">
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-2">
                                @if($article->category)
                                    <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-0.5 bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 rounded-full border border-indigo-100 dark:border-indigo-800">
                                        {{ $article->category->name }}
                                    </span>
                                @endif
                                <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full {{ $article->visibility === 'public' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $article->visibility_label }}
                                </span>
                            </div>

                            <h3 class="text-base font-extrabold text-gray-900 dark:text-white line-clamp-2 hover:text-indigo-600 transition-colors">
                                <a href="{{ route('agent.knowledge.show', $article) }}">
                                    {{ $article->title }}
                                </a>
                            </h3>

                            <p class="text-xs text-gray-500 dark:text-slate-400 line-clamp-3 mt-2 leading-relaxed">
                                {{ strip_tags($article->problem) }}
                            </p>
                        </div>

                        <div class="px-5 py-3 bg-gray-50 dark:bg-slate-800/40 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between text-xs text-gray-400">
                            <span>{{ $article->author?->name ?? 'Sistema' }} • {{ $article->created_at->format('d/m/Y') }}</span>
                            <div class="flex items-center gap-3">
                                <span>{{ $article->views }} views</span>
                                <a href="{{ route('agent.knowledge.show', $article) }}"
                                   class="font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition-colors">
                                    Ver artigo →
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Paginação --}}
            <div class="mt-4">
                {{ $articles->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- CONTEÚDO DA ABA 2: NOTION WORKSPACE --}}
    <div x-show="activeTab === 'notion'" class="space-y-4" style="display: none;">
        @if(!$notionConfigured)
            <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-2xl p-8 text-center">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 flex items-center justify-center mx-auto mb-3 text-xl font-bold">
                    N
                </div>
                <h3 class="text-base font-bold text-amber-900 dark:text-amber-200">Integração com Notion não conectada</h3>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1 max-w-md mx-auto leading-relaxed">
                    Conecte o seu Notion para exibir artigos, manuais e procedimentos criados no seu Workspace diretamente aqui no sistema.
                </p>
                <div class="mt-4">
                    <button type="button" @click="openNotionModal = true"
                            class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-xs shadow-md transition-all">
                        ⚙️ Conectar Notion Agora
                    </button>
                </div>
            </div>
        @elseif(empty($notionArticles))
            <div class="bg-white dark:bg-slate-900 border-2 border-dashed border-gray-200 dark:border-slate-800 rounded-2xl p-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-slate-600 mb-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M4.459 4.208c.746.606 1.026.56 2.428.466l13.215-.793c.28 0 .047-.28-.046-.326L17.86 1.968c-.42-.326-.981-.7-2.055-.607L3.01 2.295c-.466.046-.56.28-.374.466zm.793 3.08v13.904c0 .747.373 1.027 1.214.98l14.523-.84c.84-.046.933-.56.933-1.167V6.354c0-.606-.233-.933-.746-.887l-15.177.887c-.56.047-.747.327-.747.934zm13.682.933c.093.42.093.84-.28 1.167l-.7.653c-.187.14-.374.373-.374.653v8.587c0 .42-.187.7-.653.793-.42.093-.747-.094-.98-.42l-4.76-7.468v6.908c.28.233.56.467.56.793 0 .42-.326.654-.793.654l-2.614.14c-.093-.373-.046-.746.28-1.026l.7-.654c.187-.14.373-.373.373-.653V8.873c0-.42.187-.7.654-.793.42-.094.746.093.98.42l4.853 7.608V9.106c-.28-.233-.56-.466-.56-.793 0-.42.327-.653.794-.653z"/>
                </svg>
                <p class="text-base font-bold text-gray-600 dark:text-slate-300">Nenhum artigo encontrado no Notion</p>
                <p class="text-sm text-gray-400 mt-1 max-w-md mx-auto">
                    A conexão com o Notion está ativa, mas nenhuma página foi localizada. Clique em sincronizar ou verifique as permissões de compartilhamento no Notion.
                </p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('agent.knowledge.index', ['tab' => 'notion', 'refresh' => 1]) }}"
                       class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-sm transition-all flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Sincronizar Artigos</span>
                    </a>
                    <button type="button" @click="openNotionModal = true"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl font-bold text-xs">
                        ⚙️ Alterar Configurações
                    </button>
                    <a href="https://www.notion.so" target="_blank"
                       class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-100 rounded-xl font-bold text-xs">
                        Abrir Notion Workspace ↗
                    </a>
                </div>
            </div>
        @else
            <div class="flex items-center justify-between pb-1">
                <span class="text-xs text-gray-500 dark:text-slate-400 font-medium">
                    Exibindo <strong>{{ count($notionArticles) }}</strong> documento(s) sincronizado(s) do Notion
                </span>
                <a href="{{ route('agent.knowledge.index', ['tab' => 'notion', 'refresh' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Atualizar Lista</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($notionArticles as $notion)
                    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col justify-between">
                        @if(!empty($notion['cover']))
                            <div class="h-32 bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <img src="{{ $notion['cover'] }}" alt="Capa" class="w-full h-full object-cover">
                            </div>
                        @endif
                        <div class="p-5 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    @if(!empty($notion['icon']) && Str::startsWith($notion['icon'], 'http'))
                                        <img src="{{ $notion['icon'] }}" alt="Ícone" class="w-6 h-6 rounded-md object-contain bg-slate-100 dark:bg-slate-800 p-0.5 border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                    @else
                                        <span class="text-xl">{{ $notion['icon'] ?: '📄' }}</span>
                                    @endif
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($notion['tags'] as $tag)
                                            <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-300 rounded-full border border-indigo-100 dark:border-indigo-800">
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                                <h3 class="text-base font-extrabold text-gray-900 dark:text-white line-clamp-2">
                                    <a href="{{ route('agent.knowledge.notion.show', $notion['id']) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        {{ $notion['title'] }}
                                    </a>
                                </h3>
                            </div>

                            <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100 dark:border-slate-800 text-xs">
                                <span class="text-gray-400">
                                    {{ $notion['last_edited'] ? $notion['last_edited']->format('d/m/Y') : 'Notion' }}
                                </span>
                                <div class="flex items-center gap-3">
                                    <a href="{{ $notion['url'] ?: ('https://www.notion.so/' . str_replace('-', '', $notion['id'])) }}" target="_blank" rel="noopener noreferrer"
                                       class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition-colors inline-flex items-center gap-1 font-semibold" title="Abrir no Notion">
                                        <span>Notion</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('agent.knowledge.notion.show', $notion['id']) }}"
                                       class="inline-flex items-center gap-1 font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">
                                        Ler no sistema →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- MODAL DE CONFIGURAÇÃO VISUAL DO NOTION --}}
    <div x-show="openNotionModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none;"
         @keydown.escape.window="openNotionModal = false"
         x-cloak>

        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden"
             @click.outside="openNotionModal = false">

            <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-sm">
                        N
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-gray-900 dark:text-white">Configurar Conexão do Notion</h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400">Integre sua conta do Notion sem precisar editar o .env</p>
                    </div>
                </div>
                <button type="button" @click="openNotionModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('agent.knowledge.notion.settings') }}" class="p-6 space-y-4">
                @csrf

                {{-- Token --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-widest mb-1.5">
                        Token de Acesso (Access Token / ntn_...) <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="api_key" x-model="notionApiKey"
                           placeholder="ntn_..."
                           class="w-full px-4 py-2.5 text-sm font-mono bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 rounded-xl text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500"
                           required>
                    <p class="text-[11px] text-gray-400 mt-1">
                        Crie uma integração em <a href="https://www.notion.so/my-integrations" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline font-semibold">notion.so/my-integrations</a> e cole o token aqui.
                    </p>
                </div>

                {{-- Database ID --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-widest mb-1.5">
                        ID do Banco de Dados / Página Raiz (Opcional)
                    </label>
                    <input type="text" name="database_id" x-model="notionDatabaseId"
                           placeholder="Ex: 8f3c4e5a6b7c8d9e..."
                           class="w-full px-4 py-2.5 text-sm font-mono bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-700 rounded-xl text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-[11px] text-gray-400 mt-1">
                        Deixe vazio para listar todas as páginas compartilhadas com a integração.
                    </p>
                </div>

                {{-- Feedback do Teste de Conexão --}}
                <template x-if="testResult">
                    <div :class="testResult.success ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : 'bg-red-50 dark:bg-red-950/40 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800'"
                         class="p-3.5 rounded-xl border text-xs leading-relaxed flex items-start gap-2.5">
                        <span class="text-base shrink-0" x-text="testResult.success ? '✅' : '❌'"></span>
                        <div class="flex-1">
                            <p class="font-bold" x-text="testResult.success ? 'Conexão Estabelecida!' : 'Falha na Conexão'"></p>
                            <p class="mt-0.5" x-text="testResult.message"></p>
                        </div>
                    </div>
                </template>

                {{-- Guia rápido com alto contraste --}}
                <div class="notion-guide-box p-4 bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 space-y-2">
                    <p class="font-extrabold text-slate-900 dark:text-white text-xs flex items-center gap-1.5">
                        <span>💡</span> Como conectar em 3 passos simples:
                    </p>
                    <ol class="list-decimal list-inside space-y-1.5 text-xs text-slate-700 dark:text-slate-200 leading-relaxed font-medium">
                        <li>
                            Acesse <a href="https://www.notion.so/my-integrations" target="_blank" class="text-indigo-600 dark:text-indigo-400 font-bold underline hover:text-indigo-500">notion.so/my-integrations</a> e crie uma nova integração.
                        </li>
                        <li>
                            Copie o <strong class="text-slate-900 dark:text-white font-bold">Access token</strong> e cole no campo acima.
                        </li>
                        <li>
                            No Notion, abra a página/banco desejado &gt; clique nos <strong class="text-slate-900 dark:text-white font-bold">3 pontinhos (...)</strong> no topo &gt; <strong class="text-slate-900 dark:text-white font-bold">Connect to / Conectar a</strong> &gt; selecione sua integração.
                        </li>
                    </ol>
                </div>

                <div class="pt-2 flex items-center justify-between gap-3 border-t border-gray-100 dark:border-slate-800">
                    <button type="button" @click="testNotion()" :disabled="testingConnection"
                            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
                        <span x-show="testingConnection" class="animate-spin">🔄</span>
                        <span x-text="testingConnection ? 'Testando...' : '🔍 Testar Conexão'"></span>
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="openNotionModal = false"
                                class="px-4 py-2.5 text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white text-xs font-semibold">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow transition-all">
                            Salvar Configurações
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
