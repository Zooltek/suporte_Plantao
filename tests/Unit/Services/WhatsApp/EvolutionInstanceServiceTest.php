<?php

use App\Services\WhatsApp\EvolutionInstanceService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'whatsapp.api_url' => 'https://evolution.test',
        'whatsapp.evolution_instance' => 'amura-test',
        'whatsapp.evolution_api_key' => 'test-api-key-123',
        'whatsapp.webhook_url' => 'http://suporte12_app:8080/api/webhook/whatsapp',
    ]);

    $this->service = new EvolutionInstanceService;
});

function evolutionConfiguredWebhookPayload(): array
{
    return [
        'enabled' => true,
        'url' => 'http://suporte12_app:8080/api/webhook/whatsapp',
        'headers' => ['apikey' => 'test-api-key-123'],
        'events' => ['MESSAGES_UPSERT', 'MESSAGES_SET'],
        'webhookByEvents' => false,
        'webhookBase64' => false,
    ];
}

function evolutionClosedStateResponses(): array
{
    return [
        'https://evolution.test/instance/connectionState/amura-test' => Http::response([
            'instance' => ['state' => 'close'],
        ], 200),
        'https://evolution.test/webhook/find/amura-test' => Http::response(
            evolutionConfiguredWebhookPayload(),
            200
        ),
    ];
}

describe('EvolutionInstanceService::isConfigured()', function () {

    it('retorna true quando todas as variáveis estão presentes', function () {
        expect($this->service->isConfigured())->toBeTrue();
    });

    it('retorna false quando api_url está ausente', function () {
        config(['whatsapp.api_url' => '']);
        expect((new EvolutionInstanceService)->isConfigured())->toBeFalse();
    });

    it('retorna false quando evolution_instance está ausente', function () {
        config(['whatsapp.evolution_instance' => '']);
        expect((new EvolutionInstanceService)->isConfigured())->toBeFalse();
    });

    it('retorna false quando evolution_api_key está ausente', function () {
        config(['whatsapp.evolution_api_key' => '']);
        expect((new EvolutionInstanceService)->isConfigured())->toBeFalse();
    });
});

describe('EvolutionInstanceService::connectionState()', function () {

    it('retorna state=open quando instância está conectada', function () {
        Http::fake([
            'https://evolution.test/instance/connectionState/amura-test' => Http::response([
                'instance' => ['state' => 'open'],
            ], 200),
            'https://evolution.test/webhook/find/amura-test' => Http::response([
                'enabled' => true,
                'url' => 'http://suporte12_app:8080/api/webhook/whatsapp',
                'headers' => ['apikey' => 'test-api-key-123'],
                'events' => ['MESSAGES_UPSERT', 'MESSAGES_SET'],
                'webhookByEvents' => false,
                'webhookBase64' => false,
            ], 200),
        ]);

        $result = $this->service->connectionState();

        expect($result['state'])->toBe('open')
            ->and($result['instance'])->toBe('amura-test');
    });

    it('retorna state=close quando instância está desconectada', function () {
        Http::fake([
            'https://evolution.test/instance/connectionState/amura-test' => Http::response([
                'instance' => ['state' => 'close'],
            ], 200),
            'https://evolution.test/webhook/find/amura-test' => Http::response([
                'enabled' => true,
                'url' => 'http://suporte12_app:8080/api/webhook/whatsapp',
                'headers' => ['apikey' => 'test-api-key-123'],
                'events' => ['MESSAGES_UPSERT', 'MESSAGES_SET'],
                'webhookByEvents' => false,
                'webhookBase64' => false,
            ], 200),
        ]);

        expect($this->service->connectionState()['state'])->toBe('close');
    });

    it('configura o webhook quando a instância existe sem webhook válido', function () {
        Http::fake([
            'https://evolution.test/instance/connectionState/amura-test' => Http::response([
                'instance' => ['state' => 'open'],
            ], 200),
            'https://evolution.test/webhook/find/amura-test' => Http::response(null, 200),
            'https://evolution.test/webhook/set/amura-test' => Http::response([
                'enabled' => true,
            ], 201),
            'https://evolution.test/instance/restart/amura-test' => Http::response([
                'instance' => ['instanceName' => 'amura-test', 'state' => 'open'],
            ], 200),
        ]);

        $result = $this->service->connectionState();

        expect($result['state'])->toBe('open');

        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/webhook/set/amura-test' &&
            $request['webhook']['url'] === 'http://suporte12_app:8080/api/webhook/whatsapp' &&
            $request['webhook']['headers']['apikey'] === 'test-api-key-123' &&
            $request['webhook']['events'] === ['MESSAGES_UPSERT', 'MESSAGES_SET']
        );
        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/instance/restart/amura-test');
    });

    it('não reatualiza o webhook quando a instância está conectada e a configuração já confere', function () {
        Http::fake([
            'https://evolution.test/instance/connectionState/amura-test' => Http::response([
                'instance' => ['state' => 'open'],
            ], 200),
            'https://evolution.test/webhook/find/amura-test' => Http::response(
                evolutionConfiguredWebhookPayload(),
                200
            ),
        ]);

        $result = $this->service->connectionState();

        expect($result['state'])->toBe('open');

        Http::assertNotSent(fn ($request) => $request->url() === 'https://evolution.test/webhook/set/amura-test');
        Http::assertNotSent(fn ($request) => $request->url() === 'https://evolution.test/instance/restart/amura-test');
    });

    it('retorna state=unknown quando API responde com erro', function () {
        Http::fake([
            'https://evolution.test/instance/connectionState/amura-test' => Http::response([], 503),
        ]);

        expect($this->service->connectionState()['state'])->toBe('unknown');
    });

    it('retorna state=unknown quando API lança exceção de conexão', function () {
        Http::fake([
            'https://evolution.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
        ]);

        expect($this->service->connectionState()['state'])->toBe('unknown');
    });

    it('lança RuntimeException quando provedor não está configurado', function () {
        config(['whatsapp.api_url' => '']);
        expect(fn () => (new EvolutionInstanceService)->connectionState())
            ->toThrow(\RuntimeException::class, 'não configurado');
    });
});

describe('EvolutionInstanceService::fetchQrCode()', function () {

    it('retorna base64 e code quando API responde com sucesso', function () {
        $fakeBase64 = 'data:image/png;base64,'.base64_encode('fake-png-bytes');

        Http::fake(array_merge(
            evolutionClosedStateResponses(),
            [
                'https://evolution.test/instance/connect/amura-test' => Http::response([
                    'base64' => $fakeBase64,
                    'code' => '2@QRSTRINGabcdef',
                ], 200),
            ]
        ));

        $result = $this->service->fetchQrCode();

        expect($result['base64'])->toBe($fakeBase64)
            ->and($result['code'])->toBe('2@QRSTRINGabcdef')
            ->and($result['status'])->toBe('ready');
    });

    it('adiciona prefixo data:image/png;base64 quando ausente', function () {
        $rawBase64 = base64_encode('raw-bytes');

        Http::fake(array_merge(
            evolutionClosedStateResponses(),
            [
                'https://evolution.test/instance/connect/amura-test' => Http::response([
                    'base64' => $rawBase64,
                    'code' => 'QR',
                ], 200),
            ]
        ));

        $result = $this->service->fetchQrCode();

        expect($result['base64'])->toStartWith('data:image/png;base64,');
    });

    it('retorna status pending quando a Evolution ainda nao disponibilizou o QR Code', function () {
        Http::fake(array_merge(
            evolutionClosedStateResponses(),
            [
                'https://evolution.test/instance/connect/amura-test' => Http::response([
                    'count' => 0,
                ], 200),
            ]
        ));

        $result = $this->service->fetchQrCode();

        expect($result['status'])->toBe('pending')
            ->and($result['state'])->toBe('connecting')
            ->and($result['base64'])->toBe('')
            ->and($result['code'])->toBe('')
            ->and($result['message'])->toContain('sendo preparado');
    });

    it('recria a instancia automaticamente quando a Evolution responde 404 no QR Code', function () {
        Http::fake([
            'https://evolution.test/instance/connectionState/amura-test' => Http::response([
                'instance' => ['state' => 'close'],
            ], 200),
            'https://evolution.test/webhook/find/amura-test' => Http::sequence()
                ->push(evolutionConfiguredWebhookPayload(), 200)
                ->push(null, 200),
            'https://evolution.test/instance/connect/amura-test' => Http::response(['error' => 'not found'], 404),
            'https://evolution.test/instance/fetchInstances' => Http::response([], 200),
            'https://evolution.test/instance/create' => Http::response([
                'instance' => ['instanceName' => 'amura-test'],
            ], 201),
            'https://evolution.test/webhook/set/amura-test' => Http::response([
                'enabled' => true,
            ], 201),
        ]);

        $result = $this->service->fetchQrCode();

        expect($result['status'])->toBe('pending')
            ->and($result['state'])->toBe('initializing')
            ->and($result['message'])->toContain('foi recriada');

        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/instance/create');
        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/webhook/set/amura-test');
    });

    it('retorna status pending quando a Evolution ainda esta iniciando e a conexao falha', function () {
        Http::fake([
            'https://evolution.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('connection refused'),
        ]);

        $result = $this->service->fetchQrCode();

        expect($result['status'])->toBe('pending')
            ->and($result['state'])->toBe('booting')
            ->and($result['message'])->toContain('inicializando');
    });

    it('lança RuntimeException quando API retorna erro HTTP irrecuperavel', function () {
        Http::fake(array_merge(
            evolutionClosedStateResponses(),
            [
                'https://evolution.test/instance/connect/amura-test' => Http::response(['error' => 'server error'], 500),
            ]
        ));

        expect(fn () => $this->service->fetchQrCode())
            ->toThrow(\RuntimeException::class);
    });

    it('retorna pending quando a resposta ainda não contém base64 nem code', function () {
        Http::fake(array_merge(
            evolutionClosedStateResponses(),
            [
                'https://evolution.test/instance/connect/amura-test' => Http::response([
                    'status' => 'ok',
                    'count' => 2,
                ], 200),
            ]
        ));

        expect($this->service->fetchQrCode())
            ->toMatchArray([
                'status' => 'pending',
                'state' => 'connecting',
                'base64' => '',
                'code' => '',
            ]);
    });

    it('lança RuntimeException quando provedor não está configurado', function () {
        config(['whatsapp.evolution_api_key' => '']);
        expect(fn () => (new EvolutionInstanceService)->fetchQrCode())
            ->toThrow(\RuntimeException::class, 'não configurado');
    });

    it('envia o header apikey correto na requisição', function () {
        Http::fake(array_merge(
            evolutionClosedStateResponses(),
            [
                'https://evolution.test/instance/connect/amura-test' => Http::response([
                    'base64' => 'data:image/png;base64,abc',
                    'code' => 'QR',
                ], 200),
            ]
        ));

        $this->service->fetchQrCode();

        Http::assertSent(fn ($request) => $request->hasHeader('apikey', 'test-api-key-123') &&
            str_contains($request->url(), '/instance/connect/amura-test')
        );
    });

    it('recria a instancia automaticamente quando connectionState retorna 404', function () {
        Http::fake([
            'https://evolution.test/instance/connectionState/amura-test' => Http::response(['error' => 'not found'], 404),
            'https://evolution.test/instance/fetchInstances' => Http::response([], 200),
            'https://evolution.test/instance/create' => Http::response([
                'instance' => ['instanceName' => 'amura-test'],
            ], 201),
            'https://evolution.test/webhook/find/amura-test' => Http::response(null, 200),
            'https://evolution.test/webhook/set/amura-test' => Http::response([
                'enabled' => true,
            ], 201),
        ]);

        $result = $this->service->connectionState();

        expect($result['state'])->toBe('close')
            ->and($result['instance'])->toBe('amura-test');
    });
});
