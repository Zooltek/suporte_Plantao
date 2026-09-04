@extends('layouts.agent')

@section('title', 'EasyWiki — ' . Str::limit($article->title, 60))

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <a href="{{ route('agent.knowledge.index') }}" class="hover:text-indigo-600 transition-colors">EasyWiki</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-600 truncate max-w-xs">{{ $article->title }}</span>
    </nav>

    {{-- Artigo --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex flex-wrap gap-2 mb-3">
                @if($article->category)
                    <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full border border-indigo-100">
                        {{ $article->category->name }}
                    </span>
                @endif
                <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full
                             {{ $article->visibility === 'public' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-gray-100 text-gray-600' }}">
                    {{ $article->visibility === 'public' ? 'Público' : 'Interno' }}
                </span>
                @if($article->ticket_id)
                    <a href="{{ route('agent.ticket.show', $article->ticket_id) }}"
                       class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full border border-amber-100 hover:bg-amber-100 transition-colors">
                        Chamado #{{ $article->ticket_id }}
                    </a>
                @endif
            </div>

            <h1 class="text-xl font-extrabold text-gray-900">{{ $article->title }}</h1>

            <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-gray-400">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ $article->author?->name }}
                </span>
                <span>{{ $article->created_at->format('d/m/Y \à\s H:i') }}</span>
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{ $article->views }} visualizações
                </span>
            </div>
        </div>

        {{-- Problema --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Problema
            </h2>
            <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
                {!! nl2br(e($article->problem)) !!}
            </div>
        </div>

        {{-- Solução --}}
        <div class="px-6 py-5">
            <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Solução
            </h2>
            <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed bg-emerald-50/50 border border-emerald-100 rounded-xl p-4">
                {!! nl2br(e($article->solution)) !!}
            </div>
        </div>

        {{-- Tags --}}
        @if($article->tags)
            <div class="px-6 pb-5 flex flex-wrap gap-1.5">
                @foreach($article->tags_array as $tag)
                    <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full">
                        #{{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Ações --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center gap-3">
            <a href="{{ route('agent.knowledge.index') }}"
               class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
            <form method="POST" action="{{ route('agent.knowledge.destroy', $article) }}"
                  class="ml-auto" onsubmit="return confirm('Remover este artigo da EasyWiki?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="text-xs font-semibold text-gray-400 hover:text-red-600 transition-colors">
                    Remover artigo
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
