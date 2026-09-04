<?php

namespace Database\Seeders\Schedule;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Database\Seeder;

/**
 * Popula módulos, grupos e itens de checklist RAT para teste
 * dos CRUDs administrativos de Implantação.
 *
 * Módulos criados: EasyPDV (Instalação PDV, Vendas PDV),
 * EasyMaster (Fiscal, Logística) e Geral (Treinamento).
 *
 * Idempotente via firstOrCreate / updateOrCreate.
 */
class ImplantacaoAdminSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->modules() as $moduleDef) {
            $module = Module::firstOrCreate(
                ['name' => $moduleDef['name']],
                [
                    'description' => $moduleDef['description'],
                    'project'     => $moduleDef['project'],
                ]
            );

            foreach ($moduleDef['groups'] as $groupName => $elements) {
                $group = ElementGroup::firstOrCreate(['name' => $groupName]);

                foreach ($elements as [$name, $label]) {
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

            $this->command->info("ImplantacaoAdminSeeder: módulo \"{$module->name}\" populado.");
        }
    }

    /** @return array<int, array{name: string, description: string, project: string|null, groups: array<string, array<int, array{string, string}>>}> */
    private function modules(): array
    {
        return [
            [
                'name'        => 'Instalação PDV',
                'description' => 'Checklist de instalação do ponto de venda EasyPDV',
                'project'     => 'EasyPDV',
                'groups'      => [
                    'Preparação do Ambiente PDV' => [
                        ['pdv_aj_permissoes',     'Ajustar Permissões do Windows'],
                        ['pdv_instalar_driver',   'Instalar Driver da Impressora'],
                        ['pdv_config_rede',       'Configurar Rede e Comunicação'],
                        ['pdv_particionar_disco', 'Particionar Disco'],
                        ['pdv_instalar_runtime',  'Instalar Runtime e Dependências'],
                    ],
                    'Configuração do PDV' => [
                        ['pdv_cad_loja',          'Cadastro da Loja'],
                        ['pdv_cad_operador',      'Cadastro de Operador'],
                        ['pdv_cad_parametros',    'Parâmetros do Sistema'],
                        ['pdv_cad_formas_pgto',   'Formas de Pagamento'],
                        ['pdv_cad_impressora',    'Configurar Impressora Fiscal'],
                    ],
                    'Validação PDV' => [
                        ['pdv_teste_venda',       'Teste de Venda Completa'],
                        ['pdv_teste_sangria',     'Teste de Sangria/Suprimento'],
                        ['pdv_teste_cancelamento','Teste de Cancelamento'],
                        ['pdv_reducao_z',         'Redução Z de Teste'],
                    ],
                ],
            ],
            [
                'name'        => 'Vendas PDV',
                'description' => 'Treinamento de vendas no ponto de venda',
                'project'     => 'EasyPDV',
                'groups'      => [
                    'Operação de Venda' => [
                        ['vpdv_abertura_caixa',   'Abertura do Caixa'],
                        ['vpdv_venda_simples',    'Venda Simples'],
                        ['vpdv_venda_balanca',    'Venda com Balança'],
                        ['vpdv_desconto',         'Aplicar Desconto'],
                        ['vpdv_cancel_item',      'Cancelar Item'],
                    ],
                    'Fechamento e Relatórios' => [
                        ['vpdv_fechamento',       'Fechamento do Caixa'],
                        ['vpdv_conferencia',      'Conferência de Valores'],
                        ['vpdv_rel_vendas',       'Relatório de Vendas'],
                        ['vpdv_backup_diario',    'Backup Diário'],
                    ],
                ],
            ],
            [
                'name'        => 'Fiscal',
                'description' => 'Módulo de obrigações fiscais e acessórias',
                'project'     => 'EasyMaster',
                'groups'      => [
                    'NF-e / NFC-e' => [
                        ['fisc_config_certificado', 'Configurar Certificado Digital'],
                        ['fisc_emissao_nfe',        'Emissão de NF-e'],
                        ['fisc_emissao_nfce',       'Emissão de NFC-e'],
                        ['fisc_cancel_nfe',         'Cancelamento de NF-e'],
                        ['fisc_carta_correcao',     'Carta de Correção'],
                        ['fisc_inutilizacao',       'Inutilização de Numeração'],
                    ],
                    'SPED e Obrigações' => [
                        ['fisc_sped_fiscal',        'SPED Fiscal (ICMS/IPI)'],
                        ['fisc_sped_contrib',       'SPED Contribuições (PIS/COFINS)'],
                        ['fisc_sintegra',           'Arquivo Sintegra'],
                        ['fisc_dmed',               'DMED'],
                    ],
                    'Configuração Tributária' => [
                        ['fisc_cad_cfop',           'Cadastro de CFOP'],
                        ['fisc_cad_ncm',            'Cadastro de NCM'],
                        ['fisc_cad_cst',            'Cadastro de CST'],
                        ['fisc_tabela_icms',        'Tabela de ICMS por Estado'],
                        ['fisc_regime_tributario',  'Configurar Regime Tributário'],
                    ],
                ],
            ],
            [
                'name'        => 'Logística',
                'description' => 'Módulo de controle logístico e expedição',
                'project'     => 'EasyMaster',
                'groups'      => [
                    'Recebimento' => [
                        ['log_importar_xml',        'Importar XML de NF-e do Fornecedor'],
                        ['log_conferencia_fisica',  'Conferência Física de Mercadorias'],
                        ['log_entrada_nota',        'Entrada de Nota Fiscal'],
                        ['log_divergencia',         'Tratar Divergência de Quantidade'],
                    ],
                    'Expedição' => [
                        ['log_separacao_pedido',    'Separação de Pedido'],
                        ['log_conferencia_saida',   'Conferência de Saída'],
                        ['log_emissao_romaneio',    'Emissão de Romaneio'],
                        ['log_rastreamento',        'Rastreamento de Entrega'],
                    ],
                    'Inventário' => [
                        ['log_contagem_estoque',    'Contagem de Estoque'],
                        ['log_ajuste_estoque',      'Ajuste de Estoque'],
                        ['log_relatorio_posicao',   'Relatório de Posição de Estoque'],
                    ],
                ],
            ],
            [
                'name'        => 'Treinamento',
                'description' => 'Checklist geral para treinamentos presenciais e remotos',
                'project'     => 'Geral',
                'groups'      => [
                    'Preparação do Treinamento' => [
                        ['trein_validar_ambiente',  'Validar Ambiente do Cliente'],
                        ['trein_base_teste',        'Criar Base de Teste'],
                        ['trein_material_apoio',    'Disponibilizar Material de Apoio'],
                    ],
                    'Execução' => [
                        ['trein_apresentar_modulo', 'Apresentação do Módulo'],
                        ['trein_exercicio_pratico', 'Exercício Prático'],
                        ['trein_duvidas_cliente',   'Esclarecimento de Dúvidas'],
                    ],
                    'Encerramento' => [
                        ['trein_avaliacao',         'Avaliação do Treinamento'],
                        ['trein_pendencias',        'Registrar Pendências'],
                        ['trein_assinatura_rat',    'Assinatura do RAT'],
                    ],
                ],
            ],
        ];
    }
}
