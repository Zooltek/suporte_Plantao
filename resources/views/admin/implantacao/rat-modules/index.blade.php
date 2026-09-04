@extends('admin.layouts.master')

@section('title', 'RAT — Módulos / Projetos')

@section('content')
<div class="space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <span class="text-gray-600 font-semibold">Implantação</span>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">Módulos do RAT</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-900">Módulos / Projetos do RAT</h1>
            <p class="text-sm text-gray-500 mt-1">Gerencie os módulos de implantação usados nos relatórios de atendimento técnico.</p>
        </div>
        <a href="{{ route('admin.implantacao.rat-modules.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo Módulo
        </a>
    </div>

    {{-- Tabela --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        @if($modules->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <p class="text-sm font-semibold text-gray-500">Nenhum módulo cadastrado.</p>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Módulo</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Projeto</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Descrição</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 text-center">Itens</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($modules as $module)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 text-sm font-semibold text-gray-800">{{ $module->name }}</td>
                            <td class="px-5 py-3">
                                @if($module->project)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600 border border-indigo-200">{{ $module->project }}</span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500 max-w-xs truncate">{{ $module->description ?: '—' }}</td>
                            <td class="px-5 py-3 text-sm text-gray-500 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $module->element_types_count > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-gray-100 text-gray-400 border border-gray-200' }}">
                                    {{ $module->element_types_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.implantacao.rat-modules.edit', $module->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Editar
                                    </a>
                                    <form action="{{ route('admin.implantacao.rat-modules.destroy', $module->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="button"
                                                @click="window.confirmModal({ title: 'Excluir módulo?', message: 'O módulo &quot;{{ $module->name }}&quot; será removido permanentemente.', confirmLabel: 'Excluir' }).then(ok => { if (ok) $el.closest('form').submit(); })"
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
