<?php

/**
 * Testes UNITÁRIOS do ScheduleService — Repository mockado via interface.
 *
 * Testam exclusivamente as regras de negócio (DomainExceptions, lógica de estado)
 * sem tocar o banco de dados.
 */

use App\Contracts\Repositories\ScheduleRepositoryInterface;
use App\Contracts\Repositories\ScheduleTypeRepositoryInterface;
use App\Models\Schedule;
use App\Services\Agent\ScheduleService;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ss_service(MockInterface $repo, ?MockInterface $typeRepo = null): ScheduleService
{
    return new ScheduleService(
        $repo,
        $typeRepo ?? Mockery::mock(ScheduleTypeRepositoryInterface::class),
    );
}

function ss_repo(): MockInterface
{
    return Mockery::mock(ScheduleRepositoryInterface::class);
}

function ss_schedule(array $attrs = []): Schedule
{
    $schedule = new Schedule;
    $schedule->id = 1;
    $schedule->status = $attrs['status'] ?? 'con';
    $schedule->requires_admin_confirmation = $attrs['requires_admin_confirmation'] ?? false;
    $schedule->exists = $attrs['exists'] ?? true;

    return $schedule;
}

// ─── updateSchedule ───────────────────────────────────────────────────────────

describe('ScheduleService — updateSchedule', function () {

    it('lança DomainException quando o agendamento está finalizado', function () {
        $schedule = ss_schedule(['status' => 'fin']);
        // Mockamos isFinalized retornando true via método real (status=fin)
        // Precisamos garantir que o método isFinalized() exista no modelo
        // Vamos usar um mock parcial do Schedule
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->shouldReceive('isFinalized')->andReturn(true);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);

        $repo = ss_repo();
        // O repo NÃO deve ser chamado
        $repo->shouldNotReceive('hasActiveRecords');
        $repo->shouldNotReceive('save');

        expect(fn () => ss_service($repo)->updateSchedule($scheduleMock, []))
            ->toThrow(DomainException::class);
    });

    it('lança DomainException quando o agendamento está cancelado', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(true);

        $repo = ss_repo();
        $repo->shouldNotReceive('hasActiveRecords');

        expect(fn () => ss_service($repo)->updateSchedule($scheduleMock, []))
            ->toThrow(DomainException::class);
    });

    it('lança DomainException quando há records ativos vinculados', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->id = 99;
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldReceive('hasActiveRecords')->with(99)->andReturn(true);
        $repo->shouldNotReceive('save');

        expect(fn () => ss_service($repo)->updateSchedule($scheduleMock, ['obs' => 'x']))
            ->toThrow(DomainException::class);
    });

    it('agente pode alterar todos os campos do agendamento (não apenas obs)', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->id = 99;
        $scheduleMock->status = 'pen';
        $scheduleMock->exists = true;
        $scheduleMock->requires_admin_confirmation = false;
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);
        $scheduleMock->shouldReceive('needsAdminConfirmation')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldReceive('hasActiveRecords')->with(99)->andReturn(false);
        $repo->shouldReceive('save')->once();
        $repo->shouldReceive('findTicket')->andReturn(null);

        $typeRepo = Mockery::mock(ScheduleTypeRepositoryInterface::class);
        $typeRepo->shouldReceive('findBySlug')->andReturn(null);

        $result = ss_service($repo, $typeRepo)->updateSchedule($scheduleMock, [
            'kind' => Schedule::KIND_MEETING,
            'title' => 'Reunião alterada',
            'obs' => 'Nova obs',
            'date' => '2026-06-20',
            'start_hour' => '14:00',
        ]);

        expect($result->title)->toBe('Reunião alterada')
            ->and($result->obs)->toBe('Nova obs');
    });

});

// ─── deleteSchedule ───────────────────────────────────────────────────────────

describe('ScheduleService — deleteSchedule', function () {

    it('lança DomainException ao tentar excluir agendamento finalizado', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->shouldReceive('isFinalized')->andReturn(true);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldNotReceive('save');

        expect(fn () => ss_service($repo)->deleteSchedule($scheduleMock))
            ->toThrow(DomainException::class);
    });

    it('lança DomainException ao tentar excluir com records ativos', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->id = 77;
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldReceive('hasActiveRecords')->with(77)->andReturn(true);
        $repo->shouldNotReceive('save');

        expect(fn () => ss_service($repo)->deleteSchedule($scheduleMock))
            ->toThrow(DomainException::class);
    });

    it('muda status para del e chama save quando exclusão é válida', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->id = 55;
        $scheduleMock->status = 'con';
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldReceive('hasActiveRecords')->with(55)->andReturn(false);
        $repo->shouldReceive('save')->once();

        ss_service($repo)->deleteSchedule($scheduleMock);

        expect($scheduleMock->status)->toBe('del');
    });

});

// ─── confirmSchedule ──────────────────────────────────────────────────────────

describe('ScheduleService — confirmSchedule', function () {

    it('lança DomainException se o agendamento não aguarda confirmação', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);
        $scheduleMock->shouldReceive('needsAdminConfirmation')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldNotReceive('save');

        expect(fn () => ss_service($repo)->confirmSchedule($scheduleMock))
            ->toThrow(DomainException::class);
    });

    it('define status como con e requires_admin_confirmation como false ao confirmar', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->status = 'sch';
        $scheduleMock->requires_admin_confirmation = true;
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);
        $scheduleMock->shouldReceive('needsAdminConfirmation')->andReturn(true);

        $repo = ss_repo();
        $repo->shouldReceive('save')->once();

        $result = ss_service($repo)->confirmSchedule($scheduleMock);

        expect($result->status)->toBe('con')
            ->and($result->requires_admin_confirmation)->toBeFalse();
    });

});

// ─── finalizeSchedule ────────────────────────────────────────────────────────

describe('ScheduleService — finalizeSchedule', function () {

    it('lança DomainException quando o agendamento ainda aguarda confirmação do admin', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->id = 81;
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);
        $scheduleMock->shouldReceive('needsAdminConfirmation')->andReturn(true);

        $repo = ss_repo();
        $repo->shouldNotReceive('hasActiveRecords');
        $repo->shouldNotReceive('save');

        expect(fn () => ss_service($repo)->finalizeSchedule($scheduleMock))
            ->toThrow(DomainException::class);
    });

    it('lança DomainException quando o agendamento não possui RAT ativo', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->id = 91;
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);
        $scheduleMock->shouldReceive('needsAdminConfirmation')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldReceive('hasActiveRecords')->with(91)->andReturn(false);
        $repo->shouldNotReceive('save');

        expect(fn () => ss_service($repo)->finalizeSchedule($scheduleMock))
            ->toThrow(DomainException::class);
    });

    it('define status como fin quando a finalização é válida', function () {
        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->id = 101;
        $scheduleMock->status = 'con';
        $scheduleMock->requires_admin_confirmation = false;
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);
        $scheduleMock->shouldReceive('needsAdminConfirmation')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldReceive('hasActiveRecords')->with(101)->andReturn(true);
        $repo->shouldReceive('save')->once();

        $result = ss_service($repo)->finalizeSchedule($scheduleMock);

        expect($result->status)->toBe('fin')
            ->and($result->requires_admin_confirmation)->toBeFalse();
    });

});

// ─── updateSchedule — tipo sem cliente ───────────────────────────────────────

describe('ScheduleService — updateSchedule com tipo sem cliente', function () {

    function ss_type(string $slug, string $label, bool $requiresCustomer): \App\Models\ScheduleType
    {
        $type = new \App\Models\ScheduleType([
            'slug' => $slug,
            'label' => $label,
            'requires_customer' => $requiresCustomer,
        ]);
        $type->id = 7;

        return $type;
    }

    it('limpa cliente, módulo e contato ao voltar para um tipo que não exige cliente', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->id = 60;
        $scheduleMock->status = 'con';
        $scheduleMock->exists = true;
        $scheduleMock->customer_id = 10;
        $scheduleMock->module_id = 3;
        $scheduleMock->contact = 'Fulano';
        $scheduleMock->requires_admin_confirmation = false;
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);
        $scheduleMock->shouldReceive('needsAdminConfirmation')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldReceive('hasActiveRecords')->with(60)->andReturn(false);
        $repo->shouldReceive('save')->once();

        $typeRepo = Mockery::mock(ScheduleTypeRepositoryInterface::class);
        $typeRepo->shouldReceive('findBySlug')->with('meeting')->andReturn(ss_type('meeting', 'Reunião', false));

        // Simula o form ainda enviando o cliente antigo (select oculto)
        $result = ss_service($repo, $typeRepo)->updateSchedule($scheduleMock, [
            'kind' => 'meeting',
            'title' => 'Reunião interna',
            'customer_id' => 10,
            'module_id' => 3,
            'contact' => 'Fulano',
            'date' => '2026-06-10',
            'start_hour' => '09:00',
        ]);

        expect($result->customer_id)->toBeNull()
            ->and($result->module_id)->toBeNull()
            ->and($result->contact)->toBeNull();
    });

    it('mantém o cliente quando o tipo exige cliente', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $scheduleMock = Mockery::mock(Schedule::class)->makePartial();
        $scheduleMock->id = 61;
        $scheduleMock->status = 'con';
        $scheduleMock->exists = true;
        $scheduleMock->requires_admin_confirmation = false;
        $scheduleMock->shouldReceive('isFinalized')->andReturn(false);
        $scheduleMock->shouldReceive('isCancelled')->andReturn(false);
        $scheduleMock->shouldReceive('needsAdminConfirmation')->andReturn(false);

        $repo = ss_repo();
        $repo->shouldReceive('hasActiveRecords')->with(61)->andReturn(false);
        $repo->shouldReceive('save')->once();

        $typeRepo = Mockery::mock(ScheduleTypeRepositoryInterface::class);
        $typeRepo->shouldReceive('findBySlug')->with('implementation')->andReturn(ss_type('implementation', 'Implantação', true));

        $result = ss_service($repo, $typeRepo)->updateSchedule($scheduleMock, [
            'kind' => 'implementation',
            'title' => 'Implantação ERP',
            'customer_id' => 10,
            'module_id' => 3,
            'contact' => 'Fulano',
            'date' => '2026-06-10',
            'start_hour' => '09:00',
        ]);

        expect($result->customer_id)->toBe(10)
            ->and($result->module_id)->toBe(3)
            ->and($result->contact)->toBe('Fulano');
    });

});
