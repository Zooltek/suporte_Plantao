<?php

use App\Models\WhatsApp\WhatsAppBotMessage;
use App\Services\WhatsApp\WhatsAppBotMessageService;
use Database\Seeders\WhatsAppAutomationSeeder;

describe('WhatsAppAutomationSeeder — mensagens do bot', function () {

    it('cria todas as mensagens padrão definidas no serviço', function () {
        $this->seed(WhatsAppAutomationSeeder::class);

        expect(WhatsAppBotMessage::query()->count())->toBe(count(WhatsAppBotMessageService::DEFAULT_MESSAGES));

        foreach (WhatsAppBotMessageService::DEFAULT_MESSAGES as $key => $meta) {
            $message = WhatsAppBotMessage::query()->where('key', $key)->first();

            expect($message)->not->toBeNull()
                ->and($message->step)->toBe($meta['step'])
                ->and($message->text)->toBe(config("whatsapp.messages.{$key}"))
                ->and($message->is_active)->toBeTrue();
        }
    });

    it('não sobrescreve mensagem personalizada já preenchida', function () {
        WhatsAppBotMessage::query()->create([
            'key' => 'greeting_identified',
            'step' => 'personalizado',
            'text' => 'Mensagem personalizada',
            'is_active' => false,
        ]);

        $this->seed(WhatsAppAutomationSeeder::class);

        $message = WhatsAppBotMessage::query()->where('key', 'greeting_identified')->first();

        expect($message)->not->toBeNull()
            ->and($message->step)->toBe('personalizado')
            ->and($message->text)->toBe('Mensagem personalizada')
            ->and($message->is_active)->toBeFalse();
    });

    it('preenche texto vazio sem apagar personalizações existentes', function () {
        WhatsAppBotMessage::query()->create([
            'key' => 'greeting_identified',
            'step' => 'personalizado',
            'text' => '',
            'is_active' => false,
        ]);

        $this->seed(WhatsAppAutomationSeeder::class);

        $message = WhatsAppBotMessage::query()->where('key', 'greeting_identified')->first();

        expect($message)->not->toBeNull()
            ->and($message->step)->toBe('personalizado')
            ->and($message->text)->toBe(config('whatsapp.messages.greeting_identified'))
            ->and($message->is_active)->toBeFalse();
    });

    it('é idempotente e não duplica mensagens', function () {
        $this->seed(WhatsAppAutomationSeeder::class);
        $this->seed(WhatsAppAutomationSeeder::class);

        foreach (array_keys(WhatsAppBotMessageService::DEFAULT_MESSAGES) as $key) {
            expect(WhatsAppBotMessage::query()->where('key', $key)->count())->toBe(1);
        }
    });

});
