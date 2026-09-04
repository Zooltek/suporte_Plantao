<?php

namespace Database\Seeders\Ticket;

use App\Models\Ticket\Comment;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        if (Comment::count() > 0) {
            $this->command->info('CommentSeeder: comentários já existem, pulando.');
            return;
        }

        $ticketIds = Ticket::pluck('id');

        if ($ticketIds->isEmpty()) {
            $this->command->warn('CommentSeeder: nenhum ticket encontrado.');
            return;
        }

        $total = 0;

        foreach ($ticketIds as $ticketId) {
            // 50% dos tickets têm 1-3 comentários
            if (rand(1, 10) <= 5) {
                $count = rand(1, 3);
                Comment::factory()
                    ->count($count)
                    ->create(['ticket_id' => $ticketId]);
                $total += $count;
            }
        }

        $this->command->info("CommentSeeder: {$total} comentários criados.");
    }
}
