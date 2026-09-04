<?php

namespace Database\Seeders\Crm;

use App\Models\Crm\Feedback;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        Feedback::factory()
            ->count(10)
            ->create();

        $this->command->info('Feedbacks CRM criados com sucesso!');
    }
}
