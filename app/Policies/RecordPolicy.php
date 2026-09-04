<?php

namespace App\Policies;

use App\Models\Schedule\Record;
use App\Models\User;

/**
 * Controla acesso a RATs (Registros de Atendimento) — previne IDOR (OWASP A01).
 *
 * Regras de negócio:
 *  - view / create: qualquer agente autenticado.
 *  - update: agente responsável pelo RAT (agent_id) ou admin.
 *  - delete: somente admin.
 */
class RecordPolicy
{
    public function view(User $user, Record $record): bool
    {
        return $user->isAgent();
    }

    public function create(User $user): bool
    {
        return $user->isAgent();
    }

    public function update(User $user, Record $record): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isAgent() && (int) $record->agent_id === $user->id;
    }

    public function delete(User $user, Record $record): bool
    {
        return $user->isAdmin();
    }
}
