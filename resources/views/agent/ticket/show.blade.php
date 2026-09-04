@extends('layouts.agent')

@section('title', '#' . $ticket->id . ' — ' . Str::limit($ticket->subject, 50))

@section('content')
{{--
    Ticket Show — Detalhe completo de um chamado
    Alpine:  ticketShow (resources/js/agent/tickets/show.js)
    Tabs:    comments | attendances | issues | attachments | audit
    Design:  Tailwind CSS + sistema ocean via CSS global
    Mobile:  Stack vertical | Desktop: grid 3+1 colunas
--}}
@php
    $isAdmin = (bool) (auth('admin')->user()?->isAdmin() ?? auth()->user()?->isAdmin());
    $canUpdateTicket = auth('admin')->user()?->can('update', $ticket) ?? auth()->user()?->can('update', $ticket) ?? false;
    $closeStatuses = $closeStatuses ?? collect();
    $isTerminalTicket = ! is_null($ticket->completed_at)
        || (bool) ($ticket->status?->is_terminal ?? false);
    $isPastOrClosed = $isTerminalTicket || ($ticket->created_at && $ticket->created_at->lt(today()));
    $closeWorkflowConfig = [
        'closeStatuses' => $closeStatuses
            ->map(fn ($status) => [
                'id' => (string) $status->id,
                'name' => $status->name,
                'requiresSolution' => (bool) $status->requires_solution,
            ])
            ->values()
            ->all(),
        'initialCloseStatusId' => old('status_id', $closeStatuses->count() === 1 ? (string) $closeStatuses->first()->id : ''),
        'initialCloseSolution' => old('solution', (string) ($ticket->solution ?? '')),
        'openCloseOnLoad' => session()->has('open_close_ticket') || $errors->has('status_id') || $errors->has('solution'),
    ];
@endphp
<div x-data="ticketShow(@js($closeWorkflowConfig))" class="space-y-5">

    {{-- ══ BREADCRUMB ══════════════════════════════════════════════════════ --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium" aria-label="Breadcrumb">
        <a href="{{ route('agent.ticket.index') }}" class="hover:text-indigo-600 transition-colors">Chamados</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-600 truncate max-w-xs">{{ $ticket->subject }}</span>
    </nav>

    {{-- ══ GRID PRINCIPAL ══════════════════════════════════════════════════ --}}
    <div class="flex flex-col xl:flex-row gap-5 items-start">

        {{-- ── COLUNA PRINCIPAL ──────────────────────────────────────────── --}}
        <div class="w-full xl:flex-1 min-w-0 space-y-5">

            {{-- ── HEADER ──────────────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
                 style="border-top: 4px solid {{ $ticket->status?->color ?? '#6366f1' }}">
                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full text-gray-400 bg-gray-100 border border-gray-200">#{{ $ticket->id }}</span>
                        <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full"
                              style="background-color:{{ $ticket->status?->color ?? '#6366f1' }}20; color:{{ $ticket->status?->color ?? '#6366f1' }}">
                            {{ $ticket->status?->name ?? 'Aberto' }}
                        </span>
                        @php
                            $slaLevel  = $ticket->sla_level ?? 'normal';
                            $slaColors = ['normal'=>'bg-emerald-100 text-emerald-700','attention'=>'bg-blue-100 text-blue-700','warning'=>'bg-yellow-100 text-yellow-700','critical'=>'bg-red-100 text-red-700','resolved'=>'bg-emerald-100 text-emerald-700'];
                            $slaLabels = ['normal'=>'No Prazo','attention'=>'Atenção','warning'=>'Aviso','critical'=>'Crítico','resolved'=>'Concluído'];
                        @endphp
                        <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full {{ $slaColors[$slaLevel] ?? 'bg-gray-100 text-gray-600' }}">
                            SLA: {{ $slaLabels[$slaLevel] ?? 'N/A' }}
                        </span>
                        @if($ticket->is_recurring)
                            <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full bg-violet-100 text-violet-700">Recorrente</span>
                        @endif
                        @if((int) $ticket->origin_id === (int) config('whatsapp.chatbot.origin_id', 5) || strcasecmp((string) $ticket->origin?->name, 'WhatsApp') === 0)
                            <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full bg-green-100 text-green-700 border border-green-200">WhatsApp</span>
                        @endif
                        @if($ticket->department)
                            <span class="text-[10px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                                Setor: {{ $ticket->department->name }}
                            </span>
                        @endif
                        @if($ticket->category)
                            <span class="text-[10px] font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-100">
                                {{ $ticket->category->display_name }}
                                @if($ticket->subCategory)
                                    <span class="text-indigo-300 mx-1">›</span>
                                    <span class="text-indigo-400">{{ $ticket->subCategory->display_name }}</span>
                                @endif
                            </span>
                        @endif
                        @php
                            $codigoEmpresarial = $ticket->company?->group?->financial_code
                                ?: ($ticket->company?->codigo_empresarial ?: $ticket->company?->group?->name);
                        @endphp
                        @if($codigoEmpresarial)
                            <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200">
                                Código Empresarial: {{ $codigoEmpresarial }}
                            </span>
                        @endif
                        @if($ticket->extraCategories?->count())
                            <div class="flex flex-wrap gap-1">
                                @foreach($ticket->extraCategories as $extra)
                                    <span class="text-[10px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full border border-gray-200">
                                        {{ $extra->category?->name ?? 'Categoria' }}
                                        @if($extra->subCategory)
                                            <span class="text-gray-300 mx-1">›</span>
                                            <span class="text-gray-500">{{ $extra->subCategory?->name }}</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 leading-tight mb-4">{{ $ticket->subject }}</h1>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs border-t border-gray-100 pt-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Solicitante</p>
                            <p class="font-semibold text-gray-700 truncate">{{ $ticket->author?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Empresa</p>
                            <p class="font-semibold text-gray-700 truncate">{{ $ticket->company?->trade_name ?: ($ticket->company?->name ?? '—') }}</p>
                        </div>
                        @if($ticket->contact)
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Contato</p>
                            <p class="font-semibold text-gray-700 truncate">{{ $ticket->contact }}</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Agente</p>
                            <div class="flex items-center gap-1.5">
                                <div class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600 flex-shrink-0">
                                    {{ strtoupper(substr($ticket->agent?->name ?? '?', 0, 1)) }}
                                </div>
                                <p class="font-semibold text-gray-700 truncate">{{ $ticket->agent?->name ?? 'Sem agente' }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Criado em</p>
                            <p class="font-semibold text-gray-700">{{ $ticket->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                </div>

                @if($ticket->company?->financial_irregular)
                    <div class="px-5 sm:px-6 py-3 bg-red-50 border-t border-red-100 flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <div class="space-y-1">
                            <p class="text-sm font-bold text-red-700">Cliente com bloqueio financeiro</p>
                            @if($ticket->company->observations)
                                <p class="text-xs text-red-600 leading-relaxed">{{ $ticket->company->observations }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                @if($isPastOrClosed && $isAdmin)
                    <div class="px-5 sm:px-6 py-3 bg-amber-50 border-t border-amber-200 flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            <strong class="font-bold">Aviso ao Administrador:</strong> Este chamado é de data anterior ou está encerrado. Qualquer alteração efetuada será registrada na trilha de auditoria (usuário, data/hora e campos modificados).
                        </p>
                    </div>
                @elseif($isPastOrClosed && !$isAdmin)
                    <div class="px-5 sm:px-6 py-3 bg-blue-50 border-t border-blue-200 flex items-center gap-3">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            <strong class="font-bold">Modo de Consulta:</strong> Chamados de datas anteriores ou encerrados estão disponíveis apenas para visualização. A edição destes chamados é restrita a Administradores.
                        </p>
                    </div>
                @endif

                {{-- Ações --}}
                <div class="px-5 sm:px-6 py-3 bg-gray-50 border-t border-gray-100">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($canUpdateTicket)
                            <a href="{{ route('agent.ticket.edit', $ticket->id) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-sm active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar
                            </a>
                        @endif

                        <a href="{{ route('agent.ticket.create', ['from_ticket' => $ticket->id]) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Novo ticket (mesmo cliente)
                        </a>

                        <a href="{{ route('agent.knowledge.create', ['ticket_id' => $ticket->id]) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            EasyWiki
                        </a>

                        @if($canUpdateTicket && ! $isTerminalTicket)
                            @php
                                $isCurrentAgent = (int) ($ticket->agent_id ?? 0) === (int) auth('admin')->id();
                                $canReleaseTicket = $ticket->hasAssignedAgent() && ($isAdmin || $isCurrentAgent);
                            @endphp

                            @if($canReleaseTicket)
                                <form action="{{ route('agent.ticket.release', $ticket) }}" method="POST" class="inline-flex"
                                      onsubmit="return confirm('Devolver o chamado #{{ $ticket->id }} para a fila de pendências? O responsável será removido e o status voltará para Pendente.')">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                        Devolver para a fila
                                    </button>
                                </form>
                            @endif

                            <button type="button"
                                    @click="openClosePanel()"
                                    @disabled(! $ticket->hasAssignedAgent() || $closeStatuses->isEmpty())
                                    @class([
                                        'inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold border transition-all shadow-sm',
                                        'text-gray-700 bg-white border-gray-200 hover:bg-gray-50' => $ticket->hasAssignedAgent() && $closeStatuses->isNotEmpty(),
                                        'text-gray-400 bg-gray-100 border-gray-200 cursor-not-allowed' => ! $ticket->hasAssignedAgent() || $closeStatuses->isEmpty(),
                                    ])>
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Encerrar chamado
                            </button>
                        @elseif($isTerminalTicket)
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold text-emerald-700 bg-emerald-50 border border-emerald-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Chamado já encerrado
                            </span>
                        @endif

                        @if($isAdmin)
                        <button @click="confirmDelete = !confirmDelete"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold text-red-600 bg-red-50 border border-red-100 hover:bg-red-100 transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Excluir
                        </button>
                        @endif

                        <a href="{{ route('agent.ticket.index') }}"
                           class="ml-auto inline-flex items-center gap-1.5 text-sm font-medium text-gray-400 hover:text-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Voltar
                        </a>
                    </div>

                    @if($canUpdateTicket && ! $isTerminalTicket)
                        @if(! $ticket->hasAssignedAgent())
                            <p class="mt-3 text-xs font-semibold text-amber-700">
                                Atribua um responsável antes de encerrar o chamado.
                            </p>
                        @elseif($closeStatuses->isEmpty())
                            <p class="mt-3 text-xs font-semibold text-amber-700">
                                Nenhum status de encerramento está disponível para este ambiente.
                            </p>
                        @endif

                        {{-- Painel de confirmação: Fechar Chamado --}}
                        <div x-show="confirmClose" x-collapse style="display: none" class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-4 dark:border-amber-500/30 dark:bg-slate-900">
                            <div>
                                <p class="text-sm font-bold text-amber-900 dark:text-amber-100">Encerrar chamado #{{ $ticket->id }}</p>
                                <p class="text-xs text-amber-700 mt-1 dark:text-amber-200">
                                    Escolha o status final deste atendimento. O chamado será finalizado e você retornará à sua fila de pendências.
                                    Se a intenção era apenas devolver o chamado para a fila aberta, use o botão <strong>Devolver para a fila</strong>.
                                </p>
                            </div>

                            <form action="{{ route('agent.ticket.close', $ticket) }}" method="POST" class="space-y-4">
                                @csrf

                                <fieldset class="space-y-2">
                                    <legend class="text-[11px] font-black uppercase tracking-widest text-amber-800 dark:text-amber-200">Tipo de Encerramento</legend>

                                    @foreach($closeStatuses as $status)
                                        <label class="flex items-start gap-3 rounded-xl border px-3 py-3 transition-colors cursor-pointer"
                                               :class="closeStatusId === '{{ $status->id }}' ? 'border-emerald-300 bg-white dark:border-emerald-500 dark:bg-slate-800' : 'border-amber-200 bg-white/70 hover:bg-white dark:border-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-800'">
                                            <input type="radio"
                                                   name="status_id"
                                                   value="{{ $status->id }}"
                                                   x-model="closeStatusId"
                                                   class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                            <span class="min-w-0">
                                                <span class="block text-sm font-bold text-gray-900 dark:text-slate-100">{{ $status->name }}</span>
                                                <span class="block text-xs text-gray-500 mt-0.5 dark:text-slate-300">
                                                    {{ $status->requires_solution ? 'Exige registrar a solução aplicada.' : 'Encerra o chamado sem exigir descrição de solução.' }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach

                                    @error('status_id')
                                        <p class="text-xs font-semibold text-rose-700">{{ $message }}</p>
                                    @enderror
                                </fieldset>

                                <div x-show="closeRequiresSolution" x-collapse style="display: none" class="space-y-2">
                                    <label for="close-solution" class="block text-[11px] font-black uppercase tracking-widest text-amber-800 dark:text-amber-200">
                                        Solução Aplicada
                                    </label>
                                    <textarea id="close-solution"
                                              name="solution"
                                              x-model="closeSolution"
                                              rows="4"
                                              class="w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                                              placeholder="Descreva a solução aplicada para encerrar como Resolvido."></textarea>
                                    @error('solution')
                                        <p class="text-xs font-semibold text-rose-700">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="rounded-xl bg-white/80 border border-amber-200 px-3 py-2 text-xs text-amber-800 dark:border-slate-700 dark:bg-slate-800 dark:text-amber-100">
                                    <span x-text="closeHelperText"></span>
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit"
                                            :disabled="!closeStatusId || (closeRequiresSolution && !closeSolution.trim())"
                                            class="flex-1 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-300 disabled:cursor-not-allowed text-white transition-all"
                                            x-text="closeSubmitLabel"></button>
                                    <button type="button"
                                            @click="confirmClose = false"
                                            class="flex-1 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if($isAdmin)
                    {{-- Painel de confirmação: Excluir Chamado --}}
                    <div x-show="confirmDelete" x-collapse style="display: none" class="mt-3 rounded-xl border border-red-200 bg-red-50 p-4 space-y-3">
                        <p class="text-xs font-semibold text-red-800">Esta ação é irreversível. Excluir o chamado #{{ $ticket->id }}?</p>
                        <div class="flex gap-2">
                            <form action="{{ route('agent.ticket.destroy', $ticket->id) }}" method="POST" class="flex-1">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full py-2 rounded-xl text-xs font-bold bg-red-600 hover:bg-red-700 text-white transition-all">Excluir</button>
                            </form>
                            <button @click="confirmDelete = false" class="flex-1 py-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-all">Cancelar</button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- ── CHAMADO REFERENTE ──────────────────────────────────── --}}
            @if($ticket->referencedTicket)
                <div class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <div>
                        <p class="font-bold text-blue-800">Chamado Referente</p>
                        <p class="text-blue-700 text-xs">Vinculado ao #{{ $ticket->referencedTicket->id }}: {{ Str::limit($ticket->referencedTicket->subject, 80) }}</p>
                    </div>
                    <a href="{{ route('agent.ticket.show', $ticket->referencedTicket->id) }}"
                       class="ml-auto text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors whitespace-nowrap">
                        Ver #{{ $ticket->referencedTicket->id }} →
                    </a>
                </div>
            @endif

            @if($ticket->childTickets && $ticket->childTickets->isNotEmpty())
                <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Chamados vinculados a este</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($ticket->childTickets as $child)
                            <a href="{{ route('agent.ticket.show', $child->id) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 hover:text-indigo-600 hover:border-indigo-200 transition-all">
                                #{{ $child->id }} — {{ Str::limit($child->subject, 40) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── CONTATOS DO CLIENTE ───────────────────────────────────── --}}
            @if($ticket->company?->contacts && $ticket->company->contacts->isNotEmpty())
                <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-sm">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Contatos da Empresa</p>
                    <div class="divide-y divide-gray-100">
                        @foreach($ticket->company->contacts as $contact)
                            @php
                                $digits = preg_replace('/\\D/', '', $contact->phone ?? '');
                                $formatted = $digits ? preg_replace('/(\\d{2})(\\d{4,5})(\\d{4})/', '($1) $2-$3', $digits) : null;
                            @endphp
                            <div class="py-2 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center">
                                    {{ strtoupper(substr($contact->name ?? 'C', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $contact->name ?? 'Contato' }}</p>
                                    @if($formatted)
                                        <p class="text-xs text-gray-500">{{ $formatted }}</p>
                                    @endif
                                </div>
                                @if($contact->is_main)
                                    <span class="text-[10px] font-black uppercase text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">Principal</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── CORPO ────────────────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
                <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr($ticket->author?->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">{{ $ticket->author?->name ?? 'Solicitante' }}</p>
                        <p class="text-[10px] text-gray-400">{{ $ticket->created_at?->format('d/m/Y \à\s H:i') }}</p>
                    </div>
                </div>
                <div class="px-5 sm:px-6 py-5 prose prose-sm max-w-none text-gray-700 leading-relaxed">
                    {!! $ticket->html ?? nl2br(e($ticket->content)) !!}
                </div>
            </div>

            @include('agent.company.partials.ticket-issues', ['ticket' => $ticket])

            {{-- ══ TABS ════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

                {{-- Tab bar --}}
                <div class="flex items-center border-b border-gray-200 bg-gray-50 overflow-x-auto">
                    @php
                        $tabs = [
                            ['id'=>'comments',    'label'=>'Respostas',    'icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'count'=>$comments->count()],
                            ['id'=>'attendances', 'label'=>'Atendimentos', 'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'count'=>null],
                            ['id'=>'issues',      'label'=>'Problemas',    'icon'=>'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'count'=>null],
                            ['id'=>'attachments', 'label'=>'Anexos',       'icon'=>'M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13', 'count'=>$attachments->count()],
                            ['id'=>'audit',       'label'=>'Modificações', 'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'count'=>null],
                        ];
                        if ($webChatConversation) {
                            $tabs[] = ['id'=>'chat-web', 'label'=>'Chat Web', 'icon'=>'M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8zm-5 4H8', 'count'=>$webChatConversation->messages->count()];
                        }
                        $tabs[] = ['id'=>'whatsapp', 'label'=>'WhatsApp', 'icon'=>'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a11.72 11.72 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z', 'count'=>$whatsappConversation?->messages->count()];
                    @endphp

                    @foreach($tabs as $tab)
                        <button @click="setTab('{{ $tab['id'] }}')"
                                :class="activeTab === '{{ $tab['id'] }}'
                                    ? 'border-b-2 border-indigo-600 text-indigo-600 bg-white'
                                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                                class="inline-flex items-center gap-1.5 px-4 py-3 text-xs font-bold transition-all whitespace-nowrap flex-shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/>
                            </svg>
                            {{ $tab['label'] }}
                            @if($tab['count'] !== null)
                                <span class="ml-0.5 px-1.5 py-0.5 text-[10px] font-black rounded-full bg-gray-100 text-gray-600">{{ $tab['count'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- ── TAB: RESPOSTAS ───────────────────────────────────── --}}
                <div x-show="activeTab === 'comments'" class="p-5 sm:p-6 space-y-4">
                    @forelse($comments as $comment)
                        @php $isAgent = $comment->user?->ticketit_agent || $comment->user?->ticketit_admin; @endphp
                        <div id="comment-{{ $comment->id }}"
                             class="group flex gap-3 {{ $isAgent ? '' : 'flex-row-reverse' }} scroll-mt-20">
                            <div class="flex-shrink-0">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold {{ $isAgent ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                                    {{ strtoupper(substr($comment->user?->name ?? 'U', 0, 1)) }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="rounded-2xl border shadow-sm overflow-hidden {{ $isAgent ? 'bg-white border-gray-200 rounded-tl-none' : 'bg-indigo-50 border-indigo-100 rounded-tr-none' }}">
                                    <div class="flex items-center justify-between gap-4 px-4 py-2.5 border-b {{ $isAgent ? 'border-gray-100 bg-gray-50' : 'border-indigo-100 bg-indigo-100/50' }}">
                                        <p class="text-xs font-bold text-gray-800">
                                            {{ $comment->user?->name ?? 'Usuário' }}
                                            @if($isAgent)
                                                <span class="ml-1.5 text-[10px] font-black uppercase text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full">Suporte</span>
                                            @endif
                                        </p>
                                        <time class="text-[10px] text-gray-400 italic flex-shrink-0" datetime="{{ $comment->created_at->toIso8601String() }}">
                                            {{ $comment->created_at->format('d/m/Y H:i') }} &bull; {{ $comment->created_at->diffForHumans() }}
                                        </time>
                                    </div>
                                    <div class="px-4 py-4 prose prose-sm max-w-none text-gray-700 leading-relaxed">
                                        {!! $comment->html ?? nl2br(e($comment->content)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10 text-center rounded-2xl border border-dashed border-gray-200">
                            <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <p class="text-sm font-semibold text-gray-500">Nenhuma resposta ainda</p>
                        </div>
                    @endforelse

                    @if(! $isTerminalTicket || $isAdmin)
                        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden" id="reply">
                            <div class="px-5 py-4 bg-gray-50 border-b border-gray-100">
                                <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                    </svg>
                                    Responder ao Chamado
                                </h3>
                            </div>
                            <form action="{{ route('agent.ticket.comment.store', $ticket) }}" method="POST" class="p-5 space-y-4"
                                  @submit="isSubmitting = true">
                                @csrf
                                <textarea name="content" rows="5" x-model="commentBody"
                                          placeholder="Digite a resposta ao chamado..."
                                          class="w-full px-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 resize-y min-h-[120px] transition-all"
                                          required></textarea>
                                <div class="flex items-center gap-3">
                                    <button type="submit"
                                            :disabled="commentTooLong || isSubmitting"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm shadow-md transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        Enviar Resposta
                                    </button>
                                    <span class="text-[10px] font-semibold ml-auto" :class="commentTooLong ? 'text-red-500' : 'text-gray-400'">
                                        <span x-text="commentLength"></span> / 5000
                                    </span>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>

                {{-- ── TAB: ATENDIMENTOS ────────────────────────────────── --}}
                <div x-show="activeTab === 'attendances'" style="display:none" class="p-5 sm:p-6">
                    @include('agent.partials.attendances', ['ticket' => $ticket, 'agents' => $agentsList])
                </div>

                {{-- ── TAB: PROBLEMAS ───────────────────────────────────── --}}
                <div x-show="activeTab === 'issues'" style="display:none" class="p-5 sm:p-6">
                    <div x-data="ticketIssues({{ $ticket->id }})">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <h3 class="text-sm font-black text-gray-700 uppercase tracking-widest">Problemas</h3>
                                <span class="px-2 py-0.5 text-[10px] font-black bg-amber-100 text-amber-700 rounded-full" x-text="openCount + ' abertos'"></span>
                                <span class="px-2 py-0.5 text-[10px] font-black bg-emerald-100 text-emerald-700 rounded-full" x-text="resolvedCount + ' resolvidos'"></span>
                            </div>
                            <button @click="showForm = !showForm"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Novo Problema
                            </button>
                        </div>

                        <div x-show="showForm" x-collapse class="mb-4">
                            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 space-y-3">
                                <input type="text" x-model="form.title" placeholder="Título do problema"
                                       class="w-full px-4 py-2.5 text-sm bg-white border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                                <textarea x-model="form.description" rows="3" placeholder="Descrição detalhada (opcional)"
                                          class="w-full px-4 py-2.5 text-sm bg-white border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition-all resize-none"></textarea>
                                <div class="flex gap-2">
                                    <button @click="submitIssue()" :disabled="submitting || !form.title.trim()"
                                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all disabled:opacity-50">
                                        <span x-text="submitting ? 'Salvando...' : 'Adicionar'"></span>
                                    </button>
                                    <button @click="showForm = false"
                                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="loading" class="py-8 text-center text-gray-400">
                            <svg class="animate-spin h-6 w-6 text-indigo-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </div>

                        <div x-show="!loading && issues.length === 0" style="display:none"
                             class="py-10 text-center rounded-2xl border-2 border-dashed border-gray-200">
                            <p class="text-sm font-semibold text-gray-500">Nenhum problema registrado</p>
                        </div>

                        <div class="space-y-3">
                            <template x-for="issue in issues" :key="issue.id">
                                <div class="bg-white border rounded-2xl overflow-hidden" :class="issue.is_resolved ? 'border-emerald-200' : 'border-gray-200'">
                                    <div class="p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                                <div class="w-5 h-5 rounded-full flex-shrink-0 mt-0.5 flex items-center justify-center"
                                                     :class="issue.is_resolved ? 'bg-emerald-500' : 'bg-amber-500'">
                                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                              :d="issue.is_resolved ? 'M5 13l4 4L19 7' : 'M12 8v4m0 4h.01'"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-gray-800" x-text="issue.title"
                                                       :class="issue.is_resolved ? 'line-through text-gray-400' : ''"></p>
                                                    <template x-if="issue.description">
                                                        <p class="text-xs text-gray-500 mt-0.5" x-text="issue.description"></p>
                                                    </template>
                                                    <template x-if="issue.solution">
                                                        <div class="mt-2 text-xs text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2">
                                                            <span class="font-bold">Solução: </span><span x-text="issue.solution"></span>
                                                        </div>
                                                    </template>
                                                    <p class="text-[10px] text-gray-400 mt-1.5">
                                                        Por <span x-text="issue.creator?.name ?? '—'"></span> · <span x-text="formatDate(issue.created_at)"></span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex gap-1.5 flex-shrink-0">
                                                <template x-if="!issue.is_resolved">
                                                    <button @click="showResolveFor = (showResolveFor === issue.id ? null : issue.id)"
                                                            class="px-2.5 py-1 text-[10px] font-bold bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-all">
                                                        Resolver
                                                    </button>
                                                </template>
                                                <button @click="deleteIssue(issue.id)"
                                                        class="px-2.5 py-1 text-[10px] font-bold bg-gray-50 text-gray-500 rounded-lg hover:bg-red-50 hover:text-red-600 transition-all">✕</button>
                                            </div>
                                        </div>

                                        <template x-if="showResolveFor === issue.id">
                                            <div class="mt-3 space-y-2 border-t border-gray-100 pt-3">
                                                <textarea x-model="resolveForm.solution" rows="2"
                                                          placeholder="Descreva como foi resolvido..."
                                                          class="w-full px-3 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 transition-all resize-none"></textarea>
                                                <div class="flex gap-2">
                                                    <button @click="resolveIssue(issue.id)" :disabled="resolvingId === issue.id"
                                                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all disabled:opacity-50">
                                                        <span x-text="resolvingId === issue.id ? 'Salvando...' : 'Confirmar'"></span>
                                                    </button>
                                                    <button @click="showResolveFor = null"
                                                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-200 transition-all">Cancelar</button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ── TAB: ANEXOS ──────────────────────────────────────── --}}
                <div x-show="activeTab === 'attachments'" style="display:none" class="p-5 sm:p-6">
                    <div x-data="ticketAttachments({{ $ticket->id }})" x-data2="{ isDragging: false }">

                        <div @dragover.prevent @dragleave.prevent @drop.prevent="handleDrop($event)"
                             class="border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 rounded-2xl p-6 text-center transition-all cursor-pointer mb-5"
                             @click="$refs.fileUploadInput.click()">
                            <svg class="mx-auto w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="text-sm font-semibold text-gray-600">Clique ou arraste arquivos aqui</p>
                            <p class="text-xs text-gray-400 mt-0.5">Qualquer extensão · máximo 50MB</p>
                            <input type="file" x-ref="fileUploadInput" multiple class="hidden" @change="handleFileInput($event)">
                        </div>

                        <div x-show="uploadError" style="display:none" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700" x-text="uploadError"></div>
                        <div x-show="uploading" style="display:none" class="mb-4 flex items-center gap-2 text-xs text-indigo-600 font-semibold">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Enviando arquivo...
                        </div>

                        <div x-show="loading" class="py-6 text-center text-gray-400">
                            <svg class="animate-spin h-6 w-6 text-indigo-400 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </div>

                        <div x-show="!loading && attachments.length === 0" style="display:none" class="py-8 text-center text-gray-400">
                            <p class="text-sm font-semibold">Nenhum anexo enviado</p>
                        </div>

                        <div class="space-y-2">
                            <template x-for="att in attachments" :key="att.id">
                                <div class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-xl hover:shadow-sm transition-all">
                                    <span class="text-2xl flex-shrink-0" x-text="extensionIcon(att.mime)"></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate" x-text="att.original_name || att.name"></p>
                                        <p class="text-[10px] text-gray-400">
                                            <span x-text="att.mime?.toUpperCase()"></span> · <span x-text="att.size_human"></span> · <span x-text="formatDate(att.created_at)"></span>
                                        </p>
                                    </div>
                                    <div class="flex gap-1.5 flex-shrink-0">
                                        <a :href="`/api/v1/attachments/${att.id}/view`" target="_blank"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-all">
                                            <span x-text="att.viewable ? 'Visualizar' : 'Baixar'"></span>
                                        </a>
                                        <button @click="deleteAttachment(att.id)"
                                                class="px-2 py-1 text-[10px] font-bold bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-all">✕</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ── TAB: MODIFICAÇÕES ────────────────────────────────── --}}
                <div x-show="activeTab === 'audit'" style="display:none" class="p-5 sm:p-6">
                    <div x-data="ticketAudit({{ $ticket->id }})">

                        <div x-show="loading" class="py-8 text-center text-gray-400">
                            <svg class="animate-spin h-6 w-6 text-indigo-400 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </div>
                        <div x-show="error" style="display:none" class="p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700" x-text="error"></div>

                        <div x-show="!loading && audits.length > 0" class="flex flex-wrap gap-3 mb-4">
                            <select x-model="filterEvent"
                                    class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Todos os eventos</option>
                                <template x-for="event in eventTypes" :key="event">
                                    <option :value="event" x-text="event"></option>
                                </template>
                            </select>
                            <input type="text" x-model="searchUser" placeholder="Filtrar por usuário"
                                   class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="toggleOnlyDepartment()"
                                    :class="onlyDepartment ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-indigo-700 border-gray-200 hover:border-indigo-300'"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold border rounded-xl px-3 py-1.5 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Só departamento
                            </button>
                        </div>

                        <div x-show="!loading && filtered.length === 0" style="display:none"
                             class="py-10 text-center rounded-2xl border-2 border-dashed border-gray-200">
                            <p class="text-sm font-semibold text-gray-500">Nenhuma modificação registrada</p>
                            <p class="text-xs text-gray-400 mt-1">As alterações futuras serão registradas automaticamente</p>
                        </div>

                        <div class="space-y-3">
                            <template x-for="audit in filtered" :key="audit.id">
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" :class="eventColor(audit.event)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="eventIcon(audit.event)"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0 pb-3 border-b border-gray-100 last:border-0">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800" x-text="audit.operation"></p>
                                                <template x-if="audit.field && audit.old_value !== null">
                                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                        <span class="px-2 py-0.5 bg-red-50 text-red-700 rounded text-[10px] font-semibold line-through" x-text="audit.old_value"></span>
                                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                        </svg>
                                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded text-[10px] font-semibold" x-text="audit.new_value"></span>
                                                    </div>
                                                </template>
                                                <p class="text-[10px] text-gray-400 mt-1">
                                                    <span x-text="audit.user?.name ?? 'Sistema'"></span> · <span x-text="formatDate(audit.created_at)"></span>
                                                </p>
                                            </div>
                                            <span class="flex-shrink-0 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full" :class="eventColor(audit.event)" x-text="audit.event_label"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ── TAB: CHAT WEB ────────────────────────────────────── --}}
                @if($webChatConversation)
                <div x-show="activeTab === 'chat-web'" style="display:none; min-height: 400px;" class="flex flex-col">
                    <div class="flex items-center gap-3 border-b border-orange-100 bg-orange-50 px-5 py-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-500 text-xs font-black text-white">
                            {{ strtoupper(substr($webChatConversation->owner?->name ?? 'C', 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-800">{{ $webChatConversation->subject ?: 'Chat Web' }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $webChatConversation->owner?->name ?? 'Cliente' }}
                                &middot; sessão {{ $webChatConversation->session }}
                                &middot; {{ $webChatConversation->messages->count() }} mensagens
                            </p>
                        </div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-orange-700">
                            {{ $webChatConversation->status?->name ?? ($webChatConversation->isClosed() ? 'Fechado' : 'Aberto') }}
                        </span>
                    </div>

                    <div class="flex-1 space-y-3 overflow-y-auto bg-gray-50 p-5" style="max-height: 480px;">
                        @forelse($webChatConversation->messages as $entry)
                            @php $isSupportMessage = (int) $entry->user_id !== (int) $webChatConversation->owner_id; @endphp
                            <div class="flex {{ $isSupportMessage ? 'justify-start' : 'justify-end' }}">
                                <div class="max-w-[78%] {{ $isSupportMessage ? '' : 'order-last' }}">
                                    <div class="rounded-2xl px-3.5 py-2.5 text-sm shadow-sm {{ $isSupportMessage ? 'rounded-tl-none border border-gray-200 bg-white text-gray-800' : 'rounded-tr-none bg-orange-500 text-white' }}">
                                        <p class="mb-1 text-[10px] font-black uppercase tracking-[0.18em] {{ $isSupportMessage ? 'text-gray-400' : 'text-orange-100' }}">
                                            {{ $isSupportMessage ? ($entry->owner?->name ?? 'Suporte') : ($entry->owner?->name ?? 'Cliente') }}
                                        </p>
                                        <p class="whitespace-pre-wrap break-words">{{ $entry->content }}</p>
                                    </div>
                                    <p class="mt-1 text-[10px] text-gray-400 {{ $isSupportMessage ? 'text-left' : 'text-right' }}">
                                        {{ $entry->created_at->format('d/m H:i') }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="flex h-32 items-center justify-center text-sm text-gray-400">
                                Nenhuma mensagem registrada.
                            </div>
                        @endforelse
                    </div>

                    <div class="border-t border-orange-100 bg-white px-5 py-3 text-xs text-gray-500">
                        Respostas publicadas pelo agente no ticket são sincronizadas para este histórico.
                    </div>
                </div>
                @endif

                {{-- ── TAB: WHATSAPP ────────────────────────────────────── --}}
                @if($whatsappConversation)
                <div x-show="activeTab === 'whatsapp'" class="flex flex-col" style="min-height: 400px;">

                    {{-- Header da conversa --}}
                    <div class="flex items-center gap-3 px-5 py-3 bg-green-50 border-b border-green-100">
                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.121 1.532 5.854L.057 23.882l6.186-1.454A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.894 0-3.668-.523-5.183-1.432l-.371-.22-3.676.864.923-3.577-.241-.388A9.958 9.958 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800">+{{ $whatsappConversation->phone }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $whatsappConversation->state->label() }}
                                &middot; {{ $whatsappConversation->messages->count() }} mensagens
                            </p>
                        </div>
                        <span @class([
                            'text-xs font-semibold px-2.5 py-1 rounded-full',
                            'bg-orange-100 text-orange-700' => $whatsappConversation->state->isHumanPending(),
                            'bg-green-100 text-green-700'   => $whatsappConversation->state->value === 'completed',
                            'bg-gray-100 text-gray-600'     => ! $whatsappConversation->state->isHumanPending() && $whatsappConversation->state->value !== 'completed',
                        ])>
                            {{ $whatsappConversation->state->label() }}
                        </span>
                    </div>

                    {{-- Histórico de mensagens --}}
                    <div id="whatsapp-message-list"
                         data-poll-url="{{ route('agent.ticket.whatsapp.messages', $ticket) }}"
                         data-send-url="{{ route('agent.ticket.whatsapp.messages.store', $ticket) }}"
                         data-delete-url-template="{{ route('agent.ticket.whatsapp.messages.destroy', [$ticket, '__MESSAGE_ID__']) }}"
                         data-update-url-template="{{ route('agent.ticket.whatsapp.messages.update', [$ticket, '__MESSAGE_ID__']) }}"
                         data-can-update="{{ $canUpdateTicket ? '1' : '0' }}"
                         data-csrf="{{ csrf_token() }}"
                         class="flex-1 overflow-y-auto p-5 space-y-3 bg-gray-50" style="max-height: 480px;">
                        @forelse($whatsappConversation->messages as $msg)
                            @php
                                $isInbound = $msg->direction === 'inbound';
                                $isDeleted = $msg->isDeleted();
                            @endphp
                            <div data-whatsapp-message-id="{{ $msg->id }}" class="flex {{ $isInbound ? 'justify-start' : 'justify-end' }}">
                                <div class="max-w-[75%] {{ $isInbound ? '' : 'order-last' }}">
                                    {{-- Balão da mensagem --}}
                                    <div class="rounded-2xl px-3.5 py-2.5 text-sm shadow-sm
                                        {{ $msg->is_internal
                                            ? 'bg-amber-50 text-amber-900 rounded-tr-none border border-amber-200'
                                            : ($isInbound
                                            ? 'bg-white text-gray-800 rounded-tl-none border border-gray-200'
                                            : 'bg-green-500 text-white rounded-tr-none') }}">

                                        @if($isDeleted)
                                            <p class="whitespace-pre-wrap break-words italic opacity-75">Mensagem excluída.</p>
                                        @elseif($msg->type === 'text')
                                            <p class="whitespace-pre-wrap break-words">{{ $msg->body }}</p>
                                        @elseif($msg->type === 'image')
                                            @php
                                                $mediaUrl = $msg->attachment_path
                                                    ? route('agent.ticket.whatsapp.messages.download', ['ticket' => $ticket->id, 'message' => $msg->id, 'disposition' => 'inline'])
                                                    : null;
                                                $mediaAvailable = $msg->attachment_path && Storage::disk('public')->exists($msg->attachment_path);
                                            @endphp
                                            @if($mediaAvailable)
                                                <a href="{{ $mediaUrl }}" target="_blank">
                                                    <img src="{{ $mediaUrl }}"
                                                         alt="Imagem"
                                                         class="max-w-[240px] max-h-[240px] rounded-lg border border-gray-200 object-contain cursor-pointer hover:opacity-90 transition-opacity">
                                                </a>
                                                <p class="text-[10px] mt-1 {{ $isInbound ? 'text-gray-400' : 'text-green-200' }}">{{ $msg->mime_type ?? '' }}</p>
                                            @else
                                                <div class="flex items-center gap-2 text-xs {{ $isInbound ? 'text-gray-500' : 'text-green-100' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    Imagem · {{ $msg->mime_type ?? '—' }} {{ $msg->attachment_path ? '· arquivo indisponível' : '' }}
                                                </div>
                                            @endif
                                        @elseif($msg->type === 'video')
                                            @if($msg->attachment_path)
                                                <video controls preload="metadata"
                                                       src="{{ route('agent.ticket.whatsapp.messages.download', ['ticket' => $ticket->id, 'message' => $msg->id, 'disposition' => 'inline']) }}"
                                                       class="max-w-[280px] max-h-[280px] rounded-lg border border-gray-200"></video>
                                            @else
                                                <div class="flex items-center gap-2 text-xs {{ $isInbound ? 'text-gray-500' : 'text-green-100' }}">
                                                    Vídeo · {{ $msg->mime_type ?? '—' }}
                                                </div>
                                            @endif
                                        @elseif($msg->type === 'document')
                                            <div class="flex items-center gap-2 text-xs {{ $isInbound ? 'text-gray-500' : 'text-green-100' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <span class="font-semibold">Documento</span>
                                                <span class="truncate">{{ $msg->original_filename ?: ($msg->mime_type ?? '') }}</span>
                                                @if($msg->attachment_path)
                                                    <a href="{{ route('agent.ticket.whatsapp.messages.download', ['ticket' => $ticket->id, 'message' => $msg->id]) }}"
                                                       target="_blank"
                                                       class="text-xs underline opacity-80 hover:opacity-100">
                                                        Baixar arquivo
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif($msg->type === 'audio')
                                            @if($msg->attachment_path)
                                                <audio controls preload="metadata"
                                                       src="{{ route('agent.ticket.whatsapp.messages.download', ['ticket' => $ticket->id, 'message' => $msg->id, 'disposition' => 'inline']) }}"
                                                       class="w-full max-w-[280px] {{ $isInbound ? '' : 'invert' }}"
                                                       style="height: 36px;"></audio>
                                            @else
                                                <div class="flex items-center gap-2 text-xs {{ $isInbound ? 'text-gray-500' : 'text-green-100' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                                    </svg>
                                                    Áudio · {{ $msg->mime_type ?? '—' }}
                                                </div>
                                            @endif
                                        @else
                                            <p class="text-xs italic">{{ $msg->body ?: '(mídia)' }}</p>
                                        @endif
                                    </div>

                                    {{-- Timestamp --}}
                                    <div class="mt-1 flex items-center gap-2 {{ $isInbound ? 'justify-start' : 'justify-end' }}">
                                        <p class="text-[10px] text-gray-400">
                                            {{ $msg->created_at->format('d/m H:i') }}
                                            @if($msg->is_internal)
                                                <span class="ml-1">· Interna</span>
                                            @elseif(!$isInbound)
                                                <span class="ml-1">· {{ $msg->user?->name ?? 'Bot' }}</span>
                                            @endif
                                        </p>
                                        @if($canUpdateTicket && !$isDeleted && $msg->direction === 'outbound')
                                            <button type="button"
                                                    data-whatsapp-edit="{{ $msg->id }}"
                                                    class="text-[10px] font-semibold text-gray-400 hover:text-blue-600">
                                                Editar
                                            </button>
                                            <button type="button"
                                                    data-whatsapp-delete="{{ $msg->id }}"
                                                    class="text-[10px] font-semibold text-gray-400 hover:text-red-600">
                                                Excluir
                                            </button>
                                        @elseif($canUpdateTicket && !$isDeleted)
                                            <button type="button"
                                                    data-whatsapp-delete="{{ $msg->id }}"
                                                    class="text-[10px] font-semibold text-gray-400 hover:text-red-600">
                                                Excluir
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="flex items-center justify-center h-32 text-sm text-gray-400">
                                Nenhuma mensagem registrada.
                            </div>
                        @endforelse
                    </div>

                    @if($canUpdateTicket)
                    <div class="border-t border-green-100 bg-white px-5 py-3">
                        <form id="whatsapp-message-form" class="space-y-3" enctype="multipart/form-data">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="file" name="attachment" id="whatsapp-attachment" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,video/mp4,video/quicktime,video/webm,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,text/csv">
                            <input type="text" name="caption" id="whatsapp-caption" class="hidden" placeholder="Legenda (opcional)">
                            <div id="whatsapp-attachment-preview" class="hidden mb-2 p-2 bg-gray-100 rounded-lg flex items-center gap-2">
                                <span class="text-xs text-gray-600 truncate" id="whatsapp-attachment-name"></span>
                                <button type="button" id="whatsapp-attachment-clear" class="text-xs text-red-600 hover:text-red-800 font-semibold">✕</button>
                            </div>
                            <div class="flex items-start gap-2">
                                <textarea name="message" rows="2"
                                          placeholder="Digite uma mensagem, atalho (ex: /sefaz) ou cole um print com Ctrl+V"
                                          class="flex-1 px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-green-500 resize-y min-h-[44px]"></textarea>
                                <div class="flex flex-col gap-1.5">
                                    <button type="button" data-whatsapp-emoji
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 text-lg leading-none"
                                            title="Galeria de emojis"
                                            aria-haspopup="dialog">
                                        😊
                                    </button>
                                    <button type="button" id="whatsapp-attachment-btn"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                                            title="Anexar arquivo">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                    </button>
                                    <button type="button" data-whatsapp-record
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 relative"
                                            title="Gravar áudio">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18a6 6 0 006-6h-2a4 4 0 01-8 0H6a6 6 0 006 6zm0 0v3m-3 0h6M12 14a3 3 0 003-3V5a3 3 0 10-6 0v6a3 3 0 003 3z"/>
                                        </svg>
                                        <div id="whatsapp-volume-level" class="absolute bottom-0 left-0 h-1 bg-red-500 rounded-b transition-all duration-75" style="width: 0%"></div>
                                    </button>
                                </div>
                            </div>
                            <div id="whatsapp-recording-bar" class="hidden p-2.5 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-3 w-3 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
                                    </span>
                                    <span class="text-xs font-bold text-red-700 uppercase tracking-wide">Gravando Áudio</span>
                                    <span id="whatsapp-recording-timer" class="text-xs font-mono font-bold text-red-900 bg-red-100 px-2 py-0.5 rounded">00:00</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="whatsapp-recording-cancel" class="px-2.5 py-1 text-xs font-semibold text-red-700 hover:text-red-900 hover:bg-red-100 rounded-lg transition-colors">
                                        Cancelar
                                    </button>
                                    <button type="button" id="whatsapp-recording-stop" class="px-3 py-1 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition-colors flex items-center gap-1.5">
                                        <span class="w-2 h-2 bg-white rounded-sm"></span> Parar e Ouvir
                                    </button>
                                </div>
                            </div>

                            <div id="whatsapp-audio-preview" class="hidden p-3 bg-emerald-50/80 border border-emerald-200 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-3">
                                <div class="flex items-center gap-3 w-full sm:w-auto flex-1">
                                    <div class="p-2 bg-emerald-100 text-emerald-800 rounded-lg flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between text-xs font-semibold text-emerald-900 mb-1">
                                            <span>Áudio Pronto para Envio</span>
                                            <span id="whatsapp-audio-preview-duration" class="text-emerald-700 font-mono text-[11px]">0:00</span>
                                        </div>
                                        <audio id="whatsapp-audio-player" controls preload="metadata" class="w-full h-8" style="height: 32px;"></audio>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 w-full sm:w-auto justify-end">
                                    <button type="button" id="whatsapp-audio-discard"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-100 hover:bg-red-200 transition-colors"
                                            title="Descartar gravação">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Descartar
                                    </button>
                                    <button type="button" id="whatsapp-audio-send"
                                            class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-bold text-white bg-green-600 hover:bg-green-700 shadow-sm transition-all"
                                            title="Enviar este áudio">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Enviar Áudio
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600">
                                    <input type="checkbox" name="internal" value="1" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                    Mensagem interna
                                </label>
                                <span data-whatsapp-recording class="hidden text-xs font-semibold text-red-600">Gravando...</span>
                                <button type="submit"
                                        class="ml-auto inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-green-600 text-white hover:bg-green-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    Enviar
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif

                    {{-- Aviso de human_pending + botões Puxar para mim / Liberar bot / Pausar bot --}}
                    @if($whatsappConversation->state->isHumanPending())
                    <div class="px-5 py-3 bg-orange-50 border-t border-orange-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-start gap-3 flex-1 min-w-[240px]">
                            <svg class="w-5 h-5 text-orange-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-orange-800">Você está atendendo este cliente</p>
                                <p class="text-xs text-orange-600 mt-0.5">
                                    O bot está pausado. As mensagens chegam silenciosamente.
                                    Responda pelo WhatsApp e clique em "Liberar bot" quando concluir.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if((int) auth('admin')->id() !== (int) $ticket->agent_id)
                                <form action="{{ route('agent.ticket.capture', $ticket->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                                    <button type="submit"
                                            style="background-color:#f97316;color:#ffffff;"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition hover:opacity-90 shadow-sm"
                                            title="Atribuir este chamado a você">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                        </svg>
                                        Puxar para mim
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('agent.whatsapp.release', $whatsappConversation->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        onclick="return confirm('Reativar o bot para este cliente?')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-orange-600 text-white hover:bg-orange-700 transition shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Liberar bot
                                </button>
                            </form>
                        </div>
                    </div>
                    @else
                    <div class="px-5 py-3 bg-sky-50 border-t border-sky-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-start gap-3 flex-1 min-w-[240px]">
                            <svg class="w-5 h-5 text-sky-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-sky-900">Bot ativo</p>
                                <p class="text-xs text-sky-700 mt-0.5">
                                    O bot está ativo e respondendo às mensagens. Clique em "Pausar bot" para assumir o atendimento humano.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if((int) auth('admin')->id() !== (int) $ticket->agent_id)
                                <form action="{{ route('agent.ticket.capture', $ticket->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                                    <button type="submit"
                                            style="background-color:#f97316;color:#ffffff;"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition hover:opacity-90 shadow-sm"
                                            title="Atribuir este chamado a você">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                        </svg>
                                        Puxar para mim
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('agent.whatsapp.pause', $whatsappConversation->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-600 text-white hover:bg-sky-700 transition shadow-sm"
                                        title="Pausar bot e assumir atendimento humano">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Pausar bot
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                </div>
                @else
                {{-- Sem conversa vinculada: permite iniciar contato (outbound) --}}
                <div x-show="activeTab === 'whatsapp'" class="flex flex-col" style="display:none; min-height: 400px;">
                    @if($canUpdateTicket)
                        @php
                            $suggestedPhone = preg_replace('/\D+/', '', (string) ($ticket->company?->whatsapp_phone ?: $ticket->company?->phone));
                        @endphp
                        <div class="flex items-center gap-3 px-5 py-3 bg-green-50 border-b border-green-100">
                            <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.121 1.532 5.854L.057 23.882l6.186-1.454A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800">Iniciar conversa no WhatsApp</p>
                                <p class="text-xs text-gray-500">Este chamado ainda não possui uma conversa vinculada.</p>
                            </div>
                        </div>

                        <div class="p-5">
                            <form action="{{ route('agent.ticket.whatsapp.start', $ticket) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Número do WhatsApp</label>
                                    <input type="text" name="phone" value="{{ old('phone', $suggestedPhone) }}"
                                           placeholder="5527999999999"
                                           class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-green-500">
                                    <p class="text-[11px] text-gray-400 mt-1">Com DDI e DDD, apenas números. Ex.: 5527999999999</p>
                                    @error('phone')
                                        <p class="text-xs font-semibold text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Primeira mensagem</label>
                                    <textarea name="message" rows="3"
                                              placeholder="Olá! Aqui é o suporte Amura, entrando em contato sobre o seu chamado..."
                                              class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-green-500 resize-y">{{ old('message') }}</textarea>
                                    @error('message')
                                        <p class="text-xs font-semibold text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-green-600 text-white hover:bg-green-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        Iniciar conversa
                                    </button>
                                    <p class="text-[11px] text-gray-400">O bot ficará pausado — você assume o atendimento até liberá-lo.</p>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="flex items-center justify-center h-48 text-sm text-gray-400">
                            Este chamado não possui conversa WhatsApp.
                        </div>
                    @endif
                </div>
                @endif

            </div>{{-- /tabs container --}}
        </div>

        {{-- ── SIDEBAR ─────────────────────────────────────────────────── --}}
        <aside class="w-full xl:w-72 flex-shrink-0 space-y-4">

            {{-- Informações --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-gray-50 border-b border-gray-100">
                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-widest">Informações</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="px-5 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Status</p>
                        @if($canUpdateTicket && $quickStatuses->isNotEmpty() && ! $isTerminalTicket)
                            <form action="{{ route('agent.ticket.quick-update', $ticket->id) }}" method="POST" class="flex gap-2">
                                @csrf @method('PATCH')
                                <select name="status_id" onchange="this.form.submit()"
                                        class="flex-1 text-sm bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                    @foreach($quickStatuses as $s)
                                        <option value="{{ $s->id }}" @selected($ticket->status_id == $s->id) style="color: {{ $s->color ?? '#374151' }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold"
                                  style="background-color:{{ $ticket->status?->color ?? '#6366f1' }}20; color:{{ $ticket->status?->color ?? '#6366f1' }}">
                                {{ $ticket->status?->name ?? 'Aberto' }}
                            </span>
                        @endif
                    </div>

                    <div class="px-5 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Agente</p>
                        @if($isAdmin && $agentsList->isNotEmpty())
                            <form action="{{ route('agent.ticket.quick-update', $ticket->id) }}" method="POST" class="flex gap-2">
                                @csrf @method('PATCH')
                                <select name="agent_id" onchange="this.form.submit()"
                                        class="flex-1 text-sm bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                    <option value="">Sem agente</option>
                                    @foreach($agentsList as $ag)
                                        <option value="{{ $ag->id }}" @selected($ticket->agent_id == $ag->id)>{{ $ag->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-600 flex-shrink-0">
                                    {{ strtoupper(substr($ticket->agent?->name ?? '?', 0, 1)) }}
                                </div>
                                <span class="text-sm font-semibold text-gray-700">{{ $ticket->agent?->name ?? 'Sem agente' }}</span>
                            </div>
                            @if((int) auth('admin')->id() !== (int) $ticket->agent_id)
                                <form action="{{ route('agent.ticket.capture', $ticket->id) }}" method="POST" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                                    <button type="submit"
                                            style="background-color:#f97316;color:#ffffff;"
                                            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition hover:opacity-90">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                        </svg>
                                        Puxar para mim
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>

                    <div class="px-5 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Departamento</p>
                        @if($isAdmin && ($departmentsList ?? collect())->isNotEmpty())
                            <form action="{{ route('agent.ticket.quick-update', $ticket->id) }}" method="POST" class="flex gap-2">
                                @csrf @method('PATCH')
                                <select name="department_id" onchange="this.form.submit()"
                                        class="flex-1 text-sm bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                    <option value="">Sem departamento</option>
                                    @foreach($departmentsList as $dep)
                                        <option value="{{ $dep->id }}" @selected((int) $ticket->department_id === (int) $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $ticket->department?->name ?? 'Sem departamento' }}
                            </span>
                        @endif
                    </div>

                    <div class="px-5 py-3.5" x-data="{
                        editing: false,
                        selectedCategory: '{{ $ticket->category_id ?? '' }}',
                        selectedSubCategory: '{{ $ticket->sub_category_id ?? '' }}',
                        subCategories: [],
                        loadingSubs: false,
                        childrenUrl: '{{ url('support/settings/categories') }}',

                        init() {
                            if (this.selectedCategory) {
                                this.fetchSubs(this.selectedCategory, this.selectedSubCategory);
                            }
                        },

                        toggle() {
                            this.editing = !this.editing;
                            if (this.editing && this.selectedCategory && this.subCategories.length === 0) {
                                this.fetchSubs(this.selectedCategory, this.selectedSubCategory);
                            }
                        },

                        cancel() {
                            this.editing = false;
                            this.selectedCategory = '{{ $ticket->category_id ?? '' }}';
                            this.selectedSubCategory = '{{ $ticket->sub_category_id ?? '' }}';
                        },

                        onCategoryChange() {
                            this.selectedSubCategory = '';
                            this.subCategories = [];
                            if (!this.selectedCategory) return;
                            this.fetchSubs(this.selectedCategory, null);
                        },

                        fetchSubs(catId, preselectId) {
                            this.loadingSubs = true;
                            fetch(`${this.childrenUrl}/${catId}/children`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                this.subCategories = data || [];
                                if (preselectId) {
                                    this.selectedSubCategory = String(preselectId);
                                }
                                this.loadingSubs = false;
                            })
                            .catch(() => {
                                this.subCategories = [];
                                this.loadingSubs = false;
                            });
                        }
                    }">
                        <div class="flex items-center justify-between mb-1.5">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Categoria</p>
                            @can('update', $ticket)
                                <button type="button" @click="toggle()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors cursor-pointer">
                                    <span x-show="!editing">Alterar</span>
                                    <span x-show="editing" x-cloak>Cancelar</span>
                                </button>
                            @endcan
                        </div>

                        <!-- Visualização Atual -->
                        <div x-show="!editing">
                            <p class="text-sm font-semibold text-gray-700">
                                {{ $ticket->category?->display_name ?? '—' }}
                                @if($ticket->subCategory)
                                    <span class="text-gray-400 mx-1">›</span>
                                    <span class="text-indigo-600">{{ $ticket->subCategory->display_name }}</span>
                                @endif
                            </p>
                        </div>

                        <!-- Formulário de Edição Rápida -->
                        @can('update', $ticket)
                            <div x-show="editing" x-cloak class="mt-2 pt-2 border-t border-gray-100">
                                <form action="{{ route('agent.ticket.quick-update', $ticket->id) }}" method="POST" class="space-y-2.5">
                                    @csrf
                                    @method('PATCH')
                                    
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Módulo / Categoria</label>
                                        <select name="category_id" x-model="selectedCategory" @change="onCategoryChange()" required
                                                class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1.5 outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                            <option value="">Selecione a categoria</option>
                                            @foreach(($categoriesList ?? collect()) as $cat)
                                                <option value="{{ $cat->category_id }}" @selected((int)$ticket->category_id === (int)$cat->category_id)>{{ $cat->display_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Problema Central / Subcategoria</label>
                                        <select name="sub_category_id" x-model="selectedSubCategory" :disabled="loadingSubs || subCategories.length === 0"
                                                class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1.5 outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                            <option value="" x-text="loadingSubs ? 'Carregando subcategorias...' : (subCategories.length === 0 ? 'Sem subcategorias cadastradas' : 'Sem subcategoria / Opcional')"></option>
                                            <template x-for="sub in subCategories" :key="sub.id">
                                                <option :value="sub.id" x-text="sub.name" :selected="String(sub.id) === String(selectedSubCategory)"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div class="flex items-center gap-1.5 pt-1">
                                        <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold py-1.5 px-3 rounded-lg shadow-sm transition-all text-center">
                                            Salvar
                                        </button>
                                        <button type="button" @click="cancel()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium py-1.5 px-3 rounded-lg transition-all">
                                            Fechar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endcan
                    </div>

                    <div class="px-5 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Empresa</p>
                        @if($ticket->company)
                            <a href="{{ route('agent.companies.manage.edit', $ticket->company->id) }}" class="text-sm font-semibold text-indigo-600 hover:underline">
                                {{ $ticket->company->trade_name ?: $ticket->company->name }}
                            </a>
                        @else
                            <p class="text-sm text-gray-400">—</p>
                        @endif
                    </div>

                    {{-- Módulos Contratados --}}
                    @if($ticket->company && $ticket->company->moduleTypes->isNotEmpty())
                    <div class="px-5 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Módulos Contratados</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($ticket->company->moduleTypes->sortBy('sort_order') as $module)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                             bg-violet-50 text-violet-700 border border-violet-200 leading-none">
                                    {{ $module->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($ticket->contact)
                    <div class="px-5 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Contato</p>
                        <p class="text-sm font-semibold text-gray-700">{{ $ticket->contact }}</p>
                    </div>
                    @endif

                    @if($ticket->referencedTicket)
                    <div class="px-5 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Chamado Referente</p>
                        <a href="{{ route('agent.ticket.show', $ticket->referencedTicket->id) }}" class="text-sm font-semibold text-blue-600 hover:underline">
                            #{{ $ticket->referencedTicket->id }}
                        </a>
                    </div>
                    @endif

                    <div class="px-5 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Criado em</p>
                        <p class="text-sm font-semibold text-gray-700">{{ $ticket->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $ticket->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="px-5 py-3.5">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Última atualização</p>
                        <p class="text-sm font-semibold text-gray-700">{{ $ticket->updated_at?->format('d/m/Y H:i') }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $ticket->updated_at?->diffForHumans() }}</p>
                    </div>
                </div>
            </div>

        </aside>
    </div>

    {{-- Modal de Edição de Mensagem WhatsApp --}}
    <div id="whatsapp-edit-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" onclick="closeWhatsAppEditModal()"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md transform rounded-2xl bg-white shadow-xl transition-all">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-800">Editar Mensagem</h3>
                        <button type="button" onclick="closeWhatsAppEditModal()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <label for="whatsapp-edit-text" class="block text-sm font-medium text-gray-700 mb-2">Novo conteúdo</label>
                        <textarea id="whatsapp-edit-text" rows="5" class="w-full rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-700 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none resize-none shadow-sm" placeholder="Digite o novo texto..."></textarea>
                        <p class="mt-2 text-xs text-gray-400">A mensagem será editada no WhatsApp do cliente.</p>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button type="button" onclick="closeWhatsAppEditModal()" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">Cancelar</button>
                        <button type="button" id="whatsapp-edit-save" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm shadow-indigo-500/20 transition-all">Salvar Alterações</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
