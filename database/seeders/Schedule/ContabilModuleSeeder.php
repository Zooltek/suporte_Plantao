<?php

namespace Database\Seeders\Schedule;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Database\Seeder;

/**
 * Popula o módulo "Contábil" com os grupos e elementos do checklist
 * para o sistema EasyMaster.
 *
 * Idempotente: usa firstOrCreate em todos os níveis.
 */
class ContabilModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'Contábil'],
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

        $this->command->info('ContabilModuleSeeder: módulo "Contábil" populado com sucesso.');
    }

    /** @return array<string, array<int, array{string, string}>> */
    private function structure(): array
    {
        return [
            'Configuração Contábil' => [
                ['cont_plano_contas',   'Plano de Contas'],
                ['cont_centros_custo',  'Centros de Custo'],
                ['cont_historico',      'Histórico Padrão'],
            ],
            'Lançamentos' => [
                ['cont_lanc_manual',    'Lançamento Manual'],
                ['cont_integracao',     'Integração com Fiscal'],
                ['cont_depreciacao',    'Depreciação de Ativos'],
            ],
            'Apuração e Relatórios' => [
                ['cont_balancete',      'Balancete'],
                ['cont_dre',            'DRE'],
                ['cont_balanço',        'Balanço Patrimonial'],
                ['cont_sped_contabil',  'SPED Contábil'],
            ],
        ];
    }
}
