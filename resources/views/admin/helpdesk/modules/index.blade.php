@extends('admin.layouts.master')

@section('title', 'Helpdesk — Módulos Contratados')

@section('content')
<div class="space-y-5">
    @php
        $modulesMissingRat = $modules->filter(fn ($module) => is_null($module->rat_module_id));
        $modulesMissingRatWithCustomers = $modulesMissingRat->filter(fn ($module) => $module->companies_count > 0);
        $impactedCustomers = (int) $modulesMissingRatWithCustomers->sum('companies_count');
    @endphp

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <a href="{{ route('admin.helpdesk.dashboard') }}" class="hover:text-indigo-600 transition-colors">Helpdesk</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">Módulos Contratados</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-900">Módulos Contratados</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $modules->count() }} módulo(s) cadastrado(s)</p>
        </div>
        <a href="{{ route('admin.helpdesk.modules.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo Módulo
        </a>
    </div>

    @if($modulesMissingRatWithCustomers->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <p class="font-bold">Atenção operacional</p>
            <p class="mt-1">
                {{ $modulesMissingRatWithCustomers->count() }} módulo(s) contratado(s) estão sem checklist RAT padrão e já impactam {{ $impactedCustomers }} vínculo(s) de cliente.
            </p>
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        @if($modules->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <p class="text-sm font-semibold text-gray-500">Nenhum módulo cadastrado.</p>
                <a href="{{ route('admin.helpdesk.modules.create') }}" class="mt-3 text-sm font-bold text-indigo-600 hover:underline">Criar o primeiro</a>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest w-12">#</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Nome</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Slug</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Checklist RAT</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Clientes</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Ordem</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($modules as $module)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 text-xs font-mono text-gray-400">{{ $module->id }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-800">{{ $module->name }}</td>
                            <td class="px-6 py-4"><code class="text-[11px] text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ $module->slug }}</code></td>
                            <td class="px-6 py-4">
                                @if($module->ratModule)
                                    <div class="space-y-1">
                                        <p class="text-sm font-bold text-gray-800">{{ $module->ratModule->name }}</p>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if($module->ratModule->project)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    {{ $module->ratModule->project }}
                                                </span>
                                            @endif
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ ($module->ratModule->element_types_count ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                {{ $module->ratModule->element_types_count ?? 0 }} item(ns)
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Sem checklist padrão
                                        </span>
                                        <p class="text-[11px] text-gray-500">O ticket exigirá seleção manual do template técnico.</p>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="space-y-1">
                                    <p class="text-sm font-bold text-gray-700">{{ $module->companies_count }}</p>
                                    @if(is_null($module->rat_module_id) && $module->companies_count > 0)
                                        <p class="text-[11px] font-semibold text-rose-600">Com impacto</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-semibold text-gray-600">{{ $module->sort_order }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($module->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500 border border-gray-200">Inativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.helpdesk.modules.edit', $module->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Editar
                                    </a>
                                    <form action="{{ route('admin.helpdesk.modules.destroy', $module->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="button"
                                                @click="window.confirmModal({ title: 'Excluir módulo?', message: 'O módulo &quot;{{ $module->name }}&quot; será removido e desvinculado de todos os clientes.', confirmLabel: 'Excluir' }).then(ok => { if (ok) $el.closest('form').submit(); })"
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
