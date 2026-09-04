@extends('admin.layouts.master')

@section('title', 'Helpdesk — Gerenciar Status')

@section('content')
<div class="space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <a href="{{ route('admin.helpdesk.dashboard') }}" class="hover:text-indigo-600 transition-colors">Helpdesk</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">Status</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-900">Gerenciar Status</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $statuses->count() }} status cadastrado(s)</p>
        </div>
        <a href="{{ route('admin.helpdesk.status.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo Status
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        @if($statuses->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <p class="text-sm font-semibold text-gray-500">Nenhum status cadastrado.</p>
                <a href="{{ route('admin.helpdesk.status.create') }}" class="mt-3 text-sm font-bold text-indigo-600 hover:underline">Criar o primeiro</a>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest w-12">#</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Cor</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Nome</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Chamados</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($statuses as $status)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 text-xs font-mono text-gray-400">{{ $status->id }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-5 h-5 rounded-full border-2 border-white shadow-sm flex-shrink-0"
                                         style="background-color: {{ $status->color ?? '#6366f1' }}"></div>
                                    <code class="text-[10px] text-gray-400">{{ $status->color ?? '—' }}</code>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold"
                                      style="background-color: {{ $status->color ?? '#6366f1' }}20; color: {{ $status->color ?? '#6366f1' }}">
                                    {{ $status->name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-bold text-gray-700">{{ $status->tickets_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.helpdesk.status.edit', $status->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Editar
                                    </a>
                                    <form action="{{ route('admin.helpdesk.status.destroy', $status->id) }}" method="POST" id="form-status-{{ $status->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button"
                                                @click="window.confirmModal({ title: 'Excluir status?', message: 'O status &quot;{{ $status->name }}&quot; será removido permanentemente.', confirmLabel: 'Excluir' }).then(ok => { if (ok) $el.closest('form').submit(); })"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
