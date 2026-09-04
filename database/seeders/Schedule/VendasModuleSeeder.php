<?php

namespace Database\Seeders\Schedule;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Database\Seeder;

/**
 * Popula o módulo "Vendas RC" com os grupos e elementos do checklist
 * conforme a Ordem de Serviço (OS nº 2810) do sistema EasyMaster.
 *
 * Grupos alinhados aos requisitos funcionais da RTA:
 *   Geral · Venda ECF/NFE/NFC-e · Pagamento · Operações de Caixa
 *   Devolução de Venda · Técnico e Segurança
 *
 * Idempotente: usa updateOrCreate para garantir que reagrupamentos
 * (group_id) sejam aplicados em re-execuções sem duplicar registros.
 */
class VendasModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'Vendas RC'],
            ['project' => 'EasyMaster']
        );

        foreach ($this->structure() as $groupName => $elements) {
            $group = ElementGroup::firstOrCreate(['name' => $groupName]);

            foreach ($elements as [$name, $label]) {
                // updateOrCreate garante que re-agrupamentos (group_id) sejam
                // aplicados em ambientes que já tinham o seeder anterior.
                ElementType::updateOrCreate(
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

        $this->command->info('VendasModuleSeeder: módulo "Vendas RC" populado com sucesso.');
    }

    /** @return array<string, array<int, array{string, string}>> */
    private function structure(): array
    {
        return [
            // Req. 10 — apresentação, abertura e cadastros iniciais
            'Geral' => [
                ['apres_menus',    'Apresentação dos Menus'],
                ['leitura_x',      'Leitura X'],
                ['fundo_caixa',    'Fundo de Caixa'],
                ['cad_cliente',    'Cadastro de Cliente'],
                ['lanc_pre_venda', 'Lançamento de Pré-Venda'],
            ],

            // Req. 11 — fluxo de venda no PDV
            'Venda ECF/NFE/NFC-e' => [
                ['pesq_pre_venda', 'Pesquisa de Pré-Venda/DAV'],
                ['cliente_venda',  'Cliente na Venda'],
                ['incluir_manual', 'Incluir Itens Manualmente'],
                ['incluir_ccd',    'Incluir Itens CCD'],
                ['desconto_item',  'Desconto no Item'],
                ['cancel_item',    'Cancelamento de Item'],
                ['anulacao_cupom', 'Anulação de Cupom'],
            ],

            // Req. 12 — formas e condições de pagamento
            'Pagamento' => [
                ['pgto_dinheiro',    'Dinheiro'],
                ['pgto_cartao',      'Cartão'],
                ['pgto_cheque',      'Cheque'],
                ['pgto_duplicata',   'Duplicata'],
                ['pgto_diversos',    'Diversos'],
                ['pgto_funcionario', 'Funcionário'],
                ['pgto_financeira',  'Financeira'],
                ['pgto_cartao_pref', 'Cartão Preferencial'],
                ['fat_pendente',     'Faturamento Pendente'],
                ['nfe_venda_ecf',    'NF-e de Venda em ECF'],
                ['cancel_venda',     'Cancelamento de Venda'],
            ],

            // Req. 13 — operações de gestão e caixa
            'Operações de Caixa' => [
                ['cons_estoque',     'Consulta de Estoque'],
                ['cons_vendas',      'Consulta de Vendas'],
                ['orcamento',        'Orçamento'],
                ['sangria',          'Sangria'],
                ['despesa',          'Despesa'],
                ['vale_funcionario', 'Vale de Funcionário'],
                ['conf_caixa',       'Conferência do Caixa'],
                ['reducao_z',        'Redução Z'],
            ],

            // Req. 14 — devoluções e emissão de nota
            'Devolução de Venda' => [
                ['dev_pesq_venda',   'Pesquisa de Venda'],
                ['dev_sel_itens',    'Seleção de Itens'],
                ['dev_emissao_nota', 'Emissão da Nota'],
            ],

            // Req. 15 — validação técnica e segurança de dados
            'Técnico e Segurança' => [
                ['validacao_sistema', 'Validação do Sistema'],
                ['backup',            'Backup'],
            ],
        ];
    }
}
