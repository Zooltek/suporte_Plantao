<?php

namespace App\Http\Controllers\Crm;

use App\Enums\FeedbackStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\FeedbackCancelRequest;
use App\Http\Requests\Crm\FeedbackStoreRequest;
use App\Models\Crm\Feedback;
use App\Models\Crm\Feedback\ElementType;
use App\Models\Crm\Feedback\Form;
use App\Models\Customer;
use App\Models\Ticket\Ticket;
use App\Models\UserRating;
use App\Services\Crm\FeedbackService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function __construct(
        protected FeedbackService $feedbackService
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('crm.index');
    }

    /**
     * Exibe o formulário de novo feedback.
     */
    public function create(Request $request): View|RedirectResponse
    {
        try {
            $viewData = $this->feedbackService->prepareCreateViewData($request->all());

            if (isset($viewData['isEmpty']) && $viewData['isEmpty']) {
                 return view('crm.feedback.empty');
            }

            return view('crm.feedback.create', $viewData);

        } catch (Exception $e) {
            Log::error('Erro ao abrir formulário de feedback: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('crm.index')
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Armazena um novo feedback ou finaliza um pendente.
     */
    public function store(FeedbackStoreRequest $request): View|RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->feedbackService->createFeedback(
                $validated,
                $this->currentUserId()
            );

            return view('crm.feedback.success', [
                'redirectUrl' => route('crm.index', [
                    'form_id' => (int) ($validated['form_id'] ?? 1),
                    'tab' => 'concluidos',
                ]),
            ]);

        } catch (Exception $e) {
            Log::error("Erro ao processar feedback: " . $e->getMessage(), [
                'user_id' => $this->currentUserId(),
                'trace'   => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Adia/cancela um feedback com motivo.
     */
    public function cancel(FeedbackCancelRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data) {
                $feedback = !empty($data['feedback_id'])
                    ? Feedback::query()->findOrFail((int) $data['feedback_id'])
                    : new Feedback();

                $feedback->customer_id = (int) $data['customer_id'];
                $feedback->form_id = (int) $data['form_id'];
                $feedback->user_id = $feedback->user_id ?: $this->currentUserId();
                $feedback->status = FeedbackStatus::CANCELED->value;
                $feedback->cancel_content = $data['cancel_content'];
                $feedback->completed_at = $feedback->completed_at ?: now();
                $feedback->save();

                UserRating::query()
                    ->where('feedback_id', $feedback->id)
                    ->delete();
            });

            return redirect()
                ->route('crm.index')
                ->with('success', 'Feedback adiado com sucesso.');
        } catch (Exception $e) {
            Log::error('Erro ao adiar feedback: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Não foi possível adiar o feedback.']);
        }
    }

    /**
     * Exibe a tela de retorno de um feedback finalizado.
     */
    public function retorno(int $id): View|RedirectResponse
    {
        try {
            $feedback = Feedback::query()
                ->with(['customer', 'elements.type'])
                ->findOrFail($id);

            return view('crm.feedback.retorno', [
                'feedback' => $feedback,
            ]);
        } catch (Exception $e) {
            Log::error("Erro ao abrir retorno do feedback {$id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('crm.index')
                ->withErrors(['error' => 'Não foi possível abrir o retorno deste feedback.']);
        }
    }

    /**
     * Salva o retorno de um feedback.
     */
    public function retornoUpdate(Request $request, int $id): View|RedirectResponse
    {
        $data = $request->validate([
            'return' => ['required', 'string', 'max:5000'],
        ]);
        $redirectUrl = route('crm.index', ['tab' => 'concluidos']);

        try {
            DB::transaction(function () use ($id, $data) {
                $feedback = Feedback::query()->findOrFail($id);
                $feedback->return_content = $data['return'];
                $feedback->return_author_id = $this->currentUserId();
                $feedback->return_at = now();
                $feedback->save();
            });

            $feedback = Feedback::query()->findOrFail($id);
            $redirectUrl = route('crm.index', [
                'form_id' => (int) $feedback->form_id,
                'tab' => 'concluidos',
            ]);

            return view('crm.feedback.success', [
                'redirectUrl' => $redirectUrl,
            ]);
        } catch (Exception $e) {
            Log::error("Erro ao salvar retorno do feedback {$id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Não foi possível salvar o retorno.']);
        }
    }

    /**
     * Cancela um feedback existente.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->feedbackService->cancelFeedback($id);

            return redirect()
                ->route('crm.index')
                ->with('success', 'Feedback cancelado com sucesso.');

        } catch (Exception $e) {
            Log::error("Erro ao cancelar feedback {$id}: " . $e->getMessage());

            return back()->withErrors(['error' => 'Não foi possível cancelar o feedback. Tente novamente.']);
        }
    }

    /**
     * Retorna o histórico de feedbacks de um cliente (HTML parcial).
     */
    public function history(int $customerId): View
    {
        $recentFeedbacks = Feedback::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('crm.feedback.partials.history-list', [
            'recentFeedbacks' => $recentFeedbacks,
        ]);
    }

    private function currentUserId(): int
    {
        return (int) (Auth::guard('admin')->id() ?? Auth::id());
    }
}
