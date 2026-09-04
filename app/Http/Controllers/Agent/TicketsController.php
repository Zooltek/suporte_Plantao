<?php

namespace App\Http\Controllers\Agent;

use App\Contracts\Repositories\SettingsRepositoryInterface;
use App\Exceptions\TicketBusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\Tickets\CloseTicketRequest;
use App\Http\Requests\Agent\Tickets\QuickUpdateTicketRequest;
use App\Http\Requests\Agent\Tickets\SaveTicketRequest;
use App\Http\Requests\Agent\Tickets\TicketIndexRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Schedule\Module as RatModule;
use App\Models\Ticket\Agent;
use App\Models\Ticket\Attachment;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\Agent\TicketQueryService;
use App\Services\Agent\TicketService;
use App\Services\Agent\TicketShowService;
use App\Services\Agent\TicketTechnicalContextService;
use App\Services\Ticket\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class TicketsController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
        private readonly TicketQueryService $ticketQueryService,
        private readonly TicketShowService $ticketShowService,
        private readonly TicketTechnicalContextService $ticketTechnicalContextService,
        private readonly SettingsRepositoryInterface $settingsRepository,
        private readonly AttachmentService $attachmentService,
    ) {}

    /**
     * Lista chamados com filtros e paginação.
     * A lógica de query foi delegada à TicketQueryService (SRP).
     */
    public function index(TicketIndexRequest $request): View
    {
        $user = Auth::guard('admin')->user();

        $tickets = $this->ticketQueryService->listForUser($user, $request);
        $filterData = $this->ticketQueryService->getFilterData($user, $request);

        return view('agent.ticket.index', array_merge(
            [
                'tickets' => $tickets,
                'settings' => $this->settingsRepository->getForUser((int) $user?->id),
            ],
            $filterData
        ));
    }

    public function create(Request $request): View
    {
        $data = $this->getCommonFormData();
        $data['now'] = now()->toDateTimeString();
        $data['now_c'] = bcrypt($data['now']);
        $data['prefillTicket'] = null;
        $data['canChooseDepartment'] = Auth::guard('admin')->user()?->isAdmin() ?? false;

        if ($request->filled('from_ticket')) {
            $data['prefillTicket'] = Ticket::with(['company', 'category', 'subCategory'])
                ->find($request->integer('from_ticket'));
        }

        return view('agent.ticket.create', $data);
    }

    /**
     * Exibe o detalhe de um ticket (read-only com sidebar de ações).
     * Lógica delegada à TicketShowService (SRP).
     */
    public function show(Ticket $ticket): View
    {
        $user = Auth::guard('admin')->user();
        $this->authorize('view', $ticket);

        $ticket = $this->ticketShowService->loadForDisplay($ticket);
        $comments = $this->ticketShowService->getComments($ticket);
        $attachments = $this->ticketShowService->getAttachments($ticket);
        $context = $this->ticketShowService->getContextData($user);

        // Conversa WhatsApp vinculada ao ticket (se houver)
        $whatsappConversation = WhatsAppConversation::where('ticket_id', $ticket->id)
            ->with(['messages' => fn ($q) => $q->with(['user', 'deletedBy'])->orderBy('created_at')])
            ->latest()
            ->first();

        $webChatConversation = Conversation::where('ticket_id', $ticket->id)
            ->with([
                'owner',
                'status',
                'messages' => fn ($query) => $query->with('owner')->orderBy('created_at'),
            ])
            ->latest()
            ->first();

        return view('agent.ticket.show', array_merge(
            compact('ticket', 'comments', 'attachments', 'whatsappConversation', 'webChatConversation'),
            $context
        ));
    }

    public function edit(Ticket $ticket): View
    {
        $ticket->load(['company', 'category', 'subCategory']);
        $this->authorize('update', $ticket);

        $data = $this->getCommonFormData();
        $data['ticket'] = $ticket;
        $data['attachments'] = Attachment::where('status', 1)->where('ticket_id', $ticket->id)->get();
        $data['token'] = $this->ticketService->requestElapsedToken($ticket);
        $data['prefillTicket'] = null;
        $data['canChooseDepartment'] = Auth::guard('admin')->user()?->can('changeDepartment', $ticket) ?? false;

        return view('agent.ticket.create', $data);
    }

    public function attendancesPartial(Ticket $ticket): View
    {
        $ticket->load([
            'attendances.user',
            'issues' => fn ($query) => $query->orderBy('created_at', 'asc'),
        ]);
        $this->authorize('view', $ticket);

        return view('agent.partials.attendances', compact('ticket'));
    }

    public function store(SaveTicketRequest $request): View|RedirectResponse
    {
        return $this->processSave(new Ticket, $request);
    }

    public function update(SaveTicketRequest $request, Ticket $ticket): View|RedirectResponse
    {
        $this->authorize('update', $ticket);

        return $this->processSave($ticket, $request);
    }

    /**
     * Atualização pontual de status ou agente a partir da tela de detalhe.
     *
     * Aceita apenas os campos status_id ou agent_id, aplicando as regras
     * de negócio mínimas necessárias para cada campo (sem o payload completo
     * do formulário de edição).
     */
    public function quickUpdate(QuickUpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        try {
            if ($request->has('status_id')) {
                $needsSchedule = $this->ticketService->quickUpdateStatus(
                    $ticket,
                    (int) $request->input('status_id')
                );

                if ($needsSchedule) {
                    return redirect()
                        ->route('agent.schedules.create', ['ticket_id' => $ticket->id])
                        ->with('status', "Chamado #{$ticket->id} atualizado. Crie o agendamento para a visita técnica.");
                }
            }

            if ($request->has('agent_id')) {
                $this->ticketService->quickUpdateAgent(
                    $ticket,
                    $request->filled('agent_id') ? (int) $request->input('agent_id') : null
                );
            }

            if ($request->has('department_id')) {
                $this->ticketService->quickUpdateDepartment(
                    $ticket,
                    $request->filled('department_id') ? (int) $request->input('department_id') : null
                );
            }

            if ($request->has('category_id')) {
                $this->ticketService->quickUpdateCategory(
                    $ticket,
                    (int) $request->input('category_id'),
                    $request->filled('sub_category_id') ? (int) $request->input('sub_category_id') : null
                );
            }

            return redirect()
                ->route('agent.ticket.show', $ticket->id)
                ->with('status', "Chamado #{$ticket->id} atualizado.");

        } catch (TicketBusinessException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    /**
     * Encerra um chamado e devolve o usuário para a fila de pendências.
     */
    public function close(CloseTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        try {
            $closedTicket = $this->ticketService->closeTicket(
                $ticket,
                (int) $request->input('status_id'),
                $request->input('solution')
            );

            $statusName = $closedTicket->status()->value('name') ?? 'Encerrado';
            $message = sprintf(
                'Chamado #%d encerrado como %s. Você foi redirecionado para a fila de pendências.',
                $closedTicket->id,
                $statusName
            );

            return redirect()
                ->route('agent.calendar.condensed', ['active' => 'pending'])
                ->with('status', $message);
        } catch (TicketBusinessException $e) {
            return back()
                ->withInput()
                ->with('warning', $e->getMessage())
                ->with('open_close_ticket', true);
        }
    }

    /**
     * Captura um chamado para o usuário logado.
     */
    public function capture(Request $request): JsonResponse|RedirectResponse
    {
        $ticket = Ticket::findOrFail((int) ($request->route('id') ?? $request->input('ticket_id')));
        $user = Auth::guard('admin')->user();
        abort_unless($user instanceof \App\Models\User, 403);

        $this->authorize('view', $ticket);

        try {
            $this->ticketService->captureTicket($ticket, $user);
        } catch (TicketBusinessException $e) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }

            return back()->with('warning', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['agent_id' => $user->id]);
        }

        return redirect()
            ->route('agent.ticket.show', $ticket->id)
            ->with('status', "Chamado #{$ticket->id} capturado com sucesso.");
    }

    /**
     * Devolve um chamado capturado para a fila de pendências aberta:
     * remove o agente responsável e reverte o status para Pendente.
     *
     * Permitido para o agente atualmente atribuído e para administradores.
     * Não pode ser executado em chamados em status terminal.
     */
    public function release(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        if (Status::isTerminal((int) $ticket->status_id)) {
            return back()->with('warning', 'Chamados encerrados não podem ser devolvidos à fila.');
        }

        if (! $ticket->hasAssignedAgent()) {
            return back()->with('warning', 'Este chamado já está sem responsável.');
        }

        try {
            $this->ticketService->quickUpdateAgent($ticket, null);
        } catch (TicketBusinessException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('agent.calendar.condensed', ['active' => 'pending'])
            ->with('status', "Chamado #{$ticket->id} devolvido à fila de pendências.");
    }

    /**
     * Remove um chamado (Apenas Admin).
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user->can('delete', $ticket), 403, 'Apenas administradores podem excluir chamados.');

        $ticket->delete();

        return redirect()->route('agent.index');
    }

    /**
     * Centraliza a chamada ao serviço e o tratamento visual de erros.
     */
    private function processSave(Ticket $ticket, SaveTicketRequest $request): View|RedirectResponse
    {
        $isNew = ! $ticket->exists;

        try {
            $user = Auth::guard('admin')->user();

            $savedTicket = $this->ticketService->saveTicket(
                $ticket,
                $request->validated(),
                $user
            );

            $this->uploadAttachments($request, (int) $savedTicket->id);

            return $this->redirectAfterSave($savedTicket, $isNew);

        } catch (TicketBusinessException|AccessDeniedHttpException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Ticket System Error', [
                'message' => $e->getMessage(),
                'exception' => $e,
                'route' => $request->path(),
                'host' => $request->getHost(),
                'ip' => $request->ip(),
                'ticket_id' => $ticket->exists ? $ticket->id : null,
                'user_id' => Auth::guard('admin')->id(),
            ]);

            return back()->withInput()->with('error', 'Ocorreu um erro interno. Tente novamente mais tarde.');
        }
    }

    /**
     * Salva os arquivos enviados via campo `arquivo[]` do formulário,
     * delegando ao AttachmentService o upload e o vínculo com o ticket.
     */
    private function uploadAttachments(SaveTicketRequest $request, int $ticketId): void
    {
        $files = $request->file('arquivo', []);

        if (! is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $this->attachmentService->upload($file, $ticketId);
        }
    }

    /**
     * Após salvar, redireciona para:
     *   - Visita Técnica → criação de agendamento
     *   - Demais casos  → Lista de Chamados com mensagem de feedback
     */
    private function redirectAfterSave(Ticket $ticket, bool $isNew): RedirectResponse
    {
        $statusId = (int) $ticket->status_id;
        $needsSchedule = Status::requiresSchedule($statusId) && ! Status::isTerminal($statusId);

        if ($needsSchedule) {
            return redirect()
                ->route('agent.schedules.create', ['ticket_id' => $ticket->id])
                ->with('status', "Chamado #{$ticket->id} salvo. Crie o agendamento para a visita técnica.");
        }

        $message = $isNew
            ? "Chamado #{$ticket->id} criado com sucesso."
            : "Chamado #{$ticket->id} atualizado com sucesso.";

        return redirect()
            ->route('agent.ticket.index')
            ->with('status', $message);
    }

    private function getCommonFormData(): array
    {
        $companies = Company::with([
            'group',
            'software',
            'moduleTypes.ratModule' => fn ($query) => $query->withCount('elementTypes'),
            'scheduleModules' => fn ($query) => $query->withCount('elementTypes'),
        ])
            ->orderBy('trade_name', 'asc')
            ->get();

        $ratModules = RatModule::query()
            ->withCount('elementTypes')
            ->has('elementTypes')
            ->orderBy('project')
            ->orderBy('name')
            ->get();

        $technicalCatalog = $this->ticketTechnicalContextService->buildForCompanies($companies);

        return [
            'companies' => $companies,
            'ratModules' => $ratModules,
            'companyTechnicalContexts' => $technicalCatalog['contexts'],
            'ticketVersionCatalog' => $technicalCatalog['versionCatalog'],
            'origins' => Origin::all(),
            'statuses' => Status::manualDecisionOptions(),
            'agents' => Agent::active()->where('ticketit_agent', '!=', 0)
                ->with('department:id,name')
                ->orderBy('name', 'asc')->get(),
            'task_agents' => Agent::active()->orderBy('name', 'asc')->get(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()
                ->with('description')
                ->root()
                ->orderedByDisplayName()
                ->get(),
        ];
    }
}
