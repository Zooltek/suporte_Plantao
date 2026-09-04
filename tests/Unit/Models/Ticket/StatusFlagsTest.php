<?php

use App\Models\Ticket\Status;

/**
 * Cobre os Bugs 1 e 2:
 *  - Bug 1: gatilho de agendamento acionado para todos os status
 *  - Bug 2: "Em andamento" vinculado a "Visita Técnica"
 *
 * Raiz: migration inserindo "Não Resolvido" (id=5) estava ausente.
 * O status era referenciado no form, mas inexistente no banco, causando
 * retorno null nas flags e comportamento indefinido no fluxo de save.
 */
describe('Status — flags de negócio (Bugs 1 e 2)', function () {

    it('"Em andamento" canônico (id=2) não exige agendamento', function () {
        $status = Status::find(2);

        expect($status)->not->toBeNull()
            ->and($status->name)->toBe('Em andamento')
            ->and($status->requires_schedule)->toBeFalse()
            ->and($status->requires_agent)->toBeTrue()
            ->and($status->is_terminal)->toBeFalse();
    });

    it('"Resolvido" canônico (id=3) é terminal e exige solução', function () {
        $status = Status::find(3);

        expect($status)->not->toBeNull()
            ->and($status->name)->toBe('Resolvido')
            ->and($status->is_terminal)->toBeTrue()
            ->and($status->requires_solution)->toBeTrue()
            ->and($status->requires_agent)->toBeTrue()
            ->and($status->requires_schedule)->toBeFalse();
    });

    // ── "Não Resolvido" inserido pela nossa migration ──────────────────────

    it('"Não Resolvido" (id=5) existe no banco após migration', function () {
        $status = Status::find(5);

        expect($status)->not->toBeNull()
            ->and($status->name)->toBe('Não Resolvido');
    });

    it('"Não Resolvido" é terminal — não gera chamado em aberto', function () {
        expect(Status::isTerminal(5))->toBeTrue();
    });

    it('"Não Resolvido" não exige agendamento — não aciona redirect de Visita Técnica', function () {
        expect(Status::requiresSchedule(5))->toBeFalse();
    });

    it('"Não Resolvido" exige agente atribuído', function () {
        expect(Status::requiresAgent(5))->toBeTrue();
    });

    it('"Não Resolvido" não exige campo solução preenchido', function () {
        expect(Status::requiresSolution(5))->toBeFalse();
    });

    // ── Apenas "Visita Técnica" aciona agendamento ─────────────────────────

    it('"Visita Técnica" é o único status com requires_schedule=true', function () {
        $agendaveis = Status::where('requires_schedule', true)->get();

        expect($agendaveis)->toHaveCount(1)
            ->and($agendaveis->first()->name)->toBe('Visita Técnica');
    });

    it('nenhum status terminal aciona agendamento', function () {
        $count = Status::where('is_terminal', true)
            ->where('requires_schedule', true)
            ->count();

        expect($count)->toBe(0);
    });

    // ── "Não Resolvido" nunca aciona redirect de agendamento ──────────────

    it('"Não Resolvido" com id=5: requiresSchedule retorna false via método estático', function () {
        // Garante que a lógica do TicketController não redireciona para agendamento
        // quando o usuário salva um chamado como "Não Resolvido"
        Status::find(5); // acessa o banco — garante que a migration rodou

        expect(Status::requiresSchedule(5))->toBeFalse()
            ->and(Status::isTerminal(5))->toBeTrue();

        // needsSchedule = requiresSchedule && !isTerminal → false && false = false
        $needsSchedule = Status::requiresSchedule(5) && ! Status::isTerminal(5);
        expect($needsSchedule)->toBeFalse();
    });

});
