<?php

namespace App\Repositories;

use App\Contracts\Repositories\ImplantacaoRepositoryInterface;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\Ticket\Status;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ImplantacaoRepository implements ImplantacaoRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getImplementationClientIds(): array
    {
        return DB::table('schedule_record')
            ->where('status', 1)
            ->pluck('customer_id')
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function countActiveSchedules(): int
    {
        return Schedule::active()
            ->whereNotIn('status', ['fin', 'can'])
            ->whereHas('records', fn ($q) => $q->where('status', 1))
            ->count();
    }

    /**
     * {@inheritdoc}
     *
     * Considera como "aberto" qualquer ticket cujo status_id não seja terminal.
     * A lista de IDs terminais é consultada diretamente para suportar SQLite em testes.
     */
    public function getOpenTicketsCount(array $clientIds): int
    {
        if (empty($clientIds)) {
            return 0;
        }

        $terminalIds = Status::where('is_terminal', true)->pluck('id')->toArray();

        return DB::table('ticketit')
            ->whereIn('company_id', $clientIds)
            ->when(!empty($terminalIds), fn ($q) => $q->whereNotIn('status_id', $terminalIds))
            ->count();
    }

    /**
     * {@inheritdoc}
     *
     * Atenção: utiliza TIMESTAMPDIFF — função exclusiva do MySQL.
     * Em ambientes SQLite (testes), este método retornará 0 pois a função não existe.
     */
    public function getTotalMinutes(array $clientIds): int
    {
        if (empty($clientIds)) {
            return 0;
        }

        if ($this->usesSqliteConnection()) {
            return $this->calculateTotalMinutesForClients($clientIds);
        }

        return (int) DB::table('schedule_record')
            ->whereIn('customer_id', $clientIds)
            ->whereNotNull('start')
            ->whereNotNull('end')
            ->selectRaw('
                SUM(
                    TIMESTAMPDIFF(MINUTE, start, `end`) -
                    COALESCE(TIMESTAMPDIFF(MINUTE, interval_start, interval_end), 0)
                ) as total_minutes
            ')
            ->value('total_minutes');
    }

    /**
     * {@inheritdoc}
     *
     * Eager loading de state e software previne N+1 no loop de exibição.
     * Os campos active_records e total_minutes são computados por cliente.
     *
     * Nota: total_minutes usa TIMESTAMPDIFF (MySQL only).
     */
    public function getClientsWithDetails(array $clientIds): Collection
    {
        if (empty($clientIds)) {
            return new Collection();
        }

        $usesSqlite = $this->usesSqliteConnection();

        return Company::whereIn('id', $clientIds)
            ->with(['state', 'software'])
            ->get()
            ->map(function (Company $client) use ($usesSqlite) {
                $client->active_records = DB::table('schedule_record')
                    ->where('customer_id', $client->id)
                    ->where('status', 1)
                    ->count();

                $client->total_minutes = $usesSqlite
                    ? $this->calculateTotalMinutesForClients([$client->id])
                    : (int) DB::table('schedule_record')
                        ->where('customer_id', $client->id)
                        ->whereNotNull('start')
                        ->whereNotNull('end')
                        ->selectRaw('
                            SUM(
                                TIMESTAMPDIFF(MINUTE, start, `end`) -
                                COALESCE(TIMESTAMPDIFF(MINUTE, interval_start, interval_end), 0)
                            ) as total_minutes
                        ')
                        ->value('total_minutes');

                return $client;
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * {@inheritdoc}
     *
     * Eager loading de customer, agent, module e records (ativos) previne N+1.
     * withCount de active_records_count evita query extra por item na view.
     */
    public function getSchedulesPaginated(int $perPage): LengthAwarePaginator
    {
        return Schedule::active()
            ->whereNotIn('status', ['fin', 'can'])
            ->whereHas('records', fn ($q) => $q->where('status', 1))
            ->with([
                'customer',
                'agent',
                'module',
                'records' => fn ($q) => $q->where('status', 1),
            ])
            ->withCount(['records as active_records_count' => fn ($q) => $q->where('status', 1)])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    private function usesSqliteConnection(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private function calculateTotalMinutesForClients(array $clientIds): int
    {
        return DB::table('schedule_record')
            ->whereIn('customer_id', $clientIds)
            ->whereNotNull('start')
            ->whereNotNull('end')
            ->get(['start', 'end', 'interval_start', 'interval_end'])
            ->sum(function ($record) {
                $minutes = Carbon::parse($record->start)->diffInMinutes(Carbon::parse($record->end));

                if ($record->interval_start && $record->interval_end) {
                    $minutes -= Carbon::parse($record->interval_start)
                        ->diffInMinutes(Carbon::parse($record->interval_end));
                }

                return max(0, $minutes);
            });
    }
}
