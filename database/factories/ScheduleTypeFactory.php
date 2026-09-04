<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ScheduleType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleTypeFactory extends Factory
{
    protected $model = ScheduleType::class;

    public function definition(): array
    {
        return [
            'slug'              => $this->faker->unique()->slug(2),
            'label'             => $this->faker->words(2, true),
            'requires_customer' => false,
            'is_system'         => false,
            'is_active'         => true,
            'sort_order'        => $this->faker->numberBetween(100, 999),
        ];
    }

    public function requiresCustomer(): self
    {
        return $this->state(fn () => ['requires_customer' => true]);
    }

    public function system(): self
    {
        return $this->state(fn () => ['is_system' => true]);
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
