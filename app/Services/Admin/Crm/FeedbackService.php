<?php

namespace App\Services\Admin\Crm;

use App\Contracts\Repositories\FeedbackRepositoryInterface;
use App\Models\Crm\Feedback\ElementType;
use App\Models\Crm\Feedback\Form;
use Illuminate\Database\Eloquent\Collection;

class FeedbackService
{
    public function __construct(
        private readonly FeedbackRepositoryInterface $feedbackRepository,
    ) {}

    /**
     * Obtém todos os formulários de feedback cadastrados.
     */
    public function getAllForms(): Collection
    {
        return $this->feedbackRepository->getAllForms();
    }

    /**
     * Obtém um formulário específico com seus elementos.
     */
    public function getFormWithElements(int $formId): ?Form
    {
        return $this->feedbackRepository->findForm($formId);
    }

    /**
     * Obtém os elementos de um formulário específico.
     */
    public function getFormElements(?int $formId = null): Collection
    {
        return $this->feedbackRepository->getFormElements($formId);
    }

    /**
     * Cria um novo formulário de feedback.
     */
    public function createForm(array $data): Form
    {
        return $this->feedbackRepository->createForm($data);
    }

    /**
     * Atualiza um formulário existente.
     */
    public function updateForm(Form $form, array $data): Form
    {
        return $this->feedbackRepository->updateForm($form, $data);
    }

    /**
     * Cria um novo elemento de feedback.
     */
    public function createElement(array $data): ElementType
    {
        return $this->feedbackRepository->createFormElement($data);
    }

    /**
     * Atualiza um elemento de feedback.
     */
    public function updateElement(ElementType $element, array $data): ElementType
    {
        return $this->feedbackRepository->updateFormElement($element, $data);
    }

    /**
     * Remove um elemento de feedback.
     */
    public function deleteElement(ElementType $element): bool
    {
        return $this->feedbackRepository->deleteFormElement($element);
    }

    /**
     * Obtém estatísticas dos formulários.
     */
    public function getStats(): array
    {
        return $this->feedbackRepository->getFeedbackFormStats();
    }
}
