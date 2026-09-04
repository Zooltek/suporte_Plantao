<?php

namespace App\Services\Ticket\Routing;

use App\Models\Category;
use App\Models\Department;
use App\Models\User;

/**
 * Decide o department_id de um ticket aplicando uma precedência única.
 *
 * Precedência (do mais específico ao mais genérico):
 *
 *   1. Override manual (form/API) — intent explícita do operador.
 *   2. Departamento da Subcategoria — natureza mais específica do chamado.
 *   3. Departamento da Categoria pai — natureza genérica do chamado.
 *   4. Departamento do canal — escolha do cliente no bot/portal.
 *   5. Departamento do agente atribuído — herdado de quem vai operar.
 *   6. Fallback Suporte Técnico — evita NULL na fila quando nada se aplica.
 *
 * Este Resolver é o único ponto de domínio que define o departamento;
 * Services específicos (TicketService, WhatsAppTicketService, etc.)
 * devem apenas montar o intent e delegar.
 */
class TicketDepartmentResolver
{
    /**
     * @var array<int,?int> cache simples por categoria dentro da request
     */
    private array $categoryDepartmentCache = [];

    /**
     * @var array<int,?int> cache simples por agente dentro da request
     */
    private array $agentDepartmentCache = [];

    private ?int $supportFallbackId = null;

    private bool $supportFallbackResolved = false;

    public function resolve(TicketDepartmentRoutingIntent $intent): ?int
    {
        if ($intent->explicitDepartmentId !== null) {
            return $intent->explicitDepartmentId;
        }

        $fromSubCategory = $this->departmentIdForCategory($intent->subCategoryId);
        if ($fromSubCategory !== null) {
            return $fromSubCategory;
        }

        $fromCategory = $this->departmentIdForCategory($intent->categoryId);
        if ($fromCategory !== null) {
            return $fromCategory;
        }

        if ($intent->channelDepartmentId !== null) {
            return $intent->channelDepartmentId;
        }

        $fromAgent = $this->departmentIdForAgent($intent->agentId);
        if ($fromAgent !== null) {
            return $fromAgent;
        }

        if ($intent->allowSupportFallback) {
            return $this->supportFallbackDepartmentId();
        }

        return null;
    }

    private function departmentIdForCategory(?int $categoryId): ?int
    {
        if ($categoryId === null) {
            return null;
        }

        if (array_key_exists($categoryId, $this->categoryDepartmentCache)) {
            return $this->categoryDepartmentCache[$categoryId];
        }

        $value = Category::query()
            ->whereKey($categoryId)
            ->value('department_id');

        $resolved = $value ? (int) $value : null;
        $this->categoryDepartmentCache[$categoryId] = $resolved;

        return $resolved;
    }

    private function departmentIdForAgent(?int $agentId): ?int
    {
        if ($agentId === null) {
            return null;
        }

        if (array_key_exists($agentId, $this->agentDepartmentCache)) {
            return $this->agentDepartmentCache[$agentId];
        }

        $value = User::query()
            ->whereKey($agentId)
            ->value('department_id');

        $resolved = $value ? (int) $value : null;
        $this->agentDepartmentCache[$agentId] = $resolved;

        return $resolved;
    }

    private function supportFallbackDepartmentId(): ?int
    {
        if ($this->supportFallbackResolved) {
            return $this->supportFallbackId;
        }

        $this->supportFallbackResolved = true;

        $id = Department::query()
            ->where('name', 'like', '%Suporte%')
            ->orderBy('id')
            ->value('id');

        $this->supportFallbackId = $id ? (int) $id : null;

        return $this->supportFallbackId;
    }
}
