<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\ProfileService;
use App\Http\Requests\ProfileChangePasswordRequest;
use App\Http\Requests\ProfileFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {
        $this->middleware('auth');
    }

    public function index(): View
    {
        return view('helpdesk.profile.edit', [
            'user' => Auth::user()
        ]);
    }

    public function update(ProfileFormRequest $request): RedirectResponse
    {
        $this->profileService->updateProfile(
            Auth::user(),
            $request->validated(),
            $request->file('file')
        );

        return redirect()->route('profile')
            ->with('status', 'Perfil alterado com sucesso!');
    }

    public function changePassword(ProfileChangePasswordRequest $request): RedirectResponse
    {
        $result = $this->profileService->changePassword(
            Auth::user(),
            $request->old_password,
            $request->password
        );

        $type = $result['success'] ? 'status' : 'warning';
        
        return redirect()->route('profile')->with($type, $result['message']);
    }
}
