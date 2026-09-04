<?php

namespace Database\Factories\Crm\Feedback;

use App\Models\Crm\Feedback\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        return [
            'name' => 'Formulário ' . $this->faker->unique()->word(),
        ];
    }
}
