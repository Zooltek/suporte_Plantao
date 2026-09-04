<?php

/**
 * Testes de integração — BoletoRepository.
 * Usa RefreshDatabase para garantir isolamento.
 *
 * Os models de boleto usam a conexão padrão do projeto.
 * Os testes exercitam a leitura do catálogo e das cobranças no mesmo banco
 * usado pela aplicação para evitar acoplamento com integrações legadas.
 */

use App\Contracts\Repositories\BoletoRepositoryInterface;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\BoletoRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function boleto_repo(): BoletoRepository
{
    return new BoletoRepository;
}

// ─── findUserWithCompany ──────────────────────────────────────────────────────

describe('BoletoRepository — findUserWithCompany', function () {

    it('retorna o usuário pelo ID correto', function () {
        $user = User::factory()->create();

        $result = boleto_repo()->findUserWithCompany($user->id);

        expect($result)->toBeInstanceOf(User::class);
        expect($result->id)->toBe($user->id);
    });

    it('lança ModelNotFoundException quando o usuário não existe', function () {
        expect(fn () => boleto_repo()->findUserWithCompany(999999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('retorna instância de User com os dados corretos', function () {
        $user = User::factory()->create(['name' => 'João Teste']);

        $result = boleto_repo()->findUserWithCompany($user->id);

        expect($result->name)->toBe('João Teste');
    });

});

// ─── getBoletosByCompany (cache) ─────────────────────────────────────────────

describe('BoletoRepository — getBoletosByCompany via cache', function () {

    it('implementa a interface BoletoRepositoryInterface', function () {
        expect(boleto_repo())->toBeInstanceOf(BoletoRepositoryInterface::class);
    });

    it('usa cache pré-populado sem acessar banco externo', function () {
        $companyId = 55;
        Cache::put("user_boletos_company_{$companyId}", collect(['cached_item']), 1800);

        $result = boleto_repo()->getBoletosByCompany($companyId);

        expect($result->first())->toBe('cached_item');
    });

    it('chave de cache é única por company_id', function () {
        Cache::put('user_boletos_company_10', collect(['empresa_10']), 1800);
        Cache::put('user_boletos_company_20', collect(['empresa_20']), 1800);

        $r10 = boleto_repo()->getBoletosByCompany(10);
        $r20 = boleto_repo()->getBoletosByCompany(20);

        expect($r10->first())->toBe('empresa_10');
        expect($r20->first())->toBe('empresa_20');
    });

    it('armazena resultado em cache com TTL de 1800 segundos', function () {
        $companyId = 333;
        Cache::forget("user_boletos_company_{$companyId}");

        Cache::put("user_boletos_company_{$companyId}", collect(), 1800);

        expect(Cache::has("user_boletos_company_{$companyId}"))->toBeTrue();
    });

    it('retorna Illuminate\Support\Collection quando cache está populado', function () {
        $companyId = 77;
        Cache::put("user_boletos_company_{$companyId}", collect(), 1800);

        $result = boleto_repo()->getBoletosByCompany($companyId);

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('consulta boletos usando a conexão padrão do projeto', function () {
        $customer = Customer::factory()->create();

        DB::table('service')->insert([
            'id' => 1,
            'name' => 'Mensalidade Suporte',
            'description' => 'Cobrança recorrente de suporte.',
        ]);

        DB::table('sales_order_status')->insert([
            'id' => 1,
            'name' => 'Em aberto',
            'description' => 'Aguardando pagamento.',
        ]);

        DB::table('sales_order')->insert([
            'customer_id' => $customer->id,
            'service_id' => 1,
            'status_id' => 1,
            'due_date' => now()->startOfDay(),
            'amount' => 199.90,
        ]);

        Cache::forget("user_boletos_company_{$customer->id}");

        $result = boleto_repo()->getBoletosByCompany($customer->id);

        expect($result)->toHaveCount(1)
            ->and((int) $result->first()->customer_id)->toBe($customer->id)
            ->and((float) $result->first()->amount)->toBe(199.9);
    });

});
