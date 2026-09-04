<?php

namespace App\Services\WhatsApp\Providers;

use App\Contracts\WhatsApp\WhatsAppProviderContract;
use App\DTO\WhatsApp\IncomingMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provider genérico compatível com o formato da Meta Cloud API.
 * A maioria dos provedores parceiros (Zenvia, Gupshup, etc.) suportam
 * este formato ou oferecem adaptador para ele.
 *
 * Formato de webhook esperado:
 * {
 *   "from": "5527999999999",
 *   "type": "text|image|document",
 *   "message_id": "...",
 *   "timestamp": "...",
 *   "text": { "body": "..." },           // para type=text
 *   "media": { "id": "...", "url": "...", "mime_type": "..." }  // para mídias
 * }
 */
class GenericWhatsAppProvider implements WhatsAppProviderContract
{
    public function verifyWebhook(Request $request): bool
    {
        $secret = config('whatsapp.webhook_secret');

        if (empty($secret)) {
            return true; // Sem segredo configurado: aceita (apenas em dev)
        }

        $signature = $request->header('X-Hub-Signature-256')
            ?? $request->header('X-Webhook-Signature')
            ?? '';

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    public function parseIncoming(Request $request): ?IncomingMessage
    {
        $payload = $request->json()->all();

        // Ignora payloads de status de entrega
        if (isset($payload['status']) || ! isset($payload['from'])) {
            return null;
        }

        $type = $payload['type'] ?? 'text';
        $body = $payload['text']['body'] ?? '';
        $media = $payload['media'] ?? [];

        return new IncomingMessage(
            from: $payload['from'],
            body: $body,
            type: $type,
            mediaUrl: $media['url'] ?? null,
            mediaId: $media['id'] ?? null,
            mimetype: $media['mime_type'] ?? null,
            timestamp: (string) ($payload['timestamp'] ?? now()->timestamp),
            messageId: $payload['message_id'] ?? uniqid('msg_', true),
        );
    }

    public function sendText(string $to, string $message): string|false
    {
        $apiUrl = config('whatsapp.api_url');
        $apiToken = config('whatsapp.api_token');
        $from = config('whatsapp.from_number');

        if (empty($apiUrl) || empty($apiToken)) {
            Log::warning('[WhatsApp] sendText ignorado: API não configurada.', compact('to'));

            return false;
        }

        try {
            $response = Http::withToken($apiToken)
                ->timeout(10)
                ->post($apiUrl.'/messages', [
                    'from' => $from,
                    'to' => $to,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if (! $response->successful()) {
                Log::error('[WhatsApp] Falha ao enviar mensagem.', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return $response->json('messages.0.id') ?? $response->json('id') ?? false;
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] Exceção ao enviar mensagem.', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * O envio de mídia no formato Meta Cloud API exige upload prévio do
     * arquivo para obter um media id — fluxo não modelado neste provedor
     * genérico. Use o EvolutionApiProvider para envio de mídia.
     */
    public function sendMedia(string $to, string $mediaPath, ?string $caption = null, ?string $mimetype = null, ?string $fileName = null): string|false
    {
        Log::warning('[WhatsApp] sendMedia não suportado pelo provedor genérico.', compact('to'));

        return false;
    }

    /**
     * Envio de áudio não suportado pelo provedor genérico — ver sendMedia().
     */
    public function sendAudio(string $to, string $mediaPath, ?string $mimetype = null): string|false
    {
        Log::warning('[WhatsApp] sendAudio não suportado pelo provedor genérico.', compact('to'));

        return false;
    }

    /**
     * A Meta Cloud API não expõe exclusão de mensagens já enviadas.
     */
    public function deleteMessage(string $to, string $messageId): bool
    {
        Log::warning('[WhatsApp] deleteMessage não suportado pelo provedor genérico.', compact('to', 'messageId'));

        return false;
    }

    /**
     * A Meta Cloud API não expõe edição de mensagens já enviadas.
     */
    public function editMessage(string $to, string $messageId, string $newText): bool
    {
        Log::warning('[WhatsApp] editMessage não suportado pelo provedor genérico.', compact('to', 'messageId'));

        return false;
    }

    public function downloadMedia(string $mediaId, ?string $mediaUrl): string
    {
        $apiUrl = config('whatsapp.api_url');
        $apiToken = config('whatsapp.api_token');

        $url = $mediaUrl ?? ($apiUrl.'/media/'.$mediaId);

        $response = Http::withToken($apiToken)
            ->timeout(30)
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "[WhatsApp] Falha ao baixar mídia {$mediaId}: HTTP {$response->status()}"
            );
        }

        return $response->body();
    }

    /**
     * O formato genérico (Meta Cloud API) não expõe verificação de número
     * fora do envio. Assume número válido e delega a checagem ao envio real.
     */
    public function checkNumberExists(string $phone): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'generic';
    }
}
