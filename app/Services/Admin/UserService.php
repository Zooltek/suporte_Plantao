<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    /**
     * Mapeia o role numérico para os flags de acesso.
     * role=1: Agente, role=2: Admin, role=3: CRM (também recebe acesso a chamados).
     */
    private function resolveRoleFlags(int $role): array
    {
        return [
            'ticketit_agent' => in_array($role, [1, 2, 3], true),
            'ticketit_admin' => $role === 2,
        ];
    }

    /**
     * Cria um novo usuário com as regras de negócio aplicadas.
     *
     * Comportamento de senha:
     * - Com senha informada: salva o hash e marca must_change_password=true
     *   (o usuário será forçado a trocar na próxima sessão).
     * - Sem senha: gera senha aleatória de 64 chars (A07/OWASP) e
     *   envia link de redefinição por e-mail.
     */
    public function store(array $data): User
    {
        $role = (int) $data['role'];

        if (!in_array($role, [1, 2, 3], true)) {
            throw new \DomainException("Role inválido: {$role}");
        }
        $departmentId         = (int) $data['department_id'];
        $hasTemporaryPassword = !empty($data['password']);

        $user = $this->repository->create(array_merge([
            'name'                 => $data['name'],
            'email'                => $data['email'],
            'password'             => $hasTemporaryPassword
                                        ? $data['password']             // cast 'hashed' faz o hash automaticamente
                                        : Hash::make(Str::random(64)),  // senha nunca utilizável manualmente
            'must_change_password' => $hasTemporaryPassword,
            'department_id'        => $departmentId,
            'active'               => 1,
            'is_oncall'            => filter_var($data['is_oncall'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
        ], $this->resolveRoleFlags($role)));

        if (!$hasTemporaryPassword) {
            Password::broker('admins')->sendResetLink(['email' => $user->email]);
        }

        Log::info('Usuário criado', [
            'user_id'            => $user->id,
            'email'              => $user->email,
            'role'               => $role,
            'temporary_password' => $hasTemporaryPassword,
            'is_oncall'          => $user->is_oncall,
        ]);

        return $user;
    }

    /**
     * Atualiza um usuário existente.
     */
    public function update(User $user, array $data): User
    {
        $role = (int) $data['role'];

        if (!in_array($role, [1, 2, 3], true)) {
            throw new \DomainException("Role inválido: {$role}");
        }
        $departmentId = (int) $data['department_id'];

        $updatePayload = array_merge([
            'name'                      => $data['name'],
            'email'                     => $data['email'],
            'department_id'             => $departmentId,
            'active'                    => filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'deployment_admin'          => filter_var($data['deployment_admin'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'can_manage_implementation' => filter_var($data['can_manage_implementation'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'is_oncall'                 => filter_var($data['is_oncall'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
        ], $this->resolveRoleFlags($role));

        if (!empty($data['password'])) {
            $updatePayload['password'] = $data['password'];
            $updatePayload['must_change_password'] = true;
        }

        $updated = $this->repository->update($user, $updatePayload);

        Log::info('Usuário atualizado', [
            'user_id'            => $user->id,
            'email'              => $user->email,
            'role'               => $role,
            'temporary_password' => !empty($data['password']),
        ]);

        return $updated;
    }

    /**
     * Redefine a senha do usuário.
     */
    public function resetPassword(User $user): void
    {
        Password::broker('admins')->sendResetLink(['email' => $user->email]);
        Log::info('Reset de senha enviado', ['user_id' => $user->id, 'email' => $user->email]);
    }

    /**
     * Obtém o preview de registros que serão afetados pela exclusão.
     */
    public function getDeletionPreview(User $user): array
    {
        return $this->repository->getDeletionPreviewData($user);
    }

    /**
     * Anonimiza um usuário existente em vez de deletá-lo.
     * Reatribui chamados ativos, agendamentos e tarefas para o novo responsável indicado (ou Admin ID 1 fallback).
     * Preserva o histórico de tickets finalizados substituindo dados pessoais.
     */
    public function anonymize(User $user, ?int $transferToUserId = null): array
    {
        $preview = $this->getDeletionPreview($user);
        $transferredCount = 0;

        if ($preview['total_active_items'] > 0) {
            $targetUserId = $transferToUserId ?? $preview['default_transfer_agent_id'] ?? 1;
            $targetUser = User::find($targetUserId) ?? User::where('ticketit_admin', 1)->first() ?? User::first();

            if ($targetUser && (int) $targetUser->id !== (int) $user->id) {
                $this->repository->reassignActiveRecords($user, $targetUser);
                $transferredCount = $preview['total_active_items'];
                Log::info('Vínculos ativos reatribuídos antes da exclusão', [
                    'from_user_id' => $user->id,
                    'to_user_id' => $targetUser->id,
                    'active_tickets' => $preview['active_tickets_count'],
                    'pending_schedules' => $preview['pending_schedules_count'],
                    'active_tasks' => $preview['active_tasks_count'],
                ]);
            }
        }

        Log::warning('Usuário anonimizado (exclusão)', [
            'user_id' => $user->id,
            'email' => $user->email,
            'transferred_items' => $transferredCount,
        ]);

        $this->repository->anonymize($user);

        return [
            'transferred_items' => $transferredCount,
            'closed_tickets_preserved' => $preview['closed_tickets_count'],
        ];
    }
}
