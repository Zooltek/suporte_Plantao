<?php

namespace App\Contracts\Repositories;

use App\Models\Category;
use App\Models\Company;
use App\Models\Tasks\Task;
use App\Models\Ticket\Ticket;
use Closure;

interface TicketRepositoryInterface
{
    /**
     * Busca uma categoria pelo seu category_id (PK customizada).
     * Retorna null se não encontrada.
     */
    public function findCategoryById(int $id): ?Category;

    /**
     * Busca uma empresa pelo id.
     * Lança ModelNotFoundException se não encontrada.
     */
    public function findCompanyOrFail(int $id): Company;

    /**
     * Persiste (INSERT ou UPDATE) o ticket no banco.
     */
    public function save(Ticket $ticket): void;

    /**
     * Apaga as categorias extras do ticket e recria com os dados fornecidos.
     *
     * @param  array<int, array{category_id: int, sub_category_id: int}>  $extras
     */
    public function syncExtraCategories(Ticket $ticket, array $extras): void;

    /**
     * Associa os IDs de Attachment pendentes ao ticket recém-persistido.
     *
     * @param  int[]  $attachmentIds
     */
    public function updateAttachments(array $attachmentIds, int $ticketId): void;

    /**
     * Cria uma Task vinculada ao ticket no sistema de tarefas.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTask(array $data): Task;

    /**
     * Atualiza atributos pontuais do ticket (e.g. task_id, elapsed_token).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateTicket(Ticket $ticket, array $attributes): void;

    /**
     * Retorna o ID do status "Solicitação" (com cache para evitar query repetida).
     */
    public function getSolicitacaoStatusId(): int;

    /**
     * Verifica se um status é terminal (chamado finalizado/encerrado).
     * Encapsula a query ao banco, mantendo o Service sem acesso direto ao Eloquent.
     */
    public function isStatusTerminal(int $statusId): bool;

    /**
     * Verifica se um status exige agendamento após salvar o chamado.
     * Encapsula a query ao banco, mantendo o Service sem acesso direto ao Eloquent.
     */
    public function isStatusRequiresSchedule(int $statusId): bool;

    /**
     * Verifica se um status exige o preenchimento da solução.
     * Encapsula a query ao banco, mantendo o Service sem acesso direto ao Eloquent.
     */
    public function isStatusRequiresSolution(int $statusId): bool;

    /**
     * Executa um Closure dentro de uma transação de banco de dados.
     * Centraliza o controle transacional na camada de Repository.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function transaction(Closure $callback): mixed;
}
