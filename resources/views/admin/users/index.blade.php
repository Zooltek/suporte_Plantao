@extends('admin.layouts.master')

@section('content')
<div x-data="userManager({
        defaultDepartmentId: '{{ $departments->where('is_crm', false)->first()?->id ?? $departments->first()?->id ?? '' }}',
        crmDepartmentId: '{{ $crmDepartmentId ?? '' }}'
    })" class="container mx-auto px-4 py-8">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Usuários</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gerencie os usuários e níveis de acesso do sistema</p>
        </div>
        <button @click="openCreateModal()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Usuário
        </button>
    </div>

    <div class="overflow-x-auto bg-white rounded-2xl border border-gray-200 shadow-sm">
        <table class="min-w-full divide-y divide-gray-100 text-sm whitespace-nowrap">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 w-12">ID</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Nome</th>
                    <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">E-mail</th>
                    <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Acesso</th>
                    <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Departamento</th>
                    <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400 w-20">Ativo</th>
                    <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400 w-24">Plantão</th>
                    <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400 w-24">Implantação</th>
                    <th class="px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-gray-400 w-28">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                @php
                    $departmentId = (int) $user->department_id;
                    $roleValue = $user->ticketit_admin
                        ? 2
                        : ($user->ticketit_agent ? 1 : ($departmentId === 3 ? 3 : 1));
                    $roleLabel = match ($roleValue) {
                        2 => 'Administrador',
                        1 => 'Agente',
                        3 => 'CRM / Comercial',
                        default => 'Agente',
                    };
                    $roleClass = match ($roleValue) {
                        2 => 'bg-blue-50 text-blue-700',
                        1 => 'bg-indigo-50 text-indigo-700',
                        3 => 'bg-teal-50 text-teal-700',
                        default => 'bg-slate-100 text-slate-500',
                    };
                    $deptName   = $departments->firstWhere('id', $user->department_id)?->name ?? '—';
                @endphp
                <tr x-data="userRow({{ $user->id }}, @js([
                        'name'                      => $user->name,
                        'email'                     => $user->email,
                        'role'                      => $roleValue,
                        'department_id'             => $user->department_id,
                        'ticketit_admin'            => (bool) $user->ticketit_admin,
                        'ticketit_agent'            => (bool) $user->ticketit_agent,
                        'active'                    => (bool) $user->active,
                        'is_oncall'                 => (bool) $user->is_oncall,
                        'deployment_admin'          => (bool) $user->deployment_admin,
                        'can_manage_implementation' => (bool) $user->can_manage_implementation,
                    ]))"
                    class="group hover:bg-blue-50/30 transition-colors duration-150">

                    {{-- ID --}}
                    <td class="px-5 py-4 text-xs text-gray-400 font-semibold">{{ $user->id }}</td>

                    {{-- Nome --}}
                    <td class="px-5 py-4 font-semibold text-gray-900">{{ $user->name }}</td>

                    {{-- E-mail --}}
                    <td class="px-4 py-4 text-gray-500 text-xs">{{ $user->email }}</td>

                    {{-- Acesso --}}
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full {{ $roleClass }}">
                            {{ $roleLabel }}
                        </span>
                    </td>

                    {{-- Departamento --}}
                    <td class="px-4 py-4 text-gray-600 text-xs">{{ $deptName }}</td>

                    {{-- Ativo --}}
                    <td class="px-4 py-4 text-center">
                        @if($user->active)
                            <span class="inline-flex items-center px-2.5 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full">Sim</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Plantão --}}
                    <td class="px-4 py-4 text-center">
                        @if($user->is_oncall)
                            <span class="inline-flex items-center px-2.5 py-0.5 bg-blue-50 text-blue-700 text-xs font-bold rounded-full border border-blue-200" title="Habilitado no App de Plantão Mobile">
                                ⏱️ Sim
                            </span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Implantação --}}
                    <td class="px-4 py-4 text-center">
                        <div class="inline-flex items-center justify-center gap-1.5">
                            @if($user->deployment_admin)
                                <span title="Adm. Implantação"
                                      class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-100 text-purple-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </span>
                            @endif
                            @if($user->can_manage_implementation)
                                <span title="Ger. Implementação"
                                      class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </span>
                            @endif
                            @if(!$user->deployment_admin && !$user->can_manage_implementation)
                                <span class="text-gray-300">—</span>
                            @endif
                        </div>
                    </td>

                    {{-- Ações --}}
                    <td class="px-4 py-4 text-right">
                        <div class="flex justify-end gap-1">
                            {{-- Editar --}}
                            <button
                                @click="$dispatch('edit-user', { formData })"
                                class="admin-icon-btn admin-icon-btn--edit"
                                title="Editar usuário">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"/>
                                </svg>
                            </button>

                            {{-- Reset senha --}}
                            <button
                                @click="resetPassword()"
                                :disabled="loading"
                                class="admin-icon-btn admin-icon-btn--warning"
                                title="Enviar e-mail de redefinição de senha">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11.536 16.464l-1.414-1.414 1.414-1.414-1.414-1.414 1.414-1.414-1.414-1.414 1.414-1.414z"/>
                                </svg>
                            </button>

                            {{-- Visualizar --}}
                            <button
                                @click="$dispatch('show-user', {
                                    formData,
                                    role_display: '{{ addslashes($roleLabel) }}',
                                    department_display: '{{ addslashes($deptName) }}'
                                })"
                                class="admin-icon-btn admin-icon-btn--view"
                                title="Visualizar usuário">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2"/>
                                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2"/>
                                </svg>
                            </button>

                            {{-- Excluir --}}
                            <button
                                @click="deleteUser()"
                                :disabled="loading"
                                class="admin-icon-btn admin-icon-btn--delete"
                                title="Excluir usuário">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-16 text-center text-gray-400">Nenhum usuário encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.users.modals.create')
    @include('admin.users.modals.edit')
    @include('admin.users.modals.show')
    @include('admin.users.modals.delete-transfer')

</div>
@endsection
