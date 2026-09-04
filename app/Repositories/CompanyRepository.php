<?php

namespace App\Repositories;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CompanyRepository implements CompanyRepositoryInterface
{
    private const TERMINAL_STATUS_CACHE_TTL = 3600;

    // -------------------------------------------------------------------------
    // Company Tickets
    // -------------------------------------------------------------------------

    public function paginateCompanyTickets(
        Company $company,
        array $filters,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->companyTicketsQuery($company, $filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function limitCompanyTickets(
        Company $company,
        array $filters,
        int $take,
    ): Collection {
        return $this->companyTicketsQuery($company, $filters)
            ->limit($take)
            ->get();
    }

    /**
     * Constrói a query base de tickets por empresa com eager loading e filtros.
     * Eager loading de status, agent e category previne N+1 no loop de exibição.
     */
    private function companyTicketsQuery(Company $company, array $filters): Builder
    {
        $query = Ticket::query()
            ->with(['status', 'agent', 'category.description', 'subCategory.description', 'issues'])
            ->withSlaDependencies()
            ->where('company_id', $company->id)
            ->orderByDesc('ticketit.created_at');

        if (! empty($filters['start'])) {
            $query->whereDate('ticketit.created_at', '>=', Carbon::parse($filters['start'])->toDateString());
        }

        if (! empty($filters['end'])) {
            $query->whereDate('ticketit.created_at', '<=', Carbon::parse($filters['end'])->toDateString());
        }

        if (! empty($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query;
    }

    // -------------------------------------------------------------------------
    // Status helpers (com cache)
    // -------------------------------------------------------------------------

    public function getTerminalStatusIds(): array
    {
        return Cache::remember(
            'ticket.status_ids.terminal',
            self::TERMINAL_STATUS_CACHE_TTL,
            static fn () => Status::where('is_terminal', true)->pluck('id')->toArray()
        );
    }

    // -------------------------------------------------------------------------
    // Pending & Finalized Tickets
    // -------------------------------------------------------------------------

    public function getPendingTickets(Company $company, array $terminalIds): Collection
    {
        return Ticket::query()
            ->withSlaDependencies()
            ->where('company_id', $company->id)
            ->queuePending()
            ->get();
    }

    public function getFinalizedTickets(
        Company $company,
        array $terminalIds,
        int $limit = 5,
    ): Collection {
        return Ticket::query()
            ->with([
                'status:id,name,color',
                'agent:id,name',
                'category:category_id,parent_id,priority',
                'category.description:category_id,name',
                'issues',
            ])
            ->where('company_id', $company->id)
            ->whereIn('status_id', $terminalIds)
            ->orderByDesc('completed_at')
            ->limit($limit)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Schedules
    // -------------------------------------------------------------------------

    public function getCompanySchedules(Company $company): Collection
    {
        return Schedule::where('customer_id', $company->id)
            ->orderByDesc('start_at')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Company listing & search
    // -------------------------------------------------------------------------

    public function listWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Company::with('state');

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('trade_name')->paginate($perPage);
    }

    /**
     * Busca avançada para API (autocomplete / filtros externos).
     * Retorna array de dados simplificados para renderização via Alpine.js.
     * Busca sem acento usando COLLATE para busca flexível.
     */
    public function searchForApi(array $filters): Collection
    {
        $query = Company::query();

        if (($status = data_get($filters, 'status'))) {
            $query->where('is_active', $status === 'active');
        }

        foreach (['ecommerce', 'crm', 'tef'] as $service) {
            if (data_get($filters, 'service') === $service) {
                $query->where("has_{$service}", true);
            }
        }

        if (strlen($q = trim((string) data_get($filters, 'q', ''))) >= 2) {
            $this->applyFlexibleCompanySearch($query, $q);
        }

        foreach (['name', 'cnpj', 'contact_name' => 'contact'] as $dbField => $filterKey) {
            $key = is_int($dbField) ? $filterKey : $dbField;
            if ($val = data_get($filters, $filterKey)) {
                $query->where($key, 'like', "%{$val}%");
            }
        }

        $items = $query->with([
            'state:id,abbreviation',
            'group:id,hash',
            'moduleTypes.ratModule' => fn ($query) => $query->withCount('elementTypes'),
            'scheduleModules' => fn ($query) => $query->withCount('elementTypes'),
        ])
            ->limit(15)
            ->get();

        $result = collect();
        foreach ($items as $c) {
            $result->push([
                'id' => $c->id,
                'name' => $c->name,
                'trade_name' => $c->trade_name,
                'cnpj' => $c->cnpj,
                'contact_name' => $c->contact_name,
                'phone' => $c->phone,
                'city' => $c->city,
                'state_abbr' => $c->state?->abbr,
                'observations' => $c->observations,
                'is_active' => $c->is_active,
                'financial_irregular' => $c->financial_irregular,
                'group_hash' => $c->group?->hash,
                'module_types' => $c->moduleTypes->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'rat_template_id' => $m->rat_module_id,
                    'rat_template_name' => $m->ratModule?->name,
                    'rat_template_project' => $m->ratModule?->project,
                    'rat_template_item_count' => (int) ($m->ratModule?->element_types_count ?? 0),
                ])->values(),
                'schedule_rat_modules' => $c->scheduleModules
                    ->filter(fn ($m) => (int) ($m->element_types_count ?? 0) > 0)
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                        'project' => $m->project,
                        'element_count' => (int) ($m->element_types_count ?? 0),
                    ])->values(),
            ]);
        }

        return $result;
    }

    /**
     * Remove acentos de uma string para busca flexível.
     */
    private function removeAccents(string $term): string
    {
        if (! class_exists(\Normalizer::class)) {
            return Str::ascii($term);
        }

        $normalized = \Normalizer::normalize($term, \Normalizer::FORM_D);

        return preg_replace('/[\x{0300}-\x{036f}]/u', '', $normalized) ?: $term;
    }

    private function applyFlexibleCompanySearch(Builder $query, string $term): void
    {
        $like = "%{$term}%";
        $normalizedLike = '%'.mb_strtolower($this->removeAccents($term)).'%';
        $digits = preg_replace('/\D+/', '', $term) ?: '';

        $query->where(function (Builder $query) use ($like, $normalizedLike, $digits) {
            foreach (['name', 'trade_name', 'contact_name', 'city', 'phone'] as $column) {
                $query
                    ->orWhere($column, 'like', $like)
                    ->orWhereRaw($this->unaccentExpression($column).' LIKE ?', [$normalizedLike]);
            }

            if ($digits !== '') {
                $query
                    ->orWhereRaw($this->digitsOnlyExpression('cnpj').' LIKE ?', ["%{$digits}%"])
                    ->orWhereRaw($this->digitsOnlyExpression('phone').' LIKE ?', ["%{$digits}%"]);
            } else {
                $query->orWhere('cnpj', 'like', $like);
            }

            $query->orWhereHas('state', function (Builder $stateQuery) use ($like, $normalizedLike) {
                $stateQuery
                    ->where('abbreviation', 'like', $like)
                    ->orWhereRaw($this->unaccentExpression('states.name').' LIKE ?', [$normalizedLike]);
            });
        });
    }

    private function unaccentExpression(string $column): string
    {
        $expression = "LOWER(COALESCE({$column}, ''))";

        foreach ($this->accentReplacementMap() as $accented => $plain) {
            $expression = "REPLACE({$expression}, '{$accented}', '{$plain}')";
        }

        return $expression;
    }

    private function digitsOnlyExpression(string $column): string
    {
        $expression = "COALESCE({$column}, '')";

        foreach (['.', '/', '-', ' ', '(', ')'] as $char) {
            $expression = "REPLACE({$expression}, '{$char}', '')";
        }

        return $expression;
    }

    /**
     * @return array<string, string>
     */
    private function accentReplacementMap(): array
    {
        return [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
        ];
    }

    public function getLatestTicketTechnicalContexts(array $companyIds): array
    {
        $companyIds = array_values(array_unique(array_map('intval', array_filter($companyIds))));

        if ($companyIds === []) {
            return [];
        }

        return Ticket::query()
            ->select(['company_id', 'version', 'release', 'created_at', 'id'])
            ->whereIn('company_id', $companyIds)
            ->where(function (Builder $query) {
                $query
                    ->where(function (Builder $subQuery) {
                        $subQuery->whereNotNull('version')->where('version', '!=', '');
                    })
                    ->orWhere(function (Builder $subQuery) {
                        $subQuery->whereNotNull('release')->where('release', '!=', '');
                    });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('company_id')
            ->map(function (Collection $tickets) {
                $latest = $tickets->first();

                return [
                    'version' => (string) ($latest->version ?? ''),
                    'release' => (string) ($latest->release ?? ''),
                    'created_at' => $latest->created_at,
                ];
            })
            ->all();
    }

    // -------------------------------------------------------------------------
    // Persistence
    // -------------------------------------------------------------------------

    public function save(Company $company): void
    {
        $company->save();
    }
}
