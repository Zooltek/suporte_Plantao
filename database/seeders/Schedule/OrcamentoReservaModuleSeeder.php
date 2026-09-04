<?php

namespace Database\Seeders\Schedule;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Database\Seeder;

/**
 * Popula o módulo "Orçamento/Reserva" com os grupos e elementos do checklist
 * conforme a Ordem de Serviço (OS nº 2871) do sistema EasyMaster.
 *
 * Idempotente: usa firstOrCreate em todos os níveis.
 */
class OrcamentoReservaModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'Orçamento/Reserva'],
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

        $this->command->info('OrcamentoReservaModuleSeeder: módulo "Orçamento/Reserva" populado com sucesso.');
    }

    /** @return array<string, array<int, array{string, string}>> */
    private function structure(): array
    {
        return [
            'Orçamento' => [
                ['orc_conceito',            'Conceito de Orçamento'],
                ['orc_cadastros',           'Cadastros'],
                ['orc_consultas',           'Consultas'],
                ['orc_inclusao',            'Inclusão de Orçamento'],
                ['orc_incl_alt_excl_itens', 'Inclusão/Alteração/Exclusão de Itens'],
                ['orc_totalizacao',         'Totalização do Orçamento'],
                ['orc_romaneio',            'Romaneio'],
                ['orc_cancel_alt',          'Cancelamento/Alteração de Orçamento'],
                ['orc_exportacao',          'Exportação de Orçamento'],
                ['orc_relatorio',           'Relatório'],
            ],
            'Reserva' => [
                ['res_conceito',            'Conceito de Reserva'],
                ['res_cadastros',           'Cadastros'],
                ['res_inclusao',            'Inclusão de Nova Reserva'],
                ['res_incl_alt_excl_itens', 'Inclusão/Alteração/Exclusão de Itens'],
                ['res_desconto_item',       'Desconto no Item'],
                ['res_gravacao_romaneio',   'Gravação/Romaneio'],
                ['res_alt_cliente',         'Alteração do Cliente'],
                ['res_devolucao',           'Devolução'],
                ['res_devolucao_total',     'Devolução Total/Fechamento'],
                ['res_listagem',            'Listagem das Reservas'],
                ['res_itens',               'Itens'],
                ['res_minuta',              'Minuta'],
                ['res_fechamento',          'Fechamento'],
                ['res_desconto',            'Desconto'],
                ['res_formas_cond_pgto',    'Formas/Condição de Pagamento'],
            ],
            'Ordem de Serviço' => [
                ['os_conceito',             'Conceito de Ordem de Serviço'],
                ['os_inclusao',             'Inclusão de OS'],
                ['os_req_peca',             'Requisição de Peça'],
                ['os_req_servico',          'Requisição de Serviço'],
                ['os_alt_cliente',          'Alteração de Cliente'],
                ['os_transferencia',        'Transferência'],
                ['os_fechamento',           'Fechamento'],
                ['os_consulta',             'Consulta'],
                ['os_relatorio',            'Relatório'],
            ],
        ];
    }
}
