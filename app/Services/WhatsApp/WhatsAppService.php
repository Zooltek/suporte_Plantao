<?php

namespace App\Services\WhatsApp;

use App\Contracts\Repositories\WhatsAppRepositoryInterface;
use App\Contracts\WhatsApp\WhatsAppProviderContract;
use App\DTO\WhatsApp\IncomingMessage;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fachada de comunicação com o provedor WhatsApp.
 *
 * Responsabilidades:
 *  - Enviar mensagens de texto
 *  - Persistir mensagens recebidas/enviadas (via Repository)
 *  - Baixar e armazenar mídias recebidas
 */
class WhatsAppService
{
    public function __construct(
        private readonly WhatsAppProviderContract $provider,
        private readonly WhatsAppRepositoryInterface $whatsAppRepository,
    ) {}

    /**
     * Envia texto ao cliente e registra a mensagem como outbound.
     * Retorna o provider_message_id em caso de sucesso.
     *
     * $providerText permite entregar ao cliente um texto diferente do que será
     * persistido em $message — usado para assinar a mensagem com o nome do
     * agente sem poluir o histórico. Quando null, o cliente recebe $message.
     */
    public function send(
        WhatsAppConversation $conversation,
        string $message,
        ?int $userId = null,
        bool $internal = false,
        ?string $providerText = null,
    ): string|false {
        if (! $internal && trim($message) === '' && trim($providerText ?? '') === '') {
            return false;
        }

        $providerMessageId = null;

        if (! $internal && config('whatsapp.enabled', false)) {
            $result = $this->provider->sendText($conversation->phone, $providerText ?? $message);

            if (! $result) {
                Log::warning('[WhatsApp] Mensagem outbound não confirmada pelo provedor.', [
                    'phone' => $conversation->phone,
                    'conversation' => $conversation->id,
                ]);
            } else {
                $providerMessageId = is_string($result) ? $result : null;
            }
        }

        $this->whatsAppRepository->createOutboundMessage($conversation, $message, $userId, $internal, $providerMessageId);

        return $providerMessageId ?? false;
    }

    /**
     * Envia mídia (imagem, documento) ao cliente e registra a mensagem como outbound.
     * Retorna o provider_message_id em caso de sucesso.
     *
     * $fileName preserva o nome original do arquivo no destino — sem ele, o cliente
     * receberia o UUID interno do storage como nome.
     */
    public function sendMedia(
        WhatsAppConversation $conversation,
        string $mediaPath,
        ?string $caption = null,
        ?string $mimetype = null,
        ?int $userId = null,
        ?string $fileName = null,
    ): string|false {
        if (! config('whatsapp.enabled', false)) {
            Log::debug('[WhatsApp] WHATSAPP_ENABLED=false — mídia salva, envio real pulado.', [
                'phone' => $conversation->phone,
                'path' => $mediaPath,
            ]);

            return false;
        }

        $result = $this->provider->sendMedia(
            $conversation->phone,
            $mediaPath,
            $caption,
            $mimetype,
            $fileName,
        );

        if (! $result) {
            Log::warning('[WhatsApp] Mídia outbound não confirmada pelo provedor.', [
                'phone' => $conversation->phone,
                'conversation' => $conversation->id,
            ]);

            return false;
        }

        return is_string($result) ? $result : false;
    }

    /**
     * Envia áudio (voice note) ao cliente e registra a mensagem como outbound.
     * Retorna o provider_message_id em caso de sucesso.
     */
    public function sendAudio(
        WhatsAppConversation $conversation,
        string $mediaPath,
        ?string $mimetype = null,
        ?int $userId = null,
    ): string|false {
        if (! config('whatsapp.enabled', false)) {
            Log::debug('[WhatsApp] WHATSAPP_ENABLED=false — áudio salvo, envio real pulado.', [
                'phone' => $conversation->phone,
                'path' => $mediaPath,
            ]);

            return false;
        }

        $result = $this->provider->sendAudio(
            $conversation->phone,
            $mediaPath,
            $mimetype
        );

        if (! $result) {
            Log::warning('[WhatsApp] Áudio outbound não confirmado pelo provedor.', [
                'phone' => $conversation->phone,
                'conversation' => $conversation->id,
            ]);

            return false;
        }

        return is_string($result) ? $result : false;
    }

    /**
     * Verifica se um número possui conta ativa no WhatsApp.
     * Quando o envio real está desabilitado (ambiente local), assume válido.
     */
    public function numberExists(string $phone): bool
    {
        if (! config('whatsapp.enabled', false)) {
            return true;
        }

        return $this->provider->checkNumberExists($phone);
    }

    /**
     * Exclui uma mensagem enviada ao cliente via WhatsApp.
     */
    public function deleteMessage(WhatsAppConversation $conversation, string $messageId): bool
    {
        if (! config('whatsapp.enabled', false)) {
            Log::debug('[WhatsApp] WHATSAPP_ENABLED=false — exclusão no WhatsApp pulada.', [
                'phone' => $conversation->phone,
                'messageId' => $messageId,
            ]);

            return true;
        }

        return $this->provider->deleteMessage($conversation->phone, $messageId);
    }

    /**
     * Edita uma mensagem enviada ao cliente via WhatsApp.
     */
    public function editMessage(WhatsAppConversation $conversation, string $messageId, string $newText): bool
    {
        if (! config('whatsapp.enabled', false)) {
            Log::debug('[WhatsApp] WHATSAPP_ENABLED=false — edição no WhatsApp pulada.', [
                'phone' => $conversation->phone,
                'messageId' => $messageId,
                'newText' => $newText,
            ]);

            return true;
        }

        return $this->provider->editMessage($conversation->phone, $messageId, $newText);
    }

    /**
     * Persiste mensagem inbound recebida via webhook.
     */
    public function recordInbound(
        WhatsAppConversation $conversation,
        IncomingMessage $message
    ): WhatsAppMessage {
        $attachmentPath = null;
        $originalFilename = null;

        if ($message->isMedia()) {
            $attachmentPath = $this->downloadAndStoreMedia($message);
            $originalFilename = $message->fileName;
        }

        return $this->whatsAppRepository->createInboundMessage(
            $conversation,
            $message,
            $attachmentPath,
            $originalFilename,
        );
    }

    /**
     * Retorna o attachment_path da mensagem inbound já persistida.
     * Usado para evitar baixar a mesma mídia duas vezes (recordInbound + handler).
     */
    public function findInboundAttachmentPath(string $providerMessageId): ?string
    {
        return $this->whatsAppRepository
            ->findInboundByProviderMessageId($providerMessageId)
            ?->attachment_path;
    }

    /**
     * Baixa uma mídia do provedor, armazena localmente e retorna o path.
     * Retorna null se download falhar.
     */
    public function downloadAndStoreMedia(IncomingMessage $message): ?string
    {
        if (! $message->isMedia() || (! $message->mediaId && ! $message->mediaUrl)) {
            return null;
        }

        try {
            $binary = $this->provider->downloadMedia($message->mediaId ?? '', $message->mediaUrl);

            if ($binary === '') {
                Log::warning('[WhatsApp] Provider retornou mídia vazia.', [
                    'messageId' => $message->messageId,
                    'mimetype' => $message->mimetype,
                ]);

                return null;
            }

            $extension = $this->resolveExtension($message->fileName, $message->mimetype ?? '');
            $filename = Str::uuid().($extension !== '' ? '.'.$extension : '');
            $path = 'whatsapp/attachments/'.$filename;

            Storage::disk('public')->put($path, $binary);

            return $path;
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] Falha ao baixar mídia.', [
                'messageId' => $message->messageId,
                'mimetype' => $message->mimetype,
                'has_mediaUrl' => $message->mediaUrl !== null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verifica idempotência: mensagem já foi processada?
     */
    public function alreadyProcessed(string $providerMessageId, ?string $body = null): bool
    {
        // Se body informado, usar chave composta para evitar falsos positivos
        // quando o provider usa o mesmo messageId para mensagens diferentes
        if ($body !== null) {
            return $this->whatsAppRepository->messageAlreadyExistsWithBody($providerMessageId, $body);
        }

        return $this->whatsAppRepository->messageAlreadyExists($providerMessageId);
    }

    /**
     * Resolve a extensão do arquivo persistido em storage:
     * preferindo a extensão do nome original quando o provedor fornece
     * (cobre formatos atípicos como .xml, .p12, .eml que não têm mime
     * estável), caindo no mapeamento por MIME e por fim em .bin.
     */
    private function resolveExtension(?string $fileName, string $mime): string
    {
        if (is_string($fileName) && $fileName !== '') {
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);

            if (is_string($ext) && $ext !== '' && preg_match('/^[A-Za-z0-9]{1,8}$/', $ext) === 1) {
                return strtolower($ext);
            }
        }

        return $this->extensionFromMime($mime);
    }

    private function extensionFromMime(string $mime): string
    {
        $mime = strtolower(trim($mime));

        return match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'gif') => 'gif',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'pdf') => 'pdf',
            str_contains($mime, 'mp4') => 'mp4',
            str_contains($mime, 'quicktime') => 'mov',
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'mpeg'), str_contains($mime, 'mp3') => 'mp3',
            str_contains($mime, 'wav') => 'wav',
            str_contains($mime, 'ogg') => 'ogg',
            str_contains($mime, 'spreadsheetml.sheet') => 'xlsx',
            str_contains($mime, 'ms-excel') => 'xls',
            str_contains($mime, 'wordprocessingml.document') => 'docx',
            str_contains($mime, 'msword') => 'doc',
            str_contains($mime, 'csv') => 'csv',
            str_contains($mime, 'text/plain') => 'txt',
            str_contains($mime, 'zip') => 'zip',
            str_contains($mime, 'xml') => 'xml',
            str_contains($mime, 'json') => 'json',
            str_contains($mime, 'rfc822'), str_contains($mime, 'eml') => 'eml',
            str_contains($mime, 'pkcs12'), str_contains($mime, 'x-pkcs12') => 'pfx',
            str_contains($mime, 'pkcs7') => 'p7s',
            default => 'bin',
        };
    }
}
