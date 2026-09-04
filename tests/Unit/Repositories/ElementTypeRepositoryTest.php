<?php

/**
 * Testes de integração do ElementTypeRepository (CRM Feedback) — SQLite in-memory.
 *
 * Cobrem todos os métodos públicos da implementação, validando que as queries
 * produzem os resultados esperados sem acionar lógica de negócio do Service.
 */

use App\Models\Crm\Feedback\ElementType;
use App\Repositories\ElementTypeRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function etr_repo(): ElementTypeRepository
{
    return new ElementTypeRepository();
}

function etr_make(array $attrs = []): ElementType
{
    return ElementType::factory()->create($attrs);
}

// ─── getAll ───────────────────────────────────────────────────────────────────

describe('ElementTypeRepository — getAll', function () {

    it('retorna todos os ElementTypes cadastrados', function () {
        $et1 = etr_make();
        $et2 = etr_make();

        $result = etr_repo()->getAll();

        expect($result->pluck('id')->toArray())
            ->toContain($et1->id)
            ->toContain($et2->id);
    });

    it('retorna coleção vazia quando não há registros', function () {
        $result = etr_repo()->getAll();
        expect($result)->toBeEmpty();
    });

});

// ─── find ─────────────────────────────────────────────────────────────────────

describe('ElementTypeRepository — find', function () {

    it('retorna o ElementType pelo id', function () {
        $et = etr_make();

        $found = etr_repo()->find($et->id);

        expect($found->id)->toBe($et->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => etr_repo()->find(999999))
            ->toThrow(ModelNotFoundException::class);
    });

});

// ─── create ───────────────────────────────────────────────────────────────────

describe('ElementTypeRepository — create', function () {

    it('persiste um novo ElementType no banco', function () {
        $data = [
            'name'       => 'nps_score',
            'label'      => 'NPS Score',
            'type'       => 'number',
            'sort_order' => 1,
        ];

        $et = etr_repo()->create($data);

        expect($et->id)->not->toBeNull();
        expect($et->name)->toBe('nps_score');

        $this->assertDatabaseHas('crm_feedback_element_type', ['id' => $et->id, 'name' => 'nps_score']);
    });

});

// ─── update ───────────────────────────────────────────────────────────────────

describe('ElementTypeRepository — update', function () {

    it('atualiza os campos do ElementType e retorna instância fresca', function () {
        $et = etr_make(['label' => 'Original']);

        $updated = etr_repo()->update($et, ['label' => 'Atualizado']);

        expect($updated->label)->toBe('Atualizado');
        $this->assertDatabaseHas('crm_feedback_element_type', ['id' => $et->id, 'label' => 'Atualizado']);
    });

});

// ─── delete ───────────────────────────────────────────────────────────────────

describe('ElementTypeRepository — delete', function () {

    it('remove o ElementType do banco', function () {
        $et = etr_make();

        etr_repo()->delete($et);

        $this->assertDatabaseMissing('crm_feedback_element_type', ['id' => $et->id]);
    });

});
