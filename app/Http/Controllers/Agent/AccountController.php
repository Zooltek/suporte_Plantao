<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\Settings\AgentSettingsUpdate;
use App\Models\User\Setting;
use App\Services\Agent\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class AccountController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    public function index(): View
    {
        return view('agent.account.index', [
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

            return redirect()->route('agent.account')->with('status', 'Conta atualizada com sucesso.');
        } catch (Throwable) {
            return redirect()->route('agent.account')->with('error', 'Ocorreu um erro inesperado ao salvar as configurações.');
        }
    }
}
