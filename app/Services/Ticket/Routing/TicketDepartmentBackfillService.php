<?php

namespace App\Services\Ticket\Routing;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Reclassifica o department_id de chamados antigos com base no department_id
 * da Categoria (ou Subcategoria) deles — fechamento do gap da migração:
 * antes da Fase 2, o departamento era herdado do agente; depois da Fase 1,
 * a categoria passou a carregar essa informação explicitamente.
 *
 * Estratégia conservadora:
 *  - Só toca tickets cujo department_id atual = origem definida (default:
 *    "Suporte Técnico"), pois esses provavelmente foram herdados do agente.
 *  - Só reclassifica se a categoria (ou subcategoria) define um destino
 *    DIFERENTE da origem — evita updates que não mudam nada.
 *  - Idempotente: rodar 2x não faz efeito além do necessário.
 *
 * Operação em modo dry-run por padrão; ativar persistência exige `apply = true`.
 */
class TicketDepartmentBackfillService
{
    public function __construct(
        private readonly TicketDepartmentResolver $resolver,
    ) {}

    /**
     * @return array{plan: array<array{from:int,to:int,count:int}>, total: int, updated: int}
     */
    public function run(
        ?int $fromDepartmentId = null,
        bool $apply = false,
        ?int $limit = null,
        int $chunk = 500,
        ?int $actorUserId = null,
    ): array {
        $origin = $fromDepartmentId ?? $this->detectSupportDepartmentId();

        if ($origin === null) {
            return ['plan' => [], 'total' => 0, 'updated' => 0];
        }

        $resolvedActorId = $apply ? $this->resolveActorUserId($actorUserId) : $actorUserId;

        $query = DB::table('ticketit')
            ->select(['id', 'department_id', 'category_id', 'sub_category_id'])
            ->where('department_id', $origin)
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $plan = [];
        $updated = 0;
        $total = 0;

        $query->chunk($chunk, function ($rows) use ($origin, $apply, &$plan, &$updated, &$total, $resolvedActorId): void {
            foreach ($rows as $row) {
                $total++;

                $targetDepartmentId = $this->resolveTargetDepartmentFor($row);

                if ($targetDepartmentId === null || $targetDepartmentId === $origin) {
                    continue;
                }

                $this->incrementPlan($plan, $origin, $targetDepartmentId);

                if (! $apply) {
                    continue;
                }

                $this->applyUpdate($row->id, $origin, $targetDepartmentId, $resolvedActorId);
                $updated++;
            }
        });

        return ['plan' => $plan, 'total' => $total, 'updated' => $updated];
    }

    private function resolveTargetDepartmentFor(object $row): ?int
    {
        return $this->resolver->resolve(new TicketDepartmentRoutingIntent(
            subCategoryId: $row->sub_category_id ? (int) $row->sub_category_id : null,
            categoryId: $row->category_id ? (int) $row->category_id : null,
            allowSupportFallback: false,
        ));
    }

    /**
     * @param  array<array{from:int,to:int,count:int}>  $plan
     */
    private function incrementPlan(array &$plan, int $from, int $to): void
    {
        $key = "{$from}->{$to}";

        if (! isset($plan[$key])) {
            $plan[$key] = ['from' => $from, 'to' => $to, 'count' => 0];
        }

        $plan[$key]['count']++;
    }

    private function applyUpdate(int $ticketId, int $fromDepartmentId, int $toDepartmentId, ?int $actorUserId): void
    {
        DB::transaction(function () use ($ticketId, $fromDepartmentId, $toDepartmentId, $actorUserId): void {
            DB::table('ticketit')
                ->where('id', $ticketId)
                ->update([
                    'department_id' => $toDepartmentId,
                    'updated_at' => now(),
                ]);

            DB::table('ticketit_audits')->insert([
                'ticket_id' => $ticketId,
                'user_id' => $actorUserId,
                'event' => 'department_backfill',
                'operation' => sprintf(
                    'Reclassificação automática: department_id %d → %d',
                    $fromDepartmentId,
                    $toDepartmentId,
                ),
                'field' => 'department_id',
                'old_value' => (string) $fromDepartmentId,
                'new_value' => (string) $toDepartmentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Log::info('[TicketDepartmentBackfill] Ticket reclassificado.', [
            'ticket_id' => $ticketId,
            'from' => $fromDepartmentId,
            'to' => $toDepartmentId,
            'actor_user_id' => $actorUserId,
        ]);
    }

    private function detectSupportDepartmentId(): ?int
    {
        $id = Department::query()
            ->where('name', 'like', '%Suporte%')
            ->orderBy('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Garante que a auditoria sempre tenha um usuário associado, mesmo quando
     * o comando é disparado sem actor explícito (ex.: cron). Cai no primeiro
     * admin ativo encontrado.
     */
    private function resolveActorUserId(?int $actorUserId): int
    {
        if ($actorUserId !== null) {
            return $actorUserId;
        }

        $id = User::query()
            ->where('ticketit_admin', true)
            ->where('active', true)
            ->orderBy('id')
            ->value('id');

        if ($id === null) {
            throw new RuntimeException(
                'Backfill precisa de um actor: nenhum admin ativo encontrado para registrar auditoria. Informe --actor=ID.'
            );
        }

        return (int) $id;
    }
}
