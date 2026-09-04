<?php

use App\Models\Ticket\Ticket;

describe('Ticket — fila de pendências', function () {

    it('considera sem agente quando agent_id é null ou 0', function () {
        $withoutAgentNull = new Ticket(['agent_id' => null]);
        $withoutAgentZero = new Ticket(['agent_id' => 0]);
        $withAgent = new Ticket(['agent_id' => 15]);

        expect($withoutAgentNull->hasAssignedAgent())->toBeFalse()
            ->and($withoutAgentZero->hasAssignedAgent())->toBeFalse()
            ->and($withAgent->hasAssignedAgent())->toBeTrue();
    });

    it('identifica fila de pendências apenas quando está pendente e sem responsável', function () {
        $queueTicket = new Ticket([
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id' => null,
        ]);

        $assignedPending = new Ticket([
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id' => 5,
        ]);

        $openWithoutAgent = new Ticket([
            'status_id' => 1,
            'agent_id' => null,
        ]);

        expect($queueTicket->isQueuePending())->toBeTrue()
            ->and($assignedPending->isQueuePending())->toBeFalse()
            ->and($openWithoutAgent->isQueuePending())->toBeFalse();
    });

});
