<?php

use App\Models\CompanyModuleType;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Setting;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Repositories\HelpdeskRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function hr_status(array $attrs = []): Status
{
    return Status::factory()->create($attrs);
}

function hr_priority(array $attrs = []): Priority
{
    return Priority::factory()->create($attrs);
}

function hr_origin(array $attrs = []): Origin
{
    return Origin::factory()->create($attrs);
}

function hr_ticket_for(Status $status, Priority $priority): void
{
    $user       = User::factory()->agent()->create();
    $categoryId = DB::table('ticketit_categories')->insertGetId([
        'name'  => 'Cat HR',
        'color' => '#000',
    ]);

    DB::table('ticketit')->insert([
        'subject'     => 'Ticket Vinculado',
        'content'     => 'Conteúdo',
        'status_id'   => $status->id,
        'priority_id' => $priority->id,
        'user_id'     => $user->id,
        'agent_id'    => $user->id,
        'category_id' => $categoryId,
        'company_id'  => 1,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
}

// ─── Dashboard ────────────────────────────────────────────────────────────────

describe('HelpdeskRepository — getDashboardMetrics', function () {

    it('retorna as chaves obrigatórias', function () {
        Cache::flush();
        $metrics = (new HelpdeskRepository())->getDashboardMetrics();

        expect($metrics)->toHaveKeys(['total', 'open', 'closed', 'today', 'byStatus', 'topCategories']);
    });

    it('armazena resultado em cache', function () {
        Cache::flush();
        (new HelpdeskRepository())->getDashboardMetrics();

        expect(Cache::has('helpdesk.admin.dashboard_metrics'))->toBeTrue();
    });

    it('forgetDashboardCache remove a chave do cache', function () {
        Cache::put('helpdesk.admin.dashboard_metrics', ['dummy' => true], 60);
        (new HelpdeskRepository())->forgetDashboardCache();

        expect(Cache::has('helpdesk.admin.dashboard_metrics'))->toBeFalse();
    });

});

// ─── Status ───────────────────────────────────────────────────────────────────

describe('HelpdeskRepository — Status', function () {

    it('getAllStatuses retorna ordenado por nome', function () {
        hr_status(['name' => 'Zebra']);
        hr_status(['name' => 'Alpha']);

        $result = (new HelpdeskRepository())->getAllStatuses();

        $names = $result->pluck('name')->toArray();
        expect($names)->toBe(collect($names)->sort()->values()->toArray());
    });

    it('getAllStatuses inclui contagem de tickets (withCount)', function () {
        $status = hr_status(['name' => 'ComCount']);

        $result = (new HelpdeskRepository())->getAllStatuses();
        $found  = $result->firstWhere('id', $status->id);

        expect($found->tickets_count)->toBe(0);
    });

    it('findStatusOrFail retorna o status pelo id', function () {
        $status = hr_status(['name' => 'Encontrado']);

        $found = (new HelpdeskRepository())->findStatusOrFail($status->id);

        expect($found->id)->toBe($status->id);
    });

    it('findStatusOrFail lança ModelNotFoundException', function () {
        expect(fn () => (new HelpdeskRepository())->findStatusOrFail(99999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('createStatus persiste no banco', function () {
        $status = (new HelpdeskRepository())->createStatus([
            'name'  => 'Criado Via Repo',
            'color' => '#aabbcc',
        ]);

        expect($status->name)->toBe('Criado Via Repo');
        $this->assertDatabaseHas('ticketit_statuses', ['name' => 'Criado Via Repo']);
    });

    it('updateStatus persiste alterações', function () {
        $user   = User::factory()->agent()->create();
        test()->actingAs($user, 'admin');

        $status = hr_status(['name' => 'Original']);

        (new HelpdeskRepository())->updateStatus($status, ['name' => 'Alterado', 'color' => '#fff']);

        $this->assertDatabaseHas('ticketit_statuses', ['id' => $status->id, 'name' => 'Alterado']);
    });

    it('deleteStatus remove o registro', function () {
        $status = hr_status();

        (new HelpdeskRepository())->deleteStatus($status);

        $this->assertDatabaseMissing('ticketit_statuses', ['id' => $status->id]);
    });

    it('statusHasTickets retorna true quando há tickets vinculados', function () {
        $status   = hr_status();
        $priority = hr_priority();
        hr_ticket_for($status, $priority);

        $result = (new HelpdeskRepository())->statusHasTickets($status);

        expect($result)->toBeTrue();
    });

    it('statusHasTickets retorna false sem tickets', function () {
        $status = hr_status();

        expect((new HelpdeskRepository())->statusHasTickets($status))->toBeFalse();
    });

});

// ─── Priority ─────────────────────────────────────────────────────────────────

describe('HelpdeskRepository — Priority', function () {

    it('getAllPriorities retorna ordenado por nome com tickets_count', function () {
        hr_priority(['name' => 'Urgente']);
        hr_priority(['name' => 'Baixa']);

        $result = (new HelpdeskRepository())->getAllPriorities();

        $names = $result->pluck('name')->toArray();
        expect($names)->toBe(collect($names)->sort()->values()->toArray());
        expect($result->first()->tickets_count)->toBe(0);
    });

    it('findPriorityOrFail lança ModelNotFoundException', function () {
        expect(fn () => (new HelpdeskRepository())->findPriorityOrFail(99999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('createPriority persiste no banco', function () {
        $priority = (new HelpdeskRepository())->createPriority([
            'name'  => 'Prioridade Repo',
            'color' => '#cc0000',
        ]);

        expect($priority->name)->toBe('Prioridade Repo');
        $this->assertDatabaseHas('ticketit_priorities', ['name' => 'Prioridade Repo']);
    });

    it('updatePriority persiste alterações', function () {
        $user = User::factory()->agent()->create();
        test()->actingAs($user, 'admin');

        $priority = hr_priority(['name' => 'OldName']);
        (new HelpdeskRepository())->updatePriority($priority, ['name' => 'NewName', 'color' => '#000']);

        $this->assertDatabaseHas('ticketit_priorities', ['id' => $priority->id, 'name' => 'NewName']);
    });

    it('deletePriority remove o registro', function () {
        $priority = hr_priority();
        (new HelpdeskRepository())->deletePriority($priority);

        $this->assertDatabaseMissing('ticketit_priorities', ['id' => $priority->id]);
    });

    it('priorityHasTickets retorna true quando há tickets', function () {
        $status   = hr_status();
        $priority = hr_priority();
        hr_ticket_for($status, $priority);

        expect((new HelpdeskRepository())->priorityHasTickets($priority))->toBeTrue();
    });

    it('priorityHasTickets retorna false sem tickets', function () {
        $priority = hr_priority();

        expect((new HelpdeskRepository())->priorityHasTickets($priority))->toBeFalse();
    });

});

// ─── Origin ───────────────────────────────────────────────────────────────────

describe('HelpdeskRepository — Origin', function () {

    it('getAllOrigins retorna ordenado por nome com tickets_count', function () {
        hr_origin(['name' => 'Telefone']);
        hr_origin(['name' => 'Email']);

        $result = (new HelpdeskRepository())->getAllOrigins();

        $names = $result->pluck('name')->toArray();
        expect($names)->toBe(collect($names)->sort()->values()->toArray());
    });

    it('createOrigin persiste no banco', function () {
        $origin = (new HelpdeskRepository())->createOrigin([
            'name'        => 'Chat',
            'description' => 'Canal de chat',
            'status'      => 1,
        ]);

        expect($origin->name)->toBe('Chat');
        $this->assertDatabaseHas('ticketit_origin', ['name' => 'Chat']);
    });

    it('findOriginOrFail lança ModelNotFoundException', function () {
        expect(fn () => (new HelpdeskRepository())->findOriginOrFail(99999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('updateOrigin persiste alterações', function () {
        $user = User::factory()->agent()->create();
        test()->actingAs($user, 'admin');

        $origin = hr_origin(['name' => 'OldOrigin']);
        (new HelpdeskRepository())->updateOrigin($origin, ['name' => 'NewOrigin', 'status' => 1]);

        $this->assertDatabaseHas('ticketit_origin', ['id' => $origin->id, 'name' => 'NewOrigin']);
    });

    it('deleteOrigin remove o registro', function () {
        $origin = hr_origin();
        (new HelpdeskRepository())->deleteOrigin($origin);

        $this->assertDatabaseMissing('ticketit_origin', ['id' => $origin->id]);
    });

    it('originHasTickets retorna true quando há tickets vinculados', function () {
        $status   = hr_status();
        $priority = hr_priority();
        $origin   = hr_origin();

        $user       = User::factory()->agent()->create();
        $categoryId = DB::table('ticketit_categories')->insertGetId(['name' => 'Cat OHT', 'color' => '#000']);

        DB::table('ticketit')->insert([
            'subject' => 'Ticket Com Origin', 'content' => 'c',
            'status_id' => $status->id, 'priority_id' => $priority->id,
            'origin_id' => $origin->id, 'user_id' => $user->id,
            'agent_id' => $user->id, 'category_id' => $categoryId,
            'company_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        expect((new HelpdeskRepository())->originHasTickets($origin))->toBeTrue();
    });

    it('originHasTickets retorna false sem tickets', function () {
        $origin = hr_origin();
        expect((new HelpdeskRepository())->originHasTickets($origin))->toBeFalse();
    });

});

// ─── CompanyModuleType ────────────────────────────────────────────────────────

describe('HelpdeskRepository — CompanyModuleType', function () {

    it('getAllModuleTypes retorna ordenado por sort_order e nome', function () {
        CompanyModuleType::factory()->create(['name' => 'Zebra', 'sort_order' => 2]);
        CompanyModuleType::factory()->create(['name' => 'Alpha', 'sort_order' => 1]);

        $result = (new HelpdeskRepository())->getAllModuleTypes();

        expect($result->first()->name)->toBe('Alpha');
    });

    it('createModuleType persiste no banco', function () {
        $module = (new HelpdeskRepository())->createModuleType([
            'name'       => 'Módulo Repo',
            'slug'       => 'modulo_repo',
            'is_active'  => true,
            'sort_order' => 0,
        ]);

        expect($module->name)->toBe('Módulo Repo')
            ->and($module->slug)->toBe('modulo_repo');
        $this->assertDatabaseHas('company_module_types', ['slug' => 'modulo_repo']);
    });

    it('findModuleTypeOrFail lança ModelNotFoundException', function () {
        expect(fn () => (new HelpdeskRepository())->findModuleTypeOrFail(99999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('updateModuleType persiste alterações', function () {
        $user = User::factory()->agent()->create();
        test()->actingAs($user, 'admin');

        $module = CompanyModuleType::factory()->create(['name' => 'OldMod']);
        (new HelpdeskRepository())->updateModuleType($module, [
            'name' => 'NewMod', 'is_active' => true, 'sort_order' => 5,
        ]);

        $this->assertDatabaseHas('company_module_types', ['id' => $module->id, 'name' => 'NewMod']);
    });

    it('deleteModuleType remove o registro', function () {
        $module = CompanyModuleType::factory()->create();
        (new HelpdeskRepository())->deleteModuleType($module);

        $this->assertDatabaseMissing('company_module_types', ['id' => $module->id]);
    });

});

// ─── Settings ─────────────────────────────────────────────────────────────────

describe('HelpdeskRepository — Settings', function () {

    it('getSettingsSections retorna as três chaves', function () {
        $result = (new HelpdeskRepository())->getSettingsSections();

        expect($result)->toHaveKeys(['statuses', 'priorities', 'settings']);
    });

    it('findOrNewSetting retorna Setting existente', function () {
        Setting::create(['slug' => 'test_slug', 'value' => 'v1', 'default' => 'v1']);

        $setting = (new HelpdeskRepository())->findOrNewSetting('test_slug');

        expect($setting->exists)->toBeTrue()
            ->and($setting->value)->toBe('v1');
    });

    it('findOrNewSetting retorna novo Setting não persistido', function () {
        $setting = (new HelpdeskRepository())->findOrNewSetting('slug_inexistente');

        expect($setting->exists)->toBeFalse();
    });

    it('saveSetting persiste o registro', function () {
        $setting          = new Setting();
        $setting->slug    = 'slug_save_test';
        $setting->value   = 'valor';
        $setting->default = 'valor';

        (new HelpdeskRepository())->saveSetting($setting);

        $this->assertDatabaseHas('ticketit_settings', ['slug' => 'slug_save_test', 'value' => 'valor']);
    });

    it('forgetSettingsCache remove a chave ticketit_settings', function () {
        Cache::put('ticketit_settings', ['dummy' => true], 60);
        (new HelpdeskRepository())->forgetSettingsCache();

        expect(Cache::has('ticketit_settings'))->toBeFalse();
    });

});
