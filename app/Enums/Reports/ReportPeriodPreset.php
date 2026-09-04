<?php

namespace App\Enums\Reports;

use Illuminate\Support\Carbon;

enum ReportPeriodPreset: string
{
    case TODAY = 'today';
    case LAST_7_DAYS = 'last_7_days';
    case LAST_30_DAYS = 'last_30_days';
    case THIS_MONTH = 'this_month';
    case ALL_TIME = 'all_time';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::TODAY => 'Hoje',
            self::LAST_7_DAYS => 'Últimos 7 dias',
            self::LAST_30_DAYS => 'Últimos 30 dias',
            self::THIS_MONTH => 'Este mês',
            self::ALL_TIME => 'Todos os períodos',
            self::CUSTOM => 'Personalizado',
        };
    }

    public function resolveBounds(?Carbon $reference = null): array
    {
        $reference ??= now();
        $end = $reference->copy()->endOfDay();

        return match ($this) {
            self::TODAY => [
                'start' => $reference->copy()->startOfDay(),
                'end' => $end,
            ],
            self::LAST_7_DAYS => [
                'start' => $reference->copy()->subDays(7)->startOfDay(),
                'end' => $end,
            ],
            self::LAST_30_DAYS => [
                'start' => $reference->copy()->subDays(30)->startOfDay(),
                'end' => $end,
            ],
            self::THIS_MONTH => [
                'start' => $reference->copy()->startOfMonth()->startOfDay(),
                'end' => $end,
            ],
            self::ALL_TIME, self::CUSTOM => [
                'start' => null,
                'end' => null,
            ],
        };
    }

    public function description(?Carbon $reference = null): string
    {
        ['start' => $start, 'end' => $end] = $this->resolveBounds($reference);

        return match ($this) {
            self::TODAY => $start?->format('d/m/Y') ?? $this->label(),
            self::ALL_TIME => 'Sem recorte de datas',
            self::CUSTOM => 'Defina manualmente De e Até',
            default => $start && $end
                ? $start->format('d/m/Y').' até '.$end->format('d/m/Y')
                : $this->label(),
        };
    }

    public function displayPeriod(?Carbon $reference = null): string
    {
        ['start' => $start, 'end' => $end] = $this->resolveBounds($reference);

        return match ($this) {
            self::TODAY => $start ? 'Hoje ('.$start->format('d/m/Y').')' : $this->label(),
            self::LAST_7_DAYS, self::LAST_30_DAYS, self::THIS_MONTH => $start && $end
                ? $this->label().' ('.$start->format('d/m/Y').' até '.$end->format('d/m/Y').')'
                : $this->label(),
            self::ALL_TIME => 'Todos os períodos',
            self::CUSTOM => 'Personalizado',
        };
    }

    public static function filterableCases(): array
    {
        return [
            self::TODAY,
            self::LAST_7_DAYS,
            self::LAST_30_DAYS,
            self::THIS_MONTH,
            self::ALL_TIME,
        ];
    }

    public static function values(): array
    {
        return array_map(
            static fn (self $preset): string => $preset->value,
            self::cases(),
        );
    }
}
