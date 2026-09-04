<?php

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use App\Models\Software;
use App\Services\Agent\TicketTechnicalContextService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

function ttc_company(int $id, string $softwareName, string $softwareVersion): Company
{
    $company = new Company;
    $company->id = $id;
    $company->software_id = $id;
    $company->setRelation('software', new Software([
        'name' => $softwareName,
        'version' => $softwareVersion,
    ]));

    return $company;
}

describe('TicketTechnicalContextService', function () {

    it('monta contexto técnico isolado por empresa', function () {
        $companyA = ttc_company(10, 'EasyMaster', '01.32.01');
        $companyB = ttc_company(20, 'AmuraWeb', '03.05.00');

        $repository = Mockery::mock(CompanyRepositoryInterface::class);
        $repository->shouldReceive('getLatestTicketTechnicalContexts')
            ->once()
            ->with([10, 20])
            ->andReturn([
                10 => [
                    'version' => '01.30.01',
                    'release' => 'R10',
                    'created_at' => now()->subDay(),
                ],
                20 => [
                    'version' => '03.05.00',
                    'release' => 'WEB-22',
                    'created_at' => now(),
                ],
            ]);

        $service = new TicketTechnicalContextService($repository);
        $context = $service->buildForCompanies(new EloquentCollection([$companyA, $companyB]));

        expect($context['contexts']['10']['suggested_version'])->toBe('01.30.01')
            ->and($context['contexts']['10']['suggested_release'])->toBe('R10')
            ->and($context['contexts']['10']['software_version'])->toBe('01.32.01')
            ->and($context['contexts']['20']['suggested_version'])->toBe('03.05.00')
            ->and($context['contexts']['20']['suggested_release'])->toBe('WEB-22')
            ->and($context['contexts']['20']['software_name'])->toBe('AmuraWeb');
    });

    it('inclui versões de software e legado no catálogo do formulário', function () {
        $company = ttc_company(10, 'EasyMaster', '01.32.01');

        $repository = Mockery::mock(CompanyRepositoryInterface::class);
        $repository->shouldReceive('getLatestTicketTechnicalContexts')
            ->once()
            ->with([10])
            ->andReturn([
                10 => [
                    'version' => '02.10.00',
                    'release' => 'R1',
                    'created_at' => now(),
                ],
            ]);

        $service = new TicketTechnicalContextService($repository);
        $context = $service->buildForCompanies(new EloquentCollection([$company]));

        expect($context['versionCatalog'])->toContain('01.28.01')
            ->and($context['versionCatalog'])->toContain('01.30.01')
            ->and($context['versionCatalog'])->toContain('01.32.01')
            ->and($context['versionCatalog'])->toContain('02.10.00');
    });

    it('normaliza versões legadas e formatadas', function () {
        $repository = Mockery::mock(CompanyRepositoryInterface::class);
        $repository->shouldReceive('getLatestTicketTechnicalContexts')->andReturn([])->byDefault();

        $service = new TicketTechnicalContextService($repository);

        expect($service->normalizeVersion('1'))->toBe('01.28.01')
            ->and($service->normalizeVersion('1.2.3'))->toBe('01.02.03')
            ->and($service->normalizeVersion('02.05.01'))->toBe('02.05.01')
            ->and($service->normalizeVersion('99'))->toBe('')
            ->and($service->normalizeRelease(' R4 '))->toBe('R4');
    });

});
