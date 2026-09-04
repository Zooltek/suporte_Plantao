<?php

/**
 * Testes unitários do ElementTypeService (CRM Feedback).
 *
 * Todas as chamadas ao banco são interceptadas pelo Mock do
 * ElementTypeRepositoryInterface, isolando a regra de negócio do Service.
 */

use App\Contracts\Repositories\ElementTypeRepositoryInterface;
use App\Models\Crm\Feedback\ElementType;
use App\Services\Admin\Crm\ElementTypeService;
use Illuminate\Database\Eloquent\Collection;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ets_service(ElementTypeRepositoryInterface $repo): ElementTypeService
{
    return new ElementTypeService($repo);
}

function ets_makeModel(array $attrs = []): ElementType
{
    $et = new ElementType(array_merge(['name' => 'nps', 'label' => 'NPS', 'type' => 'number'], $attrs));
    $et->id = $attrs['id'] ?? 1;

    return $et;
}

// ─── getAll ───────────────────────────────────────────────────────────────────

describe('ElementTypeService — getAll', function () {

    it('delega ao repositório e retorna a coleção', function () {
        $repo = Mockery::mock(ElementTypeRepositoryInterface::class);
        $col  = new Collection([ets_makeModel(), ets_makeModel(['id' => 2])]);

        $repo->shouldReceive('getAll')->once()->andReturn($col);

        $result = ets_service($repo)->getAll();

        expect($result)->toBe($col);
    });

});

// ─── find ─────────────────────────────────────────────────────────────────────

describe('ElementTypeService — find', function () {

    it('delega ao repositório com o id correto', function () {
        $repo = Mockery::mock(ElementTypeRepositoryInterface::class);
        $et   = ets_makeModel(['id' => 7]);

        $repo->shouldReceive('find')->once()->with(7)->andReturn($et);

        $result = ets_service($repo)->find(7);

        expect($result)->toBe($et);
    });

});

// ─── create ───────────────────────────────────────────────────────────────────

describe('ElementTypeService — create', function () {

    it('delega ao repositório com os dados corretos', function () {
        $repo = Mockery::mock(ElementTypeRepositoryInterface::class);
        $data = ['name' => 'csat', 'label' => 'CSAT', 'type' => 'select'];
        $et   = ets_makeModel($data);

        $repo->shouldReceive('create')->once()->with($data)->andReturn($et);

        $result = ets_service($repo)->create($data);

        expect($result)->toBe($et);
    });

});

// ─── update ───────────────────────────────────────────────────────────────────

describe('ElementTypeService — update', function () {

    it('delega ao repositório passando o model e os dados', function () {
        $repo    = Mockery::mock(ElementTypeRepositoryInterface::class);
        $et      = ets_makeModel(['label' => 'Antigo']);
        $data    = ['label' => 'Novo'];
        $updated = ets_makeModel(['label' => 'Novo']);

        $repo->shouldReceive('update')->once()->with($et, $data)->andReturn($updated);

        $result = ets_service($repo)->update($et, $data);

        expect($result)->toBe($updated);
    });

});

// ─── delete ───────────────────────────────────────────────────────────────────

describe('ElementTypeService — delete', function () {

    it('delega ao repositório sem retorno', function () {
        $repo = Mockery::mock(ElementTypeRepositoryInterface::class);
        $et   = ets_makeModel();

        $repo->shouldReceive('delete')->once()->with($et)->andReturnNull();

        ets_service($repo)->delete($et);

        expect(true)->toBeTrue(); // verifica que não lançou exceção
    });

});
