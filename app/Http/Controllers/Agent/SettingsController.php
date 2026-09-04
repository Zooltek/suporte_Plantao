<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\Settings\AgentSettingsUpdate;
use App\Models\User\Setting;
use App\Services\Agent\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Configurações de conta do Agente.
 * Controller magro — delega toda lógica à SettingsService (SRP).
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    public function index(): View
    {
        return view('agent.settings.index', [
            'user'     => Auth::user(),
            'settings' => Setting::where('user_id', Auth::id())
                ->get()
                ->pluck('value', 'slug')
                ->toArray(),
        ]);
    }

    public function update(AgentSettingsUpdate $request): RedirectResponse
    {
        try {
            $this->settingsService->updateProfileAndPreferences(
                Auth::user(),
                $request->validated()
            );

            return redirect()->route('agent.settings')->with('status', 'Conta atualizada com sucesso');
        } catch (Throwable) {
            return redirect()->route('agent.settings')->with('error', 'Ocorreu um erro inesperado ao salvar as configurações.');
        }
    }
}
