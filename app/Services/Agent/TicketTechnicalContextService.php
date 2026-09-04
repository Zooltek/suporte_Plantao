<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

class TicketTechnicalContextService
{
    private const LEGACY_VERSION_MAP = [
        1 => '01.28.01',
        2 => '01.30.01',
        3 => '01.32.01',
    ];

    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
    ) {}

    /**
     * Monta o catálogo de contexto técnico consumido pela tela de criação/edição.
     *
     * @param  EloquentCollection<int, Company>  $companies
     * @return array{contexts: array<string, array<string, mixed>>, versionCatalog: string[]}
     */
    public function buildForCompanies(EloquentCollection $companies): array
    {
        $companyIds = $companies->pluck('id')->map(fn ($id) => (int) $id)->all();
        $latestContexts = $this->companyRepository->getLatestTicketTechnicalContexts($companyIds);
        $technicalByCompany = [];

        foreach ($companies as $company) {
            $companyId = (string) $company->id;
            $latestContext = $latestContexts[$company->id] ?? [];
            $softwareVersion = $this->normalizeVersion($company->software?->version);
            $suggestedVersion = $this->normalizeVersion($latestContext['version'] ?? null);
            $suggestedRelease = $this->normalizeRelease($latestContext['release'] ?? null);

            $technicalByCompany[$companyId] = [
                'software_name' => (string) ($company->software?->name ?? ''),
                'software_version' => $softwareVersion,
                'suggested_version' => $suggestedVersion,
                'suggested_release' => $suggestedRelease,
                'has_suggestion' => $suggestedVersion !== '' || $suggestedRelease !== '',
                'latest_context_at' => $this->formatDateTime($latestContext['created_at'] ?? null),
            ];
        }

        return [
            'contexts' => $technicalByCompany,
            'versionCatalog' => $this->buildVersionCatalog($companies, $latestContexts),
        ];
    }

    public function normalizeVersion(string|int|null $version): string
    {
        if ($version === null) {
            return '';
        }

        $normalized = trim((string) $version);

        if ($normalized === '') {
            return '';
        }

        if (ctype_digit($normalized)) {
            return self::LEGACY_VERSION_MAP[(int) $normalized] ?? '';
        }

        if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{1,2}$/', $normalized) === 1) {
            $segments = array_map(
                static fn (string $segment): string => str_pad($segment, 2, '0', STR_PAD_LEFT),
                explode('.', $normalized)
            );

            return implode('.', $segments);
        }

        return $normalized;
    }

    public function normalizeRelease(?string $release): string
    {
        return trim((string) $release);
    }

    /**
     * @param  EloquentCollection<int, Company>  $companies
     * @param  array<int, array{version: string, release: string, created_at: mixed}>  $latestContexts
     * @return string[]
     */
    private function buildVersionCatalog(EloquentCollection $companies, array $latestContexts): array
    {
        $softwareVersions = $companies
            ->map(fn (Company $company): string => $this->normalizeVersion($company->software?->version))
            ->filter();

        $ticketVersions = collect($latestContexts)
            ->pluck('version')
            ->map(fn ($version): string => $this->normalizeVersion($version))
            ->filter();

        $catalog = collect(self::LEGACY_VERSION_MAP)
            ->merge($softwareVersions)
            ->merge($ticketVersions)
            ->unique()
            ->sort(SORT_NATURAL)
            ->values();

        return $catalog->all();
    }

    private function formatDateTime(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('d/m/Y H:i');
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
