<?php

namespace App\Services\WhatsApp\Providers;

use App\Contracts\WhatsApp\WhatsAppProviderContract;
use App\DTO\WhatsApp\IncomingMessage;
use App\Support\Phone\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provedor Evolution-API v2.
 *
 * Implementa o contrato WhatsAppProviderContract para o formato de webhook
 * e a API REST da Evolution-API, substituindo o GenericWhatsAppProvider
 * sem alterar nenhuma camada de domínio (ChatBotService, WhatsAppService,
 * ProcessIncomingMessageJob).
 *
 * Formato de webhook esperado (event=messages.upsert):
 * {
 *   "event": "messages.upsert",
 *   "instance": "amura-prod",
 *   "data": {
 *     "key": { "remoteJid": "5527...@s.whatsapp.net", "fromMe": false, "id": "MSG_ID" },
 *     "pushName": "Nome Contato",
 *     "message": {
 *       "conversation": "Texto simples"           // texto
 *       "imageMessage":    { "mimetype": "...", "mediaKey": "..." }  // imagem
 *       "documentMessage": { "mimetype": "...", "mediaKey": "..." }  // documento
 *       "audioMessage":    { "mimetype": "...", "mediaKey": "..." }  // áudio
 *       "videoMessage":    { "mimetype": "...", "mediaKey": "..." }  // vídeo
 *     },
 *     "messageType": "conversation",
 *     "messageTimestamp": 1710000000
 *   }
 * }
 *
 * Nota sobre downloadMedia():
 * A Evolution-API não expõe um endpoint GET /media/{id}. Ela exige o objeto
 * completo da mensagem via POST /chat/getBase64FromMediaMessage/{instance}.
 * Para manter o contrato inalterado, o parseIncoming() serializa o sub-objeto
 * de mídia como JSON e o armazena em IncomingMessage::$mediaId. O
 * downloadMedia() detecta isso, desserializa e faz a chamada correta.
 */
class EvolutionApiProvider implements WhatsAppProviderContract
{
    private const MESSAGES_SET_RECENT_WINDOW_MINUTES = 10;

    // -------------------------------------------------------------------------
    // WhatsAppProviderContract
    // -------------------------------------------------------------------------

    /**
     * Verifica autenticidade do webhook comparando a credencial recebida
     * com a chave configurada em whatsapp.evolution_api_key.
     *
     * Chave ausente em ambientes não-locais: rejeita (falha segura).
     * Em ambiente local/testing, aceita sem chave para facilitar desenvolvimento.
     *
     * A Evolution-API envia headers no webhook da instância. O webhook global
     * usado no Docker local não suporta headers, então só em local/testing e
     * de rede privada aceitamos apikey na query string.
     */
    public function verifyWebhook(Request $request): bool
    {
        $expectedKey = config('whatsapp.evolution_api_key');

        if (empty($expectedKey)) {
            // Só permissivo em local/testing — rejeita em staging/produção
            return app()->environment('local', 'testing');
        }

        $receivedKey = $this->webhookApiKey($request);

        return hash_equals($expectedKey, $receivedKey);
    }

    /**
     * Parseia o payload da Evolution-API v2 em um IncomingMessage DTO.
     *
     * Retorna null quando:
     *   - O evento não é 'messages.upsert'
     *   - A mensagem é do próprio bot (fromMe=true), de grupo ou status
     *   - O payload não contém dados de mensagem reconhecíveis
     */
    public function parseIncoming(Request $request): ?IncomingMessage
    {
        $payload = $request->json()->all();
        $event = $payload['event'] ?? '';

        if ($event === 'messages.set') {
            return $this->parseMessagesSet($payload);
        }

        // Só processa eventos de mensagens recebidas
        if ($event !== 'messages.upsert') {
            return null;
        }

        $data = $payload['data'] ?? [];
        if (! is_array($data)) {
            return null;
        }

        if (! $this->isDirectIncomingMessageData($data)) {
            return null;
        }

        return $this->parseMessageData($data);
    }

    private function parseMessageData(array $data, bool $logMissingSender = true): ?IncomingMessage
    {
        $key = $data['key'] ?? [];

        // Ignora mensagens enviadas pelo próprio bot
        if (($key['fromMe'] ?? false) === true) {
            return null;
        }

        $messageId = $key['id'] ?? uniqid('evo_', true);
        $timestamp = (string) ($data['messageTimestamp'] ?? now()->timestamp);
        $messageType = $data['messageType'] ?? 'conversation';
        $message = $data['message'] ?? [];

        $from = $this->resolveSenderPhone($key);

        if (empty($from)) {
            if ($logMissingSender) {
                Log::warning('[Evolution] Mensagem ignorada: remetente sem telefone roteável.', [
                    'messageId' => $messageId,
                    'jid_type' => $this->jidType((string) ($key['remoteJid'] ?? '')),
                ]);
            }

            return null;
        }

        // Mensagem de texto simples
        if ($messageType === 'conversation' || isset($message['conversation'])) {
            return new IncomingMessage(
                from: $from,
                body: $message['conversation'] ?? '',
                type: 'text',
                mediaUrl: null,
                mediaId: null,
                mimetype: null,
                timestamp: $timestamp,
                messageId: $messageId,
            );
        }

        // Mensagem de texto estendida (extendedTextMessage)
        if ($messageType === 'extendedTextMessage' || isset($message['extendedTextMessage'])) {
            $body = $message['extendedTextMessage']['text'] ?? '';

            return new IncomingMessage(
                from: $from,
                body: $body,
                type: 'text',
                mediaUrl: null,
                mediaId: null,
                mimetype: null,
                timestamp: $timestamp,
                messageId: $messageId,
            );
        }

        // Mensagens de mídia
        $mediaTypeMap = [
            'imageMessage' => 'image',
            'documentMessage' => 'document',
            'audioMessage' => 'audio',
            'videoMessage' => 'video',
            'stickerMessage' => 'image',
        ];

        // Mensagem de contato (vCard)
        if ($messageType === 'contactMessage' || isset($message['contactMessage'])) {
            $contact = $message['contactMessage'] ?? [];
            $vcard = $contact['vcard'] ?? '';
            $displayName = $contact['displayName'] ?? 'Contato';

            return new IncomingMessage(
                from: $from,
                body: json_encode(['displayName' => $displayName, 'vcard' => $vcard]),
                type: 'contact',
                mediaUrl: null,
                mediaId: null,
                mimetype: 'text/vcard',
                timestamp: $timestamp,
                messageId: $messageId,
                fileName: "{$displayName}.vcf",
            );
        }

        // Múltiplos contatos
        if ($messageType === 'contactsArrayMessage' || isset($message['contactsArrayMessage'])) {
            $contacts = $message['contactsArrayMessage']['contacts'] ?? [];
            $first = $contacts[0] ?? [];
            $displayName = $first['displayName'] ?? 'Contatos';

            return new IncomingMessage(
                from: $from,
                body: json_encode($contacts),
                type: 'contact',
                mediaUrl: null,
                mediaId: null,
                mimetype: 'text/vcard',
                timestamp: $timestamp,
                messageId: $messageId,
                fileName: "contatos.vcf",
            );
        }

        // Localização
        if ($messageType === 'locationMessage' || isset($message['locationMessage'])) {
            $loc = $message['locationMessage'] ?? [];
            $lat = $loc['degreesLatitude'] ?? 0;
            $lng = $loc['degreesLongitude'] ?? 0;
            $name = $loc['name'] ?? 'Localização';
            $address = $loc['address'] ?? '';
            $mapsUrl = "https://maps.google.com/?q={$lat},{$lng}";

            return new IncomingMessage(
                from: $from,
                body: "📍 {$name} {$address}\n{$mapsUrl}",
                type: 'text',
                mediaUrl: null,
                mediaId: null,
                mimetype: null,
                timestamp: $timestamp,
                messageId: $messageId,
            );
        }

        foreach ($mediaTypeMap as $evoKey => $normalizedType) {
            if ($messageType === $evoKey || isset($message[$evoKey])) {
                $mediaObject = $message[$evoKey] ?? [];
                $mimetype = $mediaObject['mimetype'] ?? null;

                // Valida MIME type contra whitelist — rejeita tipos desconhecidos
                if (! $this->isAllowedMimeType($mimetype)) {
                    Log::warning('[Evolution] MIME type bloqueado.', [
                        'from' => $from,
                        'mimetype' => $mimetype,
                        'type' => $evoKey,
                    ]);

                    return null;
                }

                // A Evolution v2.3.7 resolve a mídia via POST
                // /chat/getBase64FromMediaMessage. Sem a key completa
                // (remoteJid + fromMe + id) o endpoint responde 400/404
                // silenciosamente. E sem o sub-objeto de mídia completo
                // (mediaKey, url, fileEncSha256...) a Evolution depende de
                // localizar a mensagem no próprio store para descriptografar
                // — race possível pós-webhook. Serializa ambos.
                $remoteJid = (string) ($key['remoteJid'] ?? '');
                $fromMe = (bool) ($key['fromMe'] ?? false);
                $fileName = isset($mediaObject['fileName']) && is_string($mediaObject['fileName'])
                    ? trim($mediaObject['fileName'])
                    : null;

                return new IncomingMessage(
                    from: $from,
                    body: '',
                    type: $normalizedType,
                    mediaUrl: $mediaObject['url'] ?? null,
                    mediaId: json_encode([
                        '_evo_message' => [
                            'key' => array_filter([
                                'id' => $messageId,
                                'remoteJid' => $remoteJid !== '' ? $remoteJid : null,
                                'fromMe' => $fromMe,
                            ], static fn ($value) => $value !== null),
                            'message' => [
                                $evoKey => $mediaObject,
                            ],
                        ],
                    ]),
                    mimetype: $mimetype,
                    timestamp: $timestamp,
                    messageId: $messageId,
                    fileName: $fileName !== '' ? $fileName : null,
                );
            }
        }

        // Tipo desconhecido — ignora sem processar
        Log::debug('[Evolution] Tipo de mensagem não mapeado.', [
            'messageType' => $messageType,
            'from' => $from,
        ]);

        return null;
    }

    /**
     * Envia mensagem de texto via Evolution-API.
     *
     * Endpoint: POST {api_url}/message/sendText/{instance}
     * Header:   apikey: {evolution_api_key}
     * Body:     { "number": "5527...", "text": "..." }
     */
    public function sendText(string $to, string $message): string|false
    {
        $baseUrl = config('whatsapp.api_url');
        $instance = config('whatsapp.evolution_instance');
        $apiKey = config('whatsapp.evolution_api_key');

        if (empty($baseUrl) || empty($instance) || empty($apiKey)) {
            Log::warning('[Evolution] sendText ignorado: provedor não configurado.', compact('to'));

            return false;
        }

        $url = rtrim($baseUrl, '/').'/message/sendText/'.$instance;

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])
                ->timeout(10)
                ->retry(2, 500)
                ->post($url, [
                    'number' => $to,
                    'text' => $message,
                    'delay' => 1200,
                ]);

            if (! $response->successful()) {
                Log::error('[Evolution] Falha ao enviar mensagem.', [
                    'to' => $to,
                    'status' => $response->status(),
                ]);

                return false;
            }

            $responseData = $response->json();
            $messageId = $responseData['key']['id'] ?? null;

            if (! $messageId) {
                Log::warning('[Evolution] sendText não retornou messageId.', [
                    'to' => $to,
                    'body' => $response->body(),
                ]);
            }

            return $messageId ?: true;

        } catch (\Throwable $e) {
            Log::error('[Evolution] Exceção ao enviar mensagem.', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verifica se um número possui conta ativa no WhatsApp.
     *
     * Endpoint: POST {api_url}/chat/whatsappNumbers/{instance}
     * Header:   apikey: {evolution_api_key}
     * Body:     { "numbers": ["5527..."] }
     *
     * Falha de comunicação ou provedor não configurado retorna false
     * (falha segura: o chamador decide se bloqueia ou segue).
     */
    public function checkNumberExists(string $phone): bool
    {
        $baseUrl = config('whatsapp.api_url');
        $instance = config('whatsapp.evolution_instance');
        $apiKey = config('whatsapp.evolution_api_key');

        if (empty($baseUrl) || empty($instance) || empty($apiKey)) {
            Log::warning('[Evolution] checkNumberExists ignorado: provedor não configurado.', compact('phone'));

            return false;
        }

        $url = rtrim($baseUrl, '/').'/chat/whatsappNumbers/'.$instance;

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])
                ->timeout(10)
                ->retry(2, 500)
                ->post($url, ['numbers' => [$phone]]);

            if (! $response->successful()) {
                Log::error('[Evolution] Falha ao verificar número.', [
                    'phone' => $phone,
                    'status' => $response->status(),
                ]);

                return false;
            }

            foreach ($response->json() ?? [] as $entry) {
                if (($entry['exists'] ?? false) === true) {
                    return true;
                }
            }

            return false;

        } catch (\Throwable $e) {
            Log::error('[Evolution] Exceção ao verificar número.', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia uma mídia (imagem, documento) via Evolution-API.
     *
     * Endpoint: POST {api_url}/message/sendMedia/{instance}
     * Header:   apikey: {evolution_api_key}
     * Body:     { "number": "5527...", "media": "base64...", "caption": "..." }
     */
    public function sendMedia(string $to, string $mediaPath, ?string $caption = null, ?string $mimetype = null, ?string $fileName = null): string|false
    {
        $baseUrl = config('whatsapp.api_url');
        $instance = config('whatsapp.evolution_instance');
        $apiKey = config('whatsapp.evolution_api_key');

        if (empty($baseUrl) || empty($instance) || empty($apiKey)) {
            Log::warning('[Evolution] sendMedia ignorado: provedor não configurado.', compact('to'));

            return false;
        }

        $fullPath = storage_path('app/public/'.$mediaPath);

        if (! file_exists($fullPath)) {
            Log::error('[Evolution] Arquivo não encontrado.', ['path' => $mediaPath]);

            return false;
        }

        $fileData = base64_encode(file_get_contents($fullPath));
        $mediatype = $this->resolveMediatype($mimetype);
        $resolvedFileName = $this->sanitizeFileName($fileName) ?? basename($mediaPath);

        $body = [
            'number' => $to,
            'media' => $fileData,
            'mediatype' => $mediatype,
            'fileName' => $resolvedFileName,
        ];

        if (! empty($caption)) {
            $body['caption'] = $caption;
        }

        $url = rtrim($baseUrl, '/').'/message/sendMedia/'.$instance;

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])
                ->timeout(30)
                ->retry(2, 500)
                ->post($url, $body);

            if (! $response->successful()) {
                Log::error('[Evolution] Falha ao enviar mídia.', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $responseData = $response->json();
            $messageId = $responseData['key']['id'] ?? null;

            if (! $messageId) {
                Log::warning('[Evolution] sendMedia não retornou messageId.', [
                    'to' => $to,
                    'body' => $response->body(),
                ]);
            }

            return $messageId ?: true;

        } catch (\Throwable $e) {
            Log::error('[Evolution] Exceção ao enviar mídia.', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia uma mensagem de áudio (voice note) via Evolution-API.
     */
    public function sendAudio(string $to, string $mediaPath, ?string $mimetype = null): string|false
    {
        $baseUrl = config('whatsapp.api_url');
        $instance = config('whatsapp.evolution_instance');
        $apiKey = config('whatsapp.evolution_api_key');

        if (empty($baseUrl) || empty($instance) || empty($apiKey)) {
            Log::warning('[Evolution] sendAudio ignorado: provedor não configurado.', compact('to'));

            return false;
        }

        $fullPath = storage_path('app/public/'.$mediaPath);

        if (! file_exists($fullPath)) {
            Log::error('[Evolution] Arquivo de áudio não encontrado.', ['path' => $mediaPath]);

            return false;
        }

        $fileData = base64_encode(file_get_contents($fullPath));
        $filename = basename($mediaPath);
        $fileSize = filesize($fullPath);

        Log::debug('[Evolution] Enviando áudio via sendWhatsAppAudio', [
            'to' => $to,
            'filename' => $filename,
            'size' => $fileSize,
            'base64_length' => strlen($fileData),
        ]);

        $url = rtrim($baseUrl, '/').'/message/sendWhatsAppAudio/'.$instance;

        $body = [
            'number' => $to,
            'audio' => $fileData,
            'encoding' => true,
        ];

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])
                ->timeout(30)
                ->retry(2, 500)
                ->post($url, $body);

            if (! $response->successful()) {
                Log::error('[Evolution] Falha ao enviar áudio.', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $responseData = $response->json();
            $messageId = $responseData['key']['id'] ?? null;

            Log::debug('[Evolution] Áudio enviado com sucesso', [
                'messageId' => $messageId,
                'response' => $responseData,
            ]);

            return $messageId ?: true;

        } catch (\Throwable $e) {
            Log::error('[Evolution] Exceção ao enviar áudio.', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Higieniza o nome do arquivo para uso no payload da Evolution:
     * remove componentes de caminho e caracteres de controle, mantendo
     * o nome original quando seguro.
     */
    private function sanitizeFileName(?string $fileName): ?string
    {
        if ($fileName === null) {
            return null;
        }

        $trimmed = trim($fileName);

        if ($trimmed === '') {
            return null;
        }

        $basename = basename(str_replace(['\\', '/'], '/', $trimmed));
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $basename);

        return $clean !== '' && $clean !== '.' && $clean !== '..' ? $clean : null;
    }

    /**
     * Resolve o tipo de mídia para o endpoint da Evolution API.
     */
    private function resolveMediatype(?string $mimetype): string
    {
        if (! $mimetype) {
            return 'image';
        }

        return match (true) {
            str_contains($mimetype, 'image/') => 'image',
            str_contains($mimetype, 'video/') => 'video',
            str_contains($mimetype, 'audio/') => 'audio',
            str_contains($mimetype, 'application/pdf') => 'document',
            default => 'document',
        };
    }

    /**
     * Exclui uma mensagem enviada via Evolution-API.
     *
     * Endpoint: POST {api_url}/message/delete/{instance}
     * Header:   apikey: {evolution_api_key}
     * Body:     { "key": { "id": "MESSAGE_ID", "remoteJid": "NUMBER@s.whatsapp.net" } }
     */
    public function deleteMessage(string $to, string $messageId): bool
    {
        $baseUrl = config('whatsapp.api_url');
        $instance = config('whatsapp.evolution_instance');
        $apiKey = config('whatsapp.evolution_api_key');

        if (empty($baseUrl) || empty($instance) || empty($apiKey)) {
            Log::warning('[Evolution] deleteMessage ignorado: provedor não configurado.', compact('to'));

            return false;
        }

        $remoteJid = $to.(str_contains($to, '@') ? '' : '@s.whatsapp.net');

        $url = rtrim($baseUrl, '/').'/chat/deleteMessageForEveryone/'.$instance;

        $payload = [
            'id' => $messageId,
            'remoteJid' => $remoteJid,
            'fromMe' => true,
        ];

        Log::debug('[Evolution] Tentando excluir mensagem', [
            'url' => $url,
            'payload' => $payload,
        ]);

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])
                ->timeout(10)
                ->retry(2, 500)
                ->delete($url, $payload);

            Log::debug('[Evolution] Resposta exclusão', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (! $response->successful()) {
                Log::error('[Evolution] Falha ao excluir mensagem.', [
                    'to' => $to,
                    'messageId' => $messageId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('[Evolution] Exceção ao excluir mensagem.', [
                'to' => $to,
                'messageId' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Edita uma mensagem enviada no WhatsApp.
     *
     * Endpoint: POST {api_url}/chat/updateMessage/{instance}
     * Header:   apikey: {evolution_api_key}
     * Body:     { "number": "5527...", "text": "...", "key": { "remoteJid": "...", "fromMe": true, "id": "..." } }
     */
    public function editMessage(string $to, string $messageId, string $newText): bool
    {
        $baseUrl = config('whatsapp.api_url');
        $instance = config('whatsapp.evolution_instance');
        $apiKey = config('whatsapp.evolution_api_key');

        if (empty($baseUrl) || empty($instance) || empty($apiKey)) {
            Log::warning('[Evolution] editMessage ignorado: provedor não configurado.', compact('to'));

            return false;
        }

        $remoteJid = $to.(str_contains($to, '@') ? '' : '@s.whatsapp.net');

        $url = rtrim($baseUrl, '/').'/chat/updateMessage/'.$instance;

        $payload = [
            'number' => $to,
            'text' => $newText,
            'key' => [
                'remoteJid' => $remoteJid,
                'fromMe' => true,
                'id' => $messageId,
            ],
        ];

        Log::debug('[Evolution] Tentando editar mensagem', [
            'url' => $url,
            'payload' => $payload,
        ]);

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])
                ->timeout(10)
                ->retry(2, 500)
                ->post($url, $payload);

            Log::debug('[Evolution] Resposta edição', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (! $response->successful()) {
                Log::error('[Evolution] Falha ao editar mensagem.', [
                    'to' => $to,
                    'messageId' => $messageId,
                    'newText' => $newText,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('[Evolution] Exceção ao editar mensagem.', [
                'to' => $to,
                'messageId' => $messageId,
                'newText' => $newText,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Baixa conteúdo binário de uma mídia via Evolution-API.
     *
     * Quando $mediaId contém JSON com chave '_evo_message' (serializado por
     * parseIncoming), usa o endpoint:
     *   POST {api_url}/chat/getBase64FromMediaMessage/{instance}
     *   Body: { "message": {objeto original} }
     *   Retorna: { "base64": "...", "mimetype": "..." }
     *
     * Fallback: se $mediaUrl estiver disponível, faz GET direto.
     */
    public function downloadMedia(string $mediaId, ?string $mediaUrl): string
    {
        // Tenta deserializar o objeto de mídia serializado pelo parseIncoming()
        $decoded = json_decode($mediaId, true);

        if (is_array($decoded) && isset($decoded['_evo_message'])) {
            try {
                return $this->downloadViaBase64Endpoint($decoded['_evo_message']);
            } catch (\Throwable $e) {
                Log::warning('[Evolution] Falha ao baixar via endpoint base64, tentando URL direta: '.$e->getMessage(), [
                    'has_mediaUrl' => ! empty($mediaUrl),
                ]);
            }
        }

        // Fallback: tenta URL direta se disponível
        if (! empty($mediaUrl)) {
            return $this->downloadViaUrl($mediaUrl);
        }

        throw new \RuntimeException(
            '[Evolution] Impossível baixar mídia: endpoint base64 falhou e mediaUrl ausente.'
        );
    }

    /**
     * Identificador legível do provedor para logs e diagnósticos.
     */
    public function name(): string
    {
        return 'evolution';
    }

    /**
     * Retorna somente metadados seguros de payloads ignorados pelo webhook.
     *
     * Não inclui corpo da mensagem, telefone/JID completo nem apikey.
     */
    public function ignoredPayloadMetadata(Request $request): array
    {
        $payload = $request->json()->all();
        $data = $payload['data'] ?? null;
        $messageData = $this->sampleMessageData($data);
        $key = is_array($messageData) && is_array($messageData['key'] ?? null)
            ? $messageData['key']
            : [];
        $remoteJid = (string) ($key['remoteJid'] ?? '');

        return [
            'provider' => $this->name(),
            'event' => $this->safeScalar($payload['event'] ?? null),
            'data_shape' => $this->dataShape($data),
            'messages_count' => $this->messagesCount($data),
            'messageType' => $this->safeScalar($messageData['messageType'] ?? null),
            'jid_type' => $this->jidType($remoteJid),
            'fromMe' => array_key_exists('fromMe', $key) ? (bool) $key['fromMe'] : null,
            'has_remoteJidAlt' => $this->hasFilledKey($key, 'remoteJidAlt'),
            'has_senderPn' => $this->hasFilledKey($key, 'senderPn'),
            'has_participantAlt' => $this->hasFilledKey($key, 'participantAlt'),
            'is_group' => str_ends_with(strtolower($remoteJid), '@g.us'),
            'is_status_broadcast' => $remoteJid === 'status@broadcast',
            'has_message' => is_array($messageData) && array_key_exists('message', $messageData),
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    private function webhookApiKey(Request $request): string
    {
        $headerKey = trim((string) $request->header('apikey', ''));

        if ($headerKey !== '') {
            return $headerKey;
        }

        if (! app()->environment('local', 'testing') || ! $this->isPrivateWebhookSource($request->ip())) {
            return '';
        }

        return trim((string) $request->query('apikey', ''));
    }

    private function sampleMessageData(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }

        if (! array_is_list($data)) {
            return $data;
        }

        foreach ($data as $messageData) {
            if (is_array($messageData)) {
                return $messageData;
            }
        }

        return null;
    }

    private function dataShape(mixed $data): string
    {
        if (! is_array($data)) {
            return get_debug_type($data);
        }

        return array_is_list($data) ? 'list' : 'object';
    }

    private function messagesCount(mixed $data): ?int
    {
        if (! is_array($data)) {
            return null;
        }

        return array_is_list($data) ? count($data) : 1;
    }

    private function safeScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : get_debug_type($value);
    }

    private function hasFilledKey(array $data, string $key): bool
    {
        return trim((string) ($data[$key] ?? '')) !== '';
    }

    private function isPrivateWebhookSource(?string $ip): bool
    {
        if (! is_string($ip) || $ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Durante reconexão via QR Code, a Evolution pode entregar mensagens recentes
     * em messages.set. Processamos apenas uma mensagem direta e recente para
     * evitar respostas automáticas para histórico antigo importado no sync.
     */
    private function parseMessagesSet(array $payload): ?IncomingMessage
    {
        $messages = $payload['data'] ?? [];

        if (! is_array($messages)) {
            return null;
        }

        $candidates = [];

        foreach ($messages as $messageData) {
            if (! is_array($messageData) || ! $this->isDirectIncomingMessageData($messageData)) {
                continue;
            }

            $message = $this->parseMessageData($messageData, logMissingSender: false);

            if ($message !== null && $this->isRecentMessage($message)) {
                $candidates[] = $message;
            }
        }

        usort(
            $candidates,
            static fn (IncomingMessage $a, IncomingMessage $b): int => (int) $b->timestamp <=> (int) $a->timestamp
        );

        return $candidates[0] ?? null;
    }

    private function isDirectIncomingMessageData(array $data): bool
    {
        $key = $data['key'] ?? [];

        if (($key['fromMe'] ?? false) === true) {
            return false;
        }

        $remoteJid = (string) ($key['remoteJid'] ?? '');

        return $remoteJid !== ''
            && ! str_ends_with(strtolower($remoteJid), '@g.us')
            && $remoteJid !== 'status@broadcast';
    }

    private function isRecentMessage(IncomingMessage $message): bool
    {
        return (int) $message->timestamp >= now()
            ->subMinutes(self::MESSAGES_SET_RECENT_WINDOW_MINUTES)
            ->timestamp;
    }

    /**
     * Resolve o telefone do remetente a partir dos JIDs da Evolution.
     */
    private function resolveSenderPhone(array $key): ?string
    {
        $remoteJid = trim((string) ($key['remoteJid'] ?? ''));

        if ($remoteJid === '') {
            return null;
        }

        // JID individual padrão: "5527999999999@s.whatsapp.net"
        if (str_ends_with($remoteJid, '@s.whatsapp.net')) {
            $phone = strstr($remoteJid, '@', true);

            return is_string($phone) && $phone !== '' ? $phone : null;
        }

        // JID LID (Multi-Device): o telefone real vem em remoteJidAlt, senderPn ou participantAlt
        if (str_ends_with(strtolower($remoteJid), '@lid')) {
            $alt = $key['remoteJidAlt'] ?? $key['senderPn'] ?? $key['participantAlt'] ?? null;
            if ($alt && is_string($alt)) {
                $altClean = preg_replace('/:\d+(?=@)/', '', $alt) ?? $alt;
                $withoutDomain = preg_replace('/@[^@]+$/', '', $altClean) ?? $altClean;
                $normalized = PhoneNumber::normalize($withoutDomain);

                return $normalized !== '' ? $normalized : null;
            }
        }

        // Grupos e broadcasts não são suportados pelo chatbot de atendimento
        return null;
    }

    private function jidType(string $jid): string
    {
        if (empty($jid)) {
            return 'empty';
        }

        if (str_ends_with($jid, '@s.whatsapp.net')) {
            return 'individual';
        }

        if (str_ends_with($jid, '@g.us')) {
            return 'group';
        }

        if ($jid === 'status@broadcast') {
            return 'status_broadcast';
        }

        return substr(strrchr($jid, '@') ?: '', 1) ?: 'unknown';
    }

    /**
     * Chama POST /chat/getBase64FromMediaMessage/{instance} e decodifica base64.
     */
    private function downloadViaBase64Endpoint(array $messageObject): string
    {
        $baseUrl = config('whatsapp.api_url');
        $instance = config('whatsapp.evolution_instance');
        $apiKey = config('whatsapp.evolution_api_key');

        $url = rtrim($baseUrl, '/').'/chat/getBase64FromMediaMessage/'.$instance;

        $response = Http::withHeaders(['apikey' => $apiKey])
            ->timeout(30)
            ->post($url, [
                'message' => $messageObject,
                'convertToMp4' => false,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "[Evolution] Falha ao baixar mídia via base64: HTTP {$response->status()}"
            );
        }

        $base64 = $response->json('base64')
            ?? $response->json('data.base64')
            ?? $response->json('file.base64')
            ?? $response->json('data.file')
            ?? $response->json('file')
            ?? $response->json('data');

        if (empty($base64)) {
            throw new \RuntimeException(
                '[Evolution] Resposta do endpoint de base64 não contém campo "base64".'
            );
        }

        if (is_array($base64)) {
            $base64 = $base64['base64'] ?? $base64['file'] ?? reset($base64);
        }

        if (is_string($base64) && str_contains($base64, ',')) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        return base64_decode((string) $base64, strict: true)
            ?: throw new \RuntimeException('[Evolution] Falha ao decodificar base64 da mídia.');
    }

    /**
     * Baixa mídia via URL direta (fallback).
     * Protegido contra SSRF: aceita apenas https e bloqueia IPs privados/loopback (exceto host configurado).
     */
    private function downloadViaUrl(string $url): string
    {
        $this->assertSafeUrl($url);

        $apiKey = config('whatsapp.evolution_api_key');

        $response = Http::withHeaders(array_filter(['apikey' => $apiKey]))
            ->timeout(30)
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "[Evolution] Falha ao baixar mídia via URL: HTTP {$response->status()}"
            );
        }

        return $response->body();
    }

    /**
     * Valida se a URL é segura para requisição externa (proteção SSRF).
     *
     * @throws \RuntimeException
     */
    private function assertSafeUrl(string $url): void
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        if (empty($host)) {
            throw new \RuntimeException('[Evolution] SSRF bloqueado: host ausente na URL de mídia.');
        }

        // Permite o host configurado da própria Evolution API
        $configuredEvoHost = parse_url((string) config('whatsapp.api_url'), PHP_URL_HOST);
        if ($configuredEvoHost && strcasecmp($host, $configuredEvoHost) === 0) {
            return;
        }

        if (($parsed['scheme'] ?? '') !== 'https') {
            throw new \RuntimeException(
                '[Evolution] SSRF bloqueado: apenas URLs https são permitidas para download de mídia externa.'
            );
        }

        // Verifica se o host já é um IP literal privado/reservado
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $isPrivateLiteral = ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($isPrivateLiteral) {
                throw new \RuntimeException(
                    "[Evolution] SSRF bloqueado: IP '{$host}' é privado ou reservado."
                );
            }

            return; // IP público literal — seguro
        }

        // Em produção/staging resolve o hostname via DNS e revalida o IP resultante
        if (! app()->environment('testing')) {
            $ip = gethostbyname($host);

            if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                throw new \RuntimeException('[Evolution] SSRF bloqueado: não foi possível resolver o host.');
            }

            $isPrivate = ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

            if ($isPrivate) {
                throw new \RuntimeException(
                    "[Evolution] SSRF bloqueado: IP '{$ip}' é privado ou reservado."
                );
            }
        }
    }

    /**
     * Whitelist de MIME types aceitos para mensagens de mídia.
     *
     * Normaliza parâmetros extras como '; codecs=opus' antes de comparar.
     */
    private function isAllowedMimeType(?string $mimetype): bool
    {
        // O suporte recebe arquivos técnicos arbitrários (.dll, .pfx, .exe, .bat,
        // certificados, dumps, etc.). Bloqueia apenas mimetype ausente/vazio,
        // que sinaliza payload malformado. Arquivos são armazenados em disco
        // público sem execução server-side; análise pelo agente é manual.
        return is_string($mimetype) && trim($mimetype) !== '';
    }
}
