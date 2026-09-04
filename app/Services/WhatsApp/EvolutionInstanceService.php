<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gerencia o ciclo de vida da instância Evolution-API.
 *
 * Responsabilidades:
 *  - Consultar o estado de conexão da instância (open / connecting / close)
 *  - Buscar o QR Code para autenticação via celular
 *
 * Exclusivamente para uso administrativo — nunca exposto ao público.
 */
class EvolutionInstanceService
{
    private const BAILEYS_INTEGRATION = 'WHATSAPP-BAILEYS';

    private const WEBHOOK_EVENTS = ['MESSAGES_UPSERT', 'MESSAGES_SET'];

    private string $baseUrl;

    private string $instance;

    private string $apiKey;

    private string $webhookUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('whatsapp.api_url', ''), '/');
        $this->instance = (string) config('whatsapp.evolution_instance', '');
        $this->apiKey = (string) config('whatsapp.evolution_api_key', '');
        $this->webhookUrl = $this->resolveWebhookUrl();
    }

    /**
     * Verifica se o provedor está configurado com os dados mínimos necessários.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->instance) && ! empty($this->apiKey);
    }

    /**
     * Retorna o estado de conexão atual da instância.
     *
     * Evolution-API v2: GET /instance/connectionState/{instance}
     * Resposta: { "instance": { "state": "open" | "connecting" | "close" } }
     *
     * @return array{state: string, instance: string}
     *
     * @throws \RuntimeException
     */
    public function connectionState(): array
    {
        $this->assertConfigured();

        try {
            $response = $this->request('get', "/instance/connectionState/{$this->instance}", 10);

            if ($response->status() === 404 && $this->ensureInstanceExists()) {
                return ['state' => 'close', 'instance' => $this->instance];
            }

            if (! $response->successful()) {
                Log::warning('[EvolutionInstance] Falha ao consultar connectionState.', [
                    'status' => $response->status(),
                    'instance' => $this->instance,
                ]);

                return ['state' => 'unknown', 'instance' => $this->instance];
            }

            $state = $response->json('instance.state')
                ?? $response->json('state')
                ?? 'unknown';

            $this->ensureWebhookConfigured(restartWhenChanged: $state === 'open');

            return ['state' => $state, 'instance' => $this->instance];

        } catch (\Throwable $e) {
            Log::error('[EvolutionInstance] Exceção ao consultar connectionState.', [
                'error' => $e->getMessage(),
                'instance' => $this->instance,
            ]);

            return ['state' => 'unknown', 'instance' => $this->instance];
        }
    }

    /**
     * Busca o QR Code para autenticação da instância.
     *
     * Evolution-API v2: GET /instance/connect/{instance}
     * Resposta: { "code": "QR_STRING", "base64": "data:image/png;base64,..." }
     *
     * Só disponível quando a instância está no estado 'close' ou 'connecting'.
     * Quando já conectada (state=open), retorna erro informativo.
     *
     * @return array{
     *     status: 'ready'|'pending',
     *     base64: string,
     *     code: string,
     *     message?: string,
     *     state?: string
     * }
     *
     * @throws \RuntimeException
     */
    public function fetchQrCode(): array
    {
        $this->assertConfigured();

        try {
            // Verifica o estado atual antes de solicitar o QR Code.
            // Se já estiver conectado (open), não há QR a gerar.
            $stateData = $this->connectionState();
            if (($stateData['state'] ?? '') === 'open') {
                return [
                    'status' => 'connected',
                    'state' => 'open',
                    'base64' => '',
                    'code' => '',
                    'message' => 'A instância já está conectada ao WhatsApp. Nenhum QR Code é necessário.',
                ];
            }

            $response = $this->request('get', "/instance/connect/{$this->instance}", 30);

            if ($response->status() === 404 && $this->ensureInstanceExists()) {
                return [
                    'status' => 'pending',
                    'base64' => '',
                    'code' => '',
                    'state' => 'initializing',
                    'message' => 'A instância do WhatsApp foi recriada e está inicializando. Gere o QR Code novamente em instantes.',
                ];
            }

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "[EvolutionInstance] Falha ao buscar QR Code: HTTP {$response->status()}"
                );
            }

            $body = $response->json();
            $base64 = $body['base64'] ?? null;
            $code = $body['code'] ?? null;

            if (empty($base64) && empty($code)) {
                // Respostas sem QR Code são transitórias:
                // - {"count":0} / null → Baileys ainda não gerou o primeiro QR Code (inicialização)
                // - {"count":1+} → QR anterior expirou, Baileys está gerando outro
                // - {"pairingCode":null,...} → pairing code não solicitado, aguardando QR
                // Em todos os casos, basta o frontend tentar novamente em alguns segundos.
                Log::debug('[EvolutionInstance] Resposta sem QR Code (transitório).', [
                    'instance' => $this->instance,
                    'response' => $body,
                ]);

                return [
                    'status' => 'pending',
                    'base64' => '',
                    'code' => '',
                    'state' => 'connecting',
                    'message' => 'O QR Code está sendo preparado. Aguarde alguns segundos e tente novamente.',
                ];
            }

            // Garante que o base64 está no formato data URI
            if ($base64 && ! str_starts_with($base64, 'data:')) {
                $base64 = 'data:image/png;base64,'.$base64;
            }

            return [
                'status' => 'ready',
                'base64' => $base64 ?? '',
                'code' => $code ?? '',
            ];

        } catch (ConnectionException $e) {
            return [
                'status' => 'pending',
                'base64' => '',
                'code' => '',
                'state' => 'booting',
                'message' => 'A Evolution-API está inicializando no servidor. Aguarde alguns instantes e tente novamente.',
            ];
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "[EvolutionInstance] Exceção ao buscar QR Code: {$e->getMessage()}"
            );
        }
    }

    /**
     * Desconecta a sessão atual da instância (logout).
     *
     * Evolution-API v2: DELETE /instance/logout/{instance}
     *
     * Útil quando a sessão do Baileys está corrompida e o QR Code
     * não consegue autenticar o dispositivo.
     *
     * @return array{success: bool, message: string}
     *
     * @throws \RuntimeException
     */
    public function logout(): array
    {
        $this->assertConfigured();

        try {
            $response = Http::withHeaders(['apikey' => $this->apiKey])
                ->timeout(15)
                ->delete("{$this->baseUrl}/instance/logout/{$this->instance}");

            // 2xx = logout OK, 404 = instância não existe, 400 = já desconectada.
            // Em todos esses cenários, o estado final é "desconectado" — sucesso.
            $alreadyDisconnected = $response->status() === 400
                && str_contains((string) ($response->json('response.message.0') ?? ''), 'not connected');

            if ($response->successful() || $response->status() === 404 || $alreadyDisconnected) {
                Log::info('[EvolutionInstance] Logout da instância realizado.', [
                    'instance' => $this->instance,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Sessão encerrada. Gere um novo QR Code para reconectar.',
                ];
            }

            Log::warning('[EvolutionInstance] Falha ao fazer logout da instância.', [
                'instance' => $this->instance,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            // Tenta recriar a instância como fallback para limpar sessão corrompida.
            if ($this->recreateInstance()) {
                return [
                    'success' => true,
                    'message' => 'Instância recriada. Aguarde alguns segundos e gere um novo QR Code.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Não foi possível encerrar a sessão. Tente novamente.',
            ];
        } catch (\Throwable $e) {
            Log::error('[EvolutionInstance] Exceção ao fazer logout.', [
                'instance' => $this->instance,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao conectar com a Evolution-API: '.$e->getMessage(),
            ];
        }
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException(
                '[EvolutionInstance] Provedor não configurado (api_url, instance ou api_key ausentes).'
            );
        }
    }

    private function recreateInstance(): bool
    {
        try {
            // Deleta a instância para limpar sessão corrompida do Baileys.
            Http::withHeaders(['apikey' => $this->apiKey])
                ->timeout(15)
                ->delete("{$this->baseUrl}/instance/delete/{$this->instance}");

            $createResponse = $this->request('post', '/instance/create', 15, [
                'instanceName' => $this->instance,
                'integration' => self::BAILEYS_INTEGRATION,
                'qrcode' => false,
            ]);

            if (in_array($createResponse->status(), [200, 201], true)) {
                Log::info('[EvolutionInstance] Instância recriada via reset manual.', [
                    'instance' => $this->instance,
                ]);

                $this->ensureWebhookConfigured();

                return true;
            }

            Log::warning('[EvolutionInstance] Falha ao recriar instância no reset.', [
                'instance' => $this->instance,
                'status' => $createResponse->status(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('[EvolutionInstance] Exceção ao recriar instância no reset.', [
                'instance' => $this->instance,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function ensureInstanceExists(): bool
    {
        try {
            $instancesResponse = $this->request('get', '/instance/fetchInstances', 10);

            if ($instancesResponse->successful() && $this->instanceExists($instancesResponse->json())) {
                return true;
            }

            $createResponse = $this->request('post', '/instance/create', 15, [
                'instanceName' => $this->instance,
                'integration' => self::BAILEYS_INTEGRATION,
                'qrcode' => false,
            ]);

            if (! in_array($createResponse->status(), [200, 201], true)) {
                Log::warning('[EvolutionInstance] Falha ao recriar instância ausente.', [
                    'status' => $createResponse->status(),
                    'instance' => $this->instance,
                    'response' => $createResponse->json(),
                ]);

                return false;
            }

            Log::info('[EvolutionInstance] Instância recriada automaticamente.', [
                'instance' => $this->instance,
            ]);

            $this->ensureWebhookConfigured();

            return true;
        } catch (\Throwable $e) {
            Log::warning('[EvolutionInstance] Não foi possível recriar instância ausente.', [
                'instance' => $this->instance,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function ensureWebhookConfigured(bool $restartWhenChanged = false): bool
    {
        if ($this->webhookUrl === '') {
            Log::warning('[EvolutionInstance] Webhook URL ausente; configuração do webhook ignorada.', [
                'instance' => $this->instance,
            ]);

            return false;
        }

        try {
            $response = $this->request('get', "/webhook/find/{$this->instance}", 10);

            if ($response->successful() && $this->webhookMatches($response->json())) {
                return true;
            }

            $setResponse = $this->request('post', "/webhook/set/{$this->instance}", 15, [
                'webhook' => [
                    'enabled' => true,
                    'url' => $this->webhookUrl,
                    'headers' => ['apikey' => $this->apiKey],
                    'byEvents' => false,
                    'base64' => false,
                    'events' => self::WEBHOOK_EVENTS,
                ],
            ]);

            if (! in_array($setResponse->status(), [200, 201], true)) {
                Log::warning('[EvolutionInstance] Falha ao configurar webhook da instância.', [
                    'status' => $setResponse->status(),
                    'instance' => $this->instance,
                    'response' => $setResponse->json(),
                ]);

                return false;
            }

            Log::info('[EvolutionInstance] Webhook configurado para a instância.', [
                'instance' => $this->instance,
                'url' => $this->webhookUrl,
            ]);

            if ($restartWhenChanged) {
                $this->restartInstanceAfterWebhookChange();
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('[EvolutionInstance] Não foi possível configurar o webhook da instância.', [
                'instance' => $this->instance,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function instanceExists(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        foreach ($payload as $instance) {
            if (! is_array($instance)) {
                continue;
            }

            $name = $instance['name']
                ?? $instance['instance']['instanceName']
                ?? $instance['instanceName']
                ?? null;

            if ($name === $this->instance) {
                return true;
            }
        }

        return false;
    }

    private function webhookMatches(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        $url = $payload['url']
            ?? $payload['webhook']['url']
            ?? null;
        $enabled = $payload['enabled']
            ?? $payload['webhook']['enabled']
            ?? false;
        $headers = $payload['headers']
            ?? $payload['webhook']['headers']
            ?? [];
        $events = $payload['events']
            ?? $payload['webhook']['events']
            ?? [];
        $byEvents = $payload['webhookByEvents']
            ?? $payload['byEvents']
            ?? $payload['webhook']['byEvents']
            ?? null;
        $base64 = $payload['webhookBase64']
            ?? $payload['base64']
            ?? $payload['webhook']['base64']
            ?? null;

        return $enabled === true
            && $url === $this->webhookUrl
            && ($headers['apikey'] ?? null) === $this->apiKey
            && empty(array_diff(self::WEBHOOK_EVENTS, $events))
            && $byEvents === false
            && $base64 === false;
    }

    private function restartInstanceAfterWebhookChange(): void
    {
        try {
            $response = $this->request('post', "/instance/restart/{$this->instance}", 20);

            if (! $response->successful()) {
                Log::warning('[EvolutionInstance] Webhook atualizado, mas restart da instância falhou.', [
                    'instance' => $this->instance,
                    'status' => $response->status(),
                ]);

                return;
            }

            Log::info('[EvolutionInstance] Instância reiniciada após alteração do webhook.', [
                'instance' => $this->instance,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[EvolutionInstance] Não foi possível reiniciar a instância após alterar webhook.', [
                'instance' => $this->instance,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveWebhookUrl(): string
    {
        $configuredUrl = trim((string) config('whatsapp.webhook_url', ''));

        if ($configuredUrl !== '') {
            return $configuredUrl;
        }

        $appUrl = rtrim((string) config('app.url', ''), '/');

        return $appUrl !== '' ? "{$appUrl}/api/webhook/whatsapp" : '';
    }

    private function request(string $method, string $path, int $timeout, array $data = []): \Illuminate\Http\Client\Response
    {
        $request = Http::withHeaders(['apikey' => $this->apiKey])
            ->timeout($timeout);

        $url = "{$this->baseUrl}{$path}";

        return match (strtolower($method)) {
            'post' => $request->post($url, $data),
            default => $request->get($url),
        };
    }
}
