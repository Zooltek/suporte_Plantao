<?php

namespace Database\Seeders\Schedule;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Database\Seeder;

/**
 * Popula o módulo "Instalação" com os grupos e elementos do checklist
 * conforme a Ordem de Serviço (OS nº 2904) do sistema EasyMaster.
 *
 * Idempotente: usa firstOrCreate em todos os níveis.
 */
class InstalacaoModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'Instalação'],
            ['project' => 'EasyMaster']
        );

        foreach ($this->structure() as $groupName => $elements) {
            $group = ElementGroup::firstOrCreate(['name' => $groupName]);

            foreach ($elements as [$name, $label]) {
                ElementType::firstOrCreate(
                    ['name' => $name],
                    [
                        'label'    => $label,
                        'type'     => 'checkbox',
                        'module_id' => $module->id,
                        'group_id'  => $group->id,
                    ]
                );
            }
        }

        $this->command->info('InstalacaoModuleSeeder: módulo "Instalação" populado com sucesso.');
    }

    /** @return array<string, array<int, array{string, string}>> */
    private function structure(): array
    {
        return [
            'Instalação de Componentes Básicos' => [
                ['aj_perm_windows',   'Ajustar Permissões do Windows'],
                ['chilkat',           'Chilkat'],
                ['brazip',            'Brazip'],
                ['reg_brazip',        'Registrar Brazip'],
                ['runtime_netcobol',  'RunTimeNetCobol'],
                ['vcredist',          'VCredist'],
                ['ativar_netframework', 'Ativar NetFramework 3.5'],
                ['uninfe',            'Uninfe'],
                ['unidanfe',          'Unidanfe'],
                ['danfe_viewer',      'DanfeViewer'],
                ['acbr',              'AcBR'],
                ['jogo_sistema',      'Jogo do Sistema'],
                ['instala_bematech',  'InstalaBematech'],
                ['instala_daruma',    'Instala Daruma'],
                ['leasy_reg',         'LeasyReg'],
                ['easy_reg',          'EasyReg'],
                ['particionar_disco', 'Particionar Disco'],
                ['criar_t',           'Criar T:'],
                ['mapeamento_rede',   'Realizar Mapeamento de Rede'],
                ['pasta_modem',       'Pasta Modem'],
                ['pasta_backup',      'Pasta Backup'],
            ],
            'Cadastros Básicos/Configuração' => [
                ['cad_loja',         'Loja'],
                ['cad_usuario',      'Usuário'],
                ['cad_parametro',    'Parâmetro'],
                ['cad_contador',     'Contador'],
                ['cad_icms',         'Tabela de ICMS'],
                ['cad_cfop',         'CFOP'],
                ['cad_cartoes',      'Cartões'],
                ['cad_cond_compra',  'Condições de Compra'],
                ['cad_cond_venda',   'Condições de Venda'],
            ],
            'Instalação/Configuração de ECF' => [
                ['ecf_instalar_drive',  'Instalar Drive'],
                ['ecf_config_porta',    'Configurar Porta'],
                ['ecf_gerar_arquivo',   'Gerar Arquivo de ECF'],
                ['ecf_config_ecf',      'Configurar ECF'],
                ['ecf_formas_pgto',     'Criar Formas de Pagamento'],
                ['ecf_transmitir',      'Transmitir Arquivo'],
            ],
            'Instalação de Etiquetadora' => [
                ['etiq_instalar_drive',    'Instalar Drive'],
                ['etiq_config_netuse',     'Configurar o NetUse'],
                ['etiq_fazer_bat',         'Fazer o BAT'],
                ['etiq_testar_impressao',  'Testar Impressão'],
            ],
            'Programas Acessórios' => [
                ['acess_validador_sintegra', 'Validador Sintegra'],
                ['acess_validadores_sped',   'Validadores SPED'],
                ['acess_ted_ecf',            'TED ECF'],
                ['acess_ted_paf_ecf',        'TED_PAF_ECF'],
            ],
        ];
    }
}
