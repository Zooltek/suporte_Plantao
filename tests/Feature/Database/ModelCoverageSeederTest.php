<?php

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ModelCoverageSeeder;
use Database\Seeders\RetaguardaSeeder;
use Illuminate\Support\Facades\DB;

describe('Seeders complementares — cobertura de models', function () {
    it('popula tabelas auxiliares e compatíveis que antes ficavam vazias', function () {
        $this->seed(DatabaseSeeder::class);

        expect(DB::table('admins')->count())->toBeGreaterThan(0)
            ->and(DB::table('cities')->count())->toBeGreaterThan(0)
            ->and(DB::table('service')->count())->toBeGreaterThan(0)
            ->and(DB::table('sales_order_status')->count())->toBeGreaterThan(0)
            ->and(DB::table('sales_order')->count())->toBeGreaterThan(0)
            ->and(DB::table('customer_contacts')->count())->toBeGreaterThan(0)
            ->and(DB::table('solutions')->count())->toBeGreaterThan(0)
            ->and(DB::table('likes')->count())->toBeGreaterThan(0)
            ->and(DB::table('user_blink')->count())->toBeGreaterThan(0)
            ->and(DB::table('user_notifications')->count())->toBeGreaterThan(0)
            ->and(DB::table('user_settings')->count())->toBeGreaterThan(0)
            ->and(DB::table('ticketit_agent_rate')->count())->toBeGreaterThan(0)
            ->and(DB::table('user_ratings')->count())->toBeGreaterThan(0)
            ->and(DB::table('task_comments')->count())->toBeGreaterThan(0)
            ->and(DB::table('task_attachments')->count())->toBeGreaterThan(0)
            ->and(DB::table('task_references')->count())->toBeGreaterThan(0)
            ->and(DB::table('tasks_notification')->count())->toBeGreaterThan(0)
            ->and(DB::table('tasks_users_projects')->count())->toBeGreaterThan(0)
            ->and(DB::table('tasks_changelog_versions')->count())->toBeGreaterThan(0)
            ->and(DB::table('tasks_changelogs')->count())->toBeGreaterThan(0)
            ->and(DB::table('ticketit_audits')->count())->toBeGreaterThan(0)
            ->and(DB::table('ticketit_attachments')->count())->toBeGreaterThan(0)
            ->and(DB::table('ticket_extra_categories')->count())->toBeGreaterThan(0)
            ->and(DB::table('ticket_files')->count())->toBeGreaterThan(0)
            ->and(DB::table('whatsapp_conversations')->count())->toBeGreaterThan(0)
            ->and(DB::table('whatsapp_messages')->count())->toBeGreaterThan(0);
    });

    it('mantem os seeders suplementares idempotentes', function () {
        $this->seed(DatabaseSeeder::class);

        $before = [
            'cities' => DB::table('cities')->count(),
            'service' => DB::table('service')->count(),
            'sales_order_status' => DB::table('sales_order_status')->count(),
            'sales_order' => DB::table('sales_order')->count(),
            'task_attachments' => DB::table('task_attachments')->count(),
            'ticket_files' => DB::table('ticket_files')->count(),
            'whatsapp_messages' => DB::table('whatsapp_messages')->count(),
        ];

        $this->seed(RetaguardaSeeder::class);
        $this->seed(ModelCoverageSeeder::class);

        expect(DB::table('cities')->count())->toBe($before['cities'])
            ->and(DB::table('service')->count())->toBe($before['service'])
            ->and(DB::table('sales_order_status')->count())->toBe($before['sales_order_status'])
            ->and(DB::table('sales_order')->count())->toBe($before['sales_order'])
            ->and(DB::table('task_attachments')->count())->toBe($before['task_attachments'])
            ->and(DB::table('ticket_files')->count())->toBe($before['ticket_files'])
            ->and(DB::table('whatsapp_messages')->count())->toBe($before['whatsapp_messages']);
    });
});
