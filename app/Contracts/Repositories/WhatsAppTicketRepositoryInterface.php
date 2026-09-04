<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Notification as SystemNotification;
use App\Models\Ticket\Attachment;
use App\Models\Ticket\Ticket;
use App\Models\User;

interface WhatsAppTicketRepositoryInterface
{
    /**
     * Persiste um Ticket no banco (INSERT ou UPDATE).
     */
    public function saveTicket(Ticket $ticket): void;

    /**
     * Busca um User pelo id; retorna null se não encontrado.
     */
    public function findUser(int $userId): ?User;

    /**
     * Retorna o id do primeiro usuário administrador ativo, ou 1 como fallback.
     */
    public function firstAdminUserId(): int;

    /**
     * Busca o company_id por nome/trade_name. Retorna null se não encontrado.
     */
    public function findCompanyIdByName(string $name): ?int;

    /**
     * Cria uma notificação de sistema para o agente.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSystemNotification(array $data): SystemNotification;

    /**
     * Cria um registro de Attachment para um arquivo de mídia.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAttachment(array $data): Attachment;
}
