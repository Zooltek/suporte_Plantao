<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Crm\Feedback\ElementType;
use Illuminate\Database\Eloquent\Collection;

interface ElementTypeRepositoryInterface
{
    /**
     * Retorna todos os ElementTypes do CRM-Feedback.
     */
    public function getAll(): Collection;

    /**
     * Busca um ElementType pelo id; lança ModelNotFoundException se não encontrado.
     */
    public function find(int $id): ElementType;

    /**
     * Persiste um novo ElementType no banco.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ElementType;

    /**
     * Atualiza um ElementType existente e retorna a instância atualizada.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ElementType $elementType, array $data): ElementType;

    /**
     * Remove um ElementType do banco.
     */
    public function delete(ElementType $elementType): void;
}
