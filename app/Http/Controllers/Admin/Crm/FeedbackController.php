<?php

namespace App\Http\Controllers\Admin\Crm;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use App\Models\Crm\Feedback\ElementType;
use App\Models\Crm\Feedback\Form;
use App\Http\Controllers\Controller;
use App\Services\Admin\Crm\FeedbackService;

class FeedbackController extends Controller
{
    public function __construct(
        protected FeedbackService $feedbackService
    ) {}

    public function index(Request $request): View
    {
        $forms = $this->feedbackService->getAllForms();
        $selectedFormId = $request->integer('form_id');
        if (!$selectedFormId && $forms->isNotEmpty()) {
            $selectedFormId = (int) $forms->first()->id;
        }
        $element_types = $this->feedbackService->getFormElements($selectedFormId);

        $stats = $this->feedbackService->getStats();

        return view('admin.crm.feedback.index', compact('element_types', 'forms', 'stats', 'selectedFormId'));
    }

    public function store(Request $request): Response
    {
        return response()->noContent();
    }

    public function show(int $id): Response
    {
        return response()->noContent();
    }

    public function update(Request $request, int $id): Response
    {
        return response()->noContent();
    }

    public function destroy(int $id): Response
    {
        return response()->noContent();
    }
}
