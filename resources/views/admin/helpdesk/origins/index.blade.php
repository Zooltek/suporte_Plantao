@extends('admin.layouts.master')

@section('title', 'Helpdesk — Origens')

@section('content')
<div class="space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <a href="{{ route('admin.helpdesk.dashboard') }}" class="hover:text-indigo-600 transition-colors">Helpdesk</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">Canais de Origem</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-900">Canais de Origem</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $origins->count() }} origem(ns) cadastrada(s)</p>
        </div>
        <a href="{{ route('admin.helpdesk.origins.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova Origem
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        @if($origins->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                <p class="text-sm font-semibold text-gray-500">Nenhuma origem cadastrada.</p>
                <a href="{{ route('admin.helpdesk.origins.create') }}" class="mt-3 text-sm font-bold text-indigo-600 hover:underline">Criar a primeira</a>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest w-12">#</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Nome</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Descrição</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Chamados</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($origins as $origin)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 text-xs font-mono text-gray-400">{{ $origin->id }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-800">{{ $origin->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $origin->description ?? '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($origin->status)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500 border border-gray-200">Inativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-bold text-gray-700">{{ $origin->tickets_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.helpdesk.origins.edit', $origin->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Editar
                                    </a>
                                    <form action="{{ route('admin.helpdesk.origins.destroy', $origin->id) }}" method="POST" id="form-origin-{{ $origin->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button"
                                                @click="window.confirmModal({ title: 'Excluir origem?', message: 'A origem &quot;{{ $origin->name }}&quot; será removida permanentemente.', confirmLabel: 'Excluir' }).then(ok => { if (ok) $el.closest('form').submit(); })"
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
