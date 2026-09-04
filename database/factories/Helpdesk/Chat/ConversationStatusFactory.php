<?php

namespace Database\Factories\Helpdesk\Chat;

use App\Models\Helpdesk\Chat\ConversationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationStatusFactory extends Factory
{
    protected $model = ConversationStatus::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Aberto',
                'Em Atendimento',
                'Aguardando Cliente',
                'Fechado',
            ]),
        ];
    }
}
