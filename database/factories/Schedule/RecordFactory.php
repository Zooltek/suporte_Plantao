<?php

namespace Database\Factories\Schedule;

use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecordFactory extends Factory
{
    protected $model = Record::class;

    public function definition(): array
    {
        $start = Carbon::parse($this->faker->dateTimeBetween('-2 months', 'now'));
        $end   = $start->copy()->addHours($this->faker->numberBetween(1, 6));

        $intervalStart = $start->copy()->addHours(2);
        $intervalEnd   = $intervalStart->copy()->addMinutes($this->faker->numberBetween(30, 60));

        return [
            'schedule_id'    => Schedule::active()->inRandomOrder()->value('id')
                                ?? Schedule::factory(),
            'module_id'      => Module::inRandomOrder()->value('id')
                                ?? Module::firstOrCreate(['name' => 'Implantação'])->id,
            'customer_id'    => Customer::inRandomOrder()->value('id'),
            'agent_id'       => User::where('ticketit_agent', true)->inRandomOrder()->value('id')
                                ?: User::factory()->agent(),
            'status'         => $this->faker->randomElement([0, 1, 1, 1]), // maioria ativa
            'start'          => $start,
            'end'            => $end,
            'interval_start' => $intervalStart,
            'interval_end'   => $intervalEnd,
            'contact'        => $this->faker->name(),
            'obs'            => $this->faker->optional()->sentence(),
            'version'        => $this->faker->optional()->numerify('#.#.#'),
            'release'        => $this->faker->optional()->numerify('v#.##'),
        ];
    }

    /** Atividade ativa (status = 1) */
    public function active(): static
    {
        return $this->state(['status' => 1]);
    }

    /** Atividade inativa (status = 0) */
    public function inactive(): static
    {
        return $this->state(['status' => 0]);
    }

    /** Vincula ao agendamento informado */
    public function forSchedule(Schedule $schedule): static
    {
        return $this->state([
            'schedule_id' => $schedule->id,
            'customer_id' => $schedule->customer_id,
            'agent_id'    => $schedule->agent_id,
            'module_id'   => $schedule->module_id,
        ]);
    }
}
