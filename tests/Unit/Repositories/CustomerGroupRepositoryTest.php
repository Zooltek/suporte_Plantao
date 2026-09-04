<?php

use App\Models\CustomerGroup;
use App\Repositories\CustomerGroupRepository;

// ─────────────────────────────────────────────────────────────────────────────
// CustomerGroupRepository — testes unitários de upsert por financial_code
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->repo = new CustomerGroupRepository;
});

it('cria o grupo quando o financial_code ainda não existe', function () {
    $group = $this->repo->upsertByFinancialCode('ABC123', 'Grupo Novo');

    expect($group)->toBeInstanceOf(CustomerGroup::class)
        ->and($group->financial_code)->toBe('ABC123')
        ->and($group->name)->toBe('Grupo Novo')
        ->and($group->status)->toBe(true)
        ->and($group->hash)->not->toBeEmpty(); // hash gerado automaticamente
});

it('retorna o grupo existente sem duplicar quando o financial_code já existe', function () {
    $primeiro = $this->repo->upsertByFinancialCode('CODE01', 'Grupo A');
    $segundo = $this->repo->upsertByFinancialCode('CODE01', 'Grupo A');

    expect(CustomerGroup::where('financial_code', 'CODE01')->count())->toBe(1)
        ->and($segundo->id)->toBe($primeiro->id);
});

it('atualiza o name quando o grupo já existe e o nome mudou', function () {
    $this->repo->upsertByFinancialCode('CODE02', 'Nome Antigo');

    $atualizado = $this->repo->upsertByFinancialCode('CODE02', 'Nome Novo');

    expect($atualizado->name)->toBe('Nome Novo')
        ->and(CustomerGroup::where('financial_code', 'CODE02')->value('name'))->toBe('Nome Novo');
});

it('mantém grupos distintos quando o nome é igual e os códigos são diferentes', function () {
    $this->repo->upsertByFinancialCode('CODE-A', 'Mesmo Nome');
    $this->repo->upsertByFinancialCode('CODE-B', 'Mesmo Nome');

    expect(CustomerGroup::where('name', 'Mesmo Nome')->count())->toBe(2);
});

it('não atualiza o name quando o grupo já existe e o nome é o mesmo', function () {
    $original = $this->repo->upsertByFinancialCode('CODE03', 'Mesmo Nome');
    $retornado = $this->repo->upsertByFinancialCode('CODE03', 'Mesmo Nome');

    expect($retornado->id)->toBe($original->id)
        ->and($retornado->name)->toBe('Mesmo Nome');
});

it('encontra o grupo pelo financial_code', function () {
    $this->repo->upsertByFinancialCode('FIND01', 'Grupo Localizado');

    $encontrado = $this->repo->findByFinancialCode('FIND01');

    expect($encontrado)->not->toBeNull()
        ->and($encontrado->financial_code)->toBe('FIND01');
});

it('retorna null quando o financial_code não existe', function () {
    expect($this->repo->findByFinancialCode('INEXISTENTE'))->toBeNull();
});
