<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Popula dados realistas de implantação:
 *   - Módulo "Implantação" garantido
 *   - 8 agendamentos do tipo CLIENT com status variados
 *   - Cada agendamento com 1–4 atividades (Records), a maioria ativa (status=1)
 */
class ImplantacaoSeeder extends Seeder
{
    public function run(): void
    {
        $agent = User::where('ticketit_agent', true)->inRandomOrder()->first();

        if (! $agent) {
            $this->command->warn('Nenhum agente encontrado. Execute ProfilesUserSeeder primeiro.');
            return;
        }

        $module = Module::firstOrCreate(['name' => 'Implantação']);

        $customers = Customer::inRandomOrder()->limit(8)->get();

        if ($customers->isEmpty()) {
            $this->command->warn('Nenhum cliente encontrado. Execute CustomerSeeder primeiro.');
            return;
        }

        $statuses = ['sch', 'con', 'fin', 'fin', 'fin', 'con']; // maioria avançada

        foreach ($customers as $index => $customer) {
            $startAt = Carbon::now()
                ->subDays(rand(5, 60))
                ->setTime(rand(8, 16), 0);

            $schedule = Schedule::create([
                'customer_id' => $customer->id,
                'agent_id'    => $agent->id,
                'module_id'   => $module->id,
                'title'       => 'Implantação — ' . ($customer->trade_name ?? $customer->name),
                'kind'        => Schedule::KIND_CLIENT,
                'start_at'    => $startAt,
                'contact'     => $customer->contact_name ?? 'Responsável',
                'obs'         => 'Agendamento de implantação gerado via seeder.',
                'status'      => $statuses[$index % count($statuses)],
            ]);

            $recordCount = rand(1, 4);

            for ($i = 0; $i < $recordCount; $i++) {
                $recStart = $startAt->copy()->addDays($i)->setTime(rand(8, 14), 0);
                $recEnd   = $recStart->copy()->addHours(rand(2, 5));

                Record::create([
                    'schedule_id'    => $schedule->id,
                    'module_id'      => $module->id,
                    'customer_id'    => $customer->id,
                    'agent_id'       => $agent->id,
                    'status'         => $i === 0 ? 1 : rand(0, 1), // ao menos a 1ª ativa
                    'start'          => $recStart,
                    'end'            => $recEnd,
                    'interval_start' => $recStart->copy()->addHours(2),
                    'interval_end'   => $recStart->copy()->addHours(2)->addMinutes(45),
                    'contact'        => $customer->contact_name ?? 'Responsável',
                    'obs'            => "Sessão " . ($i + 1) . " de implantação.",
                    'version'        => '1.' . $i . '.0',
                    'release'        => 'v1.' . str_pad($i, 2, '0', STR_PAD_LEFT),
                ]);
            }
        }

        $this->command->info("ImplantacaoSeeder: {$customers->count()} agendamentos criados com atividades.");
    }
}
