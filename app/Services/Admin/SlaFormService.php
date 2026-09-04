<?php

namespace App\Services\Admin;

class SlaFormService
{
    /**
     * @param array<string, array<string, int>> $thresholds
     * @param array<string, mixed> $oldInput
     * @return array<int, array<string, int|string>>
     */
    public function buildRows(array $thresholds, array $oldInput = []): array
    {
        $labels = [
            'high' => 'Alta (Urgente + Alta)',
            'med' => 'Média (combinações intermediárias)',
            'low' => 'Baixa',
        ];

        $rows = [];

        foreach ($labels as $key => $label) {
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'attention' => (int) ($oldInput["sla_{$key}_attention"] ?? $thresholds[$key]['attention']),
                'warning' => (int) ($oldInput["sla_{$key}_warning"] ?? $thresholds[$key]['warning']),
                'critical' => (int) ($oldInput["sla_{$key}_critical"] ?? $thresholds[$key]['critical']),
            ];
        }

        return $rows;
    }
}
