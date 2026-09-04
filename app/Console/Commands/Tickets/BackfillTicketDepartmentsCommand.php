<?php

namespace App\Console\Commands\Tickets;

use App\Models\Department;
use App\Services\Ticket\Routing\TicketDepartmentBackfillService;
use Illuminate\Console\Command;

/**
 * Reclassifica o department_id de chamados antigos com base no department_id
 * da Categoria/Subcategoria.
 *
 * Modo padrão é dry-run: gera o relatório do plano sem persistir. Passe
 * --apply para efetivar. Origem padrão: Suporte Técnico. Pode-se escolher
 * outra origem via --from-department=ID.
 */
class BackfillTicketDepartmentsCommand extends Command
{
    protected $signature = 'tickets:backfill-departments
        {--apply : Persiste as alterações (default é dry-run)}
        {--from-department= : ID do departamento de origem (default: Suporte Técnico)}
        {--limit= : Número máximo de chamados a inspecionar}
        {--chunk=500 : Tamanho do lote de processamento}
        {--actor= : ID do usuário que será gravado na auditoria}';

    protected $description = 'Reclassifica department_id de chamados antigos com base na categoria/subcategoria';

    public function handle(TicketDepartmentBackfillService $service): int
    {
        $apply = (bool) $this->option('apply');
        $fromOption = $this->option('from-department');
        $fromDepartmentId = $fromOption !== null ? (int) $fromOption : null;
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $chunk = (int) $this->option('chunk');
        $actorUserId = $this->option('actor') !== null ? (int) $this->option('actor') : null;

        $mode = $apply ? 'APLICAR' : 'DRY-RUN';
        $this->info("[Backfill departamento dos chamados] modo: {$mode}");

        $result = $service->run(
            fromDepartmentId: $fromDepartmentId,
            apply: $apply,
            limit: $limit,
            chunk: $chunk,
            actorUserId: $actorUserId,
        );

        $this->renderReport($result);

        if (! $apply) {
            $this->newLine();
            $this->comment('Nenhuma alteração persistida. Use --apply para efetivar.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{plan: array<array{from:int,to:int,count:int}>, total: int, updated: int}  $result
     */
    private function renderReport(array $result): void
    {
        $this->line("Chamados inspecionados: {$result['total']}");
        $this->line('Chamados a reclassificar: '.array_sum(array_column($result['plan'], 'count')));

        if ($result['updated'] > 0) {
            $this->line("Chamados atualizados nesta execução: {$result['updated']}");
        }

        if (empty($result['plan'])) {
            $this->newLine();
            $this->line('Nenhuma reclassificação proposta.');

            return;
        }

        $rows = [];
        foreach ($result['plan'] as $movement) {
            $rows[] = [
                $movement['from'],
                $this->departmentName($movement['from']),
                $movement['to'],
                $this->departmentName($movement['to']),
                $movement['count'],
            ];
        }

        $this->newLine();
        $this->table(
            ['De (id)', 'De (nome)', 'Para (id)', 'Para (nome)', 'Chamados'],
            $rows,
        );
    }

    private function departmentName(int $id): string
    {
        return (string) (Department::query()->whereKey($id)->value('name') ?? '—');
    }
}
