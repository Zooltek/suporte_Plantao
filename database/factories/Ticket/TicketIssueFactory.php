<?php

namespace Database\Factories\Ticket;

use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketIssue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketIssueFactory extends Factory
{
    protected $model = TicketIssue::class;

    private static array $issues = [
        ['title' => 'Configuração de parâmetro incorreta',       'desc' => 'O parâmetro de ambiente fiscal está definido como homologação em vez de produção, causando rejeição nas notas emitidas.'],
        ['title' => 'Permissão de acesso negada ao módulo',      'desc' => 'Após a atualização, as permissões do módulo financeiro foram redefinidas incorretamente para o perfil do usuário.'],
        ['title' => 'Certificado digital vencido',               'desc' => 'O certificado A1 utilizado para assinatura de documentos fiscais está expirado, impedindo a emissão de NF-e.'],
        ['title' => 'Token de integração expirado',              'desc' => 'O token de autenticação da API do e-commerce venceu, interrompendo a sincronização automática de pedidos.'],
        ['title' => 'Saldo de estoque zerado após migração',     'desc' => 'A migração de base não atualizou os saldos acumulados, exibindo quantidade e custo zerados nos relatórios.'],
        ['title' => 'Falha no job de backup automático',         'desc' => 'O job agendado para as 02h falha com erro de permissão no diretório /var/backups após atualização do servidor.'],
        ['title' => 'DANFE gerada em branco',                   'desc' => 'O PDF da DANFE é gerado mas aparece completamente em branco. Problema relacionado ao pacote ghostscript no servidor.'],
        ['title' => 'Consulta de CNPJ retorna indisponível',    'desc' => 'A API de consulta de CNPJ da Receita Federal mudou de endpoint, causando falha em todos os preenchimentos automáticos.'],
        ['title' => 'Lentidão no fechamento mensal',            'desc' => 'O sistema fica muito lento entre os dias 1 e 5 do mês devido a processos concorrentes de fechamento contábil.'],
        ['title' => 'Erro 500 no relatório de DRE',             'desc' => 'Lançamentos contábeis sem centro de custo no período geram divisão por zero no agrupamento do DRE.'],
        ['title' => 'Impressora ECF não comunica com PDV',      'desc' => 'A porta COM da impressora ECF mudou após reinicialização do servidor, quebrando a comunicação com o PDV.'],
        ['title' => 'Usuário não consegue alterar senha',       'desc' => 'Usuários que tiveram senha resetada pelo admin não conseguem alterá-la pelo perfil por conflito de hash temporário.'],
        ['title' => 'NF-e rejeitada com código 999',            'desc' => 'Rejeição com código 999 na SEFAZ indicando ambiente incorreto. Sistema em homologação usando CNPJ real.'],
        ['title' => 'Relatório de DRE com período bloqueado',   'desc' => 'O período de fechamento anterior está bloqueado para edição, impedindo correção de lançamentos incorretos.'],
        ['title' => 'Sincronização de pedidos travada',         'desc' => 'O serviço de sincronização está em loop tentando reimportar o mesmo pedido repetidamente por falha de idempotência.'],
    ];

    private static array $solutions = [
        'Alterado o parâmetro de ambiente para Produção em Configurações > Fiscal. Testada a emissão com sucesso.',
        'Realizado reprocessamento das permissões via painel administrativo. Usuário confirmou acesso normalizado.',
        'Orientado o cliente sobre renovação do certificado. Novo certificado importado e testada comunicação com SEFAZ.',
        'Gerado novo token no painel da loja virtual e atualizado em Configurações > Integrações. Sincronização normalizada.',
        'Executado recálculo de saldos via Estoque > Utilitários. Relatórios exibindo dados corretos após o processo.',
        'Ajustadas permissões do diretório com chmod 755 e chown www-data. Job de backup executado com sucesso.',
        'Instalado ghostscript no servidor e limpo cache de documentos. DANFE reprocessada e validada pelo cliente.',
        'URL da API atualizada para brasilapi.com.br em Configurações > Integrações > Receita Federal.',
        'Reindexadas as tabelas e fechamento agendado para após as 20h. Timeout de sessão ajustado para 120 segundos.',
        'Criado centro de custo "Não Classificado" e atribuídos os lançamentos pendentes. DRE gerado com sucesso.',
        'Porta COM atualizada no PDV após verificação no Gerenciador de Dispositivos. Comunicação com ECF normalizada.',
        'Enviado link de redefinição via painel admin. Usuário redefiniu a senha e confirmou acesso.',
        'Ambiente alterado para Produção. Testada emissão de NF-e com sucesso após a correção.',
        'Desbloqueado o período pelo administrador do sistema. Lançamentos corrigidos e DRE reprocessado.',
        'Implementada idempotência na importação verificando o pedido pelo ID externo antes de inserir.',
    ];

    public function definition(): array
    {
        $issue     = $this->faker->randomElement(self::$issues);
        $isResolved = $this->faker->boolean(60);
        $createdAt = $this->faker->dateTimeBetween('-15 days', '-1 hour');

        $createdBy = User::where('ticketit_agent', true)
                         ->orWhere('ticketit_admin', true)
                         ->inRandomOrder()
                         ->value('id') ?? 1;

        return [
            'ticket_id'   => Ticket::inRandomOrder()->value('id') ?? 1,
            'created_by'  => $createdBy,
            'title'       => $issue['title'],
            'description' => $issue['desc'],
            'solution'    => $isResolved ? $this->faker->randomElement(self::$solutions) : null,
            'status'      => $isResolved ? 'resolved' : 'open',
            'resolved_at' => $isResolved ? $this->faker->dateTimeBetween($createdAt, 'now') : null,
            'resolved_by' => $isResolved ? User::where('ticketit_agent', true)->inRandomOrder()->value('id') : null,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ];
    }

    public function open(): static
    {
        return $this->state([
            'status'      => 'open',
            'solution'    => null,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(function () {
            return [
                'status'      => 'resolved',
                'solution'    => $this->faker->randomElement(self::$solutions),
                'resolved_at' => $this->faker->dateTimeBetween('-5 days', 'now'),
                'resolved_by' => User::where('ticketit_agent', true)->inRandomOrder()->value('id'),
            ];
        });
    }
}
