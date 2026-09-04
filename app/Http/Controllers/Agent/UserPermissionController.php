<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Agent\Settings\UpdateUserPermissionsRequest;
use App\Services\Agent\UserPermissionService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserPermissionController extends Controller
{
    public function __construct(
        private readonly UserPermissionService $permissionService
    ) {}

    public function index(): View
    {
        $users = User::where('active', true)
            ->orderBy('name')
            ->paginate(15);

        return view('agent.settings.user-permissions.index', compact('users'));
    }

    public function edit(User $user): View
    {
        return view('agent.settings.user-permissions.edit', compact('user'));
    }

    public function update(UpdateUserPermissionsRequest $request, User $user): RedirectResponse
    {
        try {
            $this->permissionService->updatePermissions($user, $request->validated());

            return redirect()
                ->route('agent.settings.user-permissions.index')
                ->with('success', "Permissões de implantação de '{$user->name}' atualizadas com sucesso!");

        } catch (Throwable $e) {
            Log::error("Erro ao atualizar permissões do usuário ID {$user->id}: " . $e->getMessage());

            return back()->with('error', 'Erro interno ao atualizar permissões. Tente novamente.');
        }
    }
}