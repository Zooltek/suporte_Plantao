<?php

namespace Database\Factories\Crm\Feedback;

use App\Models\Crm\Feedback;
use App\Models\Crm\Feedback\Element;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Crm\Feedback\Element>
 */
class ElementFactory extends Factory
{

    protected $model = Element::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
             'feedback_id' => Feedback::factory(),
            'element_id' => $this->faker->numberBetween(1, 10),
            'value' => $this->faker->sentence(),
        ];
    }
}
