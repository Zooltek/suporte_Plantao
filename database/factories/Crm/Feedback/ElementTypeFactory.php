<?php

namespace Database\Factories\Crm\Feedback;

use App\Models\Crm\Feedback\ElementType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElementTypeFactory extends Factory
{
    protected $model = ElementType::class;

    public function definition(): array
    {
        return [
            'label'      => $this->faker->words(2, true),
            'name'       => $this->faker->unique()->slug(2),
            'type'       => $this->faker->randomElement(['text', 'number', 'select', 'checkbox']),
            'data'       => null,
            'sort_order' => 1,
        ];
    }
}
