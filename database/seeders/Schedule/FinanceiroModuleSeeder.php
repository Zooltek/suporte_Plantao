<?php

namespace Database\Seeders\Schedule;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Database\Seeder;

/**
 * Popula o módulo "Financeiro" com os grupos e elementos do checklist
 * conforme a Ordem de Serviço (OS nº 2907) do sistema EasyMaster.
 *
 * Idempotente: usa firstOrCreate em todos os níveis.
 */
class FinanceiroModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'Financeiro'],
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

        $this->command->info('FinanceiroModuleSeeder: módulo "Financeiro" populado com sucesso.');
    }

    /** @return array<string, array<int, array{string, string}>> */
    private function structure(): array
    {
        return [
            'Lanç. de Conta' => [
                ['fin_cad_conta_corrente',       'Cadastro de Conta Corrente'],
                ['fin_incl_lancamentos',          'Inclusão de Lançamentos'],
                ['fin_transf_contas',             'Transferência entre Contas'],
                ['fin_concil_bancaria',           'Conciliação Bancária'],
            ],
            'Contas a Pagar' => [
                ['cpag_cad_fornecedor',           'Cadastros de Fornecedor, Loja, Local de Cobrança, Centro de Custo, Cond. Pagamento e Conta Corrente'],
                ['cpag_inclusao',                 'Inclusão'],
                ['cpag_alt_duplicata',            'Alteração de Duplicata'],
                ['cpag_quitar_duplicata',         'Quitar Duplicata'],
                ['cpag_cancelar_pgto',            'Cancelar Pagamento de Duplicata'],
                ['cpag_multiplos_pgtos',          'Múltiplos Pagamentos de Duplicata'],
                ['cpag_relatorios',               'Relatórios'],
                ['cpag_consultas',                'Consultas'],
            ],
            'Vale de Funcionário' => [
                ['vale_cad_loja_func',            'Cadastro de Loja/Funcionário'],
                ['vale_consulta',                 'Consulta de Vales'],
                ['vale_listagem',                 'Listagem de Vales'],
                ['vale_ditacao_alt_excl',         'Ditação/Alteração ou Exclusão de Vale'],
            ],
            'Sangria/Suprimento' => [
                ['sangria_conceitos',             'Conceitos de Sangria/Suprimento'],
                ['sangria_consultas_rel',         'Consultas e Relatórios'],
            ],
            'Despesa' => [
                ['desp_cad_loja_centro',          'Cadastros de Loja/Centro de Custo'],
                ['desp_incl_excl',                'Inclusão/Exclusão de Despesa'],
                ['desp_consultas_rel',            'Consultas e Relatórios'],
            ],
            'Faturamento Pendente' => [
                ['fatpend_inclusao',              'Inclusão'],
                ['fatpend_faturamento',           'Faturamento'],
            ],
            'Contrato' => [
                ['contrato_conceito',             'Conceito do Contrato'],
                ['contrato_inclusao',             'Inclusão'],
                ['contrato_exclusao',             'Exclusão'],
                ['contrato_relatorio',            'Relatório'],
                ['contrato_consulta',             'Consulta'],
            ],
            'Cartão' => [
                ['cartao_cad_cartao_loja_func',   'Cadastros de Cartão/Loja Funcionário/Cliente'],
                ['cartao_digitacao',              'Digitação de Cartão'],
                ['cartao_consultas',              'Consultas'],
                ['cartao_relatorios',             'Relatórios'],
                ['cartao_conciliacao',            'Conciliação'],
                ['cartao_antecipacao',            'Antecipação'],
            ],
            'Cheque' => [
                ['cheque_cadastros',              'Cadastros'],
                ['cheque_digitacao',              'Digitação'],
                ['cheque_conciliacao',            'Conciliação'],
                ['cheque_deposito',               'Depósito'],
                ['cheque_borderô',                'Borderô'],
                ['cheque_cancelamentos',          'Cancelamentos'],
                ['cheque_consultas',              'Consultas'],
                ['cheque_relatorios',             'Relatórios'],
                ['cheque_devolucao',              'Devolução'],
            ],
            'Contas a Receber' => [
                ['crec_cadastros',                'Cadastros'],
                ['crec_inclusao_conta',           'Inclusão de Conta'],
                ['crec_alteracao',                'Alteração'],
                ['crec_pgtos_multiplos',          'Pagamentos/Múltiplos'],
                ['crec_conciliacao',              'Conciliação'],
                ['crec_emissao_duplicata',        'Emissão de Duplicata'],
                ['crec_consultas',                'Consultas'],
                ['crec_relatorios',               'Relatórios'],
            ],
            'Financeira' => [
                ['fin_cadastros',                 'Cadastros'],
                ['fin_incl_excl',                 'Inclusão/Exclusão'],
                ['fin_conciliacao',               'Conciliação'],
                ['fin_consultas',                 'Consultas'],
                ['fin_relatorios',                'Relatórios'],
            ],
            'Acordos Cliente' => [
                ['acord_cli_cad',                 'Cadastros Inclu/Excl.'],
                ['acord_cli_listagem',            'Listagem'],
            ],
            'Acordos Fornecedor' => [
                ['acord_forn_cad',                'Cadastros Inclu/Excl.'],
                ['acord_forn_listagem_backup',    'Listagem / Backup'],
            ],
        ];
    }
}
