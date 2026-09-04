<?php

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\ConversationState;
use App\Models\WhatsApp\WhatsAppConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsAppConversationFactory extends Factory
{
    protected $model = WhatsAppConversation::class;

    public function definition(): array
    {
        return [
            'phone'            => '55' . fake()->numerify('279########'),
            'state'            => ConversationState::AWAITING_NAME,
            'payload'          => [],
            'company_id'       => null,
            'ticket_id'        => null,
            'last_activity_at' => now(),
            'completed_at'     => null,
        ];
    }

    public function greeting(): static
    {
        return $this->state(['state' => ConversationState::GREETING]);
    }

    public function awaitingName(): static
    {
        return $this->state(['state' => ConversationState::AWAITING_NAME]);
    }

    public function awaitingCompany(): static
    {
        return $this->state([
            'state'   => ConversationState::AWAITING_COMPANY,
            'payload' => ['name' => fake()->name()],
        ]);
    }

    public function awaitingArea(): static
    {
        return $this->state([
            'state'   => ConversationState::AWAITING_AREA,
            'payload' => ['name' => fake()->name(), 'company_name' => fake()->company()],
        ]);
    }

    public function awaitingProblem(): static
    {
        return $this->state([
            'state'   => ConversationState::AWAITING_PROBLEM,
            'payload' => [
                'name'        => fake()->name(),
                'company_name' => fake()->company(),
                'area_key'    => '1',
                'area_label'  => 'Suporte Técnico',
            ],
        ]);
    }

    public function awaitingAttachments(): static
    {
        return $this->state([
            'state'   => ConversationState::AWAITING_ATTACHMENTS,
            'payload' => [
                'name'        => fake()->name(),
                'company_name' => fake()->company(),
                'area_key'    => '1',
                'area_label'  => 'Suporte Técnico',
                'problem'     => fake()->sentence(),
            ],
        ]);
    }

    public function confirming(): static
    {
        return $this->state([
            'state'   => ConversationState::CONFIRMING,
            'payload' => [
                'name'        => fake()->name(),
                'company_name' => fake()->company(),
                'area_key'    => '1',
                'area_label'  => 'Suporte Técnico',
                'problem'     => fake()->sentence(),
                'attachments' => [],
            ],
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'state'        => ConversationState::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function awaitingMenu(): static
    {
        return $this->state(['state' => ConversationState::AWAITING_MENU]);
    }

    public function humanPending(): static
    {
        return $this->state(['state' => ConversationState::HUMAN_PENDING]);
    }

    public function cancelled(): static
    {
        return $this->state(['state' => ConversationState::CANCELLED]);
    }

    public function expired(): static
    {
        return $this->state([
            'state'            => ConversationState::AWAITING_NAME,
            'last_activity_at' => now()->subMinutes(90),
        ]);
    }
}
