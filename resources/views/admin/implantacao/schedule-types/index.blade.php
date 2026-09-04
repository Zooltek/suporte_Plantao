@extends('admin.layouts.master')

@section('title', 'Tipos de Agendamento')

@section('content')
<div class="space-y-5">

    <nav class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
        <span class="text-gray-600 font-semibold">Implantação</span>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-semibold">Tipos de Agendamento</span>
    </nav>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-900">Tipos de Agendamento</h1>
            <p class="text-sm text-gray-500 mt-1">Defina quais tipos aparecem no formulário de agendamento e se cada um exige cliente vinculado.</p>
        </div>
        <a href="{{ route('admin.implantacao.schedule-types.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo Tipo
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 font-semibold">{{ session('status') }}</div>
    @endif
    @if (session('warning'))
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800 font-semibold">{{ session('warning') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        @if($types->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <p class="text-sm font-semibold text-gray-500">Nenhum tipo cadastrado.</p>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Ordem</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Rótulo</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Slug</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 text-center">Exige Cliente</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 text-center">Ativo</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 text-center">Sistema</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-500 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($types as $type)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 text-sm text-gray-500">{{ $type->sort_order }}</td>
                            <td class="px-5 py-3 text-sm font-semibold text-gray-800">{{ $type->label }}</td>
                            <td class="px-5 py-3 text-xs font-mono text-gray-500">{{ $type->slug }}</td>
                            <td class="px-5 py-3 text-center">
                                @if($type->requires_customer)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">Sim</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 border border-gray-200">Não</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($type->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 border border-gray-200">Inativo</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($type->is_system)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Sistema</span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.implantacao.schedule-types.edit', $type->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-all">Editar</a>
                                    @unless($type->is_system)
                                        <form action="{{ route('admin.implantacao.schedule-types.destroy', $type->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="button"
                                                    @click="window.confirmModal({ title: 'Excluir tipo?', message: 'O tipo &quot;{{ $type->label }}&quot; será removido permanentemente.', confirmLabel: 'Excluir' }).then(ok => { if (ok) $el.closest('form').submit(); })"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 transition-all">Excluir</button>
                                        </form>
                                    @endunless
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
