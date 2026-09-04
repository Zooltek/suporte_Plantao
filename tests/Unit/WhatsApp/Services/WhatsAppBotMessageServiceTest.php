<?php

/**
 * Regressão: a tela admin de mensagens do bot (editableMessages) não exibia
 * todas as mensagens utilizadas pelo ChatBotService — o fluxo novo
 * (cliente identificado / não localizado / escolha de setor) ficou de fora.
 */

use App\Models\WhatsApp\WhatsAppBotMessage;
use App\Services\WhatsApp\WhatsAppBotMessageService;

describe('WhatsAppBotMessageService — editableMessages', function () {

    it('expõe para edição todas as mensagens usadas pelo ChatBotService', function () {
        $source = file_get_contents(app_path('Services/WhatsApp/ChatBotService.php'));
        preg_match_all("/message\('([a-z_]+)'\)/", $source, $matches);
        $usedKeys = array_unique($matches[1]);

        expect($usedKeys)->not->toBeEmpty();

        $editableKeys = app(WhatsAppBotMessageService::class)
            ->editableMessages()
            ->pluck('key')
            ->all();

        $missing = array_values(array_diff($usedKeys, $editableKeys));

        expect($missing)->toBe([]);
    });

    it('expõe todas as mensagens definidas em config/whatsapp.php', function () {
        $configKeys = array_keys(config('whatsapp.messages', []));

        $editableKeys = app(WhatsAppBotMessageService::class)
            ->editableMessages()
            ->pluck('key')
            ->all();

        $missing = array_values(array_diff($configKeys, $editableKeys));

        expect($missing)->toBe([]);
    });

    it('usa a mensagem padrão quando o texto salvo está vazio', function () {
        WhatsAppBotMessage::query()->create([
            'key' => 'status_not_found',
            'step' => 'consulta_chamado',
            'text' => '',
            'is_active' => true,
        ]);

        $message = app(WhatsAppBotMessageService::class)
            ->editableMessages()
            ->firstWhere('key', 'status_not_found');

        expect($message['text'])->toBe(config('whatsapp.messages.status_not_found'))
            ->and($message['text'])->not->toBe('');
    });

});

describe('WhatsAppBotMessageService — message', function () {

    it('retorna string vazia quando a mensagem está desativada no banco', function () {
        WhatsAppBotMessage::query()->create([
            'key' => 'not_found_acknowledged',
            'step' => 'cliente_nao_localizado',
            'text' => 'Show! Já anotei tudo.',
            'is_active' => false,
        ]);

        expect(app(WhatsAppBotMessageService::class)->message('not_found_acknowledged'))->toBe('');
    });

    it('retorna texto personalizado quando a mensagem está ativa', function () {
        WhatsAppBotMessage::query()->create([
            'key' => 'not_found_acknowledged',
            'step' => 'cliente_nao_localizado',
            'text' => 'Mensagem personalizada ativa.',
            'is_active' => true,
        ]);

        expect(app(WhatsAppBotMessageService::class)->message('not_found_acknowledged'))
            ->toBe('Mensagem personalizada ativa.');
    });

    it('usa fallback de config quando não há registro no banco', function () {
        expect(app(WhatsAppBotMessageService::class)->message('not_found_acknowledged'))
            ->toBe(config('whatsapp.messages.not_found_acknowledged'));
    });

});
