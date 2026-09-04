<?php

/**
 * Testes unitários do RatChecklistService.
 *
 * Todas as chamadas ao banco são interceptadas pelo Mock do
 * RatChecklistRepositoryInterface, isolando as regras de negócio do Service.
 */

use App\Contracts\Repositories\RatChecklistRepositoryInterface;
use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use App\Services\Admin\Implantacao\RatChecklistService;
use Illuminate\Support\Collection;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function rcs_service(RatChecklistRepositoryInterface $repo): RatChecklistService
{
    return new RatChecklistService($repo);
}

function rcs_makeGroup(array $attrs = []): ElementGroup
{
    $g = new ElementGroup(array_merge(['name' => 'Grupo A'], $attrs));
    $g->id = $attrs['id'] ?? 1;

    return $g;
}

function rcs_makeItem(array $attrs = []): ElementType
{
    $item = new ElementType(array_merge(['name' => 'item', 'label' => 'Item', 'type' => 'checkbox'], $attrs));
    $item->id = $attrs['id'] ?? 1;

    return $item;
}

// ─── getModulesWithItems ───────────────────────────────────────────────────────

describe('RatChecklistService — getModulesWithItems', function () {

    it('delega ao repositório e retorna a coleção', function () {
        $repo = Mockery::mock(RatChecklistRepositoryInterface::class);
        $col  = new Collection([new Module(['name' => 'M1'])]);

        $repo->shouldReceive('getModulesWithItems')->once()->andReturn($col);

        $result = rcs_service($repo)->getModulesWithItems();

        expect($result)->toBe($col);
    });

});

// ─── getAllGroups ──────────────────────────────────────────────────────────────

describe('RatChecklistService — getAllGroups', function () {

    it('delega ao repositório e retorna a coleção de grupos', function () {
        $repo = Mockery::mock(RatChecklistRepositoryInterface::class);
        $col  = new Collection([rcs_makeGroup()]);

        $repo->shouldReceive('getAllGroups')->once()->andReturn($col);

        $result = rcs_service($repo)->getAllGroups();

        expect($result)->toBe($col);
    });

});

// ─── getAllModules ─────────────────────────────────────────────────────────────

describe('RatChecklistService — getAllModules', function () {

    it('delega ao repositório e retorna a coleção de módulos', function () {
        $repo = Mockery::mock(RatChecklistRepositoryInterface::class);
        $col  = new Collection([new Module(['name' => 'M1'])]);

        $repo->shouldReceive('getAllModules')->once()->andReturn($col);

        $result = rcs_service($repo)->getAllModules();

        expect($result)->toBe($col);
    });

});

// ─── createItem — grupo existente ─────────────────────────────────────────────

describe('RatChecklistService — createItem com grupo existente', function () {

    it('usa findGroup quando group_id é informado', function () {
        $repo  = Mockery::mock(RatChecklistRepositoryInterface::class);
        $group = rcs_makeGroup(['id' => 5]);
        $item  = rcs_makeItem();

        $data = ['name' => 'n', 'label' => 'L', 'module_id' => 2, 'group_id' => 5];

        $repo->shouldReceive('findGroup')->once()->with(5)->andReturn($group);
        $repo->shouldReceive('createItem')
            ->once()
            ->with([
                'name'      => 'n',
                'label'     => 'L',
                'type'      => 'checkbox',
                'module_id' => 2,
                'group_id'  => 5,
            ])
            ->andReturn($item);

        $result = rcs_service($repo)->createItem($data);

        expect($result)->toBe($item);
    });

});

// ─── createItem — novo grupo ──────────────────────────────────────────────────

describe('RatChecklistService — createItem com novo grupo', function () {

    it('usa firstOrCreateGroup quando new_group_name é informado e group_id é null', function () {
        $repo  = Mockery::mock(RatChecklistRepositoryInterface::class);
        $group = rcs_makeGroup(['id' => 9, 'name' => 'Novo']);
        $item  = rcs_makeItem();

        $data = ['name' => 'n', 'label' => 'L', 'module_id' => 3, 'group_id' => null, 'new_group_name' => 'Novo'];

        $repo->shouldReceive('firstOrCreateGroup')->once()->with('Novo')->andReturn($group);
        $repo->shouldReceive('createItem')
            ->once()
            ->with([
                'name'      => 'n',
                'label'     => 'L',
                'type'      => 'checkbox',
                'module_id' => 3,
                'group_id'  => 9,
            ])
            ->andReturn($item);

        $result = rcs_service($repo)->createItem($data);

        expect($result)->toBe($item);
    });

    it('lança InvalidArgumentException quando nenhum grupo é informado', function () {
        $repo = Mockery::mock(RatChecklistRepositoryInterface::class);
        $data = ['name' => 'n', 'label' => 'L', 'module_id' => 3];

        expect(fn () => rcs_service($repo)->createItem($data))
            ->toThrow(\InvalidArgumentException::class);
    });

});

// ─── updateItem ───────────────────────────────────────────────────────────────

describe('RatChecklistService — updateItem', function () {

    it('delega ao repositório passando item e dados com group_id resolvido', function () {
        $repo    = Mockery::mock(RatChecklistRepositoryInterface::class);
        $group   = rcs_makeGroup(['id' => 4]);
        $item    = rcs_makeItem();
        $updated = rcs_makeItem(['label' => 'Novo']);

        $data = ['name' => 'n', 'label' => 'Novo', 'module_id' => 1, 'group_id' => 4];

        $repo->shouldReceive('findGroup')->once()->with(4)->andReturn($group);
        $repo->shouldReceive('updateItem')
            ->once()
            ->with($item, [
                'name'      => 'n',
                'label'     => 'Novo',
                'module_id' => 1,
                'group_id'  => 4,
            ])
            ->andReturn($updated);

        $result = rcs_service($repo)->updateItem($item, $data);

        expect($result)->toBe($updated);
    });

});

// ─── deleteItem ───────────────────────────────────────────────────────────────

describe('RatChecklistService — deleteItem sem elements vinculados', function () {

    it('delega ao repositório quando não há elements vinculados', function () {
        $repo = Mockery::mock(RatChecklistRepositoryInterface::class);
        $item = rcs_makeItem();

        $repo->shouldReceive('itemHasElements')->once()->with($item)->andReturn(false);
        $repo->shouldReceive('deleteItem')->once()->with($item)->andReturnNull();

        rcs_service($repo)->deleteItem($item);

        expect(true)->toBeTrue();
    });

});

describe('RatChecklistService — deleteItem com elements vinculados', function () {

    it('lança DomainException quando o item tem registros vinculados', function () {
        $repo = Mockery::mock(RatChecklistRepositoryInterface::class);
        $item = rcs_makeItem(['label' => 'Item Bloqueado']);

        $repo->shouldReceive('itemHasElements')->once()->with($item)->andReturn(true);
        $repo->shouldNotReceive('deleteItem');

        expect(fn () => rcs_service($repo)->deleteItem($item))
            ->toThrow(\DomainException::class, 'Item Bloqueado');
    });

});
