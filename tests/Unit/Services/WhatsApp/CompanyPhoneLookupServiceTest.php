<?php

use App\Models\Company;
use App\Models\CompanyContact;
use App\Services\WhatsApp\CompanyPhoneLookupService;

describe('CompanyPhoneLookupService::resolve()', function () {
    beforeEach(function () {
        $this->service = app(CompanyPhoneLookupService::class);
    });

    it('prioriza whatsapp_phone para identificação automática', function () {
        $company = Company::factory()->create([
            'whatsapp_phone' => '5527999990000',
            'phone' => '(27) 3333-0000',
        ]);

        $resolved = $this->service->resolve('(27) 99999-0000');

        expect($resolved?->id)->toBe($company->id);
    });

    it('resolve empresa pelo telefone principal formatado', function () {
        $company = Company::factory()->create([
            'phone' => '(27) 99999-0000',
        ]);

        $resolved = $this->service->resolve('5527999990000');

        expect($resolved?->id)->toBe($company->id);
    });

    it('resolve empresa por telefone de contato secundário', function () {
        $company = Company::factory()->create();

        CompanyContact::create([
            'customer_id' => $company->id,
            'name' => 'Financeiro',
            'phone' => '(27) 98888-0000',
            'is_main' => true,
        ]);

        $resolved = $this->service->resolve('5527988880000');

        expect($resolved?->id)->toBe($company->id);
    });

    it('prioriza whatsapp_phone mesmo quando outro cliente coincide no telefone principal', function () {
        $company = Company::factory()->create(['whatsapp_phone' => '5527999990000']);
        Company::factory()->create(['phone' => '(27) 99999-0000']);

        expect($this->service->resolve('5527999990000')?->id)->toBe($company->id);
    });

    it('retorna null quando o mesmo whatsapp_phone está associado a múltiplos clientes', function () {
        Company::factory()->create(['whatsapp_phone' => '5527999990000']);
        Company::factory()->create(['whatsapp_phone' => '5527999990000']);

        expect($this->service->resolve('5527999990000'))->toBeNull();
    });
});
