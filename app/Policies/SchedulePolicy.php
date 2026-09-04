<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

/**
 * Controla acesso a Agendamentos — previne IDOR (OWASP A01).
 *
 * Regras de negócio:
 *  - viewAny / view / create: qualquer agente autenticado.
 *  - update: agente responsável (agent_id) ou admin.
 *  - delete / confirm: somente admin.
 */
class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAgent();
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $user->isAgent();
    }

    public function create(User $user): bool
    {
        return $user->isAgent();
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isAgent() && (int) $schedule->agent_id === $user->id;
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->isAdmin();
    }

    public function confirm(User $user, Schedule $schedule): bool
    {
        return $user->isAdmin();
    }

    /**
     * Agente responsável pode confirmar seu próprio agendamento pendente.
     */
    public function confirmOwn(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isAgent() && (int) $schedule->agent_id === $user->id;
    }

    /**
     * Agente responsável pode cancelar seu próprio agendamento.
     */
    public function cancel(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isAgent() && (int) $schedule->agent_id === $user->id;
    }

    public function finalize(User $user, Schedule $schedule): bool
    {
        return $this->update($user, $schedule);
    }
}
