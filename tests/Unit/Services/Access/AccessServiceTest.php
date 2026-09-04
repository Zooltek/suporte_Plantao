<?php

use App\Models\Department;
use App\Models\User;
use App\Services\Access\AccessService;

/**
 * Garante que o departamento seedado "CRM/Comercial" exista marcado como
 * is_crm/is_feedback. Os testes abaixo dependem dessa flag para validar
 * as regras de acesso (não dependem mais de ID fixo).
 *
 * Usa DB::table para forçar o ID 3 — `id` não é fillable no model Department
 * e o updateOrCreate via Eloquent ignoraria o id explícito.
 */
function ensureCrmDepartment(int $id = 3): Department
{
    $dept = Department::find($id);

    if (! $dept) {
        \Illuminate\Support\Facades\DB::table('user_department')->insert([
            'id' => $id,
            'name' => 'CRM / Comercial',
            'is_default' => false,
            'is_crm' => true,
            'is_feedback' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Department::findOrFail($id);
    }

    $dept->is_crm = true;
    $dept->is_feedback = true;
    $dept->save();

    return $dept;
}

/*
|--------------------------------------------------------------------------
| isAdmin / isAgent / hasStaffAccess
|--------------------------------------------------------------------------
*/

it('isAdmin retorna true quando ticketit_admin = true', function () {
    $service = new AccessService;
    $user = User::factory()->admin()->create();

    expect($service->isAdmin($user))->toBeTrue();
});

it('isAdmin retorna false quando ticketit_admin = false', function () {
    $service = new AccessService;
    $user = User::factory()->create(['ticketit_admin' => false]);

    expect($service->isAdmin($user))->toBeFalse();
});

it('isAdmin retorna false para null', function () {
    expect((new AccessService)->isAdmin(null))->toBeFalse();
});

it('isAgent retorna true quando ticketit_agent = true', function () {
    $service = new AccessService;
    $user = User::factory()->agent()->create();

    expect($service->isAgent($user))->toBeTrue();
});

it('isAgent retorna false quando ticketit_agent = false', function () {
    $service = new AccessService;
    $user = User::factory()->create(['ticketit_agent' => false]);

    expect($service->isAgent($user))->toBeFalse();
});

it('isAgent retorna false para null', function () {
    expect((new AccessService)->isAgent(null))->toBeFalse();
});

it('hasStaffAccess retorna true para admin', function () {
    $service = new AccessService;
    $user = User::factory()->admin()->create();

    expect($service->hasStaffAccess($user))->toBeTrue();
});

it('hasStaffAccess retorna true para agente', function () {
    $service = new AccessService;
    $user = User::factory()->agent()->create();

    expect($service->hasStaffAccess($user))->toBeTrue();
});

it('hasStaffAccess retorna false para usuário comum', function () {
    $service = new AccessService;
    $user = User::factory()->create(['ticketit_admin' => false, 'ticketit_agent' => false]);

    expect($service->hasStaffAccess($user))->toBeFalse();
});

it('hasStaffAccess retorna false para null', function () {
    expect((new AccessService)->hasStaffAccess(null))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| canAccessAdmin / canAccessAgent
|--------------------------------------------------------------------------
*/

it('canAccessAdmin retorna true para ticketit_admin', function () {
    $service = new AccessService;
    $user = User::factory()->admin()->create();

    expect($service->canAccessAdmin($user))->toBeTrue();
});

it('canAccessAdmin retorna false para agente sem ticketit_admin', function () {
    $service = new AccessService;
    $user = User::factory()->agent()->create();

    expect($service->canAccessAdmin($user))->toBeFalse();
});

it('canAccessAgent retorna true para qualquer staff', function () {
    $service = new AccessService;

    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    expect($service->canAccessAgent($admin))->toBeTrue()
        ->and($service->canAccessAgent($agent))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| hasRole
|--------------------------------------------------------------------------
*/

it('hasRole admin retorna true para ticketit_admin', function () {
    $user = User::factory()->admin()->create();
    expect((new AccessService)->hasRole($user, 'admin'))->toBeTrue();
});

it('hasRole agent retorna true para ticketit_agent', function () {
    $user = User::factory()->agent()->create();
    expect((new AccessService)->hasRole($user, 'agent'))->toBeTrue();
});

it('hasRole staff retorna true para admin ou agent', function () {
    $service = new AccessService;
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    expect($service->hasRole($admin, 'staff'))->toBeTrue()
        ->and($service->hasRole($agent, 'staff'))->toBeTrue();
});

it('hasRole retorna false para role inválida', function () {
    $user = User::factory()->create();
    expect((new AccessService)->hasRole($user, 'superadmin'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Rotas e permissões auxiliares
|--------------------------------------------------------------------------
*/

it('canAccessFeedback retorna true para admin ou departamento de feedback', function () {
    $service = new AccessService;
    $admin = User::factory()->admin()->create();
    ensureCrmDepartment();
    $feedback = User::factory()->create(['department_id' => 3]);

    expect($service->canAccessFeedback($admin))->toBeTrue()
        ->and($service->canAccessFeedback($feedback))->toBeTrue();
});

it('dashboardRouteForUser resolve rota por e-mail ou departamento', function () {
    $service = new AccessService;
    $adminEmail = User::factory()->create(['email' => 'admin@teste.com']);
    $agentEmail = User::factory()->create(['email' => 'agente@teste.com']);
    $customerMail = User::factory()->create(['email' => 'cliente@teste.com']);
    ensureCrmDepartment();
    // Usuário apenas do Comercial (sem ticketit_agent): mantém crm.index
    $crmDeptOnly = User::factory()->create([
        'department_id' => 3,
        'ticketit_agent' => false,
        'ticketit_admin' => false,
    ]);

    expect($service->dashboardRouteForUser($adminEmail))->toBe('admin.dashboard')
        ->and($service->dashboardRouteForUser($agentEmail))->toBe('agent.index')
        ->and($service->dashboardRouteForUser($customerMail))->toBe('customer.dashboard')
        ->and($service->dashboardRouteForUser($crmDeptOnly))->toBe('crm.index');
});

it('dashboardRouteForUser envia usuário do Comercial com ticketit_agent para agent.index', function () {
    $service = new AccessService;
    ensureCrmDepartment();
    $crmAgent = User::factory()->agent()->create(['department_id' => 3]);

    expect($service->dashboardRouteForUser($crmAgent))->toBe('agent.index');
});

it('helpdeskIndexRouteForUser prioriza staff sobre departamento Comercial', function () {
    $service = new AccessService;
    ensureCrmDepartment();
    $crmOnly = User::factory()->create([
        'department_id' => 3,
        'ticketit_agent' => false,
        'ticketit_admin' => false,
    ]);
    $crmAgent = User::factory()->agent()->create(['department_id' => 3]);
    $agent = User::factory()->agent()->create();
    $commonDepartment = Department::factory()->create([
        'is_crm' => false,
        'is_feedback' => false,
    ]);
    $common = User::factory()->create([
        'department_id' => $commonDepartment->id,
        'ticketit_admin' => false,
        'ticketit_agent' => false,
    ]);

    expect($service->helpdeskIndexRouteForUser($crmOnly))->toBe('crm.index')
        ->and($service->helpdeskIndexRouteForUser($crmAgent))->toBe('agent.index')
        ->and($service->helpdeskIndexRouteForUser($agent))->toBe('agent.index')
        ->and($service->helpdeskIndexRouteForUser($common))->toBe('portal.home');
});

it('canAccessDeveloperArea libera apenas desenvolvedor que não é agente', function () {
    $service = new AccessService;
    $dev = User::factory()->create(['email' => 'cassianogf@gmail.com', 'ticketit_agent' => false]);

    expect($service->canAccessDeveloperArea($dev))->toBeTrue();

    // Quando o mesmo usuário vira agente, perde o acesso de dev
    $dev->update(['ticketit_agent' => true]);

    expect($service->canAccessDeveloperArea($dev))->toBeFalse();
});

it('isSupportAttendantEmail identifica atendente padrão', function () {
    $service = new AccessService;
    $attendant = User::factory()->create(['email' => 'atendente@consuldatasistemas.com.br']);
    $other = User::factory()->create();

    expect($service->isSupportAttendantEmail($attendant))->toBeTrue()
        ->and($service->isSupportAttendantEmail($other))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| adminLoginRedirectUrl
|--------------------------------------------------------------------------
*/

it('adminLoginRedirectUrl redireciona crm user para crm.index', function () {
    $user = User::factory()->create(['email' => 'crm@system.com', 'ticketit_agent' => true]);
    $url = (new AccessService)->adminLoginRedirectUrl($user);

    expect($url)->toBe(route('crm.index'));
});

it('adminLoginRedirectUrl redireciona agent para agent.index', function () {
    $user = User::factory()->agent()->create();
    $url = (new AccessService)->adminLoginRedirectUrl($user);

    expect($url)->toBe(route('agent.index'));
});

it('adminLoginRedirectUrl redireciona admin para agent.index', function () {
    $user = User::factory()->admin()->create();
    $url = (new AccessService)->adminLoginRedirectUrl($user);

    expect($url)->toBe(route('agent.index'));
});

it('adminLoginRedirectUrl redireciona usuário comum para login', function () {
    $user = User::factory()->create(['ticketit_admin' => false, 'ticketit_agent' => false]);
    $url = (new AccessService)->adminLoginRedirectUrl($user);

    expect($url)->toBe(route('login'));
});

/*
|--------------------------------------------------------------------------
| adminGuardRedirectRoute
|--------------------------------------------------------------------------
*/

it('adminGuardRedirectRoute envia admin para admin.dashboard', function () {
    $admin = User::factory()->admin()->create();
    $route = (new AccessService)->adminGuardRedirectRoute($admin);

    expect($route)->toBe('admin.dashboard');
});

it('adminGuardRedirectRoute envia usuário apenas Comercial (sem agent) para crm.index', function () {
    ensureCrmDepartment();
    $crm = User::factory()->create([
        'department_id' => 3,
        'ticketit_agent' => false,
        'ticketit_admin' => false,
    ]);

    $route = (new AccessService)->adminGuardRedirectRoute($crm);

    expect($route)->toBe('crm.index');
});

it('adminGuardRedirectRoute envia usuário Comercial com ticketit_agent para agent.index', function () {
    ensureCrmDepartment();
    $crmAgent = User::factory()->agent()->create(['department_id' => 3]);

    $route = (new AccessService)->adminGuardRedirectRoute($crmAgent);

    expect($route)->toBe('agent.index');
});

it('adminGuardRedirectRoute envia agente para agent.index', function () {
    $agent = User::factory()->agent()->create();
    $route = (new AccessService)->adminGuardRedirectRoute($agent);

    expect($route)->toBe('agent.index');
});

/*
|--------------------------------------------------------------------------
| Flags booleanas de e-mail
|--------------------------------------------------------------------------
*/

it('isCrmEmailUser identifica e-mail crm@system.com', function () {
    $service = new AccessService;
    $crm = User::factory()->create(['email' => 'crm@system.com']);
    $otro = User::factory()->create();

    expect($service->isCrmEmailUser($crm))->toBeTrue()
        ->and($service->isCrmEmailUser($otro))->toBeFalse();
});

it('isDeveloperEmail identifica e-mail do desenvolvedor', function () {
    $service = new AccessService;
    $dev = User::factory()->create(['email' => 'cassianogf@gmail.com']);
    $other = User::factory()->create();

    expect($service->isDeveloperEmail($dev))->toBeTrue()
        ->and($service->isDeveloperEmail($other))->toBeFalse();
});
