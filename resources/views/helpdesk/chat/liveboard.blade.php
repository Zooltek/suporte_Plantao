@extends('layouts.app')

@section('title', $conversation->subject ?: 'Chat com o Suporte')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-[1.45fr,0.85fr]">
        <section class="overflow-hidden rounded-3xl border border-white/60 bg-white/95 shadow-xl shadow-slate-900/5 backdrop-blur">
            <div class="border-b border-slate-100 bg-slate-950 px-6 py-5 text-white sm:px-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-orange-200">
                            Chat em andamento
                        </span>
                        <h1 class="text-2xl font-black tracking-tight">
                            {{ $conversation->subject ?: 'Atendimento sem assunto definido' }}
                        </h1>
                        <p class="text-sm text-slate-300">
                            Sessão {{ $conversation->session }} · criado em {{ $conversation->created_at?->format('d/m/Y H:i') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.2em]',
                            'bg-emerald-400/15 text-emerald-200' => ! $conversation->isClosed(),
                            'bg-slate-200/15 text-slate-200' => $conversation->isClosed(),
                        ])>
                            {{ $conversation->status?->name ?? ($conversation->isClosed() ? 'Fechado' : 'Aberto') }}
                        </span>

                        <a
                            href="{{ route('portal.chat.show', $conversation) }}"
                            class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/10"
                        >
                            Atualizar
                        </a>
                    </div>
                </div>
            </div>

            <div class="space-y-4 bg-slate-50 px-4 py-5 sm:px-6" style="max-height: 620px; overflow-y: auto;">
                @forelse($messages as $entry)
                    @php $isSupportMessage = (int) $entry->user_id !== (int) $conversation->owner_id; @endphp
                    <div class="flex {{ $isSupportMessage ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-3xl {{ $isSupportMessage ? '' : 'order-last' }}">
                            <div class="rounded-3xl px-4 py-3 shadow-sm {{ $isSupportMessage ? 'rounded-tl-none border border-slate-200 bg-white text-slate-800' : 'rounded-tr-none bg-orange-500 text-white' }}">
                                <div class="mb-2 flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.18em] {{ $isSupportMessage ? 'text-slate-400' : 'text-orange-100' }}">
                                    <span>{{ $isSupportMessage ? ($entry->owner?->name ?? 'Suporte') : 'Você' }}</span>
                                    <span>·</span>
                                    <span>{{ $entry->created_at?->format('d/m H:i') }}</span>
                                </div>
                                <p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $entry->content }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                        Nenhuma mensagem registrada nesta conversa.
                    </div>
                @endforelse
            </div>

            <div class="border-t border-slate-100 bg-white px-6 py-5 sm:px-8">
                @if($conversation->isClosed())
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                        Este chat foi encerrado. O ticket continua registrado internamente para acompanhamento pelo suporte.
                    </div>
                @else
                    <form action="{{ route('portal.chat.message.store', $conversation) }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="message" class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Nova mensagem</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                maxlength="5000"
                                placeholder="Digite aqui a atualização ou dúvida complementar."
                                class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-800 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-200"
                                required
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-slate-500">
                                As respostas do suporte são sincronizadas aqui a partir do chamado interno.
                            </p>

                            <div class="flex items-center gap-2">
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white transition hover:bg-orange-600"
                                >
                                    Enviar mensagem
                                </button>

                                <button
                                    type="submit"
                                    form="close-chat-form"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                                >
                                    Encerrar chat
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </section>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-black uppercase tracking-[0.24em] text-slate-500">Ticket interno</p>
                @if($ticket)
                    <div class="mt-4 space-y-4">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Registrado</p>
                            <p class="mt-1 text-2xl font-black text-emerald-900">#{{ $ticket->id }}</p>
                            <p class="text-xs text-emerald-700">{{ $ticket->status?->name ?? 'Pendente' }}</p>
                        </div>

                        <div class="space-y-3 text-sm text-slate-600">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Empresa</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $ticket->company?->trade_name ?: ($ticket->company?->name ?? 'Não identificada') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Origem</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $ticket->origin?->name ?? 'Chat Web' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Contato</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $ticket->contact ?: $user->name }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-700">
                        O ticket ainda não foi vinculado a esta conversa.
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-slate-200 bg-slate-950/90 p-6 text-white shadow-2xl shadow-slate-900/10">
                <p class="text-xs font-black uppercase tracking-[0.24em] text-orange-300">Orientações</p>
                <div class="mt-4 space-y-3 text-sm leading-6 text-slate-200">
                    <p>Evite enviar mensagens duplicadas. Use o botão de atualizar se estiver aguardando retorno.</p>
                    <p>Quando o suporte responder no chamado, a resposta também ficará disponível neste histórico.</p>
                    <p>Encerrar o chat fecha apenas a sessão web; o ticket continua no fluxo normal de atendimento.</p>
                </div>
            </section>
        </aside>
    </div>
</div>

@if(! $conversation->isClosed())
    <form id="close-chat-form" action="{{ route('portal.chat.close', $conversation) }}" method="POST" class="hidden">
        @csrf
    </form>
@endif
@endsection

@push('footer')
<script>
    setInterval(function () {
        const composer = document.getElementById('message');

        if (!composer) {
            return;
        }

        const isTyping = document.activeElement === composer || composer.value.trim().length > 0;

        if (document.visibilityState === 'visible' && !isTyping) {
            window.location.reload();
        }
    }, 20000);
</script>
@endpush
