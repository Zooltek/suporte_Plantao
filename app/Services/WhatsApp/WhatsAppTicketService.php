<?php

namespace App\Services\WhatsApp;

use App\Contracts\Repositories\WhatsAppTicketRepositoryInterface;
use App\Exceptions\WhatsAppPayloadException;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\Ticket\Routing\TicketDepartmentResolver;
use App\Services\Ticket\Routing\TicketDepartmentRoutingIntent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Cria um ticket a partir de uma conversa WhatsApp concluída.
 *
 * Deliberadamente separado do TicketService existente pois o contexto de
 * criação é diferente: não há agente autenticado, os campos são derivados
 * do payload do chatbot, e a origem é sempre 'WhatsApp' (id=5).
 */
class WhatsAppTicketService
{
    private readonly TicketDepartmentResolver $departmentResolver;

    public function __construct(
        private readonly WhatsAppTicketRepositoryInterface $ticketRepository,
        private readonly CompanyPhoneLookupService $companyPhoneLookup,
        ?TicketDepartmentResolver $departmentResolver = null,
    ) {
        $this->departmentResolver = $departmentResolver
            ?? app(TicketDepartmentResolver::class);
    }

    /**
     * Cria o ticket a partir dos dados coletados no chatbot.
     *
     * @throws \RuntimeException Se dados mínimos não estiverem presentes.
     */
    public function createFromConversation(WhatsAppConversation $conversation): Ticket
    {
        $payload = $conversation->payload ?? [];

        $name = data_get($payload, 'name', '');
        $problem = data_get($payload, 'problem', '');
        $area = data_get($payload, 'area_label', 'WhatsApp');

        if (empty($name) || empty($problem)) {
            throw new WhatsAppPayloadException('[WhatsApp] Payload incompleto: name ou problem ausente.');
        }

        $companyId = $this->resolveCompanyId($conversation, $payload);

        if (! $companyId) {
            $companyId = $this->createMinimalCompany($conversation, $payload);
        }

        return DB::transaction(function () use ($conversation, $payload, $name, $problem, $area, $companyId) {

            $ticket = new Ticket;
            $authorId = $this->resolveAuthorId();

            $agentId = $this->resolveAgent();

            $categoryId = $this->resolveCategoryId($payload);
            $subCategoryId = $this->resolveSubCategoryId($payload, $categoryId);

            $ticket->origin_id = config('whatsapp.chatbot.origin_id', 5);
            $ticket->status_id = $this->resolveInitialStatusId($agentId);
            $ticket->priority_id = config('whatsapp.chatbot.default_priority_id', 1);
            $ticket->agent_id = $agentId;
            $ticket->department_id = $this->departmentResolver->resolve(new TicketDepartmentRoutingIntent(
                subCategoryId: $subCategoryId,
                categoryId: $categoryId,
                channelDepartmentId: $this->resolveChannelDepartmentId($payload),
                agentId: $agentId,
            ));
            $ticket->author_id = $authorId;
            $ticket->company_id = $companyId;
            $ticket->contact = strtoupper($name);
            $ticket->trouble = $problem;
            $ticket->solution = null;
            $ticket->obs = $this->buildObservation($conversation, $payload);
            $ticket->category_id = $categoryId;
            $ticket->sub_category_id = $subCategoryId;
            $ticket->visible = 1;
            $ticket->subject = "{$area} - ".strtoupper($name);
            $ticket->content = $problem;
            $ticket->user_id = $authorId;
            $ticket->created_at = now();

            $this->ticketRepository->saveTicket($ticket);

            $this->attachMediaFiles($ticket, $payload);
            $this->notifyTicketQueue($ticket);

            Log::info('[WhatsApp] Ticket criado.', [
                'ticket_id' => $ticket->id,
                'phone' => $conversation->phone,
                'conversation' => $conversation->id,
            ]);

            return $ticket;
        });
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    /**
     * Tickets abertos pelo bot entram na fila do setor, sem responsável individual.
     * O autor do registro continua sendo resolvido separadamente para auditoria.
     */
    private function resolveAgent(): ?int
    {
        return null;
    }

    private function resolveInitialStatusId(?int $agentId): int
    {
        if ($agentId === null) {
            return Ticket::STATUS_PENDING_ID;
        }

        $configured = (int) config('whatsapp.chatbot.default_status_id', Ticket::STATUS_PENDING_ID);

        return $configured ?: Ticket::STATUS_PENDING_ID;
    }

    private function resolveAuthorId(): int
    {
        $configured = config('whatsapp.chatbot.system_user_id');

        if ($configured && $this->ticketRepository->findUser((int) $configured)) {
            return (int) $configured;
        }

        return $this->ticketRepository->firstAdminUserId();
    }

    /**
     * Resolve o company_id: usa o vínculo da conversa ou faz nova busca pelo nome.
     */
    private function resolveCompanyId(WhatsAppConversation $conversation, array $payload): ?int
    {
        $companyId = $conversation->company_id
            ?? $this->companyPhoneLookup->resolveId($conversation->phone);

        if ($companyId) {
            return $companyId;
        }

        $companyName = data_get($payload, 'company_name', '');

        if ($companyName) {
            $id = $this->ticketRepository->findCompanyIdByName($companyName);

            if ($id) {
                return $id;
            }
        }

        Log::info('[WhatsApp] Empresa não identificada. Será criado cadastro mínimo.', [
            'phone' => $conversation->phone,
            'company_name' => $companyName,
        ]);

        return null;
    }

    private function createMinimalCompany(WhatsAppConversation $conversation, array $payload): int
    {
        $companyName = trim((string) data_get($payload, 'company_name', data_get($payload, 'company_name_attempted', '')));
        $cnpjDigits = preg_replace('/\D+/', '', (string) data_get($payload, 'company_cnpj', '')) ?: null;
        $phone = preg_replace('/\D+/', '', (string) data_get($payload, 'company_phone', $conversation->phone)) ?: $conversation->phone;

        if ($companyName === '') {
            throw new WhatsAppPayloadException('[WhatsApp] Empresa não identificada: razão social ausente.');
        }

        if ($cnpjDigits) {
            $existing = Company::whereCnpjDigits($cnpjDigits)->first();

            if ($existing) {
                return (int) $existing->id;
            }
        }

        $company = Company::query()->create([
            'name' => $companyName,
            'trade_name' => $companyName,
            'cnpj' => $cnpjDigits ? $this->formatCnpj($cnpjDigits) : null,
            'phone' => $phone,
            'whatsapp_phone' => $conversation->phone,
            'contact_name' => data_get($payload, 'name'),
            'observations' => 'Cadastro mínimo criado automaticamente por chamado via WhatsApp.',
            'is_active' => true,
        ]);

        return (int) $company->id;
    }

    /**
     * Aplica a máscara padrão de CNPJ (XX.XXX.XXX/XXXX-XX). Mantém o valor
     * original caso a string não possua exatamente 14 dígitos.
     */
    private function formatCnpj(string $digits): string
    {
        if (strlen($digits) !== 14) {
            return $digits;
        }

        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $digits) ?? $digits;
    }

    /**
     * Resolve a categoria raiz com base na área escolhida pelo cliente no bot.
     *
     * Precedência:
     *   1. Categoria configurada explicitamente por área no .env / config (ex: WHATSAPP_CATEGORY_SUPORTE).
     *   2. Categoria configurada globalmente via WHATSAPP_CATEGORY_DEFAULT.
     *   3. Categoria cujo nome coincide com a área (ex: "Comercial", "Financeiro", "Suporte Técnico").
     *   4. Categoria raiz "Atendimento" (instalações com hierarquia genérica).
     *   5. Fallback padrão (ID 1).
     */
    private function resolveCategoryId(array $payload): int
    {
        $areaKey = (string) data_get($payload, 'area_key', '1');

        // 1. Configuração específica por área (WHATSAPP_CATEGORY_SUPORTE, WHATSAPP_CATEGORY_FINANCEIRO, etc.)
        $configuredAreaCategory = config("whatsapp.chatbot.categories_by_area.{$areaKey}");
        if ($configuredAreaCategory && Category::query()->where('category_id', (int) $configuredAreaCategory)->exists()) {
            return (int) $configuredAreaCategory;
        }

        // 2. Configuração explícita global padrão (WHATSAPP_CATEGORY_DEFAULT)
        $defaultCategory = config('whatsapp.chatbot.default_category_id') ?? env('WHATSAPP_CATEGORY_DEFAULT');
        if ($defaultCategory && Category::query()->where('category_id', (int) $defaultCategory)->exists()) {
            return (int) $defaultCategory;
        }

        $areaCandidates = match ($areaKey) {
            '2' => ['Financeiro'],
            '3' => ['Comercial', 'CRM / Comercial'],
            default => ['Suporte Técnico', 'Suporte'],
        };

        // 3. Tenta encontrar uma categoria raiz com o nome da área
        foreach ($areaCandidates as $name) {
            $id = $this->findCategoryIdByName($name, parentId: 0);
            if ($id) {
                return $id;
            }
        }

        // 4. Fallback: categoria raiz genérica "Atendimento"
        $atendimentoId = $this->findCategoryIdByName('Atendimento', parentId: 0);
        if ($atendimentoId) {
            return $atendimentoId;
        }

        return (int) ($defaultCategory ?: 1);
    }

    /**
     * Subcategoria filha da categoria raiz conforme a área escolhida no chatbot.
     * Busca apenas quando a categoria pai é genérica (ex: "Atendimento"),
     * pois se a categoria raiz já é a da área, subcategoria é opcional.
     */
    private function resolveSubCategoryId(array $payload, int $parentCategoryId): ?int
    {
        $areaKey = (string) data_get($payload, 'area_key', '1');

        $candidates = match ($areaKey) {
            '2' => ['Financeiro'],
            '3' => ['Comercial', 'CRM / Comercial'],
            default => ['Suporte', 'Suporte Técnico'],
        };

        foreach ($candidates as $name) {
            $id = $this->findCategoryIdByName($name, parentId: $parentCategoryId);

            if ($id) {
                return $id;
            }
        }

        return null;
    }

    private function findCategoryIdByName(string $name, ?int $parentId = null): ?int
    {
        $query = Category::query()
            ->select('solutions_category.category_id')
            ->join(
                'solutions_category_description',
                'solutions_category.category_id',
                '=',
                'solutions_category_description.category_id'
            )
            ->whereRaw('LOWER(solutions_category_description.name) = ?', [mb_strtolower($name)]);

        if ($parentId !== null) {
            $query->where('solutions_category.parent_id', $parentId);
        }

        $id = $query->value('solutions_category.category_id');

        return $id ? (int) $id : null;
    }

    /**
     * Mapeia a área escolhida pelo cliente no menu do bot (area_key) para
     * um department_id. Resultado é apenas UM dos sinais consumidos pelo
     * TicketDepartmentResolver — a categoria do chamado pode sobrepor.
     */
    private function resolveChannelDepartmentId(array $payload): ?int
    {
        $areaKey = (string) data_get($payload, 'area_key', '1');
        $areaLabel = (string) data_get($payload, 'area_label', '');

        $searchNames = match ($areaKey) {
            '2' => ['Financeiro'],
            '3' => ['Comercial', 'CRM / Comercial'],
            default => ['Suporte Técnico', 'Suporte'],
        };

        $namesToTry = array_merge(
            $searchNames,
            $areaLabel !== '' ? [$areaLabel] : [],
        );

        $departmentId = null;

        foreach ($namesToTry as $name) {
            // Match exato primeiro — determinístico se existir
            $departmentId = Department::query()
                ->where('name', $name)
                ->orderByDesc('id')
                ->value('id');

            if ($departmentId) {
                return (int) $departmentId;
            }

            // Fallback fuzzy: pega o registro mais recente para evitar
            // ambiguidade quando há vários departments com nomes parecidos
            $departmentId = Department::query()
                ->where('name', 'like', "%{$name}%")
                ->orderByDesc('id')
                ->value('id');

            if ($departmentId) {
                return (int) $departmentId;
            }
        }

        $configured = data_get(config('whatsapp.chatbot.routing_departments', []), $areaKey);

        if ($configured && Department::query()->whereKey((int) $configured)->exists()) {
            return (int) $configured;
        }

        return null;
    }

    private function buildObservation(WhatsAppConversation $conversation, array $payload): string
    {
        $parts = [
            'Chamado aberto via WhatsApp.',
            "Número: {$conversation->phone}",
        ];

        if (data_get($payload, 'company_unidentified')) {
            $parts[] = 'Cliente não identificado automaticamente; cadastro mínimo coletado no bot.';
        }

        if ($cnpj = data_get($payload, 'company_cnpj')) {
            $parts[] = "CNPJ informado: {$cnpj}";
        }

        if ($phone = data_get($payload, 'company_phone')) {
            $parts[] = "Telefone informado: {$phone}";
        }

        return implode(' ', $parts);
    }

    /**
     * Envia notificação push ao agente designado via sistema interno.
     */
    private function notifyTicketQueue(Ticket $ticket): void
    {
        $recipients = $ticket->agent_id
            ? User::query()->whereKey($ticket->agent_id)->get()
            : User::query()
                ->where('ticketit_agent', true)
                ->when($ticket->department_id, fn ($query) => $query->where('department_id', $ticket->department_id))
                ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            $ticketUrl = rescue(
                fn () => route('agent.ticket.show', $ticket->id),
                fn () => url('/agent/ticket/'.$ticket->id),
                false
            );

            foreach ($recipients as $recipient) {
                $this->ticketRepository->createSystemNotification([
                    'user_id' => $recipient->id,
                    'content' => "📱 Novo chamado via WhatsApp: #{$ticket->id} — {$ticket->contact}",
                    'action' => $ticketUrl,
                    'image' => '',
                    'status' => 1,
                ]);

                Cache::forget("user_recent_notifications_{$recipient->id}");
            }
        } catch (\Throwable $e) {
            Log::warning('[WhatsApp] Falha ao criar notificação para fila/agente.', [
                'ticket_id' => $ticket->id,
                'agent_id' => $ticket->agent_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cria registros de Attachment para cada mídia coletada durante o chatbot.
     */
    private function attachMediaFiles(Ticket $ticket, array $payload): void
    {
        $attachments = data_get($payload, 'attachments', []);

        foreach ($attachments as $mediaData) {
            $path = data_get($mediaData, 'path');

            if (! $path || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $size = Storage::disk('public')->size($path);
            $mime = data_get($mediaData, 'mime_type', 'application/octet-stream');
            $originalName = data_get($mediaData, 'original_filename') ?: basename($path);

            $this->ticketRepository->createAttachment([
                'name' => basename($path),
                'original_name' => $originalName,
                'mime' => $mime,
                'disk_path' => $path,
                'size' => $size,
                'author_id' => $ticket->author_id,
                'ticket_id' => $ticket->id,
                'status' => 1,
            ]);
        }
    }
}
