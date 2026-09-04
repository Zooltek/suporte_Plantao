@extends('admin.layouts.master')

@section('page-title', 'WhatsApp — Conversa #' . $conversation->id)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    {{-- Header / Breadcrumb --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.whatsapp.index') }}"
           class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            WhatsApp
        </a>
        <span class="text-gray-300">/</span>
        <span class="text-sm text-gray-700 font-medium">Conversa #{{ $conversation->id }}</span>
    </div>

    {{-- Info da conversa --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.121 1.532 5.854L.057 23.882l6.186-1.454A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.894 0-3.668-.523-5.183-1.432l-.371-.22-3.676.864.923-3.577-.241-.388A9.958 9.958 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                    </div>
                    <div>
                        <p dusk="conversation-phone" class="font-semibold text-gray-900 text-lg leading-tight">+{{ $conversation->phone }}</p>
                        @if($conversation->getPayloadValue('name'))
                            <p class="text-sm text-gray-500">{{ $conversation->getPayloadValue('name') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Badge estado --}}
                @php
                    $stateColors = [
                        'completed'  => 'bg-green-100 text-green-700 border-green-200',
                        'cancelled'  => 'bg-red-100 text-red-700 border-red-200',
                        'confirming' => 'bg-blue-100 text-blue-700 border-blue-200',
                    ];
                    $stateColor = $stateColors[$conversation->state->value] ?? 'bg-yellow-100 text-yellow-700 border-yellow-200';
                @endphp
                <span dusk="conversation-state" class="px-3 py-1 rounded-full text-xs font-semibold border {{ $stateColor }}">
                    {{ $conversation->state->label() }}
                </span>

                {{-- Link ticket --}}
                @if($conversation->ticket_id)
                    <a href="{{ route('agent.ticket.show', $conversation->ticket_id) }}"
                       dusk="conversation-ticket-badge"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 border border-indigo-200 hover:bg-indigo-200 transition-colors">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        Ticket #{{ $conversation->ticket_id }}
                    </a>
                @endif

                @if($conversation->state->isHumanPending())
                    <form method="POST" action="{{ route('admin.whatsapp.release', $conversation) }}">
                        @csrf
                        <button
                            type="submit"
                            dusk="release-bot-button"
                            class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Liberar bot
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.whatsapp.pause', $conversation) }}">
                        @csrf
                        <button
                            type="submit"
                            dusk="pause-bot-button"
                            class="inline-flex items-center gap-2 rounded-lg border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-100"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pausar bot
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Metadados --}}
        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide font-medium mb-0.5">Empresa</p>
                <p dusk="conversation-company" class="text-gray-700">{{ $conversation->company?->name ?? $conversation->getPayloadValue('company_name') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide font-medium mb-0.5">Área</p>
                <p dusk="conversation-area" class="text-gray-700">{{ $conversation->getPayloadValue('area_label') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide font-medium mb-0.5">Iniciada</p>
                <p class="text-gray-700">{{ $conversation->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide font-medium mb-0.5">Última atividade</p>
                <p class="text-gray-700">{{ $conversation->last_activity_at?->diffForHumans() ?? '—' }}</p>
            </div>
        </div>

        {{-- Payload coletado (problema) --}}
        @if($conversation->getPayloadValue('problem'))
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-gray-400 text-xs uppercase tracking-wide font-medium mb-1">Problema descrito</p>
                <p dusk="conversation-problem" class="text-gray-700 text-sm bg-gray-50 rounded-lg px-3 py-2 leading-relaxed">
                    {{ $conversation->getPayloadValue('problem') }}
                </p>
            </div>
        @endif
    </div>

    {{-- Timeline de mensagens --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Histórico de Mensagens</h2>
            <span id="whatsapp-admin-message-count" class="text-xs text-gray-400">{{ $conversation->messages->count() }} mensagens</span>
        </div>

        {{-- Área de chat --}}
        <div
            class="px-4 py-5 space-y-3 bg-gray-50 max-h-[560px] overflow-y-auto"
            id="chat-scroll"
            data-poll-url="{{ route('admin.whatsapp.messages', $conversation) }}"
        >
            @if($conversation->messages->isEmpty())
                <div id="whatsapp-admin-empty" class="px-5 py-10 text-center text-sm text-gray-400">
                    Nenhuma mensagem registrada.
                </div>
            @endif

            @foreach($conversation->messages as $message)
                @php
                    $isInbound = $message->direction === 'inbound';
                @endphp

                <div data-whatsapp-message-id="{{ $message->id }}" class="flex {{ $isInbound ? 'justify-start' : 'justify-end' }} items-end gap-2">

                    {{-- Avatar inbound --}}
                    @if($isInbound)
                        <div class="w-7 h-7 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0 mb-1">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @endif

                    {{-- Balão --}}
                    <div class="max-w-[75%] space-y-0.5">
                        <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm
                            {{ $isInbound
                                ? 'bg-white text-gray-800 rounded-bl-sm border border-gray-200'
                                : 'bg-green-500 text-white rounded-br-sm' }}">

                            @if($message->type === 'text' || !$message->type)
                                {!! nl2br(e($message->body)) !!}

                            @elseif($message->type === 'audio' && $message->attachment_path)
                                <div class="space-y-1">
                                    <audio controls preload="metadata" src="{{ Storage::disk('public')->url($message->attachment_path) }}" class="w-full max-w-[260px]" style="height: 36px;"></audio>
                                    @if($message->body)
                                        <p class="text-xs mt-1">{!! nl2br(e($message->body)) !!}</p>
                                    @endif
                                    <a href="{{ Storage::disk('public')->url($message->attachment_path) }}" download class="text-[11px] underline opacity-80 hover:opacity-100 block text-right">Baixar áudio</a>
                                </div>

                            @elseif($message->type === 'video' && $message->attachment_path)
                                <div class="space-y-1">
                                    <video controls preload="metadata" src="{{ Storage::disk('public')->url($message->attachment_path) }}" class="w-full max-w-[260px] rounded-lg border border-gray-200"></video>
                                    @if($message->body)
                                        <p class="text-xs mt-1">{!! nl2br(e($message->body)) !!}</p>
                                    @endif
                                    <a href="{{ Storage::disk('public')->url($message->attachment_path) }}" download class="text-[11px] underline opacity-80 hover:opacity-100 block text-right">Baixar vídeo</a>
                                </div>

                            @elseif($message->type === 'image' && $message->attachment_path)
                                <div class="space-y-1">
                                    <a href="{{ Storage::disk('public')->url($message->attachment_path) }}" target="_blank">
                                        <img src="{{ Storage::disk('public')->url($message->attachment_path) }}"
                                             alt="Imagem"
                                             class="max-w-[220px] max-h-[220px] rounded-lg border border-gray-200 object-contain">
                                    </a>
                                    @if($message->body)
                                        <p class="text-xs mt-1">{!! nl2br(e($message->body)) !!}</p>
                                    @endif
                                    <a href="{{ Storage::disk('public')->url($message->attachment_path) }}" download class="text-[11px] underline opacity-80 hover:opacity-100 block text-right">Baixar imagem</a>
                                </div>

                            @elseif($message->type === 'contact')
                                <div class="flex items-center gap-2.5 p-2 bg-white/90 rounded-lg text-xs text-gray-800">
                                    <span class="text-lg">👤</span>
                                    <div>
                                        <p class="font-bold">{{ $message->original_filename ? str_replace('.vcf', '', $message->original_filename) : 'Contato' }}</p>
                                        <p class="text-[10px] text-gray-500">Cartão de contato</p>
                                    </div>
                                </div>

                            @elseif(in_array($message->type, ['document']) || $message->attachment_path)
                                <div class="flex items-center gap-3 p-2 bg-white/90 rounded-lg {{ $isInbound ? 'text-gray-700' : 'text-gray-900' }}">
                                    <div class="px-2 py-1 rounded bg-indigo-100 text-indigo-700 font-bold text-xs flex-shrink-0">
                                        📄 DOC
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-xs truncate">{{ $message->original_filename ?: basename($message->attachment_path) }}</p>
                                        @if($message->file_size)
                                            <p class="text-[10px] text-gray-400">{{ number_format($message->file_size / 1024, 1) }} KB</p>
                                        @endif
                                    </div>
                                    @if($message->attachment_path)
                                        <a href="{{ Storage::disk('public')->url($message->attachment_path) }}"
                                           download="{{ $message->original_filename ?: basename($message->attachment_path) }}"
                                           class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-md shadow-sm transition-colors flex-shrink-0">
                                            Baixar
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Horário --}}
                        <p class="text-xs text-gray-400 {{ $isInbound ? 'text-left pl-1' : 'text-right pr-1' }}">
                            {{ $message->created_at?->format('H:i') }}
                            @if(!$isInbound)
                                <span class="ml-1 text-gray-300">{{ $message->user?->name ?? 'Chatbot' }}</span>
                            @endif
                        </p>
                    </div>

                    {{-- Avatar outbound --}}
                    @if(!$isInbound)
                        <div class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 mb-1">
                            <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                                <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                            </svg>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Marcador de estado terminal --}}
            @if($conversation->state->isTerminal())
                <div class="flex justify-center py-2">
                    <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full border border-gray-200">
                        @if($conversation->state->value === 'completed')
                            Conversa encerrada — Ticket #{{ $conversation->ticket_id }} criado
                        @else
                            Conversa cancelada
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </div>

</div>

<script>
    (() => {
        const el = document.getElementById('chat-scroll');
        const counter = document.getElementById('whatsapp-admin-message-count');
        const messageIds = new Set(Array.from(document.querySelectorAll('[data-whatsapp-message-id]')).map((node) => Number(node.dataset.whatsappMessageId)));
        const lastId = () => Math.max(0, ...Array.from(messageIds));
        const nearBottom = () => el.scrollHeight - el.scrollTop - el.clientHeight < 96;
        const scrollBottom = () => { el.scrollTop = el.scrollHeight; };
        const escapeHtml = (value = '') => String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const renderMessageBody = (message, inbound) => {
            if (message.type === 'text' || !message.type) {
                return escapeHtml(message.body || '').replaceAll('\n', '<br>');
            }

            const url = message.attachment_url ? escapeHtml(message.attachment_url) : '';
            const fileName = escapeHtml(message.original_filename || 'arquivo');

            if (message.type === 'image' && url) {
                return `<div class="space-y-1">
                    <a href="${url}" target="_blank"><img src="${url}" class="max-w-[200px] max-h-[200px] rounded-lg border border-gray-200"></a>
                    <a href="${url}" download="${fileName}" class="text-[11px] underline opacity-80 block text-right">Baixar imagem</a>
                </div>`;
            }

            if (message.type === 'audio' && url) {
                return `<div class="space-y-1">
                    <audio controls preload="metadata" src="${url}" class="w-full max-w-[260px]" style="height: 36px;"></audio>
                    <a href="${url}" download="${fileName}" class="text-[11px] underline opacity-80 block text-right">Baixar áudio</a>
                </div>`;
            }

            if (message.type === 'video' && url) {
                return `<div class="space-y-1">
                    <video controls preload="metadata" src="${url}" class="w-full max-w-[260px] rounded-lg border"></video>
                    <a href="${url}" download="${fileName}" class="text-[11px] underline opacity-80 block text-right">Baixar vídeo</a>
                </div>`;
            }

            const link = url
                ? `<a href="${url}" download="${fileName}" target="_blank" class="px-2.5 py-1 bg-indigo-600 text-white rounded text-xs font-bold">Baixar</a>`
                : '';

            return `<div class="flex items-center justify-between gap-3 p-2 bg-white/90 rounded-lg text-gray-800 text-xs">
                <div class="truncate">
                    <p class="font-bold truncate">${fileName}</p>
                    <p class="text-[10px] text-gray-400 uppercase">${escapeHtml(message.type)}</p>
                </div>
                ${link}
            </div>`;
        };

        const renderMessage = (message) => {
            if (!el || !message?.id || messageIds.has(Number(message.id))) return;

            const inbound = message.direction === 'inbound';
            const wasBottom = nearBottom();
            document.getElementById('whatsapp-admin-empty')?.remove();

            el.insertAdjacentHTML('beforeend', `
                <div data-whatsapp-message-id="${message.id}" class="flex ${inbound ? 'justify-start' : 'justify-end'} items-end gap-2">
                    ${inbound ? `
                        <div class="w-7 h-7 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0 mb-1">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    ` : ''}
                    <div class="max-w-[75%] space-y-0.5">
                        <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm ${inbound ? 'bg-white text-gray-800 rounded-bl-sm border border-gray-200' : 'bg-green-500 text-white rounded-br-sm'}">
                            ${renderMessageBody(message, inbound)}
                        </div>
                        <p class="text-xs text-gray-400 ${inbound ? 'text-left pl-1' : 'text-right pr-1'}">
                            ${escapeHtml(message.created_at_label || '')}
                            ${!inbound ? `<span class="ml-1 text-gray-300">${escapeHtml(message.user_name || 'Chatbot')}</span>` : ''}
                        </p>
                    </div>
                    ${!inbound ? `
                        <div class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 mb-1">
                            <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                                <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                            </svg>
                        </div>
                    ` : ''}
                </div>
            `);

            messageIds.add(Number(message.id));
            if (wasBottom) scrollBottom();
        };

        const poll = async () => {
            if (!el?.dataset.pollUrl) return;

            try {
                const url = new URL(el.dataset.pollUrl, window.location.origin);
                url.searchParams.set('after_id', String(lastId()));

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) return;

                const payload = await response.json();
                (payload.messages || []).forEach(renderMessage);

                if (counter && Number.isInteger(payload.messages_count)) {
                    counter.textContent = `${payload.messages_count} mensagens`;
                }
            } catch {
                // Falhas momentâneas de rede não devem interromper o acompanhamento.
            }
        };

        if (el) {
            scrollBottom();
            window.setInterval(poll, 4000);
        }
    })();
</script>
@endsection
