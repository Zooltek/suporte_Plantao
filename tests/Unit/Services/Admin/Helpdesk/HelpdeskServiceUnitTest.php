<?php

/**
 * Testes UNITÁRIOS do HelpdeskService — Repository mockado via interface.
 * Testam exclusivamente as regras de negócio: defaults, DomainException, slug.
 */

use App\Contracts\Repositories\HelpdeskRepositoryInterface;
use App\Models\CompanyModuleType;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Setting;
use App\Models\Ticket\Status;
use App\Services\Admin\Helpdesk\HelpdeskService;
use Mockery\MockInterface;

// ─── Factory helper ───────────────────────────────────────────────────────────

function hs_service(MockInterface $repo): HelpdeskService
{
    return new HelpdeskService($repo);
}

function hs_repo(): MockInterface
{
    return Mockery::mock(HelpdeskRepositoryInterface::class);
}

// ─── Status — Regras de Negócio ───────────────────────────────────────────────

describe('HelpdeskService (unit) — createStatus', function () {

    it('usa cor padrão #6366f1 quando color não é informada', function () {
        $repo = hs_repo();
        $repo->shouldReceive('createStatus')
            ->once()
            ->with(Mockery::on(fn ($a) => $a['color'] === '#6366f1'))
            ->andReturn(new Status(['name' => 'X', 'color' => '#6366f1']));
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->createStatus(['name' => 'X']);
    });

    it('usa cor informada quando presente', function () {
        $repo = hs_repo();
        $repo->shouldReceive('createStatus')
            ->once()
            ->with(Mockery::on(fn ($a) => $a['color'] === '#ff0000'))
            ->andReturn(new Status(['name' => 'X', 'color' => '#ff0000']));
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->createStatus(['name' => 'X', 'color' => '#ff0000']);
    });

    it('invalida o cache do dashboard após criação', function () {
        $repo = hs_repo();
        $repo->shouldReceive('createStatus')->andReturn(new Status());
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->createStatus(['name' => 'X']);
    });

});

describe('HelpdeskService (unit) — updateStatus', function () {

    it('mantém a cor atual quando color não é informada', function () {
        $existing = Mockery::mock(Status::class)->makePartial();
        $existing->id    = 1;
        $existing->color = '#original';
        $existing->shouldReceive('refresh')->andReturnSelf();

        $repo = hs_repo();
        $repo->shouldReceive('findStatusOrFail')->with(1)->andReturn($existing);
        $repo->shouldReceive('updateStatus')
            ->once()
            ->with($existing, Mockery::on(fn ($a) => $a['color'] === '#original'));
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->updateStatus(1, ['name' => 'Novo']);
    });

    it('invalida o cache do dashboard após atualização', function () {
        $status = Mockery::mock(Status::class)->makePartial();
        $status->id    = 1;
        $status->color = '#000';
        $status->shouldReceive('refresh')->andReturnSelf();

        $repo = hs_repo();
        $repo->shouldReceive('findStatusOrFail')->andReturn($status);
        $repo->shouldReceive('updateStatus');
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->updateStatus(1, ['name' => 'X', 'color' => '#000']);
    });

});

describe('HelpdeskService (unit) — deleteStatus', function () {

    it('lança DomainException quando status tem tickets vinculados', function () {
        $status = new Status();
        $status->id = 1;

        $repo = hs_repo();
        $repo->shouldReceive('findStatusOrFail')->with(1)->andReturn($status);
        $repo->shouldReceive('statusHasTickets')->with($status)->andReturn(true);

        expect(fn () => hs_service($repo)->deleteStatus(1))
            ->toThrow(\DomainException::class, 'chamados vinculados');
    });

    it('invalida o cache e deleta quando não há tickets', function () {
        $status = new Status();
        $status->id = 1;

        $repo = hs_repo();
        $repo->shouldReceive('findStatusOrFail')->andReturn($status);
        $repo->shouldReceive('statusHasTickets')->andReturn(false);
        $repo->shouldReceive('forgetDashboardCache')->once();
        $repo->shouldReceive('deleteStatus')->once()->andReturn(true);

        $result = hs_service($repo)->deleteStatus(1);

        expect($result)->toBeTrue();
    });

});

// ─── Priority — Regras de Negócio ─────────────────────────────────────────────

describe('HelpdeskService (unit) — createPriority', function () {

    it('usa cor padrão quando color não é informada', function () {
        $repo = hs_repo();
        $repo->shouldReceive('createPriority')
            ->once()
            ->with(Mockery::on(fn ($a) => $a['color'] === '#6366f1'))
            ->andReturn(new Priority(['name' => 'P', 'color' => '#6366f1']));
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->createPriority(['name' => 'P']);
    });

    it('invalida o cache após criação', function () {
        $repo = hs_repo();
        $repo->shouldReceive('createPriority')->andReturn(new Priority());
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->createPriority(['name' => 'P']);
    });

});

describe('HelpdeskService (unit) — deletePriority', function () {

    it('lança DomainException quando prioridade tem tickets', function () {
        $priority = new Priority();
        $priority->id = 2;

        $repo = hs_repo();
        $repo->shouldReceive('findPriorityOrFail')->with(2)->andReturn($priority);
        $repo->shouldReceive('priorityHasTickets')->andReturn(true);

        expect(fn () => hs_service($repo)->deletePriority(2))
            ->toThrow(\DomainException::class, 'prioridade vinculada');
    });

    it('invalida cache e deleta quando não há tickets', function () {
        $priority = new Priority();
        $priority->id = 2;

        $repo = hs_repo();
        $repo->shouldReceive('findPriorityOrFail')->andReturn($priority);
        $repo->shouldReceive('priorityHasTickets')->andReturn(false);
        $repo->shouldReceive('forgetDashboardCache')->once();
        $repo->shouldReceive('deletePriority')->once()->andReturn(true);

        expect(hs_service($repo)->deletePriority(2))->toBeTrue();
    });

});

// ─── Origin — Regras de Negócio ───────────────────────────────────────────────

describe('HelpdeskService (unit) — createOrigin', function () {

    it('usa description=null e status=1 por padrão', function () {
        $repo = hs_repo();
        $repo->shouldReceive('createOrigin')
            ->once()
            ->with(Mockery::on(fn ($a) => $a['description'] === null && $a['status'] === 1))
            ->andReturn(new Origin(['name' => 'Canal']));

        hs_service($repo)->createOrigin(['name' => 'Canal']);
    });

});

describe('HelpdeskService (unit) — deleteOrigin', function () {

    it('lança DomainException quando origem tem tickets', function () {
        $origin = new Origin();
        $origin->id = 5;

        $repo = hs_repo();
        $repo->shouldReceive('findOriginOrFail')->with(5)->andReturn($origin);
        $repo->shouldReceive('originHasTickets')->andReturn(true);

        expect(fn () => hs_service($repo)->deleteOrigin(5))
            ->toThrow(\DomainException::class, 'origem vinculada');
    });

    it('deleta quando não há tickets vinculados', function () {
        $origin = new Origin();
        $origin->id = 5;

        $repo = hs_repo();
        $repo->shouldReceive('findOriginOrFail')->andReturn($origin);
        $repo->shouldReceive('originHasTickets')->andReturn(false);
        $repo->shouldReceive('deleteOrigin')->once()->andReturn(true);

        expect(hs_service($repo)->deleteOrigin(5))->toBeTrue();
    });

});

// ─── CompanyModuleType — Regras de Negócio ────────────────────────────────────

describe('HelpdeskService (unit) — createModuleType', function () {

    it('gera slug automaticamente a partir do nome', function () {
        $repo = hs_repo();
        $repo->shouldReceive('createModuleType')
            ->once()
            ->with(Mockery::on(fn ($a) => $a['slug'] === 'folha_de_pagamento'))
            ->andReturn(new CompanyModuleType(['name' => 'Folha de Pagamento', 'slug' => 'folha_de_pagamento']));

        hs_service($repo)->createModuleType([
            'name'       => 'Folha de Pagamento',
            'is_active'  => true,
            'sort_order' => 0,
        ]);
    });

    it('is_active=false quando não informado', function () {
        $repo = hs_repo();
        $repo->shouldReceive('createModuleType')
            ->once()
            ->with(Mockery::on(fn ($a) => $a['is_active'] === false))
            ->andReturn(new CompanyModuleType());

        hs_service($repo)->createModuleType(['name' => 'Módulo', 'sort_order' => 0]);
    });

    it('sort_order padrão é 0', function () {
        $repo = hs_repo();
        $repo->shouldReceive('createModuleType')
            ->once()
            ->with(Mockery::on(fn ($a) => $a['sort_order'] === 0))
            ->andReturn(new CompanyModuleType());

        hs_service($repo)->createModuleType(['name' => 'Módulo']);
    });

});

// ─── Settings — Regras de Negócio ─────────────────────────────────────────────

describe('HelpdeskService (unit) — updateSetting', function () {

    it('define default igual ao value na primeira criação', function () {
        $setting = new Setting();
        // $setting->exists = false (já é o padrão de new)

        $repo = hs_repo();
        $repo->shouldReceive('findOrNewSetting')->with('chave')->andReturn($setting);
        $repo->shouldReceive('saveSetting')->once();
        $repo->shouldReceive('forgetSettingsCache')->once();
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->updateSetting('chave', 'valor');

        expect($setting->value)->toBe('valor')
            ->and($setting->default)->toBe('valor');
    });

    it('não redefine default em atualização de setting existente', function () {
        $setting           = new Setting();
        $setting->exists   = true;
        $setting->default  = 'original';

        $repo = hs_repo();
        $repo->shouldReceive('findOrNewSetting')->andReturn($setting);
        $repo->shouldReceive('saveSetting')->once();
        $repo->shouldReceive('forgetSettingsCache')->once();
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->updateSetting('chave', 'novo_valor');

        expect($setting->default)->toBe('original');
    });

    it('invalida ambos os caches (settings e dashboard)', function () {
        $setting = new Setting();

        $repo = hs_repo();
        $repo->shouldReceive('findOrNewSetting')->andReturn($setting);
        $repo->shouldReceive('saveSetting');
        $repo->shouldReceive('forgetSettingsCache')->once();
        $repo->shouldReceive('forgetDashboardCache')->once();

        hs_service($repo)->updateSetting('k', 'v');
    });

});
