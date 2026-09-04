<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

describe('AdminReportsSeeder — integração com DatabaseSeeder', function () {
    it('popula o resumo diário por problema no seed padrão da aplicação', function () {
        $this->seed(DatabaseSeeder::class);
        actingAsAdmin();

        expect(DB::table('ticketit')->whereNotNull('sub_category_id')->count())
            ->toBeGreaterThan(0);

        $response = $this->get(route('admin.reports.daily-problems'))
            ->assertOk()
            ->assertViewIs('admin.reports.daily-problems');

        expect($response->viewData('data'))->not->toBeEmpty();
    });

    it('distribui dados do resumo por problema entre softwares para o filtro funcionar', function () {
        $this->seed(DatabaseSeeder::class);
        actingAsAdmin();

        $softwareIds = DB::table('ticketit')
            ->join('customers', 'customers.id', '=', 'ticketit.company_id')
            ->where('ticketit.obs', '[seed:admin-reports:daily-problems]')
            ->whereNotNull('customers.software_id')
            ->distinct()
            ->pluck('customers.software_id');

        expect($softwareIds->count())->toBeGreaterThan(1);

        $response = $this->get(route('admin.reports.daily-problems', [
            'software_id' => $softwareIds->first(),
        ]))
            ->assertOk()
            ->assertViewIs('admin.reports.daily-problems');

        expect($response->viewData('data'))->not->toBeEmpty();
    });
});
