<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\NotificationService;
use App\Http\Requests\NotificationUpdateRequest;
use App\Http\Requests\NotificationSendRequest;
use App\Http\Requests\NotificationBlinkRequest;
use App\Models\User;
use App\Models\Helpdesk\Ticketit\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
        $this->middleware('auth');
    }

    /**
     * Interface administrativa de notificações.
     */
    public function index(): View
    {
        $administrators = Agent::admins()->get();
        $users = User::all();

        return view('admin.notification', compact('administrators', 'users'));
    }

    /**
     * Lista de notificações do próprio usuário.
     */
    public function notifications(): View
    {
        $notifications = $this->notificationService->getRecentForUser(Auth::id());

        return view('helpdesk.notifications.index', compact('notifications'));
    }

    /**
     * Envia notificações (Ação Admin).
     */
    public function send(NotificationSendRequest $request): RedirectResponse
    {
        $count = $this->notificationService->sendToGroup(
            $request->group,
            $request->validated()
        );

        return redirect('admin/notification')
            ->with('status', "{$count} notificações enviadas com sucesso.");
    }

    /**
     * Endpoint para atualização via AJAX (Blink/Top bar).
     */
    public function blink(NotificationBlinkRequest $request)
    {
        // No L12 usamos o ID do Request validado ou Auth::id() por segurança
        return response()->json(
            $this->notificationService->getRecentForUser($request->user)
        );
    }

    /**
     * Atualiza status para lido.
     */
    public function update(NotificationUpdateRequest $request): void
    {
        $this->notificationService->markAllAsRead($request->user_id);
    }
}
