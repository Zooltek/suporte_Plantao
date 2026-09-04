<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\ScheduleType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $start_at = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end_at = Carbon::parse($start_at)->addHours(rand(1, 3));

        $kind = $this->faker->randomElement([
            Schedule::KIND_CLIENT,
            Schedule::KIND_MEETING,
            Schedule::KIND_INTERNAL,
        ]);
        $scheduleTypeId = ScheduleType::query()->where('slug', $kind)->value('id');

        return [
            'customer_id'      => \App\Models\Customer::inRandomOrder()->value('id') ?? \App\Models\Customer::factory(),
            'agent_id'         => User::where('ticketit_agent', true)->inRandomOrder()->value('id') ?: User::factory()->agent(),
            'module_id'        => \App\Models\Schedule\Module::firstOrCreate(['name' => 'Implantação'])->id,
            'title'            => $this->faker->sentence(3),
            'kind'             => $kind,
            'schedule_type_id' => $scheduleTypeId,
            'start_at'         => $start_at,
            'contact'          => $this->faker->name(),
            'obs'              => $this->faker->sentence(),
            'status'           => $this->faker->randomElement(['sch', 'fin', 'can', 'con', 'del']),
        ];
    }
}
