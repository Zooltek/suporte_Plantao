<?php

namespace App\Services\WhatsApp;

use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppMessageMacro;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WhatsAppAgentChatService
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly AgentMessageSignature $signature,
    ) {}

    public function messagesForTicket(Ticket $ticket, ?int $afterId = null)
    {
        $conversation = $this->conversationForTicket($ticket);

        if (! $conversation) {
            return collect();
        }

        return $conversation->messages()
            ->with(['user', 'deletedBy'])
            ->when($afterId, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('created_at')
            ->get()
            ->map(function (WhatsAppMessage $message) use ($conversation): array {
                $message->setRelation('conversation', $conversation);

                return $this->serializeMessage($message);
            });
    }

    public function sendText(
        Ticket $ticket,
        User $user,
        string $body,
        bool $internal = false,
    ): WhatsAppMessage {
        $conversation = $this->conversationForTicket($ticket);

        if (! $conversation) {
            throw ValidationException::withMessages([
                'message' => 'Este chamado não possui conversa WhatsApp vinculada.',
            ]);
        }

        $body = $this->resolveMacro($body, $user);

        if ($internal) {
            return DB::transaction(function () use ($conversation, $user, $body): WhatsAppMessage {
                $message = WhatsAppMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                    'direction' => 'outbound',
                    'type' => 'text',
                    'body' => $body,
                    'is_internal' => true,
                ]);

                $conversation->forceFill(['last_activity_at' => now()])->save();

                return $message;
            });
        }

        // O cliente recebe a mensagem assinada com o nome do agente; o corpo
        // persistido permanece o original (o painel já atribui o autor).
        $this->whatsApp->send($conversation, $body, $user->id, false, $this->signature->apply($body, $user));
        $conversation->forceFill(['last_activity_at' => now()])->save();

        return $conversation->messages()->latest('id')->firstOrFail();
    }

    public function storeAudio(Ticket $ticket, User $user, UploadedFile $audio): WhatsAppMessage
    {
        $conversation = $this->conversationForTicket($ticket);

        if (! $conversation) {
            throw ValidationException::withMessages([
                'audio' => 'Este chamado não possui conversa WhatsApp vinculada.',
            ]);
        }

        $extension = $audio->guessExtension() ?: 'webm';
        $path = $audio->storeAs(
            'whatsapp/attachments',
            Str::uuid()->toString().'.'.$extension,
            'public'
        );

        $providerMessageId = $this->whatsApp->sendAudio($conversation, $path, $audio->getMimeType(), $user->id);

        return DB::transaction(function () use ($conversation, $user, $audio, $path, $providerMessageId): WhatsAppMessage {
            $message = WhatsAppMessage::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'direction' => 'outbound',
                'type' => 'audio',
                'body' => null,
                'attachment_path' => $path,
                'mime_type' => $audio->getMimeType(),
                'file_size' => $audio->getSize(),
                'provider_message_id' => $providerMessageId ?: null,
            ]);

            $conversation->forceFill(['last_activity_at' => now()])->save();

            return $message;
        });
    }

    /**
     * Armazena e envia um anexo (imagem ou documento) para o cliente.
     */
    public function storeAttachment(
        Ticket $ticket,
        User $user,
        UploadedFile $file,
        ?string $caption = null
    ): WhatsAppMessage {
        $conversation = $this->conversationForTicket($ticket);

        if (! $conversation) {
            throw ValidationException::withMessages([
                'attachment' => 'Este chamado não possui conversa WhatsApp vinculada.',
            ]);
        }

        $originalName = $file->getClientOriginalName() ?: null;
        $extension = $this->resolveStorageExtension($file, $originalName);
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs('whatsapp/attachments', $filename, 'public');

        $type = $this->resolveAttachmentType($file->getMimeType());

        $providerMessageId = $this->whatsApp->sendMedia(
            $conversation,
            $path,
            $this->signature->applyToCaption($caption, $user),
            $file->getMimeType(),
            $user->id,
            $originalName,
        );

        return DB::transaction(function () use ($conversation, $user, $file, $path, $type, $providerMessageId, $originalName): WhatsAppMessage {
            $message = WhatsAppMessage::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'direction' => 'outbound',
                'type' => $type,
                'body' => null,
                'attachment_path' => $path,
                'original_filename' => $originalName,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'provider_message_id' => $providerMessageId ?: null,
            ]);

            $conversation->forceFill(['last_activity_at' => now()])->save();

            return $message;
        });
    }

    /**
     * Preferimos a extensão do nome enviado pelo usuário (preserva .pfx, .xml, .p12),
     * caindo no guessExtension() do Symfony (baseado em MIME) e, se ambos falharem,
     * em um hex aleatório curto para evitar arquivos sem extensão no disco.
     */
    private function resolveStorageExtension(UploadedFile $file, ?string $originalName): string
    {
        if ($originalName !== null && $originalName !== '') {
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);

            if (is_string($ext) && $ext !== '' && preg_match('/^[A-Za-z0-9]{1,8}$/', $ext) === 1) {
                return strtolower($ext);
            }
        }

        $guessed = $file->guessExtension();

        return is_string($guessed) && $guessed !== '' ? $guessed : bin2hex(random_bytes(4));
    }

    /**
     * Resolve o tipo de mensagem baseado no MIME type.
     */
    private function resolveAttachmentType(?string $mimetype): string
    {
        if (! $mimetype) {
            return 'document';
        }

        return match (true) {
            str_contains($mimetype, 'image/') => 'image',
            str_contains($mimetype, 'video/') => 'video',
            str_contains($mimetype, 'audio/') => 'audio',
            default => 'document',
        };
    }

    public function deleteMessage(Ticket $ticket, User $user, WhatsAppMessage $message): void
    {
        $conversation = $this->conversationForTicket($ticket);

        abort_unless($conversation && (int) $message->conversation_id === (int) $conversation->id, 404);

        if ($message->direction === 'outbound' && $message->provider_message_id) {
            $this->whatsApp->deleteMessage($conversation, $message->provider_message_id);
        }

        $message->forceFill([
            'deleted_by' => $user->id,
            'deleted_at' => now(),
        ])->save();
    }

    /**
     * Atualiza o conteúdo de uma mensagem enviada.
     */
    public function updateMessage(Ticket $ticket, User $user, WhatsAppMessage $message, string $newBody): WhatsAppMessage
    {
        $conversation = $this->conversationForTicket($ticket);

        abort_unless($conversation && (int) $message->conversation_id === (int) $conversation->id, 404);
        abort_unless($message->direction === 'outbound', 400, 'Apenas mensagens enviadas podem ser editadas.');
        abort_unless(! $message->deleted_at, 400, 'Mensagens excluídas não podem ser editadas.');

        if ($message->provider_message_id) {
            // Mantém a assinatura na versão editada usando o autor original
            // (o editor pode diferir de quem enviou); fallback no editor atual.
            $author = $message->user ?: $user;
            $this->whatsApp->editMessage(
                $conversation,
                $message->provider_message_id,
                $this->signature->apply($newBody, $author),
            );
        }

        $message->forceFill([
            'body' => $newBody,
        ])->save();

        return $message;
    }

    public function serializeMessage(WhatsAppMessage $message): array
    {
        $ticketId = $message->conversation?->ticket_id;
        $hasAttachment = $message->attachment_path && $ticketId;

        $downloadUrl = $hasAttachment
            ? route('agent.ticket.whatsapp.messages.download', ['ticket' => $ticketId, 'message' => $message->id])
            : null;

        // attachment_url passa a apontar para a rota autenticada (inline) em vez
        // da URL pública: o host é resolvido pela request atual (corrige imagens
        // quebradas quando o app é acessado fora de APP_URL) e a leitura passa
        // pela Policy do ticket (evita IDOR via URL pública compartilhada).
        $attachmentUrl = $hasAttachment
            ? route('agent.ticket.whatsapp.messages.download', [
                'ticket' => $ticketId,
                'message' => $message->id,
                'disposition' => 'inline',
            ])
            : null;

        return [
            'id' => $message->id,
            'direction' => $message->direction,
            'type' => $message->type,
            'body' => $message->deleted_at ? 'Mensagem excluída.' : (string) $message->body,
            'attachment_url' => $attachmentUrl,
            'download_url' => $downloadUrl,
            'original_filename' => $message->original_filename,
            'mime_type' => $message->mime_type,
            'file_size' => $message->file_size,
            'is_internal' => (bool) $message->is_internal,
            'is_deleted' => $message->deleted_at !== null,
            'user_name' => $message->user?->name,
            'deleted_by_name' => $message->deletedBy?->name,
            'created_at' => $message->created_at?->toIso8601String(),
            'created_at_label' => $message->created_at?->format('d/m H:i'),
        ];
    }

    private function conversationForTicket(Ticket $ticket): ?WhatsAppConversation
    {
        return WhatsAppConversation::query()
            ->where('ticket_id', $ticket->id)
            ->latest('id')
            ->first();
    }

    private function resolveMacro(string $body, User $user): string
    {
        $trimmed = trim($body);

        if (! str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        $command = strtok($trimmed, " \t\n\r\0\x0B") ?: $trimmed;

        $macro = WhatsAppMessageMacro::query()
            ->where('command', $command)
            ->where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->whereNull('department_id')
                    ->orWhere('department_id', $user->department_id);
            })
            ->orderByRaw('department_id IS NULL')
            ->first();

        if (! $macro) {
            throw ValidationException::withMessages([
                'message' => "Atalho {$command} não encontrado ou inativo.",
            ]);
        }

        return $macro->text;
    }
}
