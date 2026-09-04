<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\WhatsAppBotMessage;
use App\Models\WhatsApp\WhatsAppSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WhatsAppBotMessageService
{
    public const DEFAULT_MESSAGES = [
        'menu' => ['step' => 'saudacao'],
        'human_pending' => ['step' => 'transferencia'],
        'status_found' => ['step' => 'consulta_chamado'],
        'status_not_found' => ['step' => 'consulta_chamado'],
        'greeting' => ['step' => 'identificacao_cliente'],
        'greeting_by_phone' => ['step' => 'identificacao_cliente'],
        'greeting_identified' => ['step' => 'identificacao_cliente'],
        'cnpj_located' => ['step' => 'identificacao_cliente'],
        'cnpj_not_found' => ['step' => 'identificacao_cliente'],
        'ask_company' => ['step' => 'identificacao_cliente'],
        'ask_company_cnpj' => ['step' => 'identificacao_cliente'],
        'ask_company_phone' => ['step' => 'identificacao_cliente'],
        'company_identified' => ['step' => 'identificacao_cliente'],
        'company_not_found' => ['step' => 'identificacao_cliente'],
        'ask_company_not_found' => ['step' => 'cliente_nao_localizado'],
        'not_found_comercial' => ['step' => 'cliente_nao_localizado'],
        'phone_choice' => ['step' => 'cliente_nao_localizado'],
        'phone_registered' => ['step' => 'cliente_nao_localizado'],
        'ask_phone_other' => ['step' => 'cliente_nao_localizado'],
        'not_found_acknowledged' => ['step' => 'cliente_nao_localizado'],
        'not_found_default_problem' => ['step' => 'cliente_nao_localizado'],
        'summary_not_found' => ['step' => 'cliente_nao_localizado'],
        'ask_area' => ['step' => 'escolha_setor'],
        'sector_choice' => ['step' => 'escolha_setor'],
        'problem_for_support' => ['step' => 'escolha_setor'],
        'problem_for_financeiro' => ['step' => 'escolha_setor'],
        'problem_for_comercial' => ['step' => 'escolha_setor'],
        'ask_problem' => ['step' => 'abertura_chamado'],
        'ask_attachment' => ['step' => 'abertura_chamado'],
        'confirm_summary' => ['step' => 'abertura_chamado'],
        'ticket_created' => ['step' => 'abertura_chamado'],
        'invalid_option' => ['step' => 'saudacao'],
        'cancelled' => ['step' => 'encerramento'],
        'goodbye' => ['step' => 'encerramento'],
        'session_expired' => ['step' => 'encerramento'],
        'conversation_finished' => ['step' => 'encerramento'],
        'error' => ['step' => 'encerramento'],
        'out_of_hours' => ['step' => 'fora_expediente'],
    ];

    public function message(string $key): string
    {
        $record = WhatsAppBotMessage::query()
            ->where('key', $key)
            ->first();

        if ($record !== null) {
            if (! $record->is_active) {
                return '';
            }

            $configured = $record->text;

            if (is_string($configured) && trim($configured) !== '') {
                return $configured;
            }
        }

        return (string) config("whatsapp.messages.{$key}", '');
    }

    public function editableMessages(): Collection
    {
        $configured = WhatsAppBotMessage::query()
            ->get()
            ->keyBy('key');

        return collect(self::DEFAULT_MESSAGES)
            ->map(function (array $meta, string $key) use ($configured): array {
                $message = $configured->get($key);

                return [
                    'id' => $message?->id,
                    'key' => $key,
                    'step' => $message?->step ?? $meta['step'],
                    'text' => $this->textOrDefault($message, $key),
                    'is_active' => $message?->is_active ?? true,
                ];
            })
            ->values();
    }

    private function textOrDefault(?WhatsAppBotMessage $message, string $key): string
    {
        $configured = $message?->text;

        if (is_string($configured) && trim($configured) !== '') {
            return $configured;
        }

        return (string) config("whatsapp.messages.{$key}", '');
    }

    public function setting(string $key, string $default): string
    {
        $value = WhatsAppSetting::query()
            ->where('key', $key)
            ->value('value');

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public function outsideBusinessHours(?Carbon $now = null): bool
    {
        $now ??= now();
        $businessDays = collect(explode(',', $this->setting('business_days', '1,2,3,4,5')))
            ->map(fn (string $day): int => (int) trim($day))
            ->filter(fn (int $day): bool => $day >= 1 && $day <= 7)
            ->values()
            ->all();

        if ($businessDays !== [] && ! in_array($now->dayOfWeekIso, $businessDays, true)) {
            return true;
        }

        $startsAt = $this->setting('business_hours_start', '08:30');
        $endsAt = $this->setting('business_hours_end', '18:00');
        $current = $now->format('H:i');

        return $current < $startsAt || $current > $endsAt;
    }

    public function outOfHoursCooldownMinutes(): int
    {
        return max(15, (int) $this->setting('out_of_hours_cooldown_minutes', '120'));
    }
}
