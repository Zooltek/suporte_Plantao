<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Guard 'web' é o padrão do modelo User (auth.defaults.guard).
     * Como 'admin' guard também usa o modelo User, roles com guard 'web'
     * são encontradas pelo Spatie para ambos os guards.
     */
    private const GUARD = 'web';

    /**
     * Todas as permissões do sistema, agrupadas por módulo.
     * Usar wildcards: 'admin.*' cobre qualquer 'admin.x.y'
     */
    private const PERMISSIONS = [
        // ── Painel administrativo ─────────────────────────────────────────
        'admin.dashboard',

        'admin.users.view',
        'admin.users.create',
        'admin.users.edit',
        'admin.users.delete',

        'admin.categories.view',
        'admin.categories.create',
        'admin.categories.edit',
        'admin.categories.delete',

        'admin.helpdesk.view',
        'admin.helpdesk.status.view',
        'admin.helpdesk.status.create',
        'admin.helpdesk.status.edit',
        'admin.helpdesk.status.delete',
        'admin.helpdesk.priority.view',
        'admin.helpdesk.priority.create',
        'admin.helpdesk.priority.edit',
        'admin.helpdesk.priority.delete',
        'admin.helpdesk.origins.view',
        'admin.helpdesk.origins.create',
        'admin.helpdesk.origins.edit',
        'admin.helpdesk.origins.delete',
        'admin.helpdesk.modules.view',
        'admin.helpdesk.modules.create',
        'admin.helpdesk.modules.edit',
        'admin.helpdesk.modules.delete',

        'admin.sla.view',
        'admin.sla.edit',

        // ── Tickets ───────────────────────────────────────────────────────
        'tickets.view',
        'tickets.create',
        'tickets.edit',
        'tickets.delete',
        'tickets.capture',

        // ── Atendimentos ──────────────────────────────────────────────────
        'attendances.view',
        'attendances.create',
        'attendances.edit',

        // ── Base de conhecimento ──────────────────────────────────────────
        'knowledge.view',
        'knowledge.create',
        'knowledge.edit',
        'knowledge.delete',

        // ── Agendamentos ──────────────────────────────────────────────────
        'schedules.view',
        'schedules.create',
        'schedules.edit',
        'schedules.delete',
        'schedules.confirm',

        // ── Clientes / relatórios / auditoria ─────────────────────────────
        'customers.view',
        'reports.view',
        'audits.view',

        // ── CRM / Feedback ────────────────────────────────────────────────
        'crm.view',
        'feedback.view',
        'feedback.create',
        'feedback.delete',

        // ── Implantações ──────────────────────────────────────────────────
        'implementation.manage',
    ];

    public function run(): void
    {
        // Limpa cache do Spatie antes de criar
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Cria todas as permissões
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        }

        // ── Roles e suas permissões ───────────────────────────────────────

        /** Agent: acesso ao módulo de suporte, sem painel admin */
        $agent = Role::firstOrCreate(['name' => 'agent', 'guard_name' => self::GUARD]);
        $agent->syncPermissions([
            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.capture',
            'attendances.view', 'attendances.create', 'attendances.edit',
            'knowledge.view', 'knowledge.create', 'knowledge.edit', 'knowledge.delete',
            'schedules.view', 'schedules.create', 'schedules.edit',
            'customers.view',
            'reports.view',
            'audits.view',
        ]);

        /** Admin: acesso total — inclui tudo do agent + painel admin */
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => self::GUARD]);
        $admin->syncPermissions(Permission::where('guard_name', self::GUARD)->get());

        /** CRM: acesso ao módulo CRM/Feedback */
        $crm = Role::firstOrCreate(['name' => 'crm', 'guard_name' => self::GUARD]);
        $crm->syncPermissions([
            'crm.view',
            'feedback.view', 'feedback.create', 'feedback.delete',
        ]);

        /** Deployment Admin: gerencia implantações */
        $deploymentAdmin = Role::firstOrCreate(['name' => 'deployment-admin', 'guard_name' => self::GUARD]);
        $deploymentAdmin->syncPermissions(['implementation.manage']);

        /** Implementation Manager: acesso restrito a implantações */
        $implManager = Role::firstOrCreate(['name' => 'implementation-manager', 'guard_name' => self::GUARD]);
        $implManager->syncPermissions(['implementation.manage']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ── Sincroniza roles para usuários existentes baseado nos flags ───

        $this->syncExistingUsers();

        $this->command->info('Roles e permissões criadas com sucesso.');
    }

    /**
     * Atribui roles Spatie aos usuários existentes com base nos flags booleanos.
     * Garante compatibilidade com a base de dados em produção antes da migração completa.
     */
    private function syncExistingUsers(): void
    {
        User::query()
            ->withoutGlobalScopes()
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    $roles = [];

                    if ($user->ticketit_admin) {
                        $roles[] = 'admin';
                    } elseif ($user->ticketit_agent) {
                        $roles[] = 'agent';
                    }

                    if ($user->department_id === 3 && ! $user->ticketit_admin) {
                        $roles[] = 'crm';
                    }

                    if ($user->deployment_admin) {
                        $roles[] = 'deployment-admin';
                    }

                    if ($user->can_manage_implementation && ! $user->ticketit_admin && ! $user->deployment_admin) {
                        $roles[] = 'implementation-manager';
                    }

                    if (! empty($roles)) {
                        $user->syncRoles($roles);
                    }
                }
            });
    }
}
