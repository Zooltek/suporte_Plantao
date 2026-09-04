<?php

namespace Database\Seeders\Schedule;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Database\Seeder;

/**
 * Popula o módulo "Estoque" com os grupos e elementos do checklist
 * para o sistema EasyMaster.
 *
 * Idempotente: usa firstOrCreate em todos os níveis.
 */
class EstoqueModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'Estoque'],
            ['project' => 'EasyMaster']
        );

        foreach ($this->structure() as $groupName => $elements) {
            $group = ElementGroup::firstOrCreate(['name' => $groupName]);

            foreach ($elements as [$name, $label]) {
                ElementType::firstOrCreate(
                    ['name' => $name],
                    [
                        'label'     => $label,
                        'type'      => 'checkbox',
                        'module_id' => $module->id,
                        'group_id'  => $group->id,
                    ]
                );
            }
        }

        $this->command->info('EstoqueModuleSeeder: módulo "Estoque" populado com sucesso.');
    }

    /** @return array<string, array<int, array{string, string}>> */
    private function structure(): array
    {
        return [
            'Movimentação de Estoque' => [
                ['est_entrada_nota',  'Entrada por Nota Fiscal'],
                ['est_saida_manual',  'Saída Manual'],
                ['est_transferencia', 'Transferência entre Lojas'],
                ['est_inventario',    'Inventário/Contagem'],
            ],
            'Consultas e Relatórios' => [
                ['est_cons_saldo',     'Consulta de Saldo'],
                ['est_posicao',        'Posição de Estoque'],
                ['est_curva_abc',      'Curva ABC'],
                ['est_movimentacao',   'Relatório de Movimentação'],
            ],
            'Configurações' => [
                ['est_estoque_min',    'Estoque Mínimo'],
                ['est_custo_medio',    'Custo Médio'],
                ['est_grade',          'Grade (Cor/Tamanho)'],
            ],
        ];
    }
}
