<?php

use App\Jobs\Agent\Schedule\SyncRecord;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// ─── Helper ──────────────────────────────────────────────────────────────────

function syncTestRecord(array $overrides = []): Record
{
    $company  = Company::factory()->create();
    $agent    = User::factory()->agent()->create();
    $module   = Module::firstOrCreate(['name' => 'Vendas RC'], ['project' => 'EasyMaster']);

    $schedule = Schedule::factory()->create([
        'customer_id' => $company->id,
        'agent_id'    => $agent->id,
        'module_id'   => $module->id,
        'status'      => 'con',
    ]);

    $recordId = DB::table('schedule_record')->insertGetId(array_merge([
        'schedule_id' => $schedule->id,
        'module_id'   => $module->id,
        'customer_id' => $company->id,
        'agent_id'    => $agent->id,
        'status'      => 1,
        'contact'     => 'Teste',
        'start'       => now()->subHours(3),
        'end'         => now()->subHours(1),
        'created_at'  => now(),
        'updated_at'  => now(),
    ], $overrides));

    return Record::with(['schedule.customer', 'customer'])->find($recordId);
}

// ─── handle() ────────────────────────────────────────────────────────────────

describe('SyncRecord — handle()', function () {

    it('não faz requisição quando legacy_id já está preenchido', function () {
        Http::fake();

        $record = syncTestRecord(['legacy_id' => 999]);

        (new SyncRecord($record))->handle();

        Http::assertNothingSent();
    });

    it('loga erro e retorna quando não há cliente associado ao record', function () {
        Http::fake();
        Log::spy();

        $record = syncTestRecord();
        $record->setRelation('customer', null);
        $record->setRelation('schedule', null);

        (new SyncRecord($record))->handle();

        Http::assertNothingSent();
        Log::shouldHaveReceived('error')->once();
    });

    it('envia POST para a API externa com os dados do record', function () {
        Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

        $record = syncTestRecord();

        (new SyncRecord($record))->handle();

        Http::assertSent(fn ($request) =>
            str_contains($request->url(), 'sistemaplenus.com.br') &&
            $request->method() === 'POST'
        );
    });

    it('POST inclui os campos obrigatórios do record', function () {
        Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

        $record = syncTestRecord();

        (new SyncRecord($record))->handle();

        Http::assertSent(fn ($request) =>
            isset($request->data()['legacy_id']) &&
            isset($request->data()['agent_id']) &&
            isset($request->data()['module_id'])
        );
    });

    it('lança exceção para acionar retry quando a API retorna erro 5xx', function () {
        Http::fake(['*' => Http::response('Internal Server Error', 500)]);

        $record = syncTestRecord();

        expect(fn () => (new SyncRecord($record))->handle())
            ->toThrow(\Illuminate\Http\Client\RequestException::class);
    });

    it('loga sucesso após envio bem-sucedido', function () {
        Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);
        Log::spy();

        $record = syncTestRecord();
        (new SyncRecord($record))->handle();

        Log::shouldHaveReceived('info')->once();
    });

});

// ─── failed() ────────────────────────────────────────────────────────────────

describe('SyncRecord — failed()', function () {

    it('loga mensagem crítica ao falhar permanentemente', function () {
        Log::spy();

        $record    = syncTestRecord();
        $job       = new SyncRecord($record);
        $exception = new \RuntimeException('Conexão recusada após 5 tentativas');

        $job->failed($exception);

        Log::shouldHaveReceived('critical')->once();
    });

});

// ─── configurações do job ─────────────────────────────────────────────────────

describe('SyncRecord — configurações', function () {

    it('tem tries = 5', function () {
        $record = syncTestRecord();
        expect((new SyncRecord($record))->tries)->toBe(5);
    });

    it('tem backoff = 60 segundos', function () {
        $record = syncTestRecord();
        expect((new SyncRecord($record))->backoff)->toBe(60);
    });

    it('implementa ShouldQueue', function () {
        $record = syncTestRecord();
        expect(new SyncRecord($record))
            ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    });

});
