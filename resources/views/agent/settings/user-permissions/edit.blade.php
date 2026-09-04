@extends('layouts.agent')

@section('title', 'Editar Permissões — ' . $user->name)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Permissões de Implantação</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $user->name }} — {{ $user->email }}</p>
        </div>
        <a href="{{ route('agent.settings.user-permissions.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar
        </a>
    </div>

    <form method="POST" action="{{ route('agent.settings.user-permissions.update', $user->id) }}"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Permissões de Implantação</h3>

            {{-- Administrador de Implantação --}}
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200 cursor-pointer hover:bg-orange-50/30 transition-colors"
                 x-data="{ on: {{ $user->deployment_admin ? 'true' : 'false' }} }"
                 @click="on = !on">
                <div>
                    <p class="text-sm font-semibold text-gray-900">Administrador de Implantação</p>
                    <p class="text-xs text-gray-500 mt-0.5">Gerencia agendamentos e registros de implantação</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0" @click.stop="on = !on">
                    <span class="text-xs font-semibold"
                          :class="on ? 'text-orange-600' : 'text-gray-400'"
                          x-text="on ? 'Sim' : 'Não'"></span>
                    <div class="relative w-10 h-6 rounded-full transition-colors duration-200 pointer-events-none"
                         :class="on ? 'bg-orange-500' : 'bg-gray-300'">
                        <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                             :class="on ? 'translate-x-4' : 'translate-x-0'"></div>
                    </div>
                    <input type="hidden" name="deployment_admin" :value="on ? '1' : '0'">
                </div>
            </div>

            {{-- Gerente de Implementação --}}
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200 cursor-pointer hover:bg-orange-50/30 transition-colors"
                 x-data="{ on: {{ $user->can_manage_implementation ? 'true' : 'false' }} }"
                 @click="on = !on">
                <div>
                    <p class="text-sm font-semibold text-gray-900">Gerente de Implementação</p>
                    <p class="text-xs text-gray-500 mt-0.5">Acesso ao gerenciamento de projetos de implementação</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0" @click.stop="on = !on">
                    <span class="text-xs font-semibold"
                          :class="on ? 'text-orange-600' : 'text-gray-400'"
                          x-text="on ? 'Sim' : 'Não'"></span>
                    <div class="relative w-10 h-6 rounded-full transition-colors duration-200 pointer-events-none"
                         :class="on ? 'bg-orange-500' : 'bg-gray-300'">
                        <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                             :class="on ? 'translate-x-4' : 'translate-x-0'"></div>
                    </div>
                    <input type="hidden" name="can_manage_implementation" :value="on ? '1' : '0'">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <a href="{{ route('agent.settings.user-permissions.index') }}"
               class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                Salvar Permissões
            </button>
        </div>
    </form>

</div>
@endsection
