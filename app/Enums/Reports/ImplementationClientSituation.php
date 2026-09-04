<?php

namespace App\Enums\Reports;

enum ImplementationClientSituation: string
{
    case ALL = 'all';
    case TICKETS = 'tickets';
    case SCHEDULES = 'schedules';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Todas as situações',
            self::TICKETS => 'Com tickets',
            self::SCHEDULES => 'Com agendamentos',
            self::BOTH => 'Ambos',
        };
    }

    public function matches(object $client): bool
    {
        $hasTickets = (int) ($client->open_tickets ?? 0) > 0;
        $hasSchedules = (int) ($client->active_schedules ?? 0) > 0;

        return match ($this) {
            self::ALL => $hasTickets || $hasSchedules,
            self::TICKETS => $hasTickets,
            self::SCHEDULES => $hasSchedules,
            self::BOTH => $hasTickets && $hasSchedules,
        };
    }

    public static function selectableCases(): array
    {
        return [
            self::TICKETS,
            self::SCHEDULES,
            self::BOTH,
        ];
    }

    public static function values(): array
    {
        return array_map(
            static fn (self $situation): string => $situation->value,
            self::cases(),
        );
    }
}
