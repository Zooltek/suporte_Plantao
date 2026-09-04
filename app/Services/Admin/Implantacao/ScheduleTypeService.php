<?php

declare(strict_types=1);

namespace App\Services\Admin\Implantacao;

use App\Contracts\Repositories\ScheduleTypeRepositoryInterface;
use App\Models\ScheduleType;
use DomainException;
use Illuminate\Support\Collection;

/**
 * Regras de negócio do cadastro de Tipos de Agendamento.
 * O Service não acessa o banco diretamente — toda persistência é delegada ao Repository.
 */
class ScheduleTypeService
{
    public function __construct(
        private readonly ScheduleTypeRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, ScheduleType>
     */
    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function create(array $data): ScheduleType
    {
        return $this->repository->create([
            'slug'              => $data['slug'],
            'label'             => $data['label'],
            'requires_customer' => (bool) ($data['requires_customer'] ?? false),
            'is_active'         => (bool) ($data['is_active'] ?? true),
            'is_system'         => false,
            'sort_order'        => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    /**
     * Tipos nativos (is_system=true) não permitem alterar slug nem desativar,
     * evitando quebrar integrações e fluxos legados do enum antigo.
     */
    public function update(ScheduleType $type, array $data): ScheduleType
    {
        $payload = [
            'label'             => $data['label'],
            'requires_customer' => (bool) ($data['requires_customer'] ?? false),
            'is_active'         => $type->is_system ? true : (bool) ($data['is_active'] ?? true),
            'sort_order'        => (int) ($data['sort_order'] ?? $type->sort_order),
        ];

        if (!$type->is_system) {
            $payload['slug'] = $data['slug'];
        }

        return $this->repository->update($type, $payload);
    }

    public function delete(ScheduleType $type): void
    {
        if ($type->is_system) {
            throw new DomainException('Tipos de sistema não podem ser excluídos.');
        }

        if ($this->repository->hasSchedules($type)) {
            throw new DomainException('Existem agendamentos vinculados a este tipo; desative-o em vez de excluir.');
        }

        $this->repository->delete($type);
    }
}
