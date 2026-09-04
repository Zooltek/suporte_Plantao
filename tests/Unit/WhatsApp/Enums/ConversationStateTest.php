<?php

use App\Enums\WhatsApp\ConversationState;

// ─────────────────────────────────────────────────────────────────────────────
// ConversationState Enum
// ─────────────────────────────────────────────────────────────────────────────

describe('ConversationState::label()', function () {

    it('retorna label correto para cada estado', function (ConversationState $state, string $expected) {
        expect($state->label())->toBe($expected);
    })->with([
        [ConversationState::GREETING,              'Saudação'],
        [ConversationState::AWAITING_MENU,         'Aguardando opção'],
        [ConversationState::AWAITING_NAME,         'Aguardando nome'],
        [ConversationState::AWAITING_COMPANY,      'Aguardando empresa'],
        [ConversationState::AWAITING_AREA,         'Aguardando área'],
        [ConversationState::AWAITING_PROBLEM,      'Aguardando descrição'],
        [ConversationState::AWAITING_ATTACHMENTS,  'Aguardando anexos'],
        [ConversationState::CONFIRMING,            'Confirmando'],
        [ConversationState::COMPLETED,             'Concluído'],
        [ConversationState::CANCELLED,             'Cancelado'],
        [ConversationState::HUMAN_PENDING,         'Aguardando atendente'],
    ]);

});

describe('ConversationState::isTerminal()', function () {

    it('retorna true apenas para estados terminais', function (ConversationState $state, bool $expected) {
        expect($state->isTerminal())->toBe($expected);
    })->with([
        [ConversationState::GREETING,              false],
        [ConversationState::AWAITING_MENU,         false],
        [ConversationState::AWAITING_NAME,         false],
        [ConversationState::AWAITING_COMPANY,      false],
        [ConversationState::AWAITING_AREA,         false],
        [ConversationState::AWAITING_PROBLEM,      false],
        [ConversationState::AWAITING_ATTACHMENTS,  false],
        [ConversationState::CONFIRMING,            false],
        [ConversationState::COMPLETED,             true],
        [ConversationState::CANCELLED,             true],
        [ConversationState::HUMAN_PENDING,         false],
    ]);

});

describe('ConversationState::isHumanPending()', function () {

    it('retorna true apenas para HUMAN_PENDING', function (ConversationState $state, bool $expected) {
        expect($state->isHumanPending())->toBe($expected);
    })->with([
        [ConversationState::HUMAN_PENDING, true],
        [ConversationState::GREETING,      false],
        [ConversationState::AWAITING_MENU, false],
        [ConversationState::COMPLETED,     false],
        [ConversationState::CANCELLED,     false],
    ]);

});

describe('ConversationState values', function () {

    it('possui o valor string correto para cada caso', function (ConversationState $state, string $value) {
        expect($state->value)->toBe($value);
    })->with([
        [ConversationState::GREETING,              'greeting'],
        [ConversationState::AWAITING_MENU,         'awaiting_menu'],
        [ConversationState::AWAITING_NAME,         'awaiting_name'],
        [ConversationState::AWAITING_COMPANY,      'awaiting_company'],
        [ConversationState::AWAITING_AREA,         'awaiting_area'],
        [ConversationState::AWAITING_PROBLEM,      'awaiting_problem'],
        [ConversationState::AWAITING_ATTACHMENTS,  'awaiting_attachments'],
        [ConversationState::CONFIRMING,            'confirming'],
        [ConversationState::COMPLETED,             'completed'],
        [ConversationState::CANCELLED,             'cancelled'],
        [ConversationState::HUMAN_PENDING,         'human_pending'],
    ]);

    it('pode ser instanciado a partir do valor string', function () {
        expect(ConversationState::from('greeting'))->toBe(ConversationState::GREETING);
        expect(ConversationState::from('completed'))->toBe(ConversationState::COMPLETED);
    });

});
