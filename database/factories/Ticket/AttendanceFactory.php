<?php

namespace Database\Factories\Ticket;

use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    private static array $notes = [
        'Verificado o ambiente do cliente. O problema está relacionado à configuração incorreta do parâmetro de NF-e. Orientado o usuário a acessar Configurações > Fiscal e ajustar o ambiente para Produção.',
        'Acessado remotamente via TeamViewer. Identificado conflito de permissões no módulo financeiro após atualização. Realizado reprocessamento das permissões pelo painel de administração.',
        'Recebida confirmação do cliente via e-mail. O problema foi resolvido após execução do script de recálculo de saldos. Aguardando feedback final do usuário.',
        'Realizado diagnóstico inicial. O certificado digital A1 está expirado. Orientado sobre o processo de renovação junto à autoridade certificadora e como importar o novo certificado no sistema.',
        'Verificado o log do servidor. Erro 500 causado por lançamentos sem centro de custo no período de fechamento. Criado CC genérico e atribuídos os lançamentos pendentes.',
        'Identificado que o token de integração com o e-commerce expirou. Gerado novo token no painel da loja e atualizado em Configurações > Integrações. Sincronização normalizada.',
        'Confirmado com o cliente que o backup estava falhando por permissão negada no diretório /var/backups. Ajustadas as permissões via SSH e validado o job de backup.',
        'Orientado o cliente sobre o processo de migração de dados entre empresas. Fornecido o passo a passo e checklist para evitar inconsistências durante a importação.',
        'Analisado o problema com a impressora ECF. Porta COM havia mudado após reinicialização. Atualizado a porta no PDV e testada a comunicação com sucesso.',
        'DANFE em branco resolvida após instalação do ghostscript no servidor. Cache de documentos limpo e DANFE reprocessada com sucesso. Confirmado com o cliente.',
        'Verificado problema de lentidão no fechamento mensal. Reindexadas as tabelas e agendado o fechamento para após as 20h. Configurado timeout de sessão para 120s.',
        'Cliente relatou que usuário não consegue alterar senha. Enviado link de redefinição via painel admin. Usuário redefiniu a senha e confirmou o acesso normalizado.',
        'Configurado o envio automático de boletos por e-mail. Conta remetente vinculada, antecedência definida para 5 dias e template selecionado. Testado com cliente de homologação.',
        'Verificado erro na consulta de CNPJ. URL da API da Receita Federal atualizada para brasilapi.com.br. Consultas funcionando normalmente após atualização.',
        'Atualizado multi-depósito conforme solicitação do cliente. Três depósitos cadastrados, depósito padrão configurado e testadas as transferências entre unidades.',
        'Recebida solicitação de suporte por WhatsApp. Encaminhado para análise técnica. Aguardando retorno do setor de desenvolvimento.',
        'Verificado ambiente e coletadas informações do erro. Aberto chamado interno para análise pelo time de desenvolvimento. ETA: 48 horas úteis.',
        'Realizado atendimento telefônico. Identificado que o usuário não seguiu o processo correto. Repassado o fluxo correto e confirmado entendimento.',
    ];

    public function definition(): array
    {
        $ticketId = Ticket::inRandomOrder()->value('id') ?? 1;
        $agentId  = User::where('ticketit_agent', true)
                        ->orWhere('ticketit_admin', true)
                        ->inRandomOrder()
                        ->value('id') ?? 1;

        $createdAt = $this->faker->dateTimeBetween('-20 days', 'now');

        return [
            'ticket_id'   => $ticketId,
            'user_id'     => $agentId,
            'notes'       => $this->faker->randomElement(self::$notes),
            'return_zap'  => $this->faker->boolean(30),
            'return_tel'  => $this->faker->boolean(20),
            'return_cel'  => $this->faker->boolean(15),
            'returned_by' => null,
            'returned_at' => null,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ];
    }

    public function withReturn(): static
    {
        return $this->state(function () {
            $returnedAt = $this->faker->dateTimeBetween('-5 days', 'now');
            return [
                'returned_by' => User::where('ticketit_agent', true)->inRandomOrder()->value('id'),
                'returned_at' => $returnedAt,
            ];
        });
    }
}
