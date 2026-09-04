<?php
/**
 * Script para popular dados faltantes nas empresas existentes.
 * Execute: docker compose exec suporte12_app php database/scripts/populate_companies.php
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\State;

$stateCount = State::count();
echo "States no banco: {$stateCount}\n";

$cities = [
    'São Paulo', 'Rio de Janeiro', 'Curitiba', 'Florianópolis',
    'Blumenau', 'Joinville', 'Campinas', 'Maringá', 'Londrina',
    'Criciúma', 'Chapecó', 'Lages', 'Itajaí', 'Porto Alegre',
    'Belo Horizonte', 'Salvador', 'Recife', 'Fortaleza',
];

$updated = 0;
$total = Company::count();

foreach (Company::all() as $c) {
    $data = [];

    if (empty($c->phone)) {
        $data['phone'] = '(' . str_pad(rand(11, 99), 2, '0', STR_PAD_LEFT) . ') '
            . rand(3000, 9999) . '-' . rand(1000, 9999);
    }

    if (empty($c->state_id) || $c->state_id < 1) {
        $data['state_id'] = rand(1, max(1, $stateCount));
    }

    if (empty($c->address)) {
        $data['address'] = 'Rua ' . $cities[array_rand($cities)] . ', ' . rand(10, 999);
    }

    if (empty($c->city)) {
        $data['city'] = $cities[array_rand($cities)];
    }

    if (!empty($data)) {
        $c->update($data);
        $updated++;
    }
}

echo "Total: {$total} empresas, Atualizadas: {$updated}\n";
echo "Concluído!\n";
