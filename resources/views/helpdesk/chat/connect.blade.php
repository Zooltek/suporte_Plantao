@extends('layouts.app')

@section('title', 'Chat com o Suporte')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-[1.4fr,0.9fr]">
        <section class="rounded-3xl border border-white/60 bg-white/90 p-6 shadow-xl shadow-slate-900/5 backdrop-blur sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
                <div class="space-y-2">
                    <span class="inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-orange-700">
                        Portal de Atendimento
                    </span>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">
                        Abra um chat e registre o chamado no mesmo fluxo do suporte.
                    </h1>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        A primeira mensagem já cria o ticket interno. As respostas do time de suporte passam a aparecer nesta conversa.
                    </p>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-right">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Usuário autenticado</p>
                    <p class="mt-1 text-sm font-semibold text-emerald-900">{{ $user->name }}</p>
                    <p class="text-xs text-emerald-700">{{ $user->email }}</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    Verifique os campos obrigatórios antes de iniciar o atendimento.
                </div>
            @endif

            <form action="{{ route('portal.chat.store') }}" method="POST" class="mt-6 space-y-5">
                @csrf

                <div class="space-y-2">
                    <label for="subject" class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Assunto</label>
                    <input
                        id="subject"
                        name="subject"
                        type="text"
                        value="{{ old('subject') }}"
                        maxlength="150"
                        placeholder="Ex.: Erro ao emitir nota fiscal"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-200"
                        required
                    >
                    @error('subject')
                        <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 md:grid-cols-[0.9fr,1.1fr]">
                    <div class="space-y-2">
                        <label for="category_id" class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Categoria</label>
                        <select
                            id="category_id"
                            name="category_id"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-200"
                        >
                            <option value="">Usar categoria padrão do suporte</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->category_id }}" @selected((int) old('category_id') === (int) $category->category_id)>
                                    {{ $category->display_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Empresa associada</p>
                        @if($user->company)
                            <p class="mt-2 text-sm font-semibold text-slate-900">
                                {{ $user->company->trade_name ?: $user->company->name }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Os chamados deste usuário serão vinculados automaticamente a essa empresa.
                            </p>
                        @else
                            <p class="mt-2 text-sm font-semibold text-amber-700">Usuário sem empresa vinculada</p>
                            <p class="text-xs text-slate-500">
                                O sistema usará a empresa padrão configurada para intake do chat web.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="message" class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Primeira mensagem</label>
                    <textarea
                        id="message"
                        name="message"
                        rows="8"
                        maxlength="5000"
                        placeholder="Descreva o problema com contexto suficiente para o time iniciar a análise."
                        class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-800 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-200"
                        required
                    >{{ old('message') }}</textarea>
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Use uma mensagem objetiva. Esse texto também alimenta o ticket interno.</span>
                        <span>Limite de 5000 caracteres</span>
                    </div>
                    @error('message')
                        <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                    <p class="text-xs text-slate-500">
                        Um usuário mantém apenas um chat web ativo por vez.
                    </p>

                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white transition hover:bg-orange-600"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Iniciar atendimento
                    </button>
                </div>
            </form>
        </section>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-slate-950/90 p-6 text-white shadow-2xl shadow-slate-900/10">
                <p class="text-xs font-black uppercase tracking-[0.24em] text-orange-300">Como funciona</p>
                <div class="mt-4 space-y-4 text-sm leading-6 text-slate-200">
                    <p>1. O chat cria uma conversa vinculada ao seu login.</p>
                    <p>2. A primeira mensagem abre automaticamente um ticket interno.</p>
                    <p>3. As respostas do suporte ficam sincronizadas neste painel e no fluxo do chamado.</p>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-black uppercase tracking-[0.24em] text-slate-500">Boas práticas</p>
                <div class="mt-4 space-y-3 text-sm text-slate-600">
                    <p>Informe impacto, ambiente e horário aproximado do erro.</p>
                    <p>Se houver mais de um problema, abra atendimentos separados.</p>
                    <p>O encerramento do chat não apaga o ticket; ele segue no fluxo normal do suporte.</p>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
