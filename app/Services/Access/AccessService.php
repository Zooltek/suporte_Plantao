<?php

namespace App\Services\Access;

use App\Models\Department;
use App\Models\User;

class AccessService
{
    public function isAdmin(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return (bool) $user->ticketit_admin || $user->hasRole('admin', 'web');
    }

    public function isAgent(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return (bool) $user->ticketit_agent || $user->hasRole(['admin', 'agent'], 'web');
    }

    public function hasStaffAccess(?User $user): bool
    {
        return $this->isAdmin($user) || $this->isAgent($user);
    }

    public function canAccessAdmin(?User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function canAccessAgent(?User $user): bool
    {
        return $this->hasStaffAccess($user);
    }

    public function canAccessFeedback(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole(['admin', 'crm']) || $user->hasPermissionTo('feedback.view')) {
            return true;
        }

        if (! $user->department_id) {
            return false;
        }

        return Department::query()
            ->whereKey($user->department_id)
            ->where('is_feedback', true)
            ->exists();
    }

    public function isCrmDepartment(?User $user): bool
    {
        if (! $user?->department_id) {
            return false;
        }

        return Department::query()
            ->whereKey($user->department_id)
            ->where('is_crm', true)
            ->exists();
    }

    public function isCrmEmailUser(?User $user): bool
    {
        return $user?->email === 'crm@system.com';
    }

    public function isDashboardAdminEmail(?User $user): bool
    {
        return $user?->email === 'admin@teste.com';
    }

    public function isDashboardAgentEmail(?User $user): bool
    {
        return $user?->email === 'agente@teste.com';
    }

    public function isDashboardCustomerEmail(?User $user): bool
    {
        return $user?->email === 'cliente@teste.com';
    }

    public function isDeveloperEmail(?User $user): bool
    {
        return $user?->email === 'cassianogf@gmail.com';
    }

    public function isSupportAttendantEmail(?User $user): bool
    {
        return $user?->email === 'atendente@consuldatasistemas.com.br';
    }

    public function canAccessDeveloperArea(?User $user): bool
    {
        return (bool) $user && $this->isDeveloperEmail($user) && ! $this->isAgent($user);
    }

    public function hasRole(?User $user, string $role): bool
    {
        if ($user === null) {
            return false;
        }

        // Roles virtuais não existem no Spatie — avaliadas diretamente
        return match (strtolower($role)) {
            'staff' => $this->hasStaffAccess($user),
            'crm' => $this->isCrmEmailUser($user) || $this->isCrmDepartment($user),
            // Demais roles: delega ao Spatie (com fallback nos flags para migração gradual)
            default => $user->hasRole($role),
        };
    }

    /**
     * Resolve a rota de "home" do usuário (uso em /, /dashboard e logins).
     *
     * Precedência:
     *  1. Atalhos por e-mail de desenvolvimento (admin/agent/customer demo)
     *  2. Conta sintética crm@system.com (sempre crm.index)
     *  3. Staff (admin/agente, inclusive os do Comercial com ticketit_agent)
     *     → agent.index, porque a rotina diária é tratar chamados
     *  4. Departamento Comercial sem staff access → crm.index
     *     (mantém compat para usuários só de Feedback)
     */
    public function dashboardRouteForUser(User $user): ?string
    {
        return match (true) {
            $this->isDashboardAdminEmail($user) => 'admin.dashboard',
            $this->isDashboardAgentEmail($user) => 'agent.index',
            $this->isDashboardCustomerEmail($user) => 'customer.dashboard',
            $this->isCrmEmailUser($user) => 'crm.index',
            $this->hasStaffAccess($user) => 'agent.index',
            $this->isCrmDepartment($user) => 'crm.index',
            default => null,
        };
    }

    public function helpdeskIndexRouteForUser(User $user): string
    {
        return match (true) {
            $this->hasStaffAccess($user) => 'agent.index',
            $this->isCrmDepartment($user) => 'crm.index',
            default => 'portal.home',
        };
    }

    public function adminGuardRedirectRoute(User $user): string
    {
        if ($this->isAdmin($user)) {
            return 'admin.dashboard';
        }

        if ($this->isCrmEmailUser($user)) {
            return 'crm.index';
        }

        if ($this->hasStaffAccess($user)) {
            return 'agent.index';
        }

        if ($this->isCrmDepartment($user)) {
            return 'crm.index';
        }

        return 'agent.index';
    }

    public function adminLoginRedirectUrl(User $user): string
    {
        if ($this->isCrmEmailUser($user)) {
            return route('crm.index');
        }

        // Se for Admin ou Agente, ambos vão para a lista de tickets (Agent Index)
        if ($this->hasStaffAccess($user)) {
            return route('agent.index');
        }

        return route('login');
    }
}
