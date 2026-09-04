<?php

/**
 * Testes UNITÁRIOS — TicketService::quickUpdateStatus() e quickUpdateAgent().
 *
 * Repository mockado via interface: apenas regra de negócio é testada,
 * sem dependência de banco de dados.
 */

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\TicketRepositoryInterface;
use App\Exceptions\TicketBusinessException;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Agent\TicketService;
use App\Services\Agent\TicketTechnicalContextService;
use Mockery\MockInterface;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function qus_service(MockInterface $repo): TicketService
{
    $companyRepo = Mockery::mock(CompanyRepositoryInterface::class);
    $companyRepo->shouldReceive('getLatestTicketTechnicalContexts')->andReturn([])->byDefault();

    return new TicketService($repo, new TicketTechnicalContextService($companyRepo));
}

function qus_repo(): MockInterface
{
    $repo = Mockery::mock(TicketRepositoryInterface::class);
    $repo->shouldReceive('updateTicket')->andReturn(null)->byDefault();
    $repo->shouldReceive('isStatusTerminal')->andReturn(false)->byDefault();
    $repo->shouldReceive('isStatusRequiresSchedule')->andReturn(false)->byDefault();
    $repo->shouldReceive('isStatusRequiresSolution')->andReturn(false)->byDefault();

    return $repo;
}

function qus_ticket(array $attrs = []): Ticket
{
    $ticket = new Ticket;
    $ticket->id = $attrs['id'] ?? 1;
    $ticket->agent_id = $attrs['agent_id'] ?? null;
    $ticket->status_id = $attrs['status_id'] ?? 4;

    return $ticket;
}

// ─── quickUpdateStatus ────────────────────────────────────────────────────────

describe('TicketService — quickUpdateStatus', function () {

    it('persiste o novo status e retorna false para status comum', function () {
        $repo = qus_repo();
        $repo->shouldReceive('isStatusTerminal')->with(2)->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSchedule')->with(2)->andReturn(false);
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(fn ($t, $attrs) => $attrs['status_id'] === 2 && $attrs['completed_at'] === null);

        $result = qus_service($repo)->quickUpdateStatus(qus_ticket(), 2);

        expect($result)->toBeFalse();
    });

    it('retorna true quando o status requer agendamento', function () {
        $repo = qus_repo();
        $repo->shouldReceive('isStatusTerminal')->with(21)->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSchedule')->with(21)->andReturn(true);
        $repo->shouldReceive('updateTicket')->once();

        $result = qus_service($repo)->quickUpdateStatus(qus_ticket(), 21);

        expect($result)->toBeTrue();
    });

    it('bloqueia status terminal e orienta o fluxo de encerramento', function () {
        $repo = qus_repo();
        $repo->shouldReceive('isStatusTerminal')->with(3)->andReturn(true);
        $repo->shouldReceive('updateTicket')->never();

        $ticket = qus_ticket(['agent_id' => 5]);

        expect(fn () => qus_service($repo)->quickUpdateStatus($ticket, 3))
            ->toThrow(TicketBusinessException::class, 'Use o fluxo de fechamento');
    });

    it('status terminal com requiresSchedule=true também deve usar o fluxo de encerramento', function () {
        $repo = qus_repo();
        $repo->shouldReceive('isStatusTerminal')->with(99)->andReturn(true);
        $repo->shouldReceive('updateTicket')->never();

        expect(fn () => qus_service($repo)->quickUpdateStatus(qus_ticket(['agent_id' => 5]), 99))
            ->toThrow(TicketBusinessException::class, 'Use o fluxo de fechamento');
    });

});

// ─── closeTicket ─────────────────────────────────────────────────────────────

describe('TicketService — closeTicket', function () {

    it('encerra o chamado com status terminal e solution quando exigida', function () {
        $repo = qus_repo();
        $repo->shouldReceive('isStatusTerminal')->with(3)->andReturn(true);
        $repo->shouldReceive('isStatusRequiresSchedule')->with(3)->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSolution')->with(3)->andReturn(true);
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(fn ($t, $attrs) => $attrs['status_id'] === 3
                && $attrs['solution'] === 'Solução aplicada'
                && $attrs['completed_at'] !== null
            );

        $ticket = qus_ticket(['agent_id' => 5]);
        qus_service($repo)->closeTicket($ticket, 3, 'Solução aplicada');
    });

    it('limpa solution ao encerrar com status que não exige solução', function () {
        $repo = qus_repo();
        $repo->shouldReceive('isStatusTerminal')->with(5)->andReturn(true);
        $repo->shouldReceive('isStatusRequiresSchedule')->with(5)->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSolution')->with(5)->andReturn(false);
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(fn ($t, $attrs) => $attrs['status_id'] === 5
                && array_key_exists('solution', $attrs)
                && $attrs['solution'] === null
            );

        $ticket = qus_ticket(['agent_id' => 5]);
        qus_service($repo)->closeTicket($ticket, 5, 'Texto legado');
    });

    it('impede encerrar com status não terminal', function () {
        $repo = qus_repo();
        $repo->shouldReceive('isStatusTerminal')->with(2)->andReturn(false);
        $repo->shouldReceive('updateTicket')->never();

        expect(fn () => qus_service($repo)->closeTicket(qus_ticket(['agent_id' => 5]), 2))
            ->toThrow(TicketBusinessException::class, 'status de encerramento válido');
    });

    it('exige solução ao encerrar como Resolvido', function () {
        $repo = qus_repo();
        $repo->shouldReceive('isStatusTerminal')->with(3)->andReturn(true);
        $repo->shouldReceive('isStatusRequiresSchedule')->with(3)->andReturn(false);
        $repo->shouldReceive('isStatusRequiresSolution')->with(3)->andReturn(true);
        $repo->shouldReceive('updateTicket')->never();

        expect(fn () => qus_service($repo)->closeTicket(qus_ticket(['agent_id' => 5]), 3, ''))
            ->toThrow(TicketBusinessException::class, 'Informe a solução aplicada');
    });

    it('exige agente atribuído ao encerrar', function () {
        $repo = qus_repo();
        $repo->shouldReceive('isStatusTerminal')->with(5)->andReturn(true);
        $repo->shouldReceive('isStatusRequiresSchedule')->with(5)->andReturn(false);
        $repo->shouldReceive('updateTicket')->never();

        expect(fn () => qus_service($repo)->closeTicket(qus_ticket(['agent_id' => null]), 5))
            ->toThrow(TicketBusinessException::class, 'precisa ter um agente atribuído');
    });

});

// ─── quickUpdateAgent ─────────────────────────────────────────────────────────

describe('TicketService — quickUpdateAgent', function () {

    it('persiste o novo agente sem alterar o status', function () {
        $repo = qus_repo();
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(fn ($t, $attrs) => $attrs['agent_id'] === 7 && ! array_key_exists('status_id', $attrs));

        qus_service($repo)->quickUpdateAgent(qus_ticket(['status_id' => 2]), 7);
    });

    it('ao remover o agente, retorna o chamado para a fila de pendências', function () {
        $repo = qus_repo();
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(fn ($t, $attrs) => $attrs['agent_id'] === null
                && $attrs['status_id'] === Ticket::STATUS_PENDING_ID
                && $attrs['completed_at'] === null
            );

        qus_service($repo)->quickUpdateAgent(qus_ticket(['agent_id' => 5, 'status_id' => 2]), null);
    });

    it('remove conclusão anterior ao devolver chamado finalizado para a fila', function () {
        $repo = qus_repo();
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(fn ($t, $attrs) => $attrs['agent_id'] === null &&
                $attrs['status_id'] === Ticket::STATUS_PENDING_ID &&
                $attrs['completed_at'] === null
            );

        qus_service($repo)->quickUpdateAgent(qus_ticket(['agent_id' => 5, 'status_id' => 3]), null);
    });

    it('não altera status ao trocar agente em chamado que exige agendamento', function () {
        $repo = qus_repo();
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(fn ($t, $attrs) => $attrs['agent_id'] === 9 && ! array_key_exists('status_id', $attrs));

        qus_service($repo)->quickUpdateAgent(qus_ticket(['agent_id' => 5, 'status_id' => 21]), 9);
    });

});

// ─── captureTicket ────────────────────────────────────────────────────────────

describe('TicketService — captureTicket', function () {

    it('captura chamado sem agente atribuindo o usuário atual', function () {
        $repo = qus_repo();
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(fn ($t, $attrs) => $attrs['agent_id'] === 7 && ! array_key_exists('status_id', $attrs));

        $user = new User;
        $user->id = 7;

        qus_service($repo)->captureTicket(qus_ticket(['agent_id' => null]), $user);
    });

    it('reatribui chamado já capturado por outro agente', function () {
        $repo = qus_repo();
        $repo->shouldReceive('updateTicket')
            ->once()
            ->withArgs(fn ($t, $attrs) => $attrs['agent_id'] === 7 && ! array_key_exists('status_id', $attrs));

        $user = new User;
        $user->id = 7;

        qus_service($repo)->captureTicket(qus_ticket(['agent_id' => 5]), $user);
    });

    it('bloqueia captura redundante quando o chamado já pertence ao usuário', function () {
        $repo = qus_repo();
        $repo->shouldReceive('updateTicket')->never();

        $user = new User;
        $user->id = 7;

        expect(fn () => qus_service($repo)->captureTicket(qus_ticket(['agent_id' => 7]), $user))
            ->toThrow(TicketBusinessException::class, 'Você já capturou o chamado.');
    });

});
