@extends('tasks.layouts.master')

@section('title', 'Minhas Tarefas')

@push('styles')
<style>
    .task-card { transition: transform .15s ease, box-shadow .15s ease; }
    .task-card:active { transform: scale(.985); }

    @media print {
        .no-print { display: none !important; }
        body > * > header, body > * > footer, body > * aside { display: none !important; }
    }
</style>
@endpush

@section('content')

@php
    $userId = auth('admin')->id() ?? auth()->id();
    $createErrors = $errors->getBag('default');
    $taskUpdateErrors = $errors->taskUpdate;
    $editingTaskId = old('editing_task_id', request()->query('task'));
    $oldTaskForm = [
        'project_id' => old('project_id', ''),
        'module_label_id' => old('module_label_id', ''),
        'submodule_label_id' => old('submodule_label_id', ''),
        'labels' => collect(old('labels', []))->filter()->values()->all(),
    ];

    $statusMeta = [
        'new' => ['label' => 'Nova',         'bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'dot' => 'bg-indigo-500'],
        'pen' => ['label' => 'Pendente',      'bg' => 'bg-amber-100',  'text' => 'text-amber-700',  'dot' => 'bg-amber-500'],
        'pro' => ['label' => 'Em andamento',  'bg' => 'bg-blue-100',   'text' => 'text-blue-700',   'dot' => 'bg-blue-500'],
        'sto' => ['label' => 'Parada',        'bg' => 'bg-rose-100',   'text' => 'text-rose-700',   'dot' => 'bg-rose-500'],
        'tdo' => ['label' => 'Conc. (TI)',    'bg' => 'bg-emerald-100','text' => 'text-emerald-700','dot' => 'bg-emerald-400'],
        'don' => ['label' => 'Concluída',     'bg' => 'bg-emerald-100','text' => 'text-emerald-700','dot' => 'bg-emerald-500'],
        'can' => ['label' => 'Cancelada',     'bg' => 'bg-gray-100',   'text' => 'text-gray-500',   'dot' => 'bg-gray-400'],
        'rej' => ['label' => 'Rejeitada',     'bg' => 'bg-slate-100',  'text' => 'text-slate-500',  'dot' => 'bg-slate-400'],
    ];

    $statusOptions = [
        'pen' => 'Pendente',
        'pro' => 'Em andamento',
        'sto' => 'Parada',
        'don' => 'Concluída',
        'tdo' => 'Concluída (TI)',
        'can' => 'Cancelada',
    ];

    $openStatuses = ['new', 'pen', 'pro', 'sto'];
    $openCount    = $tasks->filter(fn($t) => in_array($t->status, $openStatuses))->count();
    $doneCount    = $tasks->filter(fn($t) => !in_array($t->status, $openStatuses))->count();
    $catalogHasSubmodules = collect($projectModuleTree)
        ->flatten(1)
        ->contains(fn ($module) => ! empty($module['childs'] ?? []));
@endphp

<div
    x-data="Object.assign(mobileTasks({
        projects: @js($projects->map(fn ($project) => ['id' => (string) $project->id, 'name' => $project->name])->values()->all()),
        projectModuleTree: @js($projectModuleTree),
        oldForm: @js($oldTaskForm),
        initialExpandedTaskId: @js($editingTaskId),
        initialEditingTaskId: @js($editingTaskId),
    }), {
        drawerOpen: {{ $createErrors->any() ? 'true' : 'false' }},
        fileName: '',
    })"
    x-init="initFromServer(@js($tasks), {
        status: @js($statusFilter),
        search: @js($search),
        taskId: @js($taskIdFilter),
        classification: @js($classificationFilter),
        customerId: @js($selectedCustomerFilter),
        projectId: @js($selectedProjectFilter),
    })"
    @keydown.escape.window="drawerOpen = false"
    class="mx-auto max-w-[min(96vw,88rem)] space-y-6">

    {{-- ── Erros de validação ──────────────────── --}}
    @if($createErrors->any())
    <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3 no-print">
        <p class="font-semibold mb-1">Corrija os erros abaixo:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($createErrors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ── Header ─────────────────────────────── --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-indigo-500">Painel Operacional</p>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight sm:text-3xl">Minhas Tarefas</h1>
            <p class="mt-1 text-sm text-gray-500">
                <span class="font-semibold text-amber-600">{{ $openCount }}</span> abertas ·
                <span class="font-semibold text-emerald-600">{{ $doneCount }}</span> concluídas
            </p>
            <p class="mt-1 text-xs text-gray-400">
                A inbox reúne contexto técnico e edição inline sem sacrificar a leitura em desktop.
            </p>
        </div>

        {{-- Botão Nova Tarefa + Notificações --}}
        <div class="flex items-center gap-2 no-print xl:pt-2">

        <button @click="drawerOpen = true"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold shadow-sm hover:bg-indigo-700 active:scale-95 transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova Tarefa
        </button>

        {{-- Notification Bell --}}
        @if($userId)
        <div x-data="taskNotifications({{ $userId }})" class="relative no-print">
            <button @click="open = !open"
                    class="relative flex items-center justify-center w-10 h-10 rounded-full bg-white border border-gray-200 shadow-sm hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span x-show="unread > 0" x-cloak
                      class="absolute -top-1 -right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold"
                      x-text="unread > 9 ? '9+' : unread"></span>
            </button>

            {{-- Dropdown notificações --}}
            <div x-show="open" x-cloak @click.outside="open = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute right-0 top-12 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p class="text-sm font-bold text-gray-800">Notificações</p>
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider"
                          x-text="`${unread} não lida(s)`"></span>
                </div>
                <div class="max-h-72 overflow-y-auto divide-y divide-gray-50">
                    <template x-if="notifications.length === 0">
                        <div class="py-8 text-center text-sm text-gray-400">Nenhuma notificação</div>
                    </template>
                    <template x-for="n in notifications" :key="n.id ?? n.task_id + n.content">
                        <div @click="markSeen(n)"
                             class="px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                             :class="!n.seen ? 'bg-blue-50/40' : ''">
                            <p class="text-[13px] font-medium text-gray-800 leading-snug" x-text="n.content"></p>
                            <p class="text-[11px] text-gray-400 mt-0.5" x-text="n.timestamp ?? ''"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        @endif

        </div>{{-- fim flex botão + sino --}}
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-2xl border border-amber-100 bg-amber-50/80 px-4 py-3 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Abertas</p>
            <p class="mt-2 text-2xl font-black text-amber-600">{{ $openCount }}</p>
            <p class="mt-1 text-xs text-amber-700/80">Trabalho pendente que ainda exige acompanhamento.</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 px-4 py-3 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Concluídas</p>
            <p class="mt-2 text-2xl font-black text-emerald-600">{{ $doneCount }}</p>
            <p class="mt-1 text-xs text-emerald-700/80">Itens já encerrados ou finalizados tecnicamente.</p>
        </div>
        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/80 px-4 py-3 shadow-sm sm:col-span-2 xl:col-span-1">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-700">Visão geral</p>
            <p class="mt-2 text-2xl font-black text-indigo-600">{{ $tasks->count() }}</p>
            <p class="mt-1 text-xs text-indigo-700/80">Use os filtros e a expansão dos cards para navegar pelo histórico.</p>
        </div>
    </div>

    {{-- ── Busca + Filtro de Status ─────────────── --}}
    <div class="rounded-2xl border border-gray-200 bg-white/90 p-4 shadow-sm no-print space-y-3">
        <div class="grid gap-3 xl:grid-cols-[minmax(0,2.2fr)_minmax(0,0.8fr)_minmax(0,0.9fr)_minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,1.2fr)_auto]">
        <div class="relative xl:col-span-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input x-model.debounce.250ms="search" type="search" placeholder="Buscar por ID, título, descrição ou cliente..."
                   class="w-full pl-9 pr-4 py-2.5 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition-all">
        </div>

        <input x-model.debounce.250ms="taskIdFilter"
               type="search"
               inputmode="numeric"
               placeholder="ID da tarefa"
               class="w-full px-3 py-2.5 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition-all">

        <select x-model="statusFilter"
                class="w-full py-2.5 pl-3 pr-8 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition-all">
            <option value="open">Abertas</option>
            <option value="done">Finalizadas</option>
            <option value="all">Todas</option>
        </select>

        <select x-model="classificationFilter"
                class="w-full py-2.5 pl-3 pr-8 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition-all">
            <option value="">Classificação</option>
            <option value="bug">Erro</option>
            <option value="improvement">Melhoria</option>
            <option value="fix">Correção</option>
        </select>

        <select x-model="customerFilter"
                class="w-full py-2.5 pl-3 pr-8 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition-all">
            <option value="">Todos os clientes</option>
            @foreach($companies as $company)
            <option value="{{ $company->id }}">{{ $company->trade_name }}</option>
            @endforeach
        </select>

        <select x-model="projectFilter"
                class="w-full py-2.5 pl-3 pr-8 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition-all">
            <option value="">Todos os projetos</option>
            @foreach($projects as $project)
            <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        <button type="button"
                x-cloak
                x-show="hasActiveFilters()"
                @click="clearFilters()"
                class="px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-gray-900 transition-all">
            Limpar
        </button>
        </div>

        <p class="text-xs text-gray-400">
            A busca textual agora reconhece o número da tarefa e o filtro por ID faz o recorte exato do card desejado.
        </p>
    </div>

    <p class="text-xs text-gray-400 no-print" x-cloak x-show="hasActiveFilters()">
        Os filtros desta inbox ficam refletidos na URL para facilitar atualização e compartilhamento do contexto.
    </p>

    {{-- ── Lista de Cards ────────────────────────── --}}
    <div class="space-y-3">

        {{-- Vazio --}}
        <template x-if="!loading && filtered.length === 0">
            <div class="py-16 text-center">
                <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-600">Nenhuma tarefa encontrada</p>
                <p class="text-xs text-gray-400 mt-1">Ajuste o filtro ou o termo de busca</p>
            </div>
        </template>

        {{-- Cards --}}
        <template x-for="task in filtered" :key="task.id">
            <div class="task-card bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-4">
                {{-- Linha 1: badge de status + classificação + prazo --}}
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-3 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                  :class="statusBadge(task.status).color">
                                <span class="w-1.5 h-1.5 rounded-full"
                                      :class="statusBadge(task.status).dot"></span>
                                <span x-text="statusBadge(task.status).label"></span>
                            </span>
                            <template x-if="task.classification">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                      :class="{
                                          'bg-rose-100 text-rose-700':   task.classification === 'bug',
                                          'bg-violet-100 text-violet-700': task.classification === 'improvement',
                                          'bg-teal-100 text-teal-700':   task.classification === 'fix',
                                      }"
                                      x-text="{bug:'Erro', improvement:'Melhoria', fix:'Correção'}[task.classification] ?? task.classification">
                                </span>
                            </template>
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-900 leading-snug" x-text="task.title"></p>
                            <template x-if="task.content">
                                <p class="mt-1 text-[13px] leading-5 text-gray-500" x-text="excerpt(task.content)"></p>
                            </template>
                        </div>
                    </div>

                    <div class="flex flex-col items-stretch gap-2 sm:items-end sm:min-w-[15rem]">
                        <div class="flex items-center justify-end gap-2 flex-wrap">
                            <button type="button"
                                    @click="toggleDetails(task.id)"
                                    :aria-expanded="isExpanded(task.id)"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition-all hover:border-indigo-200 hover:text-indigo-700 hover:shadow-md">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"/>
                                </svg>
                                <span x-text="isExpanded(task.id) ? 'Recolher' : 'Visualizar'"></span>
                            </button>

                            <button type="button"
                                    @click="openEdit(task.id)"
                                    :disabled="!task.can_modify"
                                    :aria-disabled="!task.can_modify"
                                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold shadow-sm transition-all"
                                    :class="task.can_modify
                                        ? 'border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 hover:shadow-md'
                                        : 'border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span>Editar</span>
                            </button>
                        </div>

                        <span class="text-[11px] font-semibold text-right"
                              :class="isOverdue(task) ? 'text-rose-500' : 'text-gray-400'">
                            <template x-if="task.delivery_at">
                                <span>
                                    <template x-if="isOverdue(task)">⚠ </template>
                                    Prazo: <span x-text="formatDate(task.delivery_at)"></span>
                                </span>
                            </template>
                        </span>
                    </div>
                </div>

                {{-- Linha 3: meta info --}}
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-500">
                    <template x-if="task.customer">
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                            <span x-text="task.customer.trade_name ?? task.customer.name"></span>
                        </span>
                    </template>
                    <template x-if="task.project">
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            <span x-text="task.project.name"></span>
                        </span>
                    </template>
                    <template x-if="task.user">
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span x-text="task.user.name"></span>
                        </span>
                    </template>
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        <span x-text="`#${task.id}`"></span>
                    </span>
                </div>

                {{-- Linha 4: detalhes expansíveis --}}
                <div x-cloak
                     x-show="isExpanded(task.id)"
                     x-transition:enter="transition ease-out duration-180"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="rounded-2xl border border-gray-200 bg-slate-50/90 p-4 space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-white/70 bg-white px-3 py-3 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Descrição</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700" x-text="task.content || 'Sem descrição detalhada.'"></p>
                        </div>

                        <div class="grid gap-3">
                            <div class="rounded-xl border border-white/70 bg-white px-3 py-3 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Resumo rápido</p>
                                <dl class="mt-2 space-y-1.5 text-sm text-gray-600">
                                    <div class="flex items-start justify-between gap-3">
                                        <dt>Responsável</dt>
                                        <dd class="font-semibold text-gray-800" x-text="task.user?.name || 'Não definido'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt>Autor</dt>
                                        <dd class="font-semibold text-gray-800" x-text="task.author?.name || 'Não definido'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt>Criada em</dt>
                                        <dd class="font-semibold text-gray-800" x-text="formatDateTime(task.created_at) || '—'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt>Anexos</dt>
                                        <dd class="font-semibold text-gray-800" x-text="`${task.attachments?.length ?? 0} arquivo(s)`"></dd>
                                    </div>
                                </dl>
                            </div>

	                            <div class="rounded-xl border border-white/70 bg-white px-3 py-3 shadow-sm">
	                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Contexto</p>
	                                <div class="mt-2 flex flex-wrap gap-2">
                                    <template x-if="task.project">
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700" x-text="task.project.name"></span>
                                    </template>
                                    <template x-if="task.customer">
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700" x-text="task.customer.trade_name ?? task.customer.name"></span>
                                    </template>
                                    <template x-for="label in (task.labels ?? [])" :key="label.id">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600" x-text="label.name"></span>
                                    </template>
                                    <template x-if="(task.labels ?? []).length === 0 && !task.project && !task.customer">
                                        <span class="text-sm text-gray-400">Sem contexto adicional cadastrado.</span>
	                                    </template>
	                                </div>
	                            </div>
	                        </div>
	                    </div>

                    <div x-show="!isEditing(task.id)" class="rounded-xl border border-dashed border-gray-200 bg-white/90 px-3 py-2 text-[12px] text-gray-500">
                        Use <span class="font-semibold text-gray-700">Editar</span> para atualizar responsável, descrição e status sem sair da inbox.
                    </div>
                </div>

                {{-- Linha 5: edição inline --}}
                <div x-cloak
                     x-show="isEditing(task.id)"
                     x-transition:enter="transition ease-out duration-180"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 space-y-4">
                    @if($taskUpdateErrors->any())
                        <template x-if="String(task.id) === '{{ (string) $editingTaskId }}'">
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                <p class="font-semibold mb-1">Revise os dados da tarefa:</p>
                                <ul class="list-disc list-inside space-y-0.5">
                                    @foreach($taskUpdateErrors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </template>
                    @endif

                    <form :action="`{{ url('/tasks') }}/${task.id}`" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="editing_task_id" :value="task.id">

                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Editar tarefa</h3>
                                <p class="text-xs text-gray-500">O status agora faz parte da edição, junto dos demais dados operacionais.</p>
                            </div>

                            <button type="button"
                                    @click="closeEdit()"
                                    class="inline-flex items-center gap-1 rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 shadow-sm transition-all hover:bg-indigo-50">
                                Fechar edição
                            </button>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label :for="`task-title-${task.id}`" class="mb-1 block text-xs font-semibold text-gray-600">Título</label>
                                <input :id="`task-title-${task.id}`" type="text" name="title" :value="String(task.id) === '{{ (string) $editingTaskId }}' ? @js(old('title')) : (task.title ?? '')"
                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                            </div>

                            <div class="sm:col-span-2">
                                <label :for="`task-content-${task.id}`" class="mb-1 block text-xs font-semibold text-gray-600">Descrição</label>
                                <textarea :id="`task-content-${task.id}`" name="content" rows="4"
                                          class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30 resize-none"
                                          x-text="String(task.id) === '{{ (string) $editingTaskId }}' ? @js(old('content')) : (task.content ?? '')"></textarea>
                            </div>

                            <div>
                                <label :for="`task-user-${task.id}`" class="mb-1 block text-xs font-semibold text-gray-600">Responsável</label>
                                <select :id="`task-user-${task.id}`" name="user_id"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" :selected="String({{ $user->id }}) === (String(task.id) === '{{ (string) $editingTaskId }}' ? '{{ old('user_id') }}' : String(task.user_id))">
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label :for="`task-status-${task.id}`" class="mb-1 block text-xs font-semibold text-gray-600">Status</label>
                                <select :id="`task-status-${task.id}`" name="status"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" :selected="'{{ $value }}' === (String(task.id) === '{{ (string) $editingTaskId }}' ? '{{ old('status') }}' : String(task.status))">
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label :for="`task-classification-${task.id}`" class="mb-1 block text-xs font-semibold text-gray-600">Classificação</label>
                                <select :id="`task-classification-${task.id}`" name="classification"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                    <option value="">— Nenhuma —</option>
                                    <option value="bug" :selected="'bug' === (String(task.id) === '{{ (string) $editingTaskId }}' ? '{{ old('classification') }}' : String(task.classification ?? ''))">Erro</option>
                                    <option value="improvement" :selected="'improvement' === (String(task.id) === '{{ (string) $editingTaskId }}' ? '{{ old('classification') }}' : String(task.classification ?? ''))">Melhoria</option>
                                    <option value="fix" :selected="'fix' === (String(task.id) === '{{ (string) $editingTaskId }}' ? '{{ old('classification') }}' : String(task.classification ?? ''))">Correção</option>
                                </select>
                            </div>

                            <div>
                                <label :for="`task-delivery-${task.id}`" class="mb-1 block text-xs font-semibold text-gray-600">Prazo</label>
                                @php
                                    $oldDelivery = old('delivery_at');
                                    $oldDeliveryIso = '';
                                    if (is_string($oldDelivery) && $oldDelivery !== '') {
                                        try {
                                            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $oldDelivery)) {
                                                $oldDeliveryIso = \Carbon\Carbon::createFromFormat('d/m/Y', $oldDelivery)->format('Y-m-d');
                                            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', $oldDelivery)) {
                                                $oldDeliveryIso = substr($oldDelivery, 0, 10);
                                            }
                                        } catch (\Throwable) {
                                            $oldDeliveryIso = '';
                                        }
                                    }
                                @endphp
                                <input :id="`task-delivery-${task.id}`" type="date" name="delivery_at"
                                       :value="String(task.id) === '{{ (string) $editingTaskId }}'
                                            ? @js($oldDeliveryIso)
                                            : (task.delivery_at ? new Date(task.delivery_at).toISOString().slice(0, 10) : '')"
                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                            </div>

                            <div class="sm:col-span-2">
                                <label :for="`task-customer-${task.id}`" class="mb-1 block text-xs font-semibold text-gray-600">Cliente</label>
                                <select :id="`task-customer-${task.id}`" name="customer_id"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                    <option value="">— Nenhum —</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" :selected="String({{ $company->id }}) === (String(task.id) === '{{ (string) $editingTaskId }}' ? '{{ old('customer_id') }}' : String(task.customer_id ?? ''))">
                                            {{ $company->trade_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <button type="button"
                                    @click="closeEdit()"
                                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 shadow-sm transition-all hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                                Salvar alterações
                            </button>
                        </div>
                    </form>
                </div>

                <template x-if="!task.can_modify && !isExpanded(task.id)">
                    <div class="rounded-xl border border-dashed border-gray-200 px-3 py-2 text-[11px] text-gray-500">
                        Você pode visualizar os detalhes desta tarefa, mas a edição está bloqueada para o seu perfil no momento.
                    </div>
                </template>
            </div>
        </template>

    </div>

    {{-- Contador --}}
    <p class="text-center text-xs text-gray-400 py-2 no-print"
       x-show="filtered.length > 0"
       x-text="`${filtered.length} tarefa(s) exibida(s)`"></p>

    {{-- ── Drawer: Nova Tarefa ────────────────── --}}

    {{-- Overlay --}}
    <div x-show="drawerOpen" x-cloak
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="drawerOpen = false"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 no-print">
    </div>

    {{-- Painel --}}
    <div x-show="drawerOpen" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-full"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-full"
         class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[min(96vw,74rem)] flex-col bg-white shadow-2xl no-print">

        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-indigo-500">Nova Tarefa</p>
                    <h2 class="mt-1 text-lg font-bold text-gray-900">Registrar tarefa com contexto técnico completo</h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Defina o escopo técnico da tarefa com projeto, módulo e submódulo. O cliente só precisa ser informado quando a demanda for específica.
                    </p>
                </div>
                <button @click="drawerOpen = false"
                        class="flex items-center justify-center w-9 h-9 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <form action="{{ route('tasks.store') }}" method="POST"
              enctype="multipart/form-data"
              class="flex flex-1 flex-col overflow-hidden">
            @csrf

            <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(21rem,0.85fr)]">
                    <div class="space-y-5">
                        <div class="grid gap-5 lg:grid-cols-2">
                            <div class="lg:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Título <span class="text-rose-500">*</span></label>
                                <input type="text" name="title" value="{{ old('title') }}" required
                                       placeholder="Descreva brevemente a tarefa"
                                       class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                            </div>

                            <div class="lg:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Descrição <span class="text-rose-500">*</span></label>
                                <textarea name="content" rows="6" required
                                          placeholder="Detalhe o que precisa ser feito, o impacto e o resultado esperado..."
                                          class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30 resize-y">{{ old('content') }}</textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Responsável <span class="text-rose-500">*</span></label>
                                <select name="user_id" required
                                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', auth('admin')->id()) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Classificação</label>
                                <select name="classification"
                                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                    <option value="">— Nenhuma —</option>
                                    <option value="bug"         {{ old('classification') === 'bug'         ? 'selected' : '' }}>Erro</option>
                                    <option value="improvement" {{ old('classification') === 'improvement' ? 'selected' : '' }}>Melhoria</option>
                                    <option value="fix"         {{ old('classification') === 'fix'         ? 'selected' : '' }}>Correção</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Prazo</label>
                                <input type="date" name="delivery_at" value="{{ old('delivery_at') }}"
                                       class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-slate-50/70 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Anexo de apoio</h3>
                                    <p class="mt-1 text-xs text-gray-500">Inclua evidências, prints, documentos ou planilhas relacionadas à tarefa.</p>
                                </div>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-500 shadow-sm">até 50 MB</span>
                            </div>

                            <div class="mt-4">
                                <label class="relative flex flex-col items-center gap-2 w-full cursor-pointer rounded-2xl border-2 border-dashed border-gray-300 bg-white px-4 py-6 text-center transition-all hover:border-indigo-400 hover:bg-indigo-50/40 group">
                                    <svg class="w-8 h-8 text-gray-300 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-500 group-hover:text-indigo-600 transition-colors">
                                        Clique para selecionar ou arraste aqui
                                    </span>
                                    <span class="text-[11px] text-gray-400">JPG, PNG, PDF, ZIP e outros formatos aceitos</span>
                                    <input type="file" name="file" class="sr-only"
                                           @change="fileName = $event.target.files[0]?.name ?? ''">
                                </label>
                                <p x-show="fileName" x-cloak x-text="'Arquivo: ' + fileName"
                                   class="mt-2 text-xs text-indigo-600 font-medium truncate"></p>
                                @error('file')
                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                            <h3 class="text-sm font-bold text-gray-900">Contexto técnico</h3>
                            <p class="mt-1 text-xs text-gray-500">Projeto e módulo classificam a área afetada. O cliente é opcional e não altera esse catálogo técnico.</p>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Projeto</label>
                                    <select name="project_id"
                                            x-model="selectedProjectId"
                                            @change="handleProjectChange()"
                                            :disabled="availableProjects().length === 0"
                                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                        <option value="">— Nenhum —</option>
                                        <template x-for="project in availableProjects()" :key="project.id">
                                            <option :value="project.id" x-text="project.name"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-[11px] text-gray-400">
                                        Escolha o projeto impactado pela tarefa, mesmo quando ela abranger todos os clientes.
                                    </p>
                                    <p x-show="availableProjects().length === 0" x-cloak class="mt-1 text-[11px] text-amber-600">
                                        Nenhum projeto técnico está disponível no catálogo atual.
                                    </p>
                                    @error('project_id')
                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div x-show="selectedProjectId !== ''" x-cloak class="space-y-4">
                                    <template x-if="hasTaskModules()">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold text-gray-600">Módulo</label>
                                                <select name="module_label_id" x-model="selectedModuleId" @change="handleModuleChange()"
                                                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                                    <option value="">— Nenhum —</option>
                                                    <template x-for="module in availableModules()" :key="module.id">
                                                        <option :value="module.id" x-text="module.name"></option>
                                                    </template>
                                                </select>
                                                <p class="mt-1 text-[11px] text-gray-400">Use o módulo técnico mais aderente à área afetada.</p>
                                                @error('module_label_id')
                                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            @if($catalogHasSubmodules)
                                            <div>
                                                <template x-if="selectedModuleHasChildren()">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Submódulo</label>
                                                        <select name="submodule_label_id" x-model="selectedSubmoduleId"
                                                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                                            <option value="">— Nenhum —</option>
                                                            <template x-for="submodule in availableSubmodules()" :key="submodule.id">
                                                                <option :value="submodule.id" x-text="submodule.name"></option>
                                                            </template>
                                                        </select>
                                                        @error('submodule_label_id')
                                                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                </template>
                                            </div>
                                            @endif
                                        </div>
                                    </template>

                                    <template x-if="!hasTaskModules()">
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                                            Nenhum módulo técnico está disponível para o projeto selecionado.
                                        </div>
                                    </template>
                                </div>

                                <div class="rounded-xl border border-dashed border-gray-200 bg-slate-50/70 p-4">
                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Cliente opcional</label>
                                    <select name="customer_id"
                                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm shadow-sm outline-none transition-all focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30">
                                        <option value="">— Todos / não especificado —</option>
                                        @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('customer_id') == $company->id ? 'selected' : '' }}>
                                            {{ $company->trade_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-[11px] text-gray-400">
                                        Preencha apenas quando a tarefa for de um cliente específico. Projeto e módulo continuam disponíveis sem esse vínculo.
                                    </p>
                                    @error('customer_id')
                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 bg-white/95 px-5 py-4 backdrop-blur sm:px-6">
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-[12px] text-gray-500">
                        <span class="font-semibold text-gray-700">Dica:</span> use projeto, módulo e submódulo para deixar claro o impacto técnico da tarefa.
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="drawerOpen = false"
                                class="py-2.5 px-4 bg-gray-100 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="py-2.5 px-4 bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-sm hover:bg-indigo-700 active:scale-95 transition-all">
                            Salvar Tarefa
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection
