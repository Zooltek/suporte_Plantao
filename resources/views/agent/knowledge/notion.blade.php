@extends('layouts.agent')

@section('title', 'EasyWiki — ' . Str::limit(\App\Services\Notion\NotionService::cleanTitleText($article['title'] ?? 'Artigo'), 60))

@php
    $currentCleanId = \App\Services\Notion\NotionService::cleanNotionId($article['id'] ?? '');
    $rootCleanId = !empty($navigationTree['root']['clean_id']) ? $navigationTree['root']['clean_id'] : null;
    $isRootPage = $rootCleanId && ($rootCleanId === $currentCleanId);
@endphp

@section('content')
<div x-data="{
    sidebarMobileOpen: false,
    searchQuery: '',
    filterItem(title, section) {
        if (!this.searchQuery.trim()) return true;
        const q = this.searchQuery.toLowerCase().trim();
        return (title && title.toLowerCase().includes(q)) || (section && section.toLowerCase().includes(q));
    }
}" class="w-full space-y-4">

    {{-- Barra Superior: Breadcrumbs + Toggle Mobile do Sumário --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium min-w-0">
            <a href="{{ route('agent.knowledge.index', ['tab' => 'notion']) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex-shrink-0">
                EasyWiki (Notion)
            </a>
            @if(!empty($navigationTree['root']['clean_id']) && !$isRootPage)
                <svg class="w-3.5 h-3.5 text-gray-300 dark:text-slate-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ route('agent.knowledge.notion.show', $navigationTree['root']['id']) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex-shrink-0">
                    {{ $navigationTree['root']['title'] ?? 'EASYWIKI' }}
                </a>
            @endif
            <svg class="w-3.5 h-3.5 text-gray-300 dark:text-slate-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-600 dark:text-slate-300 truncate font-semibold">{{ \App\Services\Notion\NotionService::cleanTitleText($article['title'] ?? 'Artigo') }}</span>
        </nav>

        {{-- Botão de Sumário para telas menores que LG --}}
        <button type="button"
                @click="sidebarMobileOpen = !sidebarMobileOpen"
                class="lg:hidden inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold shadow-xs transition-all">
            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
            </svg>
            <span x-text="sidebarMobileOpen ? 'Ocultar Sumário' : 'Ver Sumário'">Ver Sumário</span>
        </button>
    </div>

    {{-- Layout Principal: Sumário Lateral + Conteúdo do Artigo --}}
    <div class="flex flex-col lg:flex-row items-start gap-6 w-full">

        {{-- SUMÁRIO LATERAL (Sidebar estilo GitBook/Docs) --}}
        <aside class="w-full lg:w-72 flex-shrink-0 lg:sticky lg:top-18 transition-all"
               :class="sidebarMobileOpen ? 'block' : 'hidden lg:block'">

            <div class="bg-white dark:bg-slate-900/90 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">

                {{-- Cabeçalho do Sumário --}}
                <div class="p-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-800/30">
                    <div class="flex items-center justify-between gap-2 mb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm font-bold shadow-xs">
                                📖
                            </span>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Sumário da Wiki</h2>
                        </div>
                        @if(!empty($navigationTree['sections']))
                            @php
                                $totalTopics = collect($navigationTree['sections'])->sum(fn($s) => count($s['items'] ?? []));
                            @endphp
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200/80 dark:border-slate-700">
                                {{ $totalTopics }} tópicos
                            </span>
                        @endif
                    </div>

                    {{-- Link da Raiz (ex: EASYWIKI) --}}
                    @if(!empty($navigationTree['root']))
                        <a href="{{ route('agent.knowledge.notion.show', $navigationTree['root']['id']) }}"
                           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all {{ $isRootPage ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-800' }}">
                            <span class="text-sm">{{ $navigationTree['root']['icon'] ?? '🏷️' }}</span>
                            <span class="truncate flex-1">{{ $navigationTree['root']['title'] ?? 'Início da EasyWiki' }}</span>
                            @if($isRootPage)
                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                            @endif
                        </a>
                    @endif

                    {{-- Filtro Rápido no Sumário --}}
                    <div class="relative mt-2.5">
                        <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Filtrar clientes e tópicos..."
                               class="w-full pl-8 pr-7 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        <button type="button"
                                x-show="searchQuery.length > 0"
                                @click="searchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">
                            ×
                        </button>
                    </div>
                </div>

                {{-- Corpo do Sumário (Árvore de Tópicos) com Scroll Próprio --}}
                <div class="p-3 max-h-[calc(100vh-14rem)] overflow-y-auto space-y-4 text-xs">

                    @if(!empty($navigationTree['sections']))
                        @foreach($navigationTree['sections'] as $section)
                            <div class="space-y-1"
                                 x-show="!searchQuery || {{ json_encode(collect($section['items'])->pluck('title')) }}.some(t => filterItem(t, '{{ addslashes($section['title']) }}'))">

                                {{-- Título da Seção --}}
                                <div class="px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center justify-between">
                                    <span class="truncate">{{ $section['title'] }}</span>
                                    <span class="text-[10px] font-normal text-slate-400 opacity-70">({{ count($section['items'] ?? []) }})</span>
                                </div>

                                {{-- Lista de Páginas --}}
                                <div class="space-y-0.5">
                                    @foreach($section['items'] as $item)
                                        @php
                                            $isActive = ($currentCleanId === $item['clean_id']);
                                        @endphp
                                        <a href="{{ $item['url'] }}"
                                           x-show="filterItem('{{ addslashes($item['title']) }}', '{{ addslashes($section['title']) }}')"
                                           class="group flex items-center justify-between px-2.5 py-2 rounded-xl transition-all {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 font-bold border-l-3 border-indigo-600 shadow-xs pl-2' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/70 dark:hover:bg-slate-800/60' }}"
                                           title="{{ $item['title'] }}">
                                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                                <span class="text-xs flex-shrink-0 {{ $isActive ? 'opacity-100' : 'opacity-70 group-hover:opacity-100' }}">{{ $item['icon'] ?? '📄' }}</span>
                                                <span class="truncate">{{ $item['title'] }}</span>
                                            </div>
                                            @if($isActive)
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400 flex-shrink-0"></span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="py-6 text-center text-slate-400">
                            <p class="text-xs">Nenhum tópico encontrado.</p>
                        </div>
                    @endif

                </div>

                {{-- Rodapé do Sumário --}}
                <div class="p-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between text-xs">
                    <a href="{{ route('agent.knowledge.index', ['tab' => 'notion']) }}"
                       class="text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Voltar à EasyWiki</span>
                    </a>

                    <a href="{{ route('agent.knowledge.notion.show', ['pageId' => $article['id'], 'refresh' => 1]) }}"
                       class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 hover:bg-slate-200/60 dark:hover:bg-slate-700/60 rounded-lg transition-all"
                       title="Recarregar sumário e artigo">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </a>
                </div>

            </div>
        </aside>

        {{-- ÁREA PRINCIPAL DO ARTIGO (Larga, confortável e responsiva) --}}
        <main class="flex-1 min-w-0 w-full space-y-4">

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">

                {{-- Capa do Artigo --}}
                @if(!empty($article['cover']))
                    <div class="w-full h-44 sm:h-56 bg-slate-100 dark:bg-slate-950 overflow-hidden relative">
                        <img src="{{ $article['cover'] }}" alt="Capa" class="w-full h-full object-cover">
                    </div>
                @endif

                {{-- Header do Artigo --}}
                <div class="px-6 sm:px-8 py-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest px-2.5 py-1 bg-slate-900 dark:bg-slate-700 text-white rounded-lg flex items-center gap-1.5">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.459 4.208c.746.606 1.026.56 2.428.466l13.215-.793c.28 0 .047-.28-.046-.326L17.86 1.968c-.42-.326-.981-.7-2.055-.607L3.01 2.295c-.466.046-.56.28-.374.466zm.793 3.08v13.904c0 .747.373 1.027 1.214.98l14.523-.84c.84-.046.933-.56.933-1.167V6.354c0-.606-.233-.933-.746-.887l-15.177.887c-.56.047-.747.327-.747.934zm13.682.933c.093.42.093.84-.28 1.167l-.7.653c-.187.14-.374.373-.374.653v8.587c0 .42-.187.7-.653.793-.42.093-.747-.094-.98-.42l-4.76-7.468v6.908c.28.233.56.467.56.793 0 .42-.326.654-.793.654l-2.614.14c-.093-.373-.046-.746.28-1.026l.7-.654c.187-.14.373-.373.373-.653V8.873c0-.42.187-.7.654-.793.42-.094.746.093.98.42l4.853 7.608V9.106c-.28-.233-.56-.466-.56-.793 0-.42.327-.653.794-.653z"/>
                            </svg>
                            Notion Workspace
                        </span>

                        @foreach(($article['tags'] ?? []) as $tag)
                            <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 rounded-lg border border-indigo-100 dark:border-indigo-800">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>

                    <div class="flex items-start gap-4">
                        @if(!empty($article['icon']))
                            @if(Str::startsWith($article['icon'], 'http'))
                                <img src="{{ $article['icon'] }}" alt="Ícone" class="w-12 h-12 rounded-xl object-contain bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700 flex-shrink-0 shadow-xs">
                            @else
                                <span class="text-3xl sm:text-4xl flex-shrink-0">{{ $article['icon'] }}</span>
                            @endif
                        @endif
                        <div class="min-w-0 flex-1">
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white leading-tight break-words">
                                {{ \App\Services\Notion\NotionService::cleanTitleText($article['title'] ?? 'Artigo') }}
                            </h1>
                            @if(!empty($article['last_edited']))
                                <p class="text-xs text-slate-400 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Atualizado no Notion em {{ $article['last_edited']->format('d/m/Y \à\s H:i') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Conteúdo do Artigo --}}
                <div class="px-6 sm:px-8 py-8">
                    <div class="prose prose-indigo dark:prose-invert max-w-none text-slate-800 dark:text-slate-200 leading-relaxed break-words">
                        {!! $article['html'] !!}
                    </div>
                </div>

                {{-- Rodapé / Ações --}}
                <div class="px-6 sm:px-8 py-4 bg-slate-50/70 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                    <a href="{{ route('agent.knowledge.index', ['tab' => 'notion']) }}"
                       class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Voltar à Lista Geral
                    </a>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('agent.knowledge.notion.show', ['pageId' => $article['id'], 'refresh' => 1]) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 shadow-xs transition-all"
                           title="Recarregar artigo do Notion">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Recarregar</span>
                        </a>

                        @php
                            $notionDirectUrl = !empty($article['url']) ? $article['url'] : ('https://www.notion.so/' . str_replace('-', '', $article['id']));
                        @endphp
                        <a href="{{ $notionDirectUrl }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 dark:bg-slate-700 hover:bg-slate-800 dark:hover:bg-slate-600 text-white rounded-xl text-xs font-bold shadow-xs transition-all">
                            <span>Abrir no Notion</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                </div>

            </div>

        </main>

    </div>

</div>
@endsection
