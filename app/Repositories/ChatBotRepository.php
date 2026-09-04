<?php

namespace App\Repositories;

use App\Contracts\Repositories\ChatBotRepositoryInterface;
use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use Illuminate\Database\Eloquent\Collection;

class ChatBotRepository implements ChatBotRepositoryInterface
{
    /**
     * Persiste a conversa no banco de dados.
     */
    public function saveConversation(WhatsAppConversation $conversation): void
    {
        $conversation->save();
    }

    /**
     * Busca empresa por nome ou trade_name (busca fuzzy LIKE).
     */
    public function findCompanyByName(string $name): ?Company
    {
        return Company::where('name', 'like', '%'.$name.'%')
            ->orWhere('trade_name', 'like', '%'.$name.'%')
            ->first();
    }

    /**
     * Busca empresa por CNPJ ignorando formatação.
     *
     * O cadastro legado grava o CNPJ com máscara (`92.328.407/4004-18`),
     * mas o chatbot envia apenas os 14 dígitos. A comparação normalizada
     * via SQL evita que clientes existentes sejam tratados como novos.
     */
    public function findCompanyByCnpj(string $cnpj): ?Company
    {
        return Company::whereCnpjDigits($cnpj)->first();
    }

    /**
     * Retorna o último ticket aberto por uma empresa.
     * Eager-loading do status para exibição no resumo sem N+1.
     */
    public function findLastTicketByCompanyId(int $companyId): ?Ticket
    {
        return Ticket::with('status')
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Retorna o último ticket que contenha o número de telefone no campo obs.
     * Eager-loading do status para exibição no resumo sem N+1.
     */
    public function findLastTicketByPhone(string $phone): ?Ticket
    {
        return Ticket::with('status')
            ->where('obs', 'like', '%'.$phone.'%')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Retorna todos os usuários que são agentes ou admins.
     *
     * @return Collection<int, User>
     */
    public function getAgentsAndAdmins(): Collection
    {
        return User::where('ticketit_agent', 1)
            ->orWhere('ticketit_admin', 1)
            ->get();
    }
}
