<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\Tickets\StartWhatsAppConversationRequest;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppAgentChatService;
use App\Services\WhatsApp\WhatsAppBotReleaseService;
use App\Services\WhatsApp\WhatsAppConversationStarterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsAppConversationController extends Controller
{
    public function __construct(
        private readonly WhatsAppAgentChatService $agentChatService,
        private readonly WhatsAppConversationStarterService $conversationStarter,
        private readonly WhatsAppBotReleaseService $botReleaseService,
    ) {}

    /**
     * Inicia uma conversa de WhatsApp a partir do chamado (outbound).
     * Disponível apenas quando o chamado ainda não tem conversa vinculada.
     */
    public function start(StartWhatsAppConversationRequest $request, Ticket $ticket): RedirectResponse
    {
        $redirect = redirect()->route('agent.ticket.show', ['ticket' => $ticket->id, 'tab' => 'whatsapp']);

        try {
            $this->conversationStarter->startForTicket(
                $ticket,
                Auth::guard('admin')->user(),
                $request->string('phone')->value(),
                $request->string('message')->value(),
            );
        } catch (ValidationException $exception) {
            return $redirect->withErrors($exception->errors())->withInput();
        }

        return $redirect->with('status', 'Conversa iniciada e mensagem enviada ao cliente.');
    }

    public function messages(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return response()->json([
            'messages' => $this->agentChatService->messagesForTicket(
                $ticket,
                $request->integer('after_id') ?: null
            ),
        ]);
    }

    public function store(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $ticket);

        $validated = $request->validate([
            'message' => $request->hasFile('attachment') || $request->hasFile('audio')
                ? ['nullable', 'string', 'max:5000']
                : ['required', 'string', 'max:5000'],
            'internal' => ['nullable', 'boolean'],
            'audio' => ['nullable', 'file', 'max:10240', 'mimetypes:audio/webm,audio/ogg,audio/mpeg,audio/wav,audio/mp4,audio/x-m4a,video/webm'],
            // Suporte troca arquivos técnicos arbitrários com o cliente (.dll, .pfx,
            // .exe, .bat, certificados, dumps). Não há whitelist de mimetype: o
            // arquivo é armazenado em disco público sem execução server-side e o
            // download é manual. Limite de tamanho (20MB) mitiga DoS.
            'attachment' => ['nullable', 'file', 'max:20480'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::guard('admin')->user();

        try {
            if ($request->hasFile('attachment')) {
                $message = $this->agentChatService->storeAttachment(
                    $ticket,
                    $user,
                    $request->file('attachment'),
                    $validated['caption'] ?? null
                );
            } elseif ($request->hasFile('audio')) {
                $message = $this->agentChatService->storeAudio($ticket, $user, $request->file('audio'));
            } else {
                $message = $this->agentChatService->sendText(
                    $ticket,
                    $user,
                    (string) $validated['message'],
                    $request->boolean('internal')
                );
            }
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            return back()->withErrors($exception->errors())->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->agentChatService->serializeMessage($message->load(['user', 'deletedBy'])),
            ], 201);
        }

        return back()->with('status', 'Mensagem enviada.');
    }

    public function destroy(Ticket $ticket, WhatsAppMessage $message): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $ticket);

        $this->agentChatService->deleteMessage($ticket, Auth::guard('admin')->user(), $message);

        if (request()->expectsJson()) {
            return response()->json(['status' => 'deleted']);
        }

        return back()->with('status', 'Mensagem excluída do histórico visível.');
    }

    public function update(Request $request, Ticket $ticket, WhatsAppMessage $message): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $user = Auth::guard('admin')->user();

        $updated = $this->agentChatService->updateMessage($ticket, $user, $message, $validated['body']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->agentChatService->serializeMessage($updated->load(['user', 'deletedBy'])),
            ]);
        }

        return back()->with('status', 'Mensagem editada.');
    }

    /**
     * Serve o anexo da mensagem com Content-Type real e nome original preservado.
     *
     * - Default: Content-Disposition: attachment (download forçado).
     * - Com `?disposition=inline`: serve inline para que <img>/<audio>/<video>
     *   consigam renderizar a mídia diretamente, mas apenas para mimetypes
     *   considerados seguros (image/*, audio/*, video/*). Para outros tipos a
     *   resposta cai em attachment, impedindo que HTML/JS armazenado seja
     *   executado pelo browser via inline.
     */
    public function download(Request $request, Ticket $ticket, WhatsAppMessage $message): StreamedResponse
    {
        $this->authorize('view', $ticket);

        $conversation = $message->conversation;

        abort_unless(
            $conversation && (int) $conversation->ticket_id === (int) $ticket->id,
            404,
        );

        abort_unless($message->attachment_path, 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($message->attachment_path), 404);

        $filename = $message->original_filename
            ?: basename($message->attachment_path);

        $headers = array_filter(['Content-Type' => $message->mime_type]);

        if ($request->query('disposition') === 'inline' && $this->canServeInline($message->mime_type)) {
            return $disk->response($message->attachment_path, $filename, $headers);
        }

        return $disk->download($message->attachment_path, $filename, $headers);
    }

    private function canServeInline(?string $mime): bool
    {
        if (! $mime) {
            return false;
        }

        return str_starts_with($mime, 'image/')
            || str_starts_with($mime, 'audio/')
            || str_starts_with($mime, 'video/')
            || $mime === 'application/pdf'
            || $mime === 'text/plain';
    }

    public function release(WhatsAppConversation $conversation): RedirectResponse
    {
        if ($conversation->ticket) {
            $this->authorize('update', $conversation->ticket);
        }

        if (! $this->botReleaseService->releaseManually($conversation)) {
            return back()->with('warning', 'A conversa não está em modo de atendimento humano.');
        }

        return back()->with('status', 'Bot reativado. O cliente voltará ao menu na próxima mensagem.');
    }

    public function pause(WhatsAppConversation $conversation): RedirectResponse
    {
        if ($conversation->ticket) {
            $this->authorize('update', $conversation->ticket);
        }

        if (! $this->botReleaseService->pauseManually($conversation)) {
            return back()->with('warning', 'A conversa já está em modo de atendimento humano.');
        }

        return back()->with('status', 'Bot pausado. Atendimento humano assumido.');
    }
}
