<?php

namespace App\Http\Middleware\Helpdesk;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Ticket\Ticket;
use Symfony\Component\HttpFoundation\Response;

class ResAccess
{
    /**
     * Garante que o usuário autenticado só possa ver o ticket se for o criador, 
     * o agente responsável, ou um administrador.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admins e agentes (vificados via atributos ou service) têm acesso livre
        // Aqui usamos checagem direta mirror do legado ou podemos usar o AccessService
        if (isset($user->ticketit_admin) && $user->ticketit_admin || isset($user->ticketit_agent) && $user->ticketit_agent) {
            return $next($request);
        }

        $ticketId = $request->route('ticket') ?? $request->route('id');

        if ($ticketId) {
            $ticket = Ticket::find($ticketId);                                                                      
            if ($ticket && $ticket->user_id == $user->id) {
                return $next($request);
            }
        }

        abort(403, 'Você não tem permissão para acessar este chamado.');
    }
}
