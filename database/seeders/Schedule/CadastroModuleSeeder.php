<?php

namespace Database\Seeders\Schedule;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Database\Seeder;

/**
 * Popula o módulo "Cadastro" com os grupos e elementos do checklist
 * para o sistema EasyMaster.
 *
 * Idempotente: usa firstOrCreate em todos os níveis.
 */
class CadastroModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'Cadastro'],
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

        $this->command->info('CadastroModuleSeeder: módulo "Cadastro" populado com sucesso.');
    }

    /** @return array<string, array<int, array{string, string}>> */
    private function structure(): array
    {
        return [
            'Cadastros Essenciais' => [
                ['cad_fornecedor',    'Cadastro de Fornecedor'],
                ['cad_produto',       'Cadastro de Produto'],
                ['cad_cliente_cad',   'Cadastro de Cliente'],
                ['cad_transportadora','Cadastro de Transportadora'],
                ['cad_funcionario',   'Cadastro de Funcionário'],
            ],
            'Configurações de Tributos' => [
                ['cad_cst',        'CST/CSOSN'],
                ['cad_ncm',        'NCM'],
                ['cad_natureza',   'Natureza de Operação'],
                ['cad_aliq_iss',   'Alíquota ISS'],
            ],
            'Tabelas Auxiliares' => [
                ['cad_unidade',     'Unidade de Medida'],
                ['cad_grupo_prod',  'Grupo de Produto'],
                ['cad_marca',       'Marca'],
                ['cad_banco',       'Banco'],
            ],
        ];
    }
}
