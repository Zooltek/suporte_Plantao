<?php

use App\Services\WhatsApp\Providers\EvolutionApiProvider;
use Illuminate\Http\Request;

describe('EvolutionApiProvider — Segurança', function () {

    beforeEach(function () {
        $this->provider = new EvolutionApiProvider;
    });

    // -------------------------------------------------------------------------
    // verifyWebhook — falha segura
    // -------------------------------------------------------------------------

    describe('verifyWebhook', function () {

        it('rejeita quando chave está vazia e ambiente é produção', function () {
            config(['whatsapp.evolution_api_key' => '']);
            app()->detectEnvironment(fn () => 'production');

            $request = Request::create('/webhook', 'POST');

            expect($this->provider->verifyWebhook($request))->toBeFalse();
        });

        it('rejeita quando chave está vazia e ambiente é staging', function () {
            config(['whatsapp.evolution_api_key' => '']);
            app()->detectEnvironment(fn () => 'staging');

            $request = Request::create('/webhook', 'POST');

            expect($this->provider->verifyWebhook($request))->toBeFalse();
        });

        it('aceita sem chave em ambiente local (desenvolvimento)', function () {
            config(['whatsapp.evolution_api_key' => '']);
            // ambiente de testes também é aceito (testing)
            $request = Request::create('/webhook', 'POST');

            expect($this->provider->verifyWebhook($request))->toBeTrue();
        });

        it('aceita requisição com apikey correta', function () {
            config(['whatsapp.evolution_api_key' => 'chave-secreta-123']);

            $request = Request::create('/webhook', 'POST');
            $request->headers->set('apikey', 'chave-secreta-123');

            expect($this->provider->verifyWebhook($request))->toBeTrue();
        });

        it('rejeita requisição com apikey incorreta', function () {
            config(['whatsapp.evolution_api_key' => 'chave-secreta-123']);

            $request = Request::create('/webhook', 'POST');
            $request->headers->set('apikey', 'apikey-errada');

            expect($this->provider->verifyWebhook($request))->toBeFalse();
        });

        it('rejeita requisição sem header apikey quando chave está configurada', function () {
            config(['whatsapp.evolution_api_key' => 'chave-secreta-123']);

            $request = Request::create('/webhook', 'POST');

            expect($this->provider->verifyWebhook($request))->toBeFalse();
        });

        it('aceita apikey na query apenas em ambiente local/testing com origem privada', function () {
            config(['whatsapp.evolution_api_key' => 'chave-secreta-123']);

            $request = Request::create('/webhook?apikey=chave-secreta-123', 'POST', [], [], [], [
                'REMOTE_ADDR' => '172.18.0.5',
            ]);

            expect($this->provider->verifyWebhook($request))->toBeTrue();
        });

        it('rejeita apikey na query quando a origem não é privada', function () {
            config(['whatsapp.evolution_api_key' => 'chave-secreta-123']);

            $request = Request::create('/webhook?apikey=chave-secreta-123', 'POST', [], [], [], [
                'REMOTE_ADDR' => '8.8.8.8',
            ]);

            expect($this->provider->verifyWebhook($request))->toBeFalse();
        });

        it('rejeita apikey na query fora de ambiente local/testing', function () {
            config(['whatsapp.evolution_api_key' => 'chave-secreta-123']);
            app()->detectEnvironment(fn () => 'production');

            $request = Request::create('/webhook?apikey=chave-secreta-123', 'POST', [], [], [], [
                'REMOTE_ADDR' => '172.18.0.5',
            ]);

            expect($this->provider->verifyWebhook($request))->toBeFalse();
        });
    });

    // -------------------------------------------------------------------------
    // parseIncoming — MIME type whitelist
    // -------------------------------------------------------------------------

    describe('parseIncoming — MIME whitelist', function () {

        it('aceita image/jpeg em imageMessage', function () {
            $payload = [
                'event' => 'messages.upsert',
                'data' => [
                    'key' => ['remoteJid' => '5527999999999@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg1'],
                    'messageType' => 'imageMessage',
                    'messageTimestamp' => now()->timestamp,
                    'message' => [
                        'imageMessage' => ['mimetype' => 'image/jpeg', 'url' => 'https://cdn.example.com/img.jpg'],
                    ],
                ],
            ];

            $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));
            $request->headers->set('Content-Type', 'application/json');

            $message = $this->provider->parseIncoming($request);

            expect($message)->not->toBeNull();
            expect($message->type)->toBe('image');
            expect($message->mimetype)->toBe('image/jpeg');
        });

        it('aceita MIME type técnico arbitrário (.dll/.exe/.pfx → application/x-executable)', function () {
            $payload = [
                'event' => 'messages.upsert',
                'data' => [
                    'key' => ['remoteJid' => '5527999999999@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg2'],
                    'messageType' => 'documentMessage',
                    'messageTimestamp' => now()->timestamp,
                    'message' => [
                        'documentMessage' => ['mimetype' => 'application/x-executable'],
                    ],
                ],
            ];

            $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));
            $request->headers->set('Content-Type', 'application/json');

            $message = $this->provider->parseIncoming($request);

            expect($message)->not->toBeNull();
            expect($message->type)->toBe('document');
            expect($message->mimetype)->toBe('application/x-executable');
        });

        it('bloqueia MIME type nulo', function () {
            $payload = [
                'event' => 'messages.upsert',
                'data' => [
                    'key' => ['remoteJid' => '5527999999999@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg3'],
                    'messageType' => 'imageMessage',
                    'messageTimestamp' => now()->timestamp,
                    'message' => [
                        'imageMessage' => [],
                    ],
                ],
            ];

            $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));
            $request->headers->set('Content-Type', 'application/json');

            $message = $this->provider->parseIncoming($request);

            expect($message)->toBeNull();
        });

        it('aceita application/pdf em documentMessage', function () {
            $payload = [
                'event' => 'messages.upsert',
                'data' => [
                    'key' => ['remoteJid' => '5527999999999@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg4'],
                    'messageType' => 'documentMessage',
                    'messageTimestamp' => now()->timestamp,
                    'message' => [
                        'documentMessage' => ['mimetype' => 'application/pdf'],
                    ],
                ],
            ];

            $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));
            $request->headers->set('Content-Type', 'application/json');

            $message = $this->provider->parseIncoming($request);

            expect($message)->not->toBeNull();
            expect($message->mimetype)->toBe('application/pdf');
        });
    });

    // -------------------------------------------------------------------------
    // assertSafeUrl — proteção SSRF (via downloadMedia com URL inválida)
    // -------------------------------------------------------------------------

    describe('downloadViaUrl — SSRF', function () {

        it('bloqueia URL com esquema http (não https)', function () {
            config([
                'whatsapp.evolution_api_key' => 'key',
                'whatsapp.api_url' => 'https://evo.example.com',
                'whatsapp.evolution_instance' => 'amura',
            ]);

            expect(fn () => $this->provider->downloadMedia('invalid-json', 'http://example.com/media.jpg'))
                ->toThrow(\RuntimeException::class, 'https');
        });

        it('bloqueia URL com IP de loopback (127.0.0.1)', function () {
            config([
                'whatsapp.evolution_api_key' => 'key',
                'whatsapp.api_url' => 'https://evo.example.com',
                'whatsapp.evolution_instance' => 'amura',
            ]);

            expect(fn () => $this->provider->downloadMedia('invalid-json', 'https://127.0.0.1/media.jpg'))
                ->toThrow(\RuntimeException::class, 'SSRF');
        });

        it('bloqueia URL com IP privado (192.168.x.x)', function () {
            config([
                'whatsapp.evolution_api_key' => 'key',
                'whatsapp.api_url' => 'https://evo.example.com',
                'whatsapp.evolution_instance' => 'amura',
            ]);

            expect(fn () => $this->provider->downloadMedia('invalid-json', 'https://192.168.1.1/media.jpg'))
                ->toThrow(\RuntimeException::class, 'SSRF');
        });

        it('bloqueia URL com IP privado (10.x.x.x)', function () {
            config([
                'whatsapp.evolution_api_key' => 'key',
                'whatsapp.api_url' => 'https://evo.example.com',
                'whatsapp.evolution_instance' => 'amura',
            ]);

            expect(fn () => $this->provider->downloadMedia('invalid-json', 'https://10.0.0.1/media.jpg'))
                ->toThrow(\RuntimeException::class, 'SSRF');
        });
    });
});
