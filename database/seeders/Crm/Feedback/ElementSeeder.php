<?php

namespace Database\Seeders\Crm\Feedback;

use App\Models\Crm\Feedback\Element;
use App\Models\Crm\Feedback;
use Illuminate\Database\Seeder;

class ElementSeeder extends Seeder
{
    public function run(): void
    {
        // Cria feedback base
        $feedback = Feedback::factory()->create();

        // Elementos fake vinculados ao feedback
        Element::factory()
            ->count(20)
            ->create([
                'feedback_id' => $feedback->id,
            ]);

        // Registro fixo (respeitando FK)
        Element::updateOrCreate(
            [
                'feedback_id' => $feedback->id,
                'element_id'  => 1,
            ],
            [
                'value' => 'Muito satisfeito',
            ]
        );

        $this->command->info('Elementos criados com sucesso!');
    }
}
