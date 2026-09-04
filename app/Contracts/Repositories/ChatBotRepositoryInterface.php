<?php

namespace App\Contracts\Repositories;

use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppConversation;
use Illuminate\Database\Eloquent\Collection;

interface ChatBotRepositoryInterface
{
    /**
     * Persiste a conversa no banco de dados.
     */
    public function saveConversation(WhatsAppConversation $conversation): void;

    /**
     * Busca empresa por nome ou trade_name (busca fuzzy LIKE).
     */
    public function findCompanyByName(string $name): ?Company;

    /**
     * Busca empresa por CNPJ.
     */
    public function findCompanyByCnpj(string $cnpj): ?Company;

    /**
     * Retorna o último ticket aberto por uma empresa.
     */
    public function findLastTicketByCompanyId(int $companyId): ?Ticket;

    /**
     * Retorna o último ticket que contenha o número de telefone no campo obs.
     */
    public function findLastTicketByPhone(string $phone): ?Ticket;

    /**
     * Retorna todos os usuários que são agentes ou admins (para notificação de handover).
     *
     * @return Collection<int, \App\Models\User>
     */
    public function getAgentsAndAdmins(): Collection;
}
