<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\Tickets\StoreCommentRequest;
use App\Models\Ticket\Ticket;
use App\Services\Agent\CommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Responsável exclusivamente por operações relacionadas a comentários de tickets.
 * SRP: Controller magro que delega para CommentService.
 */
class CommentsController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Armazena um novo comentário em um ticket.
     */
    public function store(StoreCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        $this->commentService->addComment($ticket, $request->input('content'), $user);

        return redirect()
            ->route('agent.ticket.show', $ticket)
            ->with('status', 'Resposta enviada com sucesso!');
    }
}
