<?php

use App\Services\WhatsApp\Providers\EvolutionApiProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

// ─────────────────────────────────────────────────────────────────────────────
// EvolutionApiProvider
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->provider = new EvolutionApiProvider;

    config([
        'whatsapp.api_url' => 'https://evolution.test',
        'whatsapp.evolution_instance' => 'amura-test',
        'whatsapp.evolution_api_key' => 'test-api-key-123',
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers de fixture
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Monta um payload de webhook Evolution-API v2 para mensagem de texto.
 */
function evoTextPayload(string $from = '5527999990000', string $body = 'Olá'): array
{
    return [
        'event' => 'messages.upsert',
        'instance' => 'amura-test',
        'data' => [
            'key' => ['remoteJid' => "{$from}@s.whatsapp.net", 'fromMe' => false, 'id' => 'MSG-001'],
            'pushName' => 'Contato Teste',
            'message' => ['conversation' => $body],
            'messageType' => 'conversation',
            'messageTimestamp' => 1710000000,
        ],
    ];
}

/**
 * Monta um payload de webhook para mensagem de imagem.
 */
function evoImagePayload(string $from = '5527999990000'): array
{
    return [
        'event' => 'messages.upsert',
        'instance' => 'amura-test',
        'data' => [
            'key' => ['remoteJid' => "{$from}@s.whatsapp.net", 'fromMe' => false, 'id' => 'MSG-IMG-001'],
            'message' => [
                'imageMessage' => [
                    'url' => 'https://mmg.whatsapp.net/img.jpg',
                    'mimetype' => 'image/jpeg',
                    'mediaKey' => 'base64keyhere==',
                    'fileLength' => 45678,
                ],
            ],
            'messageType' => 'imageMessage',
            'messageTimestamp' => 1710000001,
        ],
    ];
}

/**
 * Cria um Request com corpo JSON.
 */
function evoRequest(array $payload): Request
{
    return Request::create('/webhook', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_apikey' => 'test-api-key-123',
    ], json_encode($payload));
}

// ─────────────────────────────────────────────────────────────────────────────
// verifyWebhook
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::verifyWebhook()', function () {

    it('aceita quando evolution_api_key não está configurada (dev)', function () {
        config(['whatsapp.evolution_api_key' => '']);

        $request = Request::create('/webhook', 'POST');

        expect($this->provider->verifyWebhook($request))->toBeTrue();
    });

    it('aceita requisição com apikey correta no header', function () {
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_apikey' => 'test-api-key-123',
        ], '{}');

        expect($this->provider->verifyWebhook($request))->toBeTrue();
    });

    it('aceita apikey correta na query apenas para webhook global local da Evolution', function () {
        $request = Request::create('/webhook?apikey=test-api-key-123', 'POST', [], [], [], [
            'REMOTE_ADDR' => '172.18.0.5',
        ], '{}');

        expect($this->provider->verifyWebhook($request))->toBeTrue();
    });

    it('rejeita requisição com apikey incorreta', function () {
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_apikey' => 'chave-errada',
        ], '{}');

        expect($this->provider->verifyWebhook($request))->toBeFalse();
    });

    it('rejeita requisição sem o header apikey', function () {
        $request = Request::create('/webhook', 'POST', [], [], [], [], '{}');

        expect($this->provider->verifyWebhook($request))->toBeFalse();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// parseIncoming — texto
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::parseIncoming() — texto', function () {

    it('parseia mensagem de texto simples (conversation) corretamente', function () {
        $request = evoRequest(evoTextPayload('5527999990000', 'Preciso de suporte'));

        $msg = $this->provider->parseIncoming($request);

        expect($msg)->not->toBeNull()
            ->and($msg->from)->toBe('5527999990000')
            ->and($msg->body)->toBe('Preciso de suporte')
            ->and($msg->type)->toBe('text')
            ->and($msg->messageId)->toBe('MSG-001')
            ->and($msg->timestamp)->toBe('1710000000')
            ->and($msg->isText())->toBeTrue()
            ->and($msg->isMedia())->toBeFalse();
    });

    it('parseia extendedTextMessage (link com preview)', function () {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => ['remoteJid' => '5527111112222@s.whatsapp.net', 'fromMe' => false, 'id' => 'MSG-EXT'],
                'message' => ['extendedTextMessage' => ['text' => 'Veja este link']],
                'messageType' => 'extendedTextMessage',
                'messageTimestamp' => 1710000002,
            ],
        ];

        $msg = $this->provider->parseIncoming(evoRequest($payload));

        expect($msg)->not->toBeNull()
            ->and($msg->body)->toBe('Veja este link')
            ->and($msg->type)->toBe('text');
    });

    it('normaliza remoteJid removendo sufixo @s.whatsapp.net', function () {
        $request = evoRequest(evoTextPayload('5521987654321'));

        $msg = $this->provider->parseIncoming($request);

        expect($msg->from)->toBe('5521987654321');
    });

    it('usa remoteJidAlt quando a Evolution envia conversa direta com remoteJid @lid', function () {
        $payload = evoTextPayload();
        $payload['data']['key'] = [
            'remoteJid' => '124575610368142@lid',
            'remoteJidAlt' => '554591540313@s.whatsapp.net',
            'fromMe' => false,
            'id' => 'MSG-LID-001',
        ];
        $payload['data']['message']['conversation'] = 'Boa noite';

        $msg = $this->provider->parseIncoming(evoRequest($payload));

        expect($msg)->not->toBeNull()
            ->and($msg->from)->toBe('554591540313')
            ->and($msg->body)->toBe('Boa noite')
            ->and($msg->messageId)->toBe('MSG-LID-001');
    });

    it('ignora remoteJid @lid sem telefone alternativo roteável', function () {
        $payload = evoTextPayload();
        $payload['data']['key'] = [
            'remoteJid' => '124575610368142@lid',
            'fromMe' => false,
            'id' => 'MSG-LID-SEM-ALT',
        ];

        expect($this->provider->parseIncoming(evoRequest($payload)))->toBeNull();
    });

    it('ignora mensagens de grupos em messages.upsert', function () {
        $payload = evoTextPayload();
        $payload['data']['key']['remoteJid'] = '120363001234567890@g.us';

        $msg = $this->provider->parseIncoming(evoRequest($payload));

        expect($msg)->toBeNull();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// parseIncoming — messages.set
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::parseIncoming() — messages.set', function () {

    it('processa mensagem direta recente entregue pelo sync pós-QR', function () {
        Carbon::setTestNow(Carbon::createFromTimestamp(1710000300));

        $payload = [
            'event' => 'messages.set',
            'instance' => 'amura-test',
            'isLatest' => true,
            'data' => [
                [
                    'key' => [
                        'remoteJid' => '554599674093-1459035419@g.us',
                        'fromMe' => false,
                        'id' => 'MSG-GRUPO-IGNORADO',
                    ],
                    'message' => ['conversation' => 'Mensagem de grupo'],
                    'messageType' => 'conversation',
                    'messageTimestamp' => 1710000290,
                ],
                [
                    'key' => [
                        'remoteJid' => '124575610368142@lid',
                        'remoteJidAlt' => '554591540313@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => 'MSG-SET-LID-001',
                    ],
                    'pushName' => 'Contato Teste',
                    'message' => ['conversation' => 'Boa noite'],
                    'messageType' => 'conversation',
                    'messageTimestamp' => 1710000280,
                ],
            ],
        ];

        $msg = $this->provider->parseIncoming(evoRequest($payload));

        expect($msg)->not->toBeNull()
            ->and($msg->from)->toBe('554591540313')
            ->and($msg->body)->toBe('Boa noite')
            ->and($msg->messageId)->toBe('MSG-SET-LID-001');
    });

    it('ignora mensagem do sync pós-QR quando ela não é recente', function () {
        Carbon::setTestNow(Carbon::createFromTimestamp(1710000300));

        $payload = [
            'event' => 'messages.set',
            'instance' => 'amura-test',
            'isLatest' => true,
            'data' => [
                [
                    'key' => [
                        'remoteJid' => '124575610368142@lid',
                        'remoteJidAlt' => '554591540313@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => 'MSG-SET-ANTIGA',
                    ],
                    'message' => ['conversation' => 'Mensagem antiga'],
                    'messageType' => 'conversation',
                    'messageTimestamp' => 1709990000,
                ],
            ],
        ];

        expect($this->provider->parseIncoming(evoRequest($payload)))->toBeNull();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// parseIncoming — mídia
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::parseIncoming() — mídia', function () {

    it('parseia imageMessage com type=image e serializa a key + message completo no mediaId', function () {
        $msg = $this->provider->parseIncoming(evoRequest(evoImagePayload()));

        expect($msg)->not->toBeNull()
            ->and($msg->type)->toBe('image')
            ->and($msg->mimetype)->toBe('image/jpeg')
            ->and($msg->isMedia())->toBeTrue();

        // A Evolution v2 exige a key completa (id + remoteJid + fromMe) no
        // endpoint /chat/getBase64FromMediaMessage. Adicionalmente, o sub-objeto
        // de mídia (mediaKey, url, fileLength) precisa estar presente para que
        // a Evolution descriptografe sem depender do próprio store — race
        // pós-webhook fazia o anexo do cliente chegar com path NULL.
        $decoded = json_decode($msg->mediaId, true);
        expect($decoded)->toHaveKey('_evo_message')
            ->and($decoded['_evo_message']['key'])->toBe([
                'id' => 'MSG-IMG-001',
                'remoteJid' => '5527999990000@s.whatsapp.net',
                'fromMe' => false,
            ])
            ->and($decoded['_evo_message']['message']['imageMessage'])->toMatchArray([
                'url' => 'https://mmg.whatsapp.net/img.jpg',
                'mimetype' => 'image/jpeg',
                'mediaKey' => 'base64keyhere==',
                'fileLength' => 45678,
            ]);
    });

    it('parseia documentMessage com type=document e captura fileName original', function () {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => ['remoteJid' => '5527000000001@s.whatsapp.net', 'fromMe' => false, 'id' => 'MSG-DOC'],
                'message' => [
                    'documentMessage' => [
                        'url' => 'https://mmg.whatsapp.net/doc.pdf',
                        'mimetype' => 'application/pdf',
                        'mediaKey' => 'key==',
                        'fileName' => 'NFe-12345.xml',
                    ],
                ],
                'messageType' => 'documentMessage',
                'messageTimestamp' => 1710000003,
            ],
        ];

        $msg = $this->provider->parseIncoming(evoRequest($payload));

        expect($msg)->not->toBeNull()
            ->and($msg->type)->toBe('document')
            ->and($msg->mimetype)->toBe('application/pdf')
            ->and($msg->fileName)->toBe('NFe-12345.xml');
    });

    it('parseia audioMessage com type=audio', function () {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => ['remoteJid' => '5527000000002@s.whatsapp.net', 'fromMe' => false, 'id' => 'MSG-AUD'],
                'message' => ['audioMessage' => ['mimetype' => 'audio/ogg; codecs=opus', 'mediaKey' => 'key==']],
                'messageType' => 'audioMessage',
                'messageTimestamp' => 1710000004,
            ],
        ];

        $msg = $this->provider->parseIncoming(evoRequest($payload));

        expect($msg)->not->toBeNull()->and($msg->type)->toBe('audio');
    });

    it('parseia videoMessage com type=video', function () {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => ['remoteJid' => '5527000000003@s.whatsapp.net', 'fromMe' => false, 'id' => 'MSG-VID'],
                'message' => ['videoMessage' => ['mimetype' => 'video/mp4', 'mediaKey' => 'key==']],
                'messageType' => 'videoMessage',
                'messageTimestamp' => 1710000005,
            ],
        ];

        $msg = $this->provider->parseIncoming(evoRequest($payload));

        expect($msg)->not->toBeNull()->and($msg->type)->toBe('video');
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// parseIncoming — casos ignorados
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::parseIncoming() — payloads ignorados', function () {

    it('retorna null para mensagens enviadas pelo próprio bot (fromMe=true)', function () {
        $payload = evoTextPayload();
        $payload['data']['key']['fromMe'] = true;

        expect($this->provider->parseIncoming(evoRequest($payload)))->toBeNull();
    });

    it('retorna null para evento diferente de messages.upsert (ex: messages.update)', function () {
        $payload = evoTextPayload();
        $payload['event'] = 'messages.update';

        expect($this->provider->parseIncoming(evoRequest($payload)))->toBeNull();
    });

    it('retorna null para evento de conexão (connection.update)', function () {
        $payload = ['event' => 'connection.update', 'instance' => 'amura-test', 'data' => []];

        expect($this->provider->parseIncoming(evoRequest($payload)))->toBeNull();
    });

    it('retorna null quando remoteJid está ausente', function () {
        $payload = evoTextPayload();
        unset($payload['data']['key']['remoteJid']);

        expect($this->provider->parseIncoming(evoRequest($payload)))->toBeNull();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// sendText
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::sendText()', function () {

    it('retorna false e não faz HTTP quando configuração está incompleta', function () {
        config(['whatsapp.evolution_instance' => '']);
        Http::fake();

        $result = $this->provider->sendText('5527999990000', 'Olá');

        expect($result)->toBeFalse();
        Http::assertNothingSent();
    });

    it('envia POST para /message/sendText/{instance} com header apikey', function () {
        Http::fake([
            'https://evolution.test/message/sendText/amura-test' => Http::response(['key' => ['id' => 'OUT-001']], 200),
        ]);

        $result = $this->provider->sendText('5527999990000', 'Mensagem de teste');

        expect($result)->toBe('OUT-001');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/message/sendText/amura-test')
                && $request->hasHeader('apikey', 'test-api-key-123')
                && $request->data()['number'] === '5527999990000'
                && $request->data()['text'] === 'Mensagem de teste';
        });
    });

    it('retorna false quando API responde 4xx', function () {
        Http::fake([
            'https://evolution.test/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $result = $this->provider->sendText('5527999990000', 'Mensagem');

        expect($result)->toBeFalse();
    });

    it('retorna false quando API responde 5xx', function () {
        Http::fake([
            'https://evolution.test/*' => Http::response([], 503),
        ]);

        expect($this->provider->sendText('5527999990000', 'Mensagem'))->toBeFalse();
    });

    it('retorna false e loga quando ocorre exceção de rede', function () {
        Http::fake(fn () => throw new \Exception('Connection refused'));

        expect($this->provider->sendText('5527999990000', 'Mensagem'))->toBeFalse();
    });

    it('não inclui credenciais no log de erro', function () {
        Http::fake([
            'https://evolution.test/*' => Http::response([], 500),
        ]);

        // Apenas verifica que o método não lança exceção expondo a apikey
        $result = $this->provider->sendText('5527999990000', 'Mensagem');

        expect($result)->toBeFalse(); // sem throw
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// sendMedia — preserva o nome original do arquivo (Bug 2)
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::sendMedia() — preservação do fileName', function () {

    it('usa o fileName informado pelo agente em vez do basename interno (.bin)', function () {
        $tmpRel = 'whatsapp/attachments/'.uniqid('test_', true).'.bin';
        $absolute = storage_path('app/public/'.$tmpRel);
        @mkdir(dirname($absolute), 0775, true);
        file_put_contents($absolute, 'fake-binary-content');

        Http::fake([
            'https://evolution.test/message/sendMedia/amura-test' => Http::response(
                ['key' => ['id' => 'OUT-MEDIA-1']],
                200,
            ),
        ]);

        try {
            $result = $this->provider->sendMedia(
                '5527999990000',
                $tmpRel,
                'Veja o certificado',
                'application/x-pkcs12',
                'cert-prod-2026.pfx',
            );

            expect($result)->toBe('OUT-MEDIA-1');

            Http::assertSent(function ($request): bool {
                return $request->data()['fileName'] === 'cert-prod-2026.pfx'
                    && $request->data()['mediatype'] === 'document';
            });
        } finally {
            @unlink($absolute);
        }
    });

    it('cai no basename do path quando fileName não é fornecido (compatibilidade)', function () {
        $tmpRel = 'whatsapp/attachments/'.uniqid('legado_', true).'.pdf';
        $absolute = storage_path('app/public/'.$tmpRel);
        @mkdir(dirname($absolute), 0775, true);
        file_put_contents($absolute, 'pdf-bytes');

        Http::fake([
            'https://evolution.test/message/sendMedia/amura-test' => Http::response(
                ['key' => ['id' => 'OUT-MEDIA-2']],
                200,
            ),
        ]);

        try {
            $this->provider->sendMedia('5527999990000', $tmpRel, null, 'application/pdf');

            Http::assertSent(function ($request) use ($tmpRel): bool {
                return $request->data()['fileName'] === basename($tmpRel);
            });
        } finally {
            @unlink($absolute);
        }
    });

    it('higieniza fileName removendo componentes de path (segurança)', function () {
        $tmpRel = 'whatsapp/attachments/'.uniqid('seg_', true).'.bin';
        $absolute = storage_path('app/public/'.$tmpRel);
        @mkdir(dirname($absolute), 0775, true);
        file_put_contents($absolute, 'x');

        Http::fake([
            'https://evolution.test/message/sendMedia/amura-test' => Http::response(
                ['key' => ['id' => 'OUT-MEDIA-3']],
                200,
            ),
        ]);

        try {
            $this->provider->sendMedia(
                '5527999990000',
                $tmpRel,
                null,
                'application/x-pkcs12',
                '../../../etc/passwd',
            );

            Http::assertSent(function ($request): bool {
                return $request->data()['fileName'] === 'passwd';
            });
        } finally {
            @unlink($absolute);
        }
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// downloadMedia
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::downloadMedia()', function () {

    it('chama /chat/getBase64FromMediaMessage/{instance} quando mediaId é JSON Evolution', function () {
        $messageObject = ['key' => ['id' => 'MSG-IMG-001']];
        $mediaId = json_encode(['_evo_message' => $messageObject]);
        $fakeBytes = 'conteúdo-binário-da-imagem';

        Http::fake([
            'https://evolution.test/chat/getBase64FromMediaMessage/amura-test' => Http::response([
                'base64' => base64_encode($fakeBytes),
                'mimetype' => 'image/jpeg',
            ], 200),
        ]);

        $result = $this->provider->downloadMedia($mediaId, null);

        expect($result)->toBe($fakeBytes);

        Http::assertSent(function ($request) use ($messageObject) {
            return str_contains($request->url(), '/chat/getBase64FromMediaMessage/amura-test')
                && $request->hasHeader('apikey', 'test-api-key-123')
                && $request->data()['message'] === $messageObject;
        });
    });

    it('aceita resposta base64 em data URI', function () {
        $mediaId = json_encode(['_evo_message' => ['key' => ['id' => 'MSG-IMG-001']]]);
        $fakeBytes = 'bytes-da-imagem';

        Http::fake([
            'https://evolution.test/chat/getBase64FromMediaMessage/amura-test' => Http::response([
                'base64' => 'data:image/jpeg;base64,'.base64_encode($fakeBytes),
            ], 200),
        ]);

        expect($this->provider->downloadMedia($mediaId, null))->toBe($fakeBytes);
    });

    it('usa fallback GET via mediaUrl quando mediaId não é JSON Evolution', function () {
        $fakeBytes = 'bytes-da-imagem-via-url';
        // Usa IP literal público (1.1.1.1 = Cloudflare) para evitar resolução DNS
        // em ambiente de CI/Docker onde gethostbyname() pode não ter acesso externo.
        Http::fake([
            'https://1.1.1.1/img.jpg' => Http::response($fakeBytes, 200),
        ]);

        $result = $this->provider->downloadMedia('id-simples-sem-json', 'https://1.1.1.1/img.jpg');

        expect($result)->toBe($fakeBytes);
    });

    it('lança RuntimeException quando API de base64 retorna erro', function () {
        $mediaId = json_encode(['_evo_message' => ['imageMessage' => []]]);

        Http::fake([
            'https://evolution.test/*' => Http::response(['error' => 'Media not found'], 404),
        ]);

        expect(fn () => $this->provider->downloadMedia($mediaId, null))
            ->toThrow(\RuntimeException::class);
    });

    it('lança RuntimeException quando resposta não contém campo base64', function () {
        $mediaId = json_encode(['_evo_message' => ['imageMessage' => []]]);

        Http::fake([
            'https://evolution.test/*' => Http::response(['mimetype' => 'image/jpeg'], 200),
        ]);

        expect(fn () => $this->provider->downloadMedia($mediaId, null))
            ->toThrow(\RuntimeException::class, 'base64');
    });

    it('lança RuntimeException quando mediaId e mediaUrl são inválidos', function () {
        expect(fn () => $this->provider->downloadMedia('id-invalido', null))
            ->toThrow(\RuntimeException::class);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// parseIncoming — whitelist de MIME types
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::parseIncoming() — MIME types', function () {

    it('aceita documentMessage com MIME técnico (application/x-msdownload, .dll/.exe)', function () {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => ['remoteJid' => '5527111111111@s.whatsapp.net', 'fromMe' => false, 'id' => 'TECH-MIME'],
                'messageType' => 'documentMessage',
                'message' => ['documentMessage' => ['mimetype' => 'application/x-msdownload', 'mediaKey' => 'k']],
                'messageTimestamp' => 1710000010,
            ],
        ];

        $msg = $this->provider->parseIncoming(evoRequest($payload));
        expect($msg)->not->toBeNull();
        expect($msg->type)->toBe('document');
        expect($msg->mimetype)->toBe('application/x-msdownload');
    });

    it('retorna null para imageMessage com mimetype null', function () {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => ['remoteJid' => '5527111111111@s.whatsapp.net', 'fromMe' => false, 'id' => 'NULL-MIME'],
                'messageType' => 'imageMessage',
                'message' => ['imageMessage' => []],
                'messageTimestamp' => 1710000011,
            ],
        ];

        expect($this->provider->parseIncoming(evoRequest($payload)))->toBeNull();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// downloadMedia — proteção SSRF
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::downloadMedia() — proteção SSRF', function () {

    it('rejeita URL com esquema http:// (apenas https permitido)', function () {
        expect(fn () => $this->provider->downloadMedia('', 'http://cdn.example.com/img.jpg'))
            ->toThrow(\RuntimeException::class, 'SSRF bloqueado');
    });

    it('rejeita IP privado 192.168.x.x', function () {
        expect(fn () => $this->provider->downloadMedia('', 'https://192.168.1.100/img.jpg'))
            ->toThrow(\RuntimeException::class, 'SSRF bloqueado');
    });

    it('rejeita loopback 127.0.0.1', function () {
        expect(fn () => $this->provider->downloadMedia('', 'https://127.0.0.1/secret'))
            ->toThrow(\RuntimeException::class, 'SSRF bloqueado');
    });

    it('rejeita bloco RFC1918 10.x.x.x', function () {
        expect(fn () => $this->provider->downloadMedia('', 'https://10.0.0.1/file'))
            ->toThrow(\RuntimeException::class, 'SSRF bloqueado');
    });

    it('rejeita bloco RFC1918 172.16.x.x', function () {
        expect(fn () => $this->provider->downloadMedia('', 'https://172.16.0.1/file'))
            ->toThrow(\RuntimeException::class, 'SSRF bloqueado');
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// name
// ─────────────────────────────────────────────────────────────────────────────

describe('EvolutionApiProvider::name()', function () {

    it('retorna "evolution"', function () {
        expect($this->provider->name())->toBe('evolution');
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Service Provider — resolução via container
// ─────────────────────────────────────────────────────────────────────────────

describe('AppServiceProvider — binding WhatsAppProviderContract', function () {

    it('resolve EvolutionApiProvider quando WHATSAPP_PROVIDER=evolution', function () {
        config(['whatsapp.provider' => 'evolution']);

        // Rebind para forçar nova resolução
        app()->bind(\App\Contracts\WhatsApp\WhatsAppProviderContract::class, function () {
            return match (config('whatsapp.provider', 'generic')) {
                'evolution' => new \App\Services\WhatsApp\Providers\EvolutionApiProvider,
                default => new \App\Services\WhatsApp\Providers\GenericWhatsAppProvider,
            };
        });

        $provider = app(\App\Contracts\WhatsApp\WhatsAppProviderContract::class);

        expect($provider)->toBeInstanceOf(EvolutionApiProvider::class)
            ->and($provider->name())->toBe('evolution');
    });

    it('resolve GenericWhatsAppProvider quando WHATSAPP_PROVIDER=generic', function () {
        config(['whatsapp.provider' => 'generic']);

        app()->bind(\App\Contracts\WhatsApp\WhatsAppProviderContract::class, function () {
            return match (config('whatsapp.provider', 'generic')) {
                'evolution' => new \App\Services\WhatsApp\Providers\EvolutionApiProvider,
                default => new \App\Services\WhatsApp\Providers\GenericWhatsAppProvider,
            };
        });

        $provider = app(\App\Contracts\WhatsApp\WhatsAppProviderContract::class);

        expect($provider)->toBeInstanceOf(\App\Services\WhatsApp\Providers\GenericWhatsAppProvider::class)
            ->and($provider->name())->toBe('generic');
    });

    it('EvolutionApiProvider satisfaz completamente o contrato WhatsAppProviderContract (LSP)', function () {
        $provider = new EvolutionApiProvider;

        expect($provider)->toBeInstanceOf(\App\Contracts\WhatsApp\WhatsAppProviderContract::class);

        // Verifica que todos os métodos do contrato estão implementados
        expect(method_exists($provider, 'verifyWebhook'))->toBeTrue()
            ->and(method_exists($provider, 'parseIncoming'))->toBeTrue()
            ->and(method_exists($provider, 'sendText'))->toBeTrue()
            ->and(method_exists($provider, 'downloadMedia'))->toBeTrue()
            ->and(method_exists($provider, 'name'))->toBeTrue();
    });

});
