<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\ScheduleType;
use Illuminate\Support\Collection;

interface ScheduleTypeRepositoryInterface
{
    /**
     * Retorna todos os tipos (ativos e inativos) ordenados para listagens administrativas.
     *
     * @return Collection<int, ScheduleType>
     */
    public function getAll(): Collection;

    /**
     * Retorna apenas os tipos ativos, em cache, para uso em selects e validações.
     *
     * @return Collection<int, ScheduleType>
     */
    public function getActive(): Collection;

    /**
     * Localiza um tipo pelo slug, ou null quando inexistente.
     */
    public function findBySlug(string $slug): ?ScheduleType;

    public function create(array $data): ScheduleType;

    public function update(ScheduleType $type, array $data): ScheduleType;

    public function delete(ScheduleType $type): void;

    /**
     * Indica se o tipo possui agendamentos (ativos ou históricos) vinculados.
     */
    public function hasSchedules(ScheduleType $type): bool;

    /**
     * Invalida o cache de tipos ativos.
     */
    public function flushCache(): void;
}
