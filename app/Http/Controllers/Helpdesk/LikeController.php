<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\SolutionService;
use App\Http\Requests\LikeFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function __construct(
        protected SolutionService $solutionService
    ) {
        $this->middleware('auth');
    }

    /**
     * Armazena a avaliação do usuário.
     */
    public function store(LikeFormRequest $request, int $userId, int $solutionId): JsonResponse
    {
        // Segurança extra: garante que o usuário logado é quem está enviando o like
        if (Auth::id() !== $userId) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        $result = $this->solutionService->toggleLike(
            $userId,
            $solutionId,
            (int) $request->input('like')
        );

        $status = $result['success'] ? 'status' : 'warning';
        session()->flash($status, $result['message']);

        return response()->json($result);
    }
}
