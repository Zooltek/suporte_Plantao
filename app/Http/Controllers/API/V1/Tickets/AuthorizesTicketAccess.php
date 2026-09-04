<?php

namespace App\Http\Controllers\API\V1\Tickets;

use App\Models\Ticket\Ticket;
use Illuminate\Support\Facades\Auth;

trait AuthorizesTicketAccess
{
    /**
     * Admins acessam qualquer ticket; agentes apenas tickets sob sua responsabilidade.
     */
    private function authorizeTicketAccess(int $ticketId): Ticket
    {
        $ticket = Ticket::query()->findOrFail($ticketId);
        $user = Auth::guard('admin')->user() ?? Auth::user();

        if ($user?->ticketit_admin) {
            return $ticket;
        }

        abort_if(
            ! $user?->ticketit_agent || (int) $ticket->agent_id !== (int) $user->id,
            403,
            'Acesso não autorizado a este ticket.'
        );

        return $ticket;
    }
}
