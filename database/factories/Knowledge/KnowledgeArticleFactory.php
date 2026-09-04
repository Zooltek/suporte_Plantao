<?php

namespace Database\Factories\Knowledge;

use App\Models\Knowledge\KnowledgeArticle;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class KnowledgeArticleFactory extends Factory
{
    protected $model = KnowledgeArticle::class;

    private static array $articles = [
        [
            'title'    => 'Erro ao acessar o módulo financeiro após atualização',
            'problem'  => 'Após a atualização do sistema para a versão 3.2, usuários relatam que ao tentar acessar o módulo financeiro recebem a mensagem "Acesso Negado" mesmo possuindo as permissões corretas. O problema afeta todos os perfis de acesso, incluindo administradores.',
            'solution' => 'O problema é causado por uma alteração na estrutura de permissões da versão 3.2. Para resolver: 1) Acesse Configurações > Perfis de Acesso; 2) Selecione cada perfil e clique em "Redefinir Permissões Padrão"; 3) Salve e solicite que o usuário faça logout e login novamente. Em casos persistentes, execute o script de migração de permissões disponível em Ferramentas > Manutenção.',
            'tags'     => 'financeiro, permissão, acesso negado, atualização',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'NF-e rejeitada com código 999 — Ambiente incorreto',
            'problem'  => 'Emissão de NF-e retorna rejeição com código 999 "Erro não catalogado". Ao analisar o XML de resposta da SEFAZ, o campo cMsg indica "Ambiente de homologação não aceita CNPJ real".',
            'solution' => 'O sistema está configurado para emitir em ambiente de homologação com CNPJ real. Corrija em Configurações > Fiscal > Parâmetros NF-e: altere "Ambiente" para "Produção" se for emissão real, ou use o CNPJ de testes (11.222.333/0001-81) para homologação. Após alteração, limpe o cache de certificados em Ferramentas > Certificados.',
            'tags'     => 'nfe, sefaz, rejeição, fiscal, homologação',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'Relatório de estoque exibe valores zerados',
            'problem'  => 'O relatório de posição de estoque gerado pelo sistema exibe quantidade e custo zerados para todos os itens, mesmo com movimentações recentes. O problema foi identificado após a migração de base de dados do mês anterior.',
            'solution' => 'A migração não atualizou os saldos acumulados na tabela de posição. Execute o processo de recálculo: acesse Estoque > Utilitários > Recalcular Saldos, selecione o período desde a data da migração até hoje e confirme. O processo pode levar de 10 a 30 minutos dependendo do volume de movimentações. Após concluir, os relatórios serão exibidos corretamente.',
            'tags'     => 'estoque, relatório, saldo, migração, recálculo',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'Como configurar o envio automático de boletos por e-mail',
            'problem'  => 'Cliente solicita orientação para configurar o sistema para enviar automaticamente os boletos gerados para os e-mails dos clientes, sem necessidade de intervenção manual a cada geração.',
            'solution' => 'Acesse Financeiro > Configurações > Cobrança Automática. Ative a opção "Envio automático de boletos" e configure: 1) Conta de e-mail remetente (deve estar cadastrada em Configurações > E-mail); 2) Antecedência do envio (recomendado: 5 dias antes do vencimento); 3) Modelo de e-mail (use o template padrão ou crie em Documentos > Templates). Salve e teste com um cliente de homologação antes de ativar em produção.',
            'tags'     => 'boleto, e-mail, cobrança, automático, financeiro',
            'visibility' => 'public',
        ],
        [
            'title'    => 'Lentidão no sistema durante fechamento mensal',
            'problem'  => 'Todo início do mês, especialmente entre os dias 1 e 5, o sistema apresenta lentidão acentuada, com telas demorando mais de 30 segundos para carregar. O problema coincide com o período de fechamento contábil e fiscal.',
            'solution' => 'A lentidão é causada por processos concorrentes de fechamento. Recomendações: 1) Agende o fechamento contábil para horários de menor uso (ex: após 20h); 2) Execute Ferramentas > Manutenção > Reindexar Tabelas antes do fechamento; 3) Aumente o timeout de sessão para 120 segundos em Configurações > Performance durante o período crítico. Para ambientes com mais de 50 usuários simultâneos, avaliar upgrade de servidor.',
            'tags'     => 'performance, lentidão, fechamento, contábil, manutenção',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'Integração com sistema de e-commerce: pedidos não importam',
            'problem'  => 'Os pedidos realizados na loja virtual não estão sendo importados automaticamente para o ERP. O log de integração mostra o erro "Token de autenticação inválido ou expirado" a cada 15 minutos quando o serviço tenta sincronizar.',
            'solution' => 'O token de integração expirou (validade padrão: 90 dias). Para renovar: 1) Acesse o painel da loja virtual > Integrações > API; 2) Gere um novo token; 3) No ERP, acesse Configurações > Integrações > E-commerce e atualize o campo "Token API"; 4) Clique em "Testar Conexão" para validar; 5) Force a sincronização imediata em Pedidos > Importar do E-commerce. Configure alertas para renovação 15 dias antes do vencimento.',
            'tags'     => 'integração, ecommerce, api, token, pedidos, sincronização',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'Certificado digital A1 vencido — Como renovar',
            'problem'  => 'O certificado digital A1 utilizado para emissão de NF-e e NFS-e venceu. O sistema apresenta o erro "Certificado expirado" ao tentar emitir qualquer documento fiscal.',
            'solution' => 'Para renovar o certificado A1: 1) Adquira o novo certificado A1 junto à autoridade certificadora (validade recomendada: 3 anos); 2) No ERP, acesse Configurações > Certificados Digitais; 3) Clique em "Importar Certificado" e selecione o arquivo .pfx; 4) Informe a senha do certificado; 5) Defina como certificado padrão; 6) Teste a comunicação com a SEFAZ em Fiscal > Utilitários > Testar Certificado. Guarde backup do certificado e senha em local seguro.',
            'tags'     => 'certificado, nfe, fiscal, a1, renovação, sefaz',
            'visibility' => 'public',
        ],
        [
            'title'    => 'Usuário não consegue alterar a própria senha',
            'problem'  => 'Usuários relatam que ao tentar alterar a senha pelo painel "Meu Perfil", o sistema exibe o erro "Senha atual incorreta" mesmo quando a senha informada está correta. O problema ocorre apenas para usuários que tiveram a senha resetada pelo administrador.',
            'solution' => 'Quando o administrador reseta a senha, o sistema gera um hash temporário. Se o usuário não fez o primeiro login com essa senha temporária, ocorre o conflito. Solução: 1) Administrador deve enviar novo link de redefinição via Usuários > [usuário] > Enviar link de redefinição; 2) Usuário acessa o link no e-mail e define nova senha; 3) A partir daí, a alteração pelo perfil funcionará normalmente. Como prevenção, oriente usuários a alterar a senha no primeiro acesso.',
            'tags'     => 'senha, usuário, perfil, reset, autenticação',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'Backup automático falhando com erro de permissão',
            'problem'  => 'O job de backup automático agendado para as 02h falha com a mensagem "Permission denied: /var/backups/erp". O backup manual via interface funciona normalmente. Problema iniciou após atualização do servidor.',
            'solution' => 'A atualização do servidor redefiniu as permissões do diretório de backup. No servidor, execute: chmod 755 /var/backups/erp && chown -R www-data:www-data /var/backups/erp. Se usar serviço de backup em nuvem, verifique se as credenciais S3/Azure não expiraram em Configurações > Backup > Credenciais. Após corrigir, force execução manual do job em Ferramentas > Jobs Agendados > Executar Agora.',
            'tags'     => 'backup, permissão, servidor, job, agendado',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'Como exportar dados para migração entre empresas',
            'problem'  => 'Cliente precisa migrar os dados cadastrais (clientes, fornecedores, produtos) de uma empresa para outra dentro do mesmo sistema, sem perder o histórico de movimentações.',
            'solution' => 'Use o módulo de Migração de Dados: 1) Acesse Ferramentas > Migração de Dados > Exportar; 2) Selecione a empresa origem e os módulos desejados (Clientes, Fornecedores, Produtos, Plano de Contas); 3) Defina o período do histórico a exportar; 4) Gere o arquivo .mig; 5) Na empresa destino, acesse Ferramentas > Migração de Dados > Importar e selecione o arquivo; 6) Valide o relatório de inconsistências antes de confirmar. Recomenda-se fazer backup completo de ambas as empresas antes do processo.',
            'tags'     => 'migração, exportação, dados, empresas, backup',
            'visibility' => 'public',
        ],
        [
            'title'    => 'Impressora fiscal (ECF) não comunicando com o sistema',
            'problem'  => 'O PDV não consegue comunicar com a impressora fiscal ECF. A mensagem exibida é "Impressora não encontrada na porta COM3". A impressora está ligada e o piloto verde está aceso.',
            'solution' => 'Verifique sequencialmente: 1) Porta COM: acesse Gerenciador de Dispositivos e confirme a porta real da impressora (pode ter mudado após reinicialização); 2) No PDV, acesse Configurações > Periféricos > Impressora Fiscal e atualize a porta; 3) Verifique se o driver BEMATECH/SWEDA está instalado e atualizado; 4) Teste a porta com o aplicativo de diagnóstico da fabricante; 5) Se usar adaptador USB-Serial, reinstale o driver do adaptador. Em último caso, reinicie o serviço do spooler de impressão.',
            'tags'     => 'pdv, impressora, ecf, fiscal, com, driver',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'DANFE em branco ao tentar visualizar NF-e emitida',
            'problem'  => 'Ao clicar em "Visualizar DANFE" para uma NF-e já autorizada pela SEFAZ, o PDF é gerado mas aparece completamente em branco. O XML da nota está correto e acessível.',
            'solution' => 'O problema está no gerador de PDF. Verifique: 1) Se o servidor tem o pacote ghostscript instalado (sudo apt install ghostscript); 2) Se o diretório temporário /tmp tem permissão de escrita; 3) Limpe o cache de DANFE em Ferramentas > Cache > Limpar Cache de Documentos; 4) Tente regenerar clicando em NF-e > [nota] > Reprocessar DANFE. Se persistir, verifique os logs em storage/logs/nfe.log para identificar o erro específico do gerador.',
            'tags'     => 'nfe, danfe, pdf, impressão, sefaz',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'Como configurar múltiplos depósitos de estoque',
            'problem'  => 'A empresa possui 3 depósitos físicos e precisa controlar o estoque separadamente por localização, mas o sistema está configurado com apenas um depósito padrão.',
            'solution' => 'Ative o módulo multi-depósito: 1) Acesse Configurações > Módulos > Ativar Multi-Depósito (requer licença do módulo); 2) Cadastre os depósitos em Estoque > Depósitos > Novo; 3) Defina o depósito padrão para entrada e outro para saída (PDV); 4) Nos produtos, configure o depósito de preferência; 5) Nas NF-e de compra e venda, selecione o depósito correspondente. Para transferências entre depósitos, use Estoque > Transferências > Nova Transferência. O saldo consolidado aparece nos relatórios com opção de filtrar por depósito.',
            'tags'     => 'estoque, depósito, multi-depósito, localização, controle',
            'visibility' => 'public',
        ],
        [
            'title'    => 'Erro 500 ao gerar relatório de DRE',
            'problem'  => 'Ao tentar gerar o relatório de Demonstração do Resultado do Exercício (DRE) para o ano fiscal corrente, o sistema retorna erro 500. Relatórios de outros períodos funcionam normalmente.',
            'solution' => 'O erro 500 é causado por entradas contábeis sem centro de custo no período atual, o que gera divisão por zero no agrupamento. Para corrigir: 1) Acesse Contabilidade > Lançamentos e filtre o período do problema; 2) Identifique lançamentos sem centro de custo (filtro "CC: vazio"); 3) Complemente os lançamentos com o centro de custo correto; 4) Se não for possível identificar, crie um CC genérico "Não Classificado" e atribua a eles; 5) Tente gerar o DRE novamente. Para prevenção, configure o campo CC como obrigatório em Configurações > Contabilidade.',
            'tags'     => 'dre, relatório, contabilidade, erro 500, centro de custo',
            'visibility' => 'internal',
        ],
        [
            'title'    => 'Sincronização com banco de dados do governo (CNPJ/CPF) falha',
            'problem'  => 'A consulta automática de CNPJ para preenchimento de dados de clientes e fornecedores retorna "Serviço indisponível" para todos os CNPJs consultados. Problema iniciou há 2 dias.',
            'solution' => 'A API de consulta de CNPJ da Receita Federal teve mudança de endpoint. Atualize a URL em Configurações > Integrações > Receita Federal: substitua "www.receita.fazenda.gov.br/api/v1" por "brasilapi.com.br/api/cnpj/v1". Se a empresa utiliza API paga (Serpro), verifique se o token não venceu. Como alternativa temporária, os usuários podem usar o site cnpj.info ou a consulta manual na Receita para preencher os dados.',
            'tags'     => 'cnpj, receita federal, integração, api, consulta',
            'visibility' => 'internal',
        ],
    ];

    public function definition(): array
    {
        static $index = 0;
        $article = self::$articles[$index % count(self::$articles)];
        $index++;

        return [
            'author_id'  => User::where('ticketit_agent', true)
                                ->orWhere('ticketit_admin', true)
                                ->inRandomOrder()
                                ->value('id') ?? 1,
            'ticket_id'  => null,
            'category_id' => Category::inRandomOrder()->value('category_id'),
            'title'      => $article['title'],
            'problem'    => $article['problem'],
            'solution'   => $article['solution'],
            'tags'       => $article['tags'],
            'visibility' => $article['visibility'],
            'active'     => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function public(): static
    {
        return $this->state(['visibility' => 'public']);
    }
}
