<?php

use App\Models\Company;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\User;
use App\Services\Admin\Tasks\TaskRatContextService;

describe('TaskRatContextService', function () {

    it('serializa os RATs recentes com contexto técnico e links úteis', function () {
        $company = Company::factory()->create();
        $module = Module::factory()->create(['name' => 'Contábil', 'project' => 'EasyMaster']);
        $agent = User::factory()->agent()->create();
        $record = Record::factory()->create([
            'customer_id' => $company->id,
            'module_id' => $module->id,
            'agent_id' => $agent->id,
            'status' => 1,
            'release' => '2026.03',
            'version' => '12.4.1',
            'obs' => 'Apontamento técnico',
        ]);

        $payload = app(TaskRatContextService::class)->getSerializedRecentRecords($company->id);

        expect($payload)->toHaveCount(1)
            ->and($payload[0]['id'])->toBe((string) $record->id)
            ->and($payload[0]['moduleName'])->toBe('Contábil')
            ->and($payload[0]['projectName'])->toBe('EasyMaster')
            ->and($payload[0]['statusLabel'])->toBe('Ativo')
            ->and($payload[0]['release'])->toBe('2026.03')
            ->and($payload[0]['version'])->toBe('12.4.1')
            ->and($payload[0]['notes'])->toBe('Apontamento técnico')
            ->and($payload[0]['scheduleUrl'])->not->toBeNull()
            ->and($payload[0]['printUrl'])->not->toBeNull();
    });

    it('valida se os RATs pertencem ao cliente, projeto e módulo técnico esperados', function () {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $agent = User::factory()->agent()->create();
        $erpModule = Module::factory()->create(['name' => 'Financeiro', 'project' => 'ERP']);
        $fiscalModule = Module::factory()->create(['name' => 'Fiscal', 'project' => 'Fiscal']);
        $erpRecord = Record::factory()->create([
            'customer_id' => $company->id,
            'module_id' => $erpModule->id,
            'agent_id' => $agent->id,
        ]);
        $foreignCustomerRecord = Record::factory()->create([
            'customer_id' => $otherCompany->id,
            'module_id' => $erpModule->id,
            'agent_id' => $agent->id,
        ]);
        $foreignProjectRecord = Record::factory()->create([
            'customer_id' => $company->id,
            'module_id' => $fiscalModule->id,
            'agent_id' => $agent->id,
        ]);

        $service = app(TaskRatContextService::class);

        expect($service->recordsBelongToCustomer([$erpRecord->id], $company->id))->toBeTrue()
            ->and($service->recordsBelongToCustomer([$foreignCustomerRecord->id], $company->id))->toBeFalse()
            ->and($service->recordsBelongToProject([$erpRecord->id], 'ERP'))->toBeTrue()
            ->and($service->recordsBelongToProject([$foreignProjectRecord->id], 'ERP'))->toBeFalse()
            ->and($service->recordsBelongToScheduleModule([$erpRecord->id], $erpModule->id))->toBeTrue()
            ->and($service->recordsBelongToScheduleModule([$foreignProjectRecord->id], $erpModule->id))->toBeFalse();
    });
});
