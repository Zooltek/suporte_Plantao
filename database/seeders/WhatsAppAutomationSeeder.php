<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\WhatsApp\WhatsAppBotMessage;
use App\Models\WhatsApp\WhatsAppMessageMacro;
use App\Models\WhatsApp\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppBotMessageService;
use Illuminate\Database\Seeder;

class WhatsAppAutomationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (WhatsAppBotMessageService::DEFAULT_MESSAGES as $key => $meta) {
            $text = (string) config("whatsapp.messages.{$key}", '');

            $message = WhatsAppBotMessage::query()->firstOrCreate(
                ['key' => $key],
                [
                    'step' => $meta['step'],
                    'text' => $text,
                    'is_active' => true,
                ]
            );

            $changes = [];

            if (blank($message->text)) {
                $changes['text'] = $text;
            }

            if (blank($message->step)) {
                $changes['step'] = $meta['step'];
            }

            if ($message->is_active === null) {
                $changes['is_active'] = true;
            }

            if ($changes !== []) {
                $message->update($changes);
            }
        }

        foreach ([
            'business_hours_start' => '08:30',
            'business_hours_end' => '18:00',
            'business_days' => '1,2,3,4,5',
            'out_of_hours_cooldown_minutes' => '120',
            'ticket_closed_delay_minutes' => '10',
        ] as $key => $value) {
            WhatsAppSetting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        WhatsAppMessageMacro::query()->firstOrCreate(
            ['command' => '/sefaz'],
            [
                'text' => 'Sefaz fora do ar favor aguardar.',
                'department_id' => Department::query()->where('name', 'like', '%Suporte%')->value('id'),
                'is_active' => true,
            ]
        );
    }
}
