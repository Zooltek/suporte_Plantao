<?php

namespace App\Services\Ticket\Routing;

use App\Models\Notification;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Avisa os agentes do novo departamento quando um ticket é movido entre setores.
 *
 * Foco no roteamento manual: quando o admin altera o department_id de um
 * chamado via UI (quick update / formulário), os agentes ativos do novo setor
 * recebem uma notificação no sistema apontando para o ticket.
 *
 * Regras:
 *  - Sem-op se newDepartmentId for null (chamado fica visível a todos os setores).
 *  - Sem-op se old == new (movimentação que não muda nada).
 *  - Excluí o ator (quem disparou a mudança) da lista de destinatários.
 *  - Falhas são logadas mas nunca propagadas — UX do admin não pode quebrar
 *    porque a fila de notificações falhou.
 */
class TicketDepartmentTransferNotifier
{
    public function notify(Ticket $ticket, ?int $oldDepartmentId, ?int $newDepartmentId): void
    {
        if ($newDepartmentId === null || $oldDepartmentId === $newDepartmentId) {
            return;
        }

        $actorId = Auth::id();

        $recipients = User::query()
            ->where('ticketit_agent', true)
            ->where('active', true)
            ->where('department_id', $newDepartmentId)
            ->when($actorId, fn ($query) => $query->where('id', '!=', $actorId))
            ->get(['id']);

        if ($recipients->isEmpty()) {
            return;
        }

        $url = $this->resolveTicketUrl($ticket);
        $content = sprintf(
            '↪ Chamado #%d transferido para o seu setor — %s',
            $ticket->id,
            $ticket->contact ?: 'sem contato'
        );

        foreach ($recipients as $recipient) {
            try {
                Notification::store(
                    user_id: $recipient->id,
                    content: $content,
                    action: $url,
                    image: '',
                    status: 1,
                );

                Cache::forget("user_recent_notifications_{$recipient->id}");
            } catch (\Throwable $e) {
                Log::warning('[TicketDepartmentTransferNotifier] Falha ao notificar agente.', [
                    'ticket_id' => $ticket->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function resolveTicketUrl(Ticket $ticket): string
    {
        return (string) rescue(
            fn () => route('agent.ticket.show', $ticket->id),
            fn () => url('/agent/ticket/'.$ticket->id),
            false
        );
    }
}
