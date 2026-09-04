<?php

namespace App\Services\WhatsApp;

use App\Contracts\Repositories\ChatBotRepositoryInterface;
use App\DTO\WhatsApp\IncomingMessage;
use App\Enums\WhatsApp\ConversationState;
use App\Events\WhatsApp\HumanHandoverRequested;
use App\Models\Company;
use App\Models\Notification;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Support\Phone\PhoneNumber;
use Illuminate\Support\Facades\Log;

/**
 * Máquina de estados do chatbot WhatsApp.
 *
 * Cada método handle*() processa o estado atual, atualiza o payload da
 * conversa e transita para o próximo estado.
 *
 * Princípio Open/Closed: novos estados = novos métodos handle*(), sem
 * alterar o dispatcher principal.
 */
class ChatBotService
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly WhatsAppTicketService $ticketService,
        private readonly ChatBotRepositoryInterface $chatBotRepository,
        private readonly CompanyPhoneLookupService $companyPhoneLookup,
        ?WhatsAppBotMessageService $botMessageService = null,
    ) {
        $this->botMessageService = $botMessageService ?? app(WhatsAppBotMessageService::class);
    }

    private WhatsAppBotMessageService $botMessageService;

    /**
     * Ponto de entrada: despacha para o handler do estado atual.
     */
    public function handle(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        \Illuminate\Support\Facades\Log::info('[ChatBot] handle() started', [
            'conversation_id' => $conversation->id,
            'state' => $conversation->state->value,
            'message' => $message->normalizedBody(),
        ]);

        // Quando agente humano assumiu, o bot silencia completamente
        // (não processa nem cancelamentos — o humano está no controle)
        if ($conversation->state->isHumanPending()) {
            $this->handleHumanPending($conversation, $message);

            return;
        }

        $this->sendOutOfHoursMessageWhenNeeded($conversation);

        // Sessão expirada: reiniciar
        if ($conversation->isExpired() && ! $conversation->state->isTerminal()) {
            $this->restart($conversation);

            return;
        }

        // Cancelamento universal
        if (in_array($message->normalizedBody(), ['cancelar', 'cancel', 'sair', 'exit'], true)) {
            $this->cancel($conversation);

            return;
        }

        match ($conversation->state) {
            ConversationState::GREETING => $this->handleGreeting($conversation, $message),
            ConversationState::AWAITING_MENU => $this->handleMenu($conversation, $message),
            ConversationState::AWAITING_NAME => $this->handleName($conversation, $message),
            ConversationState::AWAITING_COMPANY => $this->handleCompany($conversation, $message),
            ConversationState::AWAITING_COMPANY_CNPJ => $this->handleCompanyCnpj($conversation, $message),
            ConversationState::AWAITING_COMPANY_PHONE => $this->handleCompanyPhone($conversation, $message),
            ConversationState::AWAITING_AREA => $this->handleArea($conversation, $message),
            ConversationState::AWAITING_PROBLEM => $this->handleProblem($conversation, $message),
            ConversationState::AWAITING_ATTACHMENTS => $this->handleAttachment($conversation, $message),
            ConversationState::CONFIRMING => $this->handleConfirm($conversation, $message),
            ConversationState::COMPLETED,
            ConversationState::CANCELLED => $this->restart($conversation),
            ConversationState::HUMAN_PENDING => null,
            ConversationState::AWAITING_NOT_FOUND_CHOICE => $this->handleNotFoundChoice($conversation, $message),
            ConversationState::AWAITING_NOT_FOUND_NAME => $this->handleNotFoundName($conversation, $message),
            ConversationState::AWAITING_NOT_FOUND_COMPANY => $this->handleNotFoundCompany($conversation, $message),
            ConversationState::AWAITING_NOT_FOUND_PHONE => $this->handleNotFoundPhone($conversation, $message),
            ConversationState::AWAITING_FINANCEIRO_CHOICE => $this->handleFinanceiroChoice($conversation, $message),
            ConversationState::AWAITING_COMERCIAL_CHOICE => $this->handleComercialChoice($conversation, $message),
        };
    }

    // -------------------------------------------------------------------------
    // Handlers de cada estado
    // -------------------------------------------------------------------------

    private function handleGreeting(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $company = $this->resolveCompanyByPhone($conversation);

        if ($company) {
            $this->greetByPhone($conversation, $company);

            return;
        }

        $this->transition($conversation, ConversationState::AWAITING_COMPANY_CNPJ);
        $text = $this->message('greeting_identified');
        $this->whatsApp->send($conversation, $text);
    }

    /**
     * Empresa identificada pelo número de telefone: pula a etapa de CNPJ
     * e segue direto para pedir o nome do solicitante.
     */
    private function greetByPhone(WhatsAppConversation $conversation, Company $company): void
    {
        $companyName = $this->displayCompanyName($company);

        $conversation->company_id = $company->id;
        $conversation->mergePayload([
            'company_name' => $companyName,
            'company_cnpj' => $this->normalizeCnpj((string) ($company->cnpj ?? '')),
        ]);
        $this->chatBotRepository->saveConversation($conversation);

        $this->transition($conversation, ConversationState::AWAITING_NAME);

        $text = $this->interpolate(
            $this->message('greeting_by_phone'),
            ['company' => $companyName]
        );
        $this->whatsApp->send($conversation, $text);
    }

    private function handleMenu(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $choice = trim($message->normalizedBody());

        match ($choice) {
            '1' => $this->startTicketFlow($conversation),
            '2' => $this->queryTicketStatus($conversation),
            '3' => $this->handover($conversation),
            default => $this->sendMenu($conversation, withInvalidOption: true),
        };
    }

    /**
     * Mensagens recebidas enquanto agente humano está atendendo.
     * O bot registra a mensagem (já feito pelo Job) e silencia.
     */
    private function handleHumanPending(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        // A mensagem já foi gravada no histórico pelo Job.
        // Apenas toca o last_activity_at para manter a sessão viva.
        $conversation->touch();
        $this->chatBotRepository->saveConversation($conversation);

        Log::info('[WhatsApp ChatBot] Mensagem recebida em modo human_pending.', [
            'conversation' => $conversation->id,
            'phone' => $conversation->phone,
        ]);
    }

    private function handleName(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $name = trim($message->body);

        if (empty($name)) {
            $this->whatsApp->send($conversation, 'Por favor, informe seu nome.');

            return;
        }

        $conversation->mergePayload(['name' => $name]);

        $this->transition($conversation, ConversationState::AWAITING_AREA);
        $text = $this->interpolate($this->message('sector_choice'), ['name' => $name]);
        $this->whatsApp->send($conversation, $text);
    }

    private function handleCompany(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $companyName = trim($message->body);

        if (empty($companyName)) {
            $this->whatsApp->send($conversation, 'Por favor, informe o nome da empresa.');

            return;
        }

        // Busca fuzzy pelo nome da empresa (via Repository)
        $company = $this->chatBotRepository->findCompanyByName($companyName);

        if ($company) {
            $conversation->company_id = $company->id;
            $conversation->mergePayload(['company_name' => $companyName]);
        } else {
            Log::info('[WhatsApp ChatBot] Empresa não encontrada na base.', [
                'phone' => $conversation->phone,
                'company_raw' => $companyName,
            ]);

            $conversation->company_id = null;
            $conversation->mergePayload([
                'company_name' => $companyName,
                'company_name_attempted' => $companyName,
                'company_unidentified' => true,
            ]);
            $this->transition($conversation, ConversationState::AWAITING_COMPANY_CNPJ);

            $notFoundMsg = $this->interpolate($this->message('company_not_found'), ['company' => $companyName]);
            $this->whatsApp->send($conversation, $notFoundMsg."\n\n".$this->message('ask_company_cnpj'));

            return;
        }

        $this->chatBotRepository->saveConversation($conversation);

        $this->transition($conversation, ConversationState::AWAITING_AREA);
        $this->whatsApp->send($conversation, $this->message('ask_area'));
    }

    private function handleCompanyCnpj(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $cnpj = $this->normalizeCnpj($message->body);

        if (! $this->validCnpj($cnpj)) {
            $this->whatsApp->send($conversation, 'CNPJ inválido. Informe o CNPJ com 14 dígitos.');

            return;
        }

        $company = $this->chatBotRepository->findCompanyByCnpj($cnpj);

        if ($company) {
            $conversation->company_id = $company->id;
            $conversation->mergePayload([
                'company_cnpj' => $cnpj,
                'company_name' => $this->displayCompanyName($company),
            ]);
            $this->chatBotRepository->saveConversation($conversation);

            $this->transition($conversation, ConversationState::AWAITING_NAME);
            $text = $this->message('cnpj_located');
            $this->whatsApp->send($conversation, $text);

            return;
        }

        $conversation->mergePayload(['company_cnpj' => $cnpj]);
        $this->transition($conversation, ConversationState::AWAITING_NOT_FOUND_CHOICE);
        $this->whatsApp->send($conversation, $this->message('cnpj_not_found'));
    }

    private function handleNotFoundChoice(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $choice = trim($message->normalizedBody());

        $option = match ($choice) {
            '1', 'comercial' => '1',
            '2', 'tentar novamente', 'tentar' => '2',
            '3', 'encerrar' => '3',
            default => null,
        };

        if (! $option) {
            $this->whatsApp->send($conversation, $this->message('invalid_option'));

            return;
        }

        match ($option) {
            '1' => $this->startNotFoundComercial($conversation),
            '2' => $this->retryCnpj($conversation),
            '3' => $this->endNotFound($conversation),
        };
    }

    private function startNotFoundComercial(WhatsAppConversation $conversation): void
    {
        $this->transition($conversation, ConversationState::AWAITING_NOT_FOUND_NAME);
        $this->whatsApp->send($conversation, $this->message('not_found_comercial'));
    }

    private function retryCnpj(WhatsAppConversation $conversation): void
    {
        $this->transition($conversation, ConversationState::AWAITING_COMPANY_CNPJ);
        $this->whatsApp->send($conversation, 'Sem problema! Digite o CNPJ novamente para eu conferir.');
    }

    private function endNotFound(WhatsAppConversation $conversation): void
    {
        $this->whatsApp->send($conversation, $this->message('goodbye'));
        $this->transition($conversation, ConversationState::COMPLETED);
    }

    private function handleCompanyPhone(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $phone = PhoneNumber::normalize($message->body);

        if (strlen($phone) < 10 || strlen($phone) > 14) {
            $this->whatsApp->send($conversation, 'Telefone inválido. Informe DDD + telefone.');

            return;
        }

        $conversation->mergePayload(['company_phone' => $phone]);
        $this->transition($conversation, ConversationState::AWAITING_AREA);
        $this->whatsApp->send($conversation, $this->message('ask_area'));
    }

    private function handleNotFoundName(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $name = trim($message->body);

        if (empty($name)) {
            $this->whatsApp->send($conversation, 'Por favor, informe seu nome.');

            return;
        }

        $conversation->mergePayload(['name' => $name]);
        $this->transition($conversation, ConversationState::AWAITING_NOT_FOUND_COMPANY);
        $text = $this->interpolate($this->message('ask_company_not_found'), ['name' => $name]);
        $this->whatsApp->send($conversation, $text);
    }

    private function handleNotFoundCompany(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $companyName = trim($message->body);

        if (empty($companyName)) {
            $this->whatsApp->send($conversation, 'Por favor, informe o nome da empresa.');

            return;
        }

        $conversation->mergePayload(['company_name' => $companyName]);
        $this->transition($conversation, ConversationState::AWAITING_NOT_FOUND_PHONE);
        $this->whatsApp->send($conversation, $this->message('phone_choice'));
    }

    private function handleNotFoundPhone(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $choice = trim($message->normalizedBody());

        if (in_array($choice, ['1', 'usar número atual', 'numero atual', 'atual'], true)) {
            $phone = PhoneNumber::normalize($conversation->phone);
            $conversation->mergePayload(['company_phone' => $phone]);
            $this->chatBotRepository->saveConversation($conversation);
            $this->whatsApp->send($conversation, $this->message('phone_registered'));
            $this->finishNotFoundSummary($conversation);

            return;
        }

        if (in_array($choice, ['2', 'informar outro', 'outro'], true)) {
            $this->whatsApp->send($conversation, $this->message('ask_phone_other'));

            return;
        }

        $phone = PhoneNumber::normalize($message->body);

        if (strlen($phone) < 10 || strlen($phone) > 14) {
            $this->whatsApp->send($conversation, 'Telefone inválido. Informe DDD + telefone.');

            return;
        }

        $conversation->mergePayload(['company_phone' => $phone]);
        $this->chatBotRepository->saveConversation($conversation);
        $this->finishNotFoundSummary($conversation);
    }

    /**
     * Encerra o fluxo "cliente não localizado": fixa área = Comercial,
     * popula descrição padrão e envia ack + resumo, indo direto para CONFIRMING.
     *
     * Esta etapa não pede descrição de problema ao usuário, conforme spec:
     * a equipe Comercial entra em contato para entender a demanda.
     */
    private function finishNotFoundSummary(WhatsAppConversation $conversation): void
    {
        if (empty($conversation->getPayloadValue('area_label'))) {
            $conversation->mergePayload([
                'area_key' => '3',
                'area_label' => 'Comercial',
            ]);
        }

        if (empty($conversation->getPayloadValue('problem'))) {
            $conversation->mergePayload([
                'problem' => $this->message('not_found_default_problem'),
            ]);
        }

        $this->transition($conversation, ConversationState::CONFIRMING);

        $this->whatsApp->send($conversation, $this->message('not_found_acknowledged'));

        $text = $this->interpolate($this->message('summary_not_found'), [
            'name' => $conversation->getPayloadValue('name', '—'),
            'company' => $conversation->getPayloadValue('company_name', '—'),
            'cnpj' => $this->formatCnpj((string) $conversation->getPayloadValue('company_cnpj', '')),
            'phone' => $conversation->getPayloadValue('company_phone', $conversation->phone),
        ]);
        $this->whatsApp->send($conversation, $text);
    }

    private function handleArea(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $areas = config('whatsapp.chatbot.routing_areas');
        $choice = trim($message->normalizedBody());

        $sectorKey = match ($choice) {
            '1', 'suporte', 'suporte técnico' => '1',
            '2', 'financeiro' => '2',
            '3', 'comercial' => '3',
            default => null,
        };

        if (! $sectorKey || ! array_key_exists($sectorKey, $areas)) {
            $this->whatsApp->send($conversation, $this->message('invalid_option'));

            return;
        }

        $conversation->mergePayload(['area_key' => $sectorKey, 'area_label' => $areas[$sectorKey]]);

        match ($sectorKey) {
            '1' => $this->handleSupportSector($conversation),
            '2' => $this->handleFinanceiroSector($conversation),
            '3' => $this->handleComercialSector($conversation),
        };
    }

    private function handleSupportSector(WhatsAppConversation $conversation): void
    {
        $this->transition($conversation, ConversationState::AWAITING_PROBLEM);
        $sector = $conversation->getPayloadValue('area_label', 'Suporte');
        $text = $this->interpolate($this->message('problem_for_support'), ['sector' => $sector]);
        $this->whatsApp->send($conversation, $text);
    }

    private function handleFinanceiroSector(WhatsAppConversation $conversation): void
    {
        $this->transition($conversation, ConversationState::AWAITING_FINANCEIRO_CHOICE);
        $this->whatsApp->send($conversation, $this->message('problem_for_financeiro'));
    }

    private function handleComercialSector(WhatsAppConversation $conversation): void
    {
        $this->transition($conversation, ConversationState::AWAITING_COMERCIAL_CHOICE);
        $this->whatsApp->send($conversation, $this->message('problem_for_comercial'));
    }

    private function handleFinanceiroChoice(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $choice = trim($message->normalizedBody());

        $option = match ($choice) {
            '1', 'abrir chamado', 'abrir', 'chamado' => '1',
            '2', 'encerrar' => '2',
            default => null,
        };

        if (! $option) {
            $this->whatsApp->send($conversation, $this->message('invalid_option'));

            return;
        }

        match ($option) {
            '1' => $this->startProblemForSector($conversation),
            '2' => $this->endWithGoodbye($conversation),
        };
    }

    private function handleComercialChoice(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $choice = trim($message->normalizedBody());

        $option = match ($choice) {
            '1', 'abrir chamado', 'abrir', 'chamado' => '1',
            '2', 'encerrar' => '2',
            default => null,
        };

        if (! $option) {
            $this->whatsApp->send($conversation, $this->message('invalid_option'));

            return;
        }

        match ($option) {
            '1' => $this->startProblemForSector($conversation),
            '2' => $this->endWithGoodbye($conversation),
        };
    }

    private function startProblemForSector(WhatsAppConversation $conversation): void
    {
        $this->transition($conversation, ConversationState::AWAITING_PROBLEM);
        $sector = $conversation->getPayloadValue('area_label', 'Suporte');
        $text = $this->interpolate($this->message('problem_for_support'), ['sector' => $sector]);
        $this->whatsApp->send($conversation, $text);
    }

    private function endWithGoodbye(WhatsAppConversation $conversation): void
    {
        $this->whatsApp->send($conversation, $this->message('goodbye'));
        $this->transition($conversation, ConversationState::COMPLETED);
    }

    private function handleProblem(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $problem = trim($message->body);

        if (empty($problem)) {
            $this->whatsApp->send($conversation, 'Por favor, descreva o problema.');

            return;
        }

        $conversation->mergePayload(['problem' => $problem]);
        $this->chatBotRepository->saveConversation($conversation);

        // Debug
        Log::info('[ChatBot] handleProblem called', [
            'conversation_id' => $conversation->id,
            'company_id' => $conversation->company_id,
            'problem' => $problem,
        ]);

        // Se está no fluxo "não identificado" (company_unidentified), vai direto para resumo
        if ($conversation->getPayloadValue('company_unidentified')) {
            Log::info('[ChatBot] Calling finishNotFoundSummary (company_unidentified)');
            $this->finishNotFoundSummary($conversation);

            return;
        }

        // Fluxo normal - pede anexos
        $this->transition($conversation, ConversationState::AWAITING_ATTACHMENTS);
        $this->whatsApp->send($conversation, $this->message('ask_attachment'));
    }

    private function handleAttachment(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $body = $message->normalizedBody();

        // Texto "confirmar" → avança sem mais anexos
        if (in_array($body, ['confirmar', 'confirm', 'ok', 'não', 'nao', 'n'], true)) {
            $this->transition($conversation, ConversationState::CONFIRMING);
            $this->sendSummary($conversation);

            return;
        }

        // É uma mídia → recordInbound já baixou e persistiu o anexo.
        // Reaproveita o attachment_path para evitar download duplicado.
        if ($message->isMedia()) {
            $path = $this->whatsApp->findInboundAttachmentPath($message->messageId)
                ?? $this->whatsApp->downloadAndStoreMedia($message);

            if ($path) {
                $attachments = $conversation->getPayloadValue('attachments', []);
                $attachments[] = [
                    'path' => $path,
                    'mime_type' => $message->mimetype,
                    'original_filename' => $message->fileName,
                ];
                $conversation->mergePayload(['attachments' => $attachments]);
                $this->chatBotRepository->saveConversation($conversation);

                $this->whatsApp->send(
                    $conversation,
                    "📎 Anexo recebido!\n\nEnvie mais arquivos ou digite *confirmar* para abrir o chamado."
                );

                return;
            }

            $this->whatsApp->send(
                $conversation,
                'Não consegui receber esse anexo. Envie o arquivo novamente ou digite *confirmar* para abrir o chamado sem anexos.'
            );

            return;
        }

        // Qualquer outro texto: solicitar novamente
        $this->whatsApp->send(
            $conversation,
            'Envie um arquivo (imagem, documento, áudio ou vídeo) ou digite *confirmar* para abrir o chamado.'
        );
    }

    private function handleConfirm(WhatsAppConversation $conversation, IncomingMessage $message): void
    {
        $body = $message->normalizedBody();

        if (in_array($body, ['1', 'confirmar', 'confirm', 'sim', 's', 'yes', 'ok'], true)) {
            $this->createTicket($conversation);

            return;
        }

        if (in_array($body, ['2', 'cancelar', 'cancel', 'não', 'nao', 'n', 'recomeçar'], true)) {
            $this->cancel($conversation);

            return;
        }

        // Reexibe resumo se resposta inesperada
        $this->sendSummary($conversation);
    }

    // -------------------------------------------------------------------------
    // Ações de triagem (menu)
    // -------------------------------------------------------------------------

    /** Opção 1 — inicia o fluxo de abertura de chamado. */
    private function startTicketFlow(WhatsAppConversation $conversation): void
    {
        $this->transition($conversation, ConversationState::AWAITING_COMPANY_CNPJ);
        $text = $this->message('greeting_identified');
        $this->whatsApp->send($conversation, $text);
    }

    /**
     * Opção 2 — consulta o status do último chamado aberto pelo contato.
     * Busca por company_id (se identificada) ou pelo número de telefone no campo obs.
     */
    private function queryTicketStatus(WhatsAppConversation $conversation): void
    {
        if (! $conversation->company_id) {
            $company = $this->resolveCompanyByPhone($conversation);

            if ($company) {
                $conversation->company_id = $company->id;
                $conversation->mergePayload(['company_name' => $this->displayCompanyName($company)]);
                $this->chatBotRepository->saveConversation($conversation);
            }
        }

        $ticket = null;

        if ($conversation->company_id) {
            $ticket = $this->chatBotRepository->findLastTicketByCompanyId($conversation->company_id);
        }

        if (! $ticket) {
            $ticket = $this->chatBotRepository->findLastTicketByPhone($conversation->phone);
        }

        if (! $ticket) {
            $this->whatsApp->send($conversation, $this->message('status_not_found'));
            // Volta para o menu
            $this->transition($conversation, ConversationState::AWAITING_MENU);
            $this->sendMenu($conversation);

            return;
        }

        $statusName = $ticket->status?->name ?? 'Em aberto';
        $text = $this->interpolate($this->message('status_found'), [
            'ticket_id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $statusName,
            'date' => $ticket->created_at->format('d/m/Y'),
        ]);

        $this->whatsApp->send($conversation, $text);
        $this->transition($conversation, ConversationState::AWAITING_MENU);
        $this->sendMenu($conversation);
    }

    /**
     * Opção 3 — transbordo para atendente humano.
     *
     * - Marca a conversa como HUMAN_PENDING
     * - Notifica todos os agentes via `user_notifications`
     * - Dispara o evento HumanHandoverRequested para extensibilidade
     */
    private function handover(WhatsAppConversation $conversation): void
    {
        $this->transition($conversation, ConversationState::HUMAN_PENDING);
        $this->whatsApp->send($conversation, $this->message('human_pending'));

        // Notifica todos os agentes no painel (via Repository)
        $agents = $this->chatBotRepository->getAgentsAndAdmins();
        $phone = '+'.$conversation->phone;

        foreach ($agents as $agent) {
            $action = (bool) $agent->ticketit_admin
                ? route('admin.whatsapp.show', $conversation->id)
                : route('agent.index');

            Notification::store(
                user_id: $agent->id,
                content: "WhatsApp: {$phone} solicitou atendimento humano.",
                action: $action,
                image: 'https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg',
            );
        }

        Log::info('[WhatsApp ChatBot] Handover solicitado — agentes notificados.', [
            'conversation' => $conversation->id,
            'phone' => $conversation->phone,
            'agents' => $agents->pluck('id'),
        ]);

        event(new HumanHandoverRequested($conversation));
    }

    // -------------------------------------------------------------------------
    // Ações
    // -------------------------------------------------------------------------

    private function createTicket(WhatsAppConversation $conversation): void
    {
        try {
            $ticket = $this->ticketService->createFromConversation($conversation);

            $conversation->ticket_id = $ticket->id;
            $conversation->completed_at = null;
            $this->transition($conversation, ConversationState::HUMAN_PENDING);

            $text = $this->interpolate(
                $this->message('ticket_created'),
                ['ticket_id' => $ticket->id]
            );
            $this->whatsApp->send($conversation, $text);
        } catch (\Throwable $e) {
            Log::error('[WhatsApp ChatBot] Erro ao criar ticket.', [
                'conversation' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
            $this->whatsApp->send($conversation, $this->message('error'));
        }
    }

    private function sendSummary(WhatsAppConversation $conversation): void
    {
        $attachments = $conversation->getPayloadValue('attachments', []);
        $attachCount = count($attachments);

        $text = $this->interpolate($this->message('confirm_summary'), [
            'name' => $conversation->getPayloadValue('name', '—'),
            'company' => $conversation->getPayloadValue('company_name', '—'),
            'cnpj' => $this->formatCnpj((string) $conversation->getPayloadValue('company_cnpj', '')),
            'phone' => $conversation->getPayloadValue('company_phone', $conversation->phone),
            'area' => $conversation->getPayloadValue('area_label', '—'),
            'problem' => $conversation->getPayloadValue('problem', '—'),
            'attachments' => $attachCount > 0 ? "{$attachCount} arquivo(s)" : 'Nenhum',
        ]);

        $this->whatsApp->send($conversation, $text);
    }

    private function cancel(WhatsAppConversation $conversation): void
    {
        $this->transition($conversation, ConversationState::CANCELLED);
        $this->whatsApp->send($conversation, $this->message('cancelled'));
    }

    private function restart(WhatsAppConversation $conversation): void
    {
        $isExpired = $conversation->isExpired();

        $conversation->payload = [];
        $conversation->company_id = null;
        $conversation->ticket_id = null;
        $conversation->completed_at = null;
        $conversation->last_activity_at = now();
        $conversation->state = ConversationState::AWAITING_COMPANY_CNPJ;
        $this->chatBotRepository->saveConversation($conversation);

        if ($isExpired) {
            $this->whatsApp->send($conversation, $this->message('session_expired'));
        }

        $company = $this->resolveCompanyByPhone($conversation);

        if ($company) {
            $this->greetByPhone($conversation, $company);

            return;
        }

        $this->whatsApp->send($conversation, $this->message('greeting_identified'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function transition(WhatsAppConversation $conversation, ConversationState $state): void
    {
        $conversation->state = $state;
        $conversation->last_activity_at = now();
        $this->chatBotRepository->saveConversation($conversation);
    }

    private function interpolate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{'.$key.'}', $value, $template);
        }

        return $template;
    }

    private function resolveCompanyByPhone(WhatsAppConversation $conversation): ?Company
    {
        return $this->companyPhoneLookup->resolve($conversation->phone);
    }

    private function displayCompanyName(Company $company): string
    {
        return $company->trade_name ?: $company->name;
    }

    private function sendMenu(WhatsAppConversation $conversation, bool $withInvalidOption = false): void
    {
        $message = $this->message('menu');

        if ($withInvalidOption) {
            $message = $this->message('invalid_option')."\n\n".$message;
        }

        $this->whatsApp->send($conversation, $message);
    }

    private function message(string $key): string
    {
        return $this->botMessageService->message($key);
    }

    private function sendOutOfHoursMessageWhenNeeded(WhatsAppConversation $conversation): void
    {
        if (app()->runningUnitTests() && ! config('whatsapp.chatbot.out_of_hours_in_tests', false)) {
            return;
        }

        if (! $this->botMessageService->outsideBusinessHours()) {
            return;
        }

        $lastSentAt = $conversation->getPayloadValue('out_of_hours_sent_at');
        $cooldown = $this->botMessageService->outOfHoursCooldownMinutes();

        if ($lastSentAt && now()->diffInMinutes($lastSentAt) < $cooldown) {
            return;
        }

        $conversation->mergePayload(['out_of_hours_sent_at' => now()->toIso8601String()]);
        $this->chatBotRepository->saveConversation($conversation);
        $this->whatsApp->send($conversation, $this->message('out_of_hours'));
    }

    private function normalizeCnpj(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function validCnpj(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        for ($t = 12; $t < 14; $t++) {
            $sum = 0;
            $pos = $t - 7;

            for ($i = $t; $i >= 1; $i--) {
                $sum += (int) $cnpj[$t - $i] * $pos--;
                if ($pos < 2) {
                    $pos = 9;
                }
            }

            $digit = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);

            if ((int) $cnpj[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    private function formatCnpj(string $cnpj): string
    {
        if (strlen($cnpj) !== 14) {
            return $cnpj ?: '—';
        }

        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj) ?? $cnpj;
    }
}
