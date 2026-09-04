<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Helpdesk\Chat\SendChatMessageRequest;
use App\Http\Requests\Helpdesk\Chat\StartChatRequest;
use App\Models\Category;
use App\Services\Helpdesk\ChatService;
use App\Models\Helpdesk\Chat\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $conversation = $this->chatService->getActiveConversation($user);

        if ($conversation) {
            return redirect()->route('portal.chat.show', $conversation);
        }

        $categories = Category::query()
            ->root()
            ->orderedByDisplayName()
            ->get();

        return view('helpdesk.chat.connect', [
            'user' => $user,
            'categories' => $categories,
        ]);
    }

    public function store(StartChatRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($conversation = $this->chatService->getActiveConversation($user)) {
            return redirect()
                ->route('portal.chat.show', $conversation)
                ->with('warning', 'Você já possui um chat ativo em andamento.');
        }

        $conversation = $this->chatService->startConversation(
            $user,
            $request->string('subject')->trim()->toString(),
            $request->string('message')->trim()->toString(),
            $request->filled('category_id') ? $request->integer('category_id') : null,
        );

        return redirect()
            ->route('portal.chat.show', $conversation)
            ->with('status', 'Chat iniciado e ticket registrado com sucesso.');
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $conversation = $this->chatService->getConversationForUser($request->user(), $conversation);

        return view('helpdesk.chat.liveboard', [
            'user' => $request->user(),
            'conversation' => $conversation,
            'messages' => $conversation->messages,
            'ticket' => $conversation->ticket,
        ]);
    }

    public function storeMessage(
        SendChatMessageRequest $request,
        Conversation $conversation,
    ): RedirectResponse {
        $conversation = $this->chatService->getConversationForUser($request->user(), $conversation);

        if ($conversation->isClosed()) {
            return redirect()
                ->route('portal.chat.show', $conversation)
                ->with('warning', 'Esta conversa já foi encerrada.');
        }

        $this->chatService->appendUserMessage(
            $conversation,
            $request->user(),
            $request->string('message')->trim()->toString(),
        );

        return redirect()
            ->route('portal.chat.show', $conversation)
            ->with('status', 'Mensagem enviada com sucesso.');
    }

    public function close(Request $request, Conversation $conversation): RedirectResponse
    {
        $conversation = $this->chatService->getConversationForUser($request->user(), $conversation);

        $this->chatService->closeConversation($conversation);

        return redirect()
            ->route('portal.chat.index')
            ->with('status', 'Chat encerrado. O ticket permanece registrado para continuidade do atendimento.');
    }
}
