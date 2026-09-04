<?php

namespace App\Services\Ticket\Routing;

/**
 * Sinais que podem influenciar o departamento responsável por um chamado.
 *
 * Imutável por design — o Resolver consome o intent inteiro e devolve
 * um único int. Adicionar novo sinal aqui obriga o Resolver a tratá-lo,
 * mantendo a precedência em um único ponto do domínio.
 */
final readonly class TicketDepartmentRoutingIntent
{
    public function __construct(
        public ?int $explicitDepartmentId = null,
        public ?int $subCategoryId = null,
        public ?int $categoryId = null,
        public ?int $channelDepartmentId = null,
        public ?int $agentId = null,
        public bool $allowSupportFallback = true,
    ) {}
}
