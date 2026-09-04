<?php

namespace App\Http\Controllers\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppProviderContract;
use App\Http\Controllers\Controller;
use App\Jobs\WhatsApp\ProcessIncomingMessageJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recebe eventos do provedor WhatsApp via webhook.
 *
 * Responsabilidades:
 *  1. Verificar assinatura da requisição (segurança)
 *  2. Parsear o payload em IncomingMessage DTO
 *  3. Despachar o processamento para fila (resposta rápida ao provedor)
 *  4. Responder 200 OK imediatamente (provedores exigem resposta < 5s)
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppProviderContract $provider,
    ) {}

    /**
     * Verificação de endpoint (challenge) — alguns provedores fazem GET antes de ativar.
     */
    public function verify(Request $request): Response|JsonResponse
    {
        $challenge = $request->query('hub_challenge');
        $secret    = config('whatsapp.webhook_secret');

        // Sem segredo configurado: aceita qualquer challenge (desenvolvimento local)
        if (!empty($secret)) {
            $token = $request->query('hub_verify_token');

            if ($token !== $secret) {
                Log::warning('[WhatsApp Webhook] Falha na verificação de token.', [
                    'ip' => $request->ip(),
                ]);
                return response('Forbidden', 403);
            }
        }

        return response((string) $challenge, 200);
    }

    /**
     * Recebe mensagens inbound do provedor.
     */
    public function receive(Request $request): JsonResponse
    {
        // 1. Verificar autenticidade conforme o provedor configurado
        if (!$this->provider->verifyWebhook($request)) {
            Log::warning('[WhatsApp Webhook] Autenticação inválida.', [
                'ip'           => $request->ip(),
                'content_type' => $request->header('Content-Type'),
                'provider'     => $this->provider->name(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 2. Parsear payload
        $message = $this->provider->parseIncoming($request);

        if ($message === null) {
            // Pode ser um status de entrega ou evento que não é mensagem — aceitar sem processar
            Log::info('[WhatsApp Webhook] Payload ignorado.', $this->ignoredPayloadMetadata($request));

            return response()->json(['status' => 'ignored'], 200);
        }

        // 3. Despachar para processamento assíncrono
        ProcessIncomingMessageJob::dispatch($message);

        Log::info('[WhatsApp Webhook] Mensagem enfileirada.', [
            'from'      => $message->from,
            'type'      => $message->type,
            'messageId' => $message->messageId,
        ]);

        // 4. Responder imediatamente ao provedor
        return response()->json(['status' => 'queued'], 200);
    }

    /**
     * Metadados seguros para investigar payloads aceitos, mas não processados.
     */
    private function ignoredPayloadMetadata(Request $request): array
    {
        if (is_callable([$this->provider, 'ignoredPayloadMetadata'])) {
            return $this->provider->ignoredPayloadMetadata($request);
        }

        $payload = $request->json()->all();

        return [
            'provider' => $this->provider->name(),
            'event' => $payload['event'] ?? null,
        ];
    }
}
