<?php

namespace App\Services\Helpdesk\Ticketit;

use App\Contracts\Helpdesk\Ticketit\SlaServiceInterface;
use App\Contracts\Repositories\SlaRepositoryInterface;
use App\Exceptions\SlaConfigurationException;
use App\Models\Category;
use App\Models\Ticket\Ticket;
use Carbon\Carbon;

/**
 * Implementação do serviço de SLA.
 *
 * Os limiares são armazenados em ticketit_settings (slugs sla_*) em minutos.
 * Mapeamento de peso → faixa de SLA:
 *   weight >= 6 → 'high'   (ex: urgente+urgente, urgente+alta, alta+alta)
 *   weight >= 4 → 'med'    (ex: urgente+baixa, alta+baixa)
 *   weight <  4 → 'low'    (ex: baixa+baixa, sem subcategoria)
 */
class SlaService implements SlaServiceInterface
{
    private const TIER_HIGH = 'high';
    private const TIER_MED  = 'med';
    private const TIER_LOW  = 'low';

    private const SLUGS = [
        self::TIER_HIGH => ['attention' => 'sla_high_attention', 'warning' => 'sla_high_warning', 'critical' => 'sla_high_critical'],
        self::TIER_MED  => ['attention' => 'sla_med_attention',  'warning' => 'sla_med_warning',  'critical' => 'sla_med_critical'],
        self::TIER_LOW  => ['attention' => 'sla_low_attention',  'warning' => 'sla_low_warning',  'critical' => 'sla_low_critical'],
    ];

    public function __construct(
        private readonly SlaRepositoryInterface $repository,
    ) {}

    /**
     * Retorna matriz de limiares indexada por faixa e nível.
     * Ex: ['high' => ['attention' => 10, 'warning' => 20, 'critical' => 30], ...]
     *
     * @return array<string, array<string, int>>
     */
    public function getThresholdsMatrix(): array
    {
        $allSlugs = collect(self::SLUGS)->flatten()->values()->all();

        $settings = $this->repository->getSettingsBySlugs($allSlugs);

        $matrix = [];

        foreach (self::SLUGS as $tier => $levels) {
            foreach ($levels as $level => $slug) {
                $matrix[$tier][$level] = (int) ($settings[$slug] ?? 0);
            }
        }

        return $matrix;
    }

    /**
     * Atualiza os limiares de SLA no banco.
     * Espera campos como: sla_high_attention, sla_med_warning, etc.
     *
     * @param array<string, mixed> $validatedInput
     */
    public function updateThresholds(array $validatedInput): void
    {
        $this->assertMonotonicThresholds($validatedInput);

        $allSlugs = collect(self::SLUGS)->flatten()->values()->all();

        $payload = [];

        foreach ($allSlugs as $slug) {
            if (array_key_exists($slug, $validatedInput)) {
                $payload[$slug] = $validatedInput[$slug];
            }
        }

        $this->repository->updateSettings($payload);
    }

    /**
     * Calcula o peso de prioridade do ticket somando os pesos de
     * categoria e subcategoria (baseado em Category::PRIORITY_WEIGHTS).
     */
    public function calculatePriorityWeight(Ticket $ticket): int
    {
        $catWeight    = Category::PRIORITY_WEIGHTS[$ticket->category?->priority    ?? ''] ?? 0;
        $subCatWeight = Category::PRIORITY_WEIGHTS[$ticket->subCategory?->priority ?? ''] ?? 0;

        return $catWeight + $subCatWeight;
    }

    /**
     * Retorna os limiares (em minutos) correspondentes ao peso de prioridade.
     *
     * @return array<string, int>
     */
    public function getThresholdsByWeight(int $priorityWeight): array
    {
        $tier = $this->resolveTier($priorityWeight);

        return $this->getThresholdsMatrix()[$tier] ?? ['attention' => 30, 'warning' => 60, 'critical' => 90];
    }

    /**
     * Resolve o nível de SLA do ticket:
     *   'resolved'  → ticket encerrado (possui completed_at)
     *   'critical'  → tempo ultrapassou o limiar crítico
     *   'warning'   → tempo ultrapassou o limiar de aviso
     *   'attention' → tempo ultrapassou o limiar de atenção
     *   'normal'    → dentro do prazo
     */
    public function resolveSlaLevel(Ticket $ticket): string
    {
        if ($ticket->completed_at) {
            return 'resolved';
        }

        $minutesOpen = (int) Carbon::parse($ticket->created_at)->diffInMinutes(Carbon::now());

        $weight     = $this->calculatePriorityWeight($ticket);
        $thresholds = $this->getThresholdsByWeight($weight);

        return match (true) {
            $minutesOpen >= $thresholds['critical']  => 'critical',
            $minutesOpen >= $thresholds['warning']   => 'warning',
            $minutesOpen >= $thresholds['attention'] => 'attention',
            default                                  => 'normal',
        };
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function resolveTier(int $weight): string
    {
        return match (true) {
            $weight >= 6 => self::TIER_HIGH,
            $weight >= 4 => self::TIER_MED,
            default      => self::TIER_LOW,
        };
    }

    /**
     * Garante que, para cada tier, attention < warning < critical.
     * Dispara SlaConfigurationException em caso de violação.
     */
    private function assertMonotonicThresholds(array $input): void
    {
        foreach (self::SLUGS as $tier => $levels) {
            $attention = (int) ($input[$levels['attention']] ?? 0);
            $warning   = (int) ($input[$levels['warning']] ?? 0);
            $critical  = (int) ($input[$levels['critical']] ?? 0);

            if (!($attention > 0 && $warning > 0 && $critical > 0)) {
                throw new SlaConfigurationException("Valores de SLA para {$tier} devem ser maiores que zero.");
            }

            if (!($attention < $warning && $warning < $critical)) {
                throw new SlaConfigurationException("Ordem inválida para o tier {$tier}: atenção < aviso < crítico.");
            }
        }
    }
}
