<?php

namespace Database\Factories\Ticket;

use App\Models\Ticket\Comment;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    private static array $comments = [
        'Verificado o ambiente do cliente. Vou escalar para a equipe técnica para análise mais detalhada.',
        'Consegui reproduzir o problema em ambiente de homologação. Identificado o ponto de falha.',
        'Aguardando retorno do cliente para confirmar se a solução aplicada resolveu o problema.',
        'Problema confirmado. Criando tarefa para o time de desenvolvimento analisar o bug.',
        'Solução aplicada com sucesso. Solicito ao cliente que valide em ambiente de produção.',
        'Verificado que o problema ocorre apenas em determinadas configurações de browser. Testando soluções.',
        'Acessado remotamente o ambiente do cliente. Coletados os logs para análise.',
        'O cliente confirmou que o problema foi resolvido. Encerrando o chamado.',
        'Situação atualizada: a equipe de TI do cliente está trabalhando na liberação de acesso ao servidor.',
        'Identificada a causa raiz. Trata-se de um bug de versão que será corrigido na próxima atualização.',
        'Solução temporária aplicada. A correção definitiva será entregue na release 3.2.1.',
        'Cliente confirmou que não consegue reproduzir mais o erro. Monitorando por 24h antes de fechar.',
        'Levantadas todas as informações necessárias. Enviando para o suporte N2 para análise especializada.',
        'Verificado que o problema está relacionado à infraestrutura do cliente, não ao sistema. Orientado o time de TI.',
        'Processo de importação concluído. Validados 1.247 registros sem inconsistências.',
        'Reunião agendada com o cliente para amanhã às 14h para demonstração do módulo configurado.',
        'Enviado e-mail com passo a passo detalhado para o cliente. Aguardando confirmação de recebimento.',
        'Atualizado o status do chamado. Aguardando aprovação do gerente do cliente para prosseguir.',
    ];

    public function definition(): array
    {
        $content   = $this->faker->randomElement(self::$comments);
        $userId    = User::where('ticketit_agent', true)
                        ->orWhere('ticketit_admin', true)
                        ->inRandomOrder()
                        ->value('id') ?? 1;
        $createdAt = $this->faker->dateTimeBetween('-20 days', 'now');

        return [
            'ticket_id'  => Ticket::inRandomOrder()->value('id') ?? 1,
            'user_id'    => $userId,
            'content'    => $content,
            'html'       => '<p>' . $content . '</p>',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
