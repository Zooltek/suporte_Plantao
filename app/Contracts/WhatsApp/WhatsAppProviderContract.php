<?php

namespace App\Contracts\WhatsApp;

use App\DTO\WhatsApp\IncomingMessage;
use Illuminate\Http\Request;

/**
 * Contrato para provedores WhatsApp Business API.
 *
 * Implementações concretas: GenericWhatsAppProvider, TwilioProvider, ZenviaProvider, etc.
 * A troca de provedor não afeta a lógica de negócio (ChatBotService, WhatsAppTicketService).
 */
interface WhatsAppProviderContract
{
    /**
     * Verifica se a requisição do webhook é legítima (assinatura HMAC ou token fixo).
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Parseia a requisição do webhook em um DTO IncomingMessage.
     * Retorna null se o payload não contiver uma mensagem válida (ex: status de entrega).
     */
    public function parseIncoming(Request $request): ?IncomingMessage;

    /**
     * Envia uma mensagem de texto simples.
     *
     * @return string|false ID da mensagem enviada ou false em caso de erro
     */
    public function sendText(string $to, string $message): string|false;

    /**
     * Envia uma mídia (imagem, documento, vídeo) via WhatsApp.
     *
     * @param  string  $to  Número do destinatário
     * @param  string  $mediaPath  Caminho do arquivo no storage
     * @param  string|null  $caption  Legenda opcional
     * @param  string|null  $mimetype  Tipo MIME do arquivo
     * @param  string|null  $fileName  Nome original do arquivo (preserva no destino)
     */
    public function sendMedia(string $to, string $mediaPath, ?string $caption = null, ?string $mimetype = null, ?string $fileName = null): string|false;

    /**
     * Envia uma mensagem de áudio (voice note) via WhatsApp.
     *
     * @param  string  $to  Número do destinatário
     * @param  string  $mediaPath  Caminho do arquivo no storage
     * @param  string|null  $mimetype  Tipo MIME do arquivo
     */
    public function sendAudio(string $to, string $mediaPath, ?string $mimetype = null): string|false;

    /**
     * Exclui uma mensagem enviada no WhatsApp.
     *
     * @param  string  $to  Número do destinatário
     * @param  string  $messageId  ID da mensagem a ser excluída
     */
    public function deleteMessage(string $to, string $messageId): bool;

    /**
     * Edita uma mensagem enviada no WhatsApp.
     *
     * @param  string  $to  Número do destinatário
     * @param  string  $messageId  ID da mensagem a ser editada
     * @param  string  $newText  Novo texto da mensagem
     */
    public function editMessage(string $to, string $messageId, string $newText): bool;

    /**
     * Faz download de uma mídia retornando o conteúdo binário.
     */
    public function downloadMedia(string $mediaId, ?string $mediaUrl): string;

    /**
     * Verifica se um número possui conta ativa no WhatsApp.
     *
     * @param  string  $phone  Número em formato E.164 sem o "+" (ex: 5527999999999)
     */
    public function checkNumberExists(string $phone): bool;

    /**
     * Retorna o nome legível do provedor para logs.
     */
    public function name(): string;
}
