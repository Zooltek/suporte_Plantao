<?php

namespace Database\Factories\Crm;

use App\Models\Crm\Feedback;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::query()->inRandomOrder()->value('id') ?? 1,
            'customer_id'  => Customer::query()->inRandomOrder()->value('id'),
            'status'       => $this->faker->boolean(80),
            'completed_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
