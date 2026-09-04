@extends('layouts.agent')

@section('title', 'Permissões de Implantação — Configurações')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Permissões de Implantação</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gerencie quem pode administrar implantações e implementações</p>
        </div>
        <a href="{{ route('agent.settings') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar às Configurações
        </a>
    </div>

    <div class="overflow-hidden bg-white rounded-2xl border border-gray-200 shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Usuário</th>
                    <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">E-mail</th>
                    <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Adm. Implantação</th>
                    <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">Ger. Implementação</th>
                    <th class="px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-gray-400 w-24">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                    <tr class="group hover:bg-orange-50/30 transition-colors duration-150">
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $user->name }}</td>
                        <td class="px-4 py-4 text-gray-500 text-xs">{{ $user->email }}</td>
                        <td class="px-4 py-4 text-center">
                            @if($user->deployment_admin)
                                <span class="inline-flex items-center px-2.5 py-0.5 bg-purple-50 text-purple-700 text-xs font-bold rounded-full">Sim</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($user->can_manage_implementation)
                                <span class="inline-flex items-center px-2.5 py-0.5 bg-orange-50 text-orange-700 text-xs font-bold rounded-full">Sim</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right">
                            <a href="{{ route('agent.settings.user-permissions.edit', $user->id) }}"
                               class="p-1.5 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors inline-flex"
                               title="Editar permissões">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center text-gray-400">Nenhum usuário ativo encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($users->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
