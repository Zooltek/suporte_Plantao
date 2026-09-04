<?php

namespace Database\Factories\Tasks;

use App\Models\Tasks\Label;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabelFactory extends Factory
{
    protected $model = Label::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->unique()->words(2, true),
            'user_id'      => null,
            'parent_id'    => null,
            'department_id'=> null,
            'is_active'    => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function childOf(int $parentId): self
    {
        return $this->state(fn () => [
            'parent_id' => $parentId,
        ]);
    }
}
