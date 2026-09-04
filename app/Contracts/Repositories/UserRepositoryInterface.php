<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Persiste um novo usuário no banco.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * Atualiza um usuário existente com os dados fornecidos.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User;

    /**
     * Anonimiza o usuário (soft-delete com dados substituídos).
     */
    public function anonymize(User $user): void;

    /**
     * Obtém o preview de itens vinculados ao usuário antes da exclusão.
     */
    public function getDeletionPreviewData(User $user): array;

    /**
     * Reatribui chamados ativos, agendamentos e tarefas para outro usuário.
     */
    public function reassignActiveRecords(User $fromUser, User $toUser): void;
}
