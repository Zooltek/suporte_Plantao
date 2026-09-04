<?php

namespace App\Support\Tickets;

use Illuminate\Support\Str;

final class TicketStatusCatalog
{
    public const OPEN_ID = 1;

    public const IN_PROGRESS_ID = 2;

    public const RESOLVED_ID = 3;

    public const PENDING_ID = 4;

    public const UNRESOLVED_ID = 5;

    public const TECHNICAL_VISIT_ID = 6;

    public const REQUEST_ID = 7;

    /**
     * @return array<int, array{id: int, name: string, color: string, is_terminal: bool, requires_schedule: bool, requires_solution: bool, requires_agent: bool}>
     */
    public static function definitions(): array
    {
        return [
            [
                'id' => self::OPEN_ID,
                'name' => 'Aberto',
                'color' => '#2563eb',
                'is_terminal' => false,
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent' => false,
            ],
            [
                'id' => self::IN_PROGRESS_ID,
                'name' => 'Em andamento',
                'color' => '#f59e0b',
                'is_terminal' => false,
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent' => true,
            ],
            [
                'id' => self::RESOLVED_ID,
                'name' => 'Resolvido',
                'color' => '#10b981',
                'is_terminal' => true,
                'requires_schedule' => false,
                'requires_solution' => true,
                'requires_agent' => true,
            ],
            [
                'id' => self::PENDING_ID,
                'name' => 'Pendente',
                'color' => '#f97316',
                'is_terminal' => false,
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent' => false,
            ],
            [
                'id' => self::UNRESOLVED_ID,
                'name' => 'Não Resolvido',
                'color' => '#6b7280',
                'is_terminal' => true,
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent' => true,
            ],
            [
                'id' => self::TECHNICAL_VISIT_ID,
                'name' => 'Visita Técnica',
                'color' => '#f97316',
                'is_terminal' => false,
                'requires_schedule' => true,
                'requires_solution' => false,
                'requires_agent' => true,
            ],
            [
                'id' => self::REQUEST_ID,
                'name' => 'Solicitação',
                'color' => '#8b5cf6',
                'is_terminal' => false,
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent' => false,
            ],
        ];
    }

    /**
     * @return int[]
     */
    public static function manualDecisionIds(): array
    {
        return [
            self::PENDING_ID,
            self::IN_PROGRESS_ID,
            self::TECHNICAL_VISIT_ID,
            self::RESOLVED_ID,
            self::UNRESOLVED_ID,
            self::REQUEST_ID,
        ];
    }

    /**
     * @return int[]
     */
    public static function quickUpdateIds(): array
    {
        return [
            self::PENDING_ID,
            self::IN_PROGRESS_ID,
            self::TECHNICAL_VISIT_ID,
        ];
    }

    /**
     * @return int[]
     */
    public static function closeWorkflowIds(): array
    {
        return [
            self::RESOLVED_ID,
            self::UNRESOLVED_ID,
        ];
    }

    /**
     * @return int[]
     */
    public static function preserveWithoutAgentIds(): array
    {
        return [
            self::PENDING_ID,
            self::REQUEST_ID,
        ];
    }

    /**
     * @return int[]
     */
    public static function preserveWithAgentIds(): array
    {
        return [
            self::PENDING_ID,
            self::IN_PROGRESS_ID,
            self::TECHNICAL_VISIT_ID,
            self::RESOLVED_ID,
            self::UNRESOLVED_ID,
            self::REQUEST_ID,
        ];
    }

    /**
     * @return array{id: int, name: string, color: string, is_terminal: bool, requires_schedule: bool, requires_solution: bool, requires_agent: bool}|null
     */
    public static function findById(int $id): ?array
    {
        foreach (self::definitions() as $definition) {
            if ($definition['id'] === $id) {
                return $definition;
            }
        }

        return null;
    }

    public static function normalizeName(?string $name): string
    {
        return (string) Str::of((string) $name)
            ->ascii()
            ->lower()
            ->squish();
    }

    public static function isRequestStatus(int $statusId): bool
    {
        return $statusId === self::REQUEST_ID;
    }
}
