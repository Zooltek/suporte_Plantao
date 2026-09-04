<?php

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Report\DepartmentReportService;

function drt_setup(): array
{
    $admin = User::factory()->admin()->create();
    $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);

    $statusOpen = Status::factory()->create(['name' => 'Aberto QA']);
    $statusTerminal = Status::factory()->terminal()->create(['name' => 'Resolvido QA']);

    $deptA = Department::factory()->create(['name' => 'Dept A Report']);
    $deptB = Department::factory()->create(['name' => 'Dept B Report']);

    $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    CategoryDescription::factory()->create(['category_id' => $cat->category_id, 'name' => 'Categoria QA']);
    $sub = Category::factory()->create(['parent_id' => $cat->category_id, 'priority' => 'low']);

    $baseAttrs = [
        'author_id' => $admin->id,
        'user_id' => $admin->id,
        'company_id' => $company->id,
        'category_id' => $cat->category_id,
        'sub_category_id' => $sub->category_id,
    ];

    return compact('admin', 'statusOpen', 'statusTerminal', 'deptA', 'deptB', 'baseAttrs');
}

describe('DepartmentReportService', function () {

    it('agrupa contagens por departamento', function () {
        ['baseAttrs' => $baseAttrs, 'statusOpen' => $statusOpen, 'deptA' => $deptA, 'deptB' => $deptB] = drt_setup();

        // 2 abertos no A, 1 no B
        Ticket::factory()->create(array_merge($baseAttrs, ['status_id' => Ticket::STATUS_PENDING_ID, 'department_id' => $deptA->id]));
        Ticket::factory()->create(array_merge($baseAttrs, ['status_id' => Ticket::STATUS_PENDING_ID, 'department_id' => $deptA->id]));
        Ticket::factory()->create(array_merge($baseAttrs, ['status_id' => Ticket::STATUS_PENDING_ID, 'department_id' => $deptB->id]));

        $report = app(DepartmentReportService::class)->buildReport();

        $byDept = collect($report)->keyBy('department_id');
        expect($byDept[$deptA->id]['total'])->toBe(2)
            ->and($byDept[$deptA->id]['open'])->toBe(2)
            ->and($byDept[$deptB->id]['total'])->toBe(1);
    });

    it('conta resolvidos via is_terminal do status', function () {
        ['baseAttrs' => $baseAttrs, 'statusTerminal' => $statusTerminal, 'deptA' => $deptA] = drt_setup();

        Ticket::factory()->create(array_merge($baseAttrs, [
            'status_id' => $statusTerminal->id,
            'department_id' => $deptA->id,
            'completed_at' => now(),
        ]));

        $report = app(DepartmentReportService::class)->buildReport();
        $row = collect($report)->firstWhere('department_id', $deptA->id);

        expect($row['resolved'])->toBe(1)->and($row['open'])->toBe(0);
    });

    it('calcula tempo médio de atendimento apenas para resolvidos', function () {
        ['baseAttrs' => $baseAttrs, 'statusTerminal' => $statusTerminal, 'deptA' => $deptA] = drt_setup();

        $created = now()->subHours(4);
        Ticket::factory()->create(array_merge($baseAttrs, [
            'status_id' => $statusTerminal->id,
            'department_id' => $deptA->id,
            'created_at' => $created,
            'completed_at' => $created->copy()->addHours(2),
        ]));

        $report = app(DepartmentReportService::class)->buildReport();
        $row = collect($report)->firstWhere('department_id', $deptA->id);

        // 2 horas = 120 minutos (com margem de arredondamento)
        expect($row['avg_resolution_minutes'])->toBeGreaterThanOrEqual(115)
            ->and($row['avg_resolution_minutes'])->toBeLessThanOrEqual(125);
    });

    it('respeita filtro de período', function () {
        ['baseAttrs' => $baseAttrs, 'deptA' => $deptA] = drt_setup();

        Ticket::factory()->create(array_merge($baseAttrs, [
            'status_id' => Ticket::STATUS_PENDING_ID,
            'department_id' => $deptA->id,
            'created_at' => now()->subDays(60),
        ]));

        $report = app(DepartmentReportService::class)
            ->buildReport(now()->subDays(7), now());

        expect($report->firstWhere('department_id', $deptA->id))->toBeNull();
    });

    it('agrupa tickets sem departamento como "Sem departamento"', function () {
        ['baseAttrs' => $baseAttrs] = drt_setup();

        Ticket::factory()->create(array_merge($baseAttrs, [
            'status_id' => Ticket::STATUS_PENDING_ID,
            'department_id' => null,
        ]));

        $report = app(DepartmentReportService::class)->buildReport();
        $row = collect($report)->firstWhere('department_id', null);

        expect($row)->not->toBeNull()
            ->and($row['department_name'])->toContain('Sem departamento');
    });

});

describe('Rota agent.report.by-department — autorização', function () {

    it('admin tem acesso ao relatório', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('agent.report.by-department'))
            ->assertOk()
            ->assertViewIs('agent.reports.by-department');
    });

    it('agente comum recebe 403', function () {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent, 'admin')
            ->get(route('agent.report.by-department'))
            ->assertForbidden();
    });

});
