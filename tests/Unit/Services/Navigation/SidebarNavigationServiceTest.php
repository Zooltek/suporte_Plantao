<?php

use App\Models\Department;
use App\Models\User;
use App\Services\Access\AccessService;
use App\Services\Navigation\SidebarNavigationService;

function makeUserMock(bool $isAdmin = false, bool $isAgent = false, int $departmentId = 1): User
{
    $user = Mockery::mock(User::class)->makePartial();
    $user->ticketit_admin = $isAdmin;
    $user->ticketit_agent = $isAgent;
    $user->department_id = $departmentId;
    $user->email = 'test@example.com';
    $user->shouldReceive('hasRole')->andReturnUsing(function ($roles) use ($isAdmin, $isAgent) {
        $roles = is_array($roles) ? $roles : [$roles];
        if ($isAdmin && in_array('admin', $roles)) {
            return true;
        }
        if ($isAgent && in_array('agent', $roles)) {
            return true;
        }

        return false;
    });
    $user->shouldReceive('hasPermissionTo')->andReturn(false);

    return $user;
}

/*
|--------------------------------------------------------------------------
| Admin — deve ver todos os módulos (exceto o atual)
|--------------------------------------------------------------------------
*/

it('admin vê todos os módulos de navegação no contexto de tasks', function () {
    $user = makeUserMock(isAdmin: true);
    $service = new SidebarNavigationService(new AccessService);

    $items = $service->getNavigationItems($user, 'tasks');
    $modules = array_column($items, 'module');

    expect($modules)->toContain('admin')
        ->toContain('agent')
        ->toContain('tickets')
        ->toContain('crm')
        ->toContain('knowledge')
        ->not->toContain('tasks');
});

it('admin não vê o módulo admin quando já está no contexto admin', function () {
    $user = makeUserMock(isAdmin: true);
    $service = new SidebarNavigationService(new AccessService);

    $items = $service->getNavigationItems($user, 'admin');
    $modules = array_column($items, 'module');

    expect($modules)->not->toContain('admin')
        ->toContain('tasks');
});

/*
|--------------------------------------------------------------------------
| Agent (não admin) — deve ver módulos de staff, mas não admin/knowledge
|--------------------------------------------------------------------------
*/

it('agente vê módulos de staff mas não admin nem knowledge', function () {
    $user = makeUserMock(isAgent: true);
    $service = new SidebarNavigationService(new AccessService);

    $items = $service->getNavigationItems($user, 'tasks');
    $modules = array_column($items, 'module');

    expect($modules)->toContain('agent')
        ->toContain('tickets')
        ->not->toContain('admin')
        ->not->toContain('knowledge');
});

/*
|--------------------------------------------------------------------------
| Usuário CRM — deve ver somente CRM
|--------------------------------------------------------------------------
*/

it('usuário CRM vê painel CRM na navegação de tasks', function () {
    $crmDepartment = Department::factory()->create([
        'is_crm' => true,
        'is_feedback' => true,
    ]);
    $user = User::factory()->create([
        'ticketit_admin' => false,
        'ticketit_agent' => false,
        'department_id' => $crmDepartment->id,
    ]);
    $service = new SidebarNavigationService(new AccessService);

    $items = $service->getNavigationItems($user, 'tasks');
    $modules = array_column($items, 'module');

    expect($modules)->toContain('crm')
        ->not->toContain('admin')
        ->not->toContain('agent')
        ->not->toContain('tickets')
        ->not->toContain('knowledge');
});

/*
|--------------------------------------------------------------------------
| Usuário comum (sem roles) — lista vazia
|--------------------------------------------------------------------------
*/

it('usuário sem roles não vê nenhum item de navegação', function () {
    $user = makeUserMock();
    $service = new SidebarNavigationService(new AccessService);

    $items = $service->getNavigationItems($user, 'tasks');

    expect($items)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Estrutura dos itens — devem conter todas as chaves necessárias
|--------------------------------------------------------------------------
*/

it('cada item retorna as chaves necessárias para renderização', function () {
    $user = makeUserMock(isAdmin: true);
    $service = new SidebarNavigationService(new AccessService);

    $items = $service->getNavigationItems($user, 'tasks');

    foreach ($items as $item) {
        expect($item)->toHaveKeys([
            'label',
            'route',
            'module',
            'hoverBg',
            'hoverText',
            'hoverBorder',
            'iconSvg',
        ]);
        expect($item['label'])->toBeString()->not->toBeEmpty();
        expect($item['route'])->toBeString()->not->toBeEmpty();
        expect($item['iconSvg'])->toContain('<path');
    }
});

/*
|--------------------------------------------------------------------------
| Módulo atual nunca aparece na lista
|--------------------------------------------------------------------------
*/

it('o módulo atual é sempre excluído da navegação', function () {
    $user = makeUserMock(isAdmin: true);
    $service = new SidebarNavigationService(new AccessService);

    $modulesToTest = ['tasks', 'admin', 'agent', 'tickets', 'crm', 'knowledge'];

    foreach ($modulesToTest as $currentModule) {
        $items = $service->getNavigationItems($user, $currentModule);
        $modules = array_column($items, 'module');

        expect($modules)->not->toContain($currentModule);
    }
});
