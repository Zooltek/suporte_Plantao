<?php

namespace App\Contracts\Helpdesk\Ticketit;

use App\Models\Ticket\Ticket;

interface SlaServiceInterface
{
    /**
     * @return array<string, array<string, int>>
     */
    public function getThresholdsMatrix(): array;

    /**
     * @param array<string, mixed> $validatedInput
     */
    public function updateThresholds(array $validatedInput): void;

    public function calculatePriorityWeight(Ticket $ticket): int;

    public function resolveSlaLevel(Ticket $ticket): string;

    /**
     * @return array<string, int>
     */
    public function getThresholdsByWeight(int $priorityWeight): array;
}
