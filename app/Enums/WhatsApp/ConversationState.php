<?php

namespace App\Enums\WhatsApp;

/**
 * Estados da máquina de estados do chatbot WhatsApp.
 *
 * Fluxo principal:
 * GREETING → AWAITING_COMPANY_CNPJ → AWAITING_NAME → AWAITING_AREA
 *   → AWAITING_PROBLEM → AWAITING_ATTACHMENTS → CONFIRMING → HUMAN_PENDING
 * }
 */
enum ConversationState: string
{
    case GREETING             = 'greeting';
    case AWAITING_MENU        = 'awaiting_menu';
    case AWAITING_NAME        = 'awaiting_name';
    case AWAITING_COMPANY     = 'awaiting_company';
    case AWAITING_COMPANY_CNPJ = 'awaiting_company_cnpj';
    case AWAITING_COMPANY_PHONE = 'awaiting_company_phone';
    case AWAITING_AREA        = 'awaiting_area';
    case AWAITING_PROBLEM     = 'awaiting_problem';
    case AWAITING_ATTACHMENTS = 'awaiting_attachments';
    case CONFIRMING           = 'confirming';
    case COMPLETED            = 'completed';
    case CANCELLED            = 'cancelled';
    case HUMAN_PENDING        = 'human_pending';
    /** Cliente não localizado - opção Comercial */
    case AWAITING_NOT_FOUND_CHOICE = 'awaiting_not_found_choice';
    case AWAITING_NOT_FOUND_NAME = 'awaiting_not_found_name';
    case AWAITING_NOT_FOUND_COMPANY = 'awaiting_not_found_company';
    case AWAITING_NOT_FOUND_PHONE = 'awaiting_not_found_phone';
    /** Setor Financeiro - escolha abertura de chamado */
    case AWAITING_FINANCEIRO_CHOICE = 'awaiting_financeiro_choice';
    /** Setor Comercial - escolha abertura de chamado */
    case AWAITING_COMERCIAL_CHOICE = 'awaiting_comercial_choice';

    public function label(): string
    {
        return match ($this) {
            self::GREETING             => 'Saudação',
            self::AWAITING_MENU        => 'Aguardando opção',
            self::AWAITING_NAME        => 'Aguardando nome',
            self::AWAITING_COMPANY     => 'Aguardando empresa',
            self::AWAITING_COMPANY_CNPJ => 'Aguardando CNPJ',
            self::AWAITING_COMPANY_PHONE => 'Aguardando telefone',
            self::AWAITING_AREA        => 'Aguardando área',
            self::AWAITING_PROBLEM     => 'Aguardando descrição',
            self::AWAITING_ATTACHMENTS => 'Aguardando anexos',
            self::CONFIRMING           => 'Confirmando',
            self::COMPLETED            => 'Concluído',
            self::CANCELLED            => 'Cancelado',
            self::HUMAN_PENDING        => 'Aguardando atendente',
            self::AWAITING_NOT_FOUND_CHOICE => 'Não localizado - escolha',
            self::AWAITING_NOT_FOUND_NAME => 'Não localizado - nome',
            self::AWAITING_NOT_FOUND_COMPANY => 'Não localizado - empresa',
            self::AWAITING_NOT_FOUND_PHONE => 'Não localizado - telefone',
            self::AWAITING_FINANCEIRO_CHOICE => 'Financeiro - escolha',
            self::AWAITING_COMERCIAL_CHOICE => 'Comercial - escolha',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }

    /** Estado em que o bot não responde (agente humano assumiu). */
    public function isHumanPending(): bool
    {
        return $this === self::HUMAN_PENDING;
    }
}
