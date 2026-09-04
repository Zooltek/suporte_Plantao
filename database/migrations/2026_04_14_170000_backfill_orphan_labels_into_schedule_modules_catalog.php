<?php

declare(strict_types=1);

use App\Models\Schedule\Module as ScheduleModule;
use App\Models\Tasks\Label;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill de módulos órfãos de Tarefas para o catálogo unificado de Implantação.
 *
 * Contexto: após a unificação do cadastro de módulos em Implantação
 * (schedule_record_module), labels raiz ativos sem schedule_module_id não
 * aparecem nos fluxos de Tarefa/Implantação/Agendamento. Esta migration
 * cria um schedule_record_module correspondente para cada label órfão e
 * preenche o FK, preservando hierarquia: submódulos continuam como filhos
 * do label raiz já vinculado e são expostos via relação childs.
 *
 * Projeto inferido do nome do departamento do label (ou "Geral" no
 * fallback). Usuário pode reclassificar posteriormente pela UI de
 * Implantação sem perder vínculos.
 *
 * Idempotente: respeita labels já vinculados e não duplica módulos
 * existentes no catálogo (firstOrCreate por name+project).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            Label::query()
                ->whereNull('parent_id')
                ->whereNull('schedule_module_id')
                ->where('is_active', true)
                ->with('department:id,name')
                ->chunkById(200, function ($labels): void {
                    foreach ($labels as $label) {
                        $project = trim((string) ($label->department->name ?? '')) ?: 'Geral';

                        $module = ScheduleModule::query()->firstOrCreate(
                            ['name' => $label->name, 'project' => $project],
                            ['description' => null],
                        );

                        $label->schedule_module_id = $module->id;
                        $label->saveQuietly();
                    }
                });
        });
    }

    public function down(): void
    {
        // Migração unidirecional de dados: reverter exige rastrear quais
        // schedule_record_module foram criados nesta execução. Como o
        // backfill é idempotente e seguro, a reversão fica a cargo de
        // operação manual se necessário.
    }
};
