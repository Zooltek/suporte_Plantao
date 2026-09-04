<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppBotMessage;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppMessageMacro;
use App\Models\WhatsApp\WhatsAppSetting;
use App\Services\WhatsApp\EvolutionInstanceService;
use App\Services\WhatsApp\WhatsAppBotMessageService;
use App\Services\WhatsApp\WhatsAppBotReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Painel de configuração e monitoramento WhatsApp no Admin.
 */
class WhatsAppController extends Controller
{
    public function __construct(
        private readonly WhatsAppBotMessageService $botMessageService,
        private readonly WhatsAppBotReleaseService $botReleaseService,
    ) {}

    public function index(): View
    {
        $stats = [
            'total_conversations' => WhatsAppConversation::count(),
            'active' => WhatsAppConversation::whereNotIn('state', ['completed', 'cancelled'])->count(),
            'completed' => WhatsAppConversation::where('state', 'completed')->count(),
            'tickets_created' => WhatsAppConversation::whereNotNull('ticket_id')->count(),
        ];

        $recentConversations = WhatsAppConversation::with('ticket')
            ->withCount('messages')
            ->latest()
            ->limit(20)
            ->get();

        // Chamados abertos via WhatsApp (origin_id = 5)
        $whatsappTickets = Ticket::where('origin_id', config('whatsapp.chatbot.origin_id', 5))
            ->with(['agent', 'company', 'priority'])
            ->latest('created_at')
            ->limit(50)
            ->get();

        // Status carregado separado (coluna "status" na tabela conflita com a relação)
        $statusMap = Status::all()->keyBy('id');

        $config = [
            'enabled' => config('whatsapp.enabled'),
            'provider' => config('whatsapp.provider'),
            'from_number' => config('whatsapp.from_number'),
            'local_test_numbers' => config('whatsapp.local_test_numbers', []),
            'api_url' => config('whatsapp.api_url') ? '✓ Configurado' : '✗ Não configurado',
            'api_token' => config('whatsapp.api_token') ? '✓ Configurado' : '✗ Não configurado',
            'webhook_url' => config('whatsapp.webhook_url') ?: url('api/webhook/whatsapp'),
        ];

        $botMessages = $this->botMessageService->editableMessages();
        $macros = WhatsAppMessageMacro::query()->with('department')->orderBy('command')->get();
        $departments = Department::query()->orderBy('name')->get();
        $whatsappSettings = [
            'business_hours_start' => $this->botMessageService->setting('business_hours_start', '08:30'),
            'business_hours_end' => $this->botMessageService->setting('business_hours_end', '18:00'),
            'business_days' => $this->botMessageService->setting('business_days', '1,2,3,4,5'),
            'out_of_hours_cooldown_minutes' => $this->botMessageService->setting('out_of_hours_cooldown_minutes', '120'),
            'ticket_closed_delay_minutes' => $this->botMessageService->setting('ticket_closed_delay_minutes', (string) config('whatsapp.chatbot.ticket_closed_delay_minutes', '10')),
        ];

        return view('admin.whatsapp.index', compact('stats', 'recentConversations', 'config', 'whatsappTickets', 'statusMap', 'botMessages', 'macros', 'departments', 'whatsappSettings'));
    }

    /**
     * Lista resumida das conversas mais recentes em formato JSON.
     * Consumido pelo polling do painel admin para reconciliar novas conversas
     * sem precisar recarregar a página (F5).
     */
    public function recentConversations(): JsonResponse
    {
        $conversations = WhatsAppConversation::query()
            ->with('ticket:id')
            ->withCount('messages')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (WhatsAppConversation $conv): array => [
                'id' => $conv->id,
                'phone' => $conv->phone,
                'state' => $conv->state->value,
                'state_label' => $conv->state->label(),
                'ticket_id' => $conv->ticket_id,
                'messages_count' => $conv->messages_count,
                'last_activity_at' => $conv->last_activity_at?->toIso8601String(),
                'last_activity_human' => $conv->last_activity_at?->diffForHumans(),
                'show_url' => route('admin.whatsapp.show', $conv->id),
                'ticket_url' => $conv->ticket_id ? route('agent.ticket.show', $conv->ticket_id) : null,
            ]);

        return response()->json(['conversations' => $conversations]);
    }

    public function show(WhatsAppConversation $conversation): View
    {
        $conversation->load(['messages' => fn ($q) => $q->orderBy('created_at'), 'ticket', 'company']);

        return view('admin.whatsapp.show', compact('conversation'));
    }

    public function messages(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $afterId = $request->integer('after_id');

        $messages = $conversation->messages()
            ->with('user')
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('created_at')
            ->get()
            ->map(fn (WhatsAppMessage $message): array => $this->serializeMessage($message))
            ->values();

        return response()->json([
            'messages' => $messages,
            'messages_count' => $conversation->messages()->count(),
        ]);
    }

    /**
     * Retorna o estado de conexão atual da instância Evolution-API.
     * Consumido via AJAX pelo painel admin (Alpine.js).
     */
    public function connectionState(EvolutionInstanceService $service): JsonResponse
    {
        if (config('whatsapp.provider') !== 'evolution') {
            return response()->json(['state' => 'not_applicable', 'instance' => null]);
        }

        if (! $service->isConfigured()) {
            return response()->json(['state' => 'not_configured', 'instance' => null]);
        }

        return response()->json($service->connectionState());
    }

    /**
     * Gera e retorna o QR Code da instância Evolution-API.
     * Consumido via AJAX pelo painel admin (Alpine.js).
     */
    public function qrCode(EvolutionInstanceService $service): JsonResponse
    {
        if (config('whatsapp.provider') !== 'evolution') {
            return response()->json(['error' => 'Provedor não é Evolution-API.'], 400);
        }

        if (! $service->isConfigured()) {
            return response()->json(['error' => 'Evolution-API não configurada no .env.'], 400);
        }

        try {
            return response()->json($service->fetchQrCode());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Faz logout da instância Evolution-API (limpa sessão corrompida).
     * Consumido via AJAX pelo painel admin (Alpine.js).
     */
    public function logout(EvolutionInstanceService $service): JsonResponse
    {
        if (config('whatsapp.provider') !== 'evolution') {
            return response()->json(['error' => 'Provedor não é Evolution-API.'], 400);
        }

        if (! $service->isConfigured()) {
            return response()->json(['error' => 'Evolution-API não configurada no .env.'], 400);
        }

        $result = $service->logout();

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Libera a conversa de volta ao bot (desativa o modo human_pending).
     *
     * O agente clica em "Liberar bot" após terminar o atendimento manual.
     * A conversa volta ao GREETING para que a próxima mensagem reapresente o menu.
     */
    public function release(WhatsAppConversation $conversation): RedirectResponse
    {
        if (! $this->botReleaseService->releaseManually($conversation)) {
            return back()->with('warning', 'A conversa não está em modo de atendimento humano.');
        }

        return back()->with('success', 'Bot reativado. O cliente voltará ao menu automaticamente na próxima mensagem.');
    }

    /**
     * Pausa o bot e assume o atendimento manual da conversa.
     */
    public function pause(WhatsAppConversation $conversation): RedirectResponse
    {
        if (! $this->botReleaseService->pauseManually($conversation)) {
            return back()->with('warning', 'A conversa já está em modo de atendimento humano.');
        }

        return back()->with('success', 'Bot pausado. Atendimento humano assumido.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_hours_start' => ['required', 'date_format:H:i'],
            'business_hours_end' => ['required', 'date_format:H:i'],
            'business_days' => ['required', 'regex:/^[1-7](,[1-7])*$/'],
            'out_of_hours_cooldown_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'ticket_closed_delay_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
        ]);

        foreach ($validated as $key => $value) {
            WhatsAppSetting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return back()->with('success', 'Configurações de expediente e automação atualizadas.');
    }

    public function saveBotMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100'],
            'step' => ['required', 'string', 'max:100'],
            'text' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        WhatsAppBotMessage::query()->updateOrCreate(
            ['key' => $validated['key']],
            [
                'step' => $validated['step'],
                'text' => $validated['text'],
                'is_active' => $request->boolean('is_active'),
            ]
        );

        return back()->with('success', 'Mensagem do bot salva.');
    }

    public function saveMacro(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'max:80', 'regex:/^\/[a-z0-9._-]+$/i'],
            'text' => ['required', 'string', 'max:5000'],
            'department_id' => ['nullable', 'integer', 'exists:user_department,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        WhatsAppMessageMacro::query()->updateOrCreate(
            ['command' => mb_strtolower($validated['command'])],
            [
                'text' => $validated['text'],
                'department_id' => $validated['department_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return back()->with('success', 'Atalho salvo.');
    }

    public function deleteMacro(WhatsAppMessageMacro $macro): RedirectResponse
    {
        $macro->delete();

        return back()->with('success', 'Atalho removido.');
    }

    private function serializeMessage(WhatsAppMessage $message): array
    {
        return [
            'id' => $message->id,
            'direction' => $message->direction,
            'type' => $message->type,
            'body' => $message->deleted_at ? 'Mensagem excluída.' : (string) $message->body,
            'attachment_url' => $message->attachment_path ? Storage::disk('public')->url($message->attachment_path) : null,
            'mime_type' => $message->mime_type,
            'file_size_label' => $message->file_size ? number_format($message->file_size / 1024, 1).' KB' : null,
            'is_deleted' => $message->deleted_at !== null,
            'user_name' => $message->user?->name,
            'created_at' => $message->created_at?->toIso8601String(),
            'created_at_label' => $message->created_at?->format('H:i'),
        ];
    }
}
