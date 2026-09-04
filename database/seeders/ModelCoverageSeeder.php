<?php

namespace Database\Seeders;

use App\Enums\WhatsApp\ConversationState;
use App\Models\Admin;
use App\Models\Blink;
use App\Models\Category;
use App\Models\CompanyContact;
use App\Models\Crm\Feedback;
use App\Models\Customer;
use App\Models\Notification as UserNotification;
use App\Models\Tasks\Project;
use App\Models\Tasks\Task;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\User\Setting as UserSetting;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ModelCoverageSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();
        $customer = Customer::query()->orderBy('id')->first();
        $ticket = Ticket::query()->orderBy('id')->first();
        $project = Project::query()->active()->orderBy('id')->first();
        $tasks = Task::query()->orderBy('id')->limit(2)->get();
        $feedback = Feedback::query()->orderBy('id')->first();

        if (! $user || ! $customer || ! $ticket || ! $project || $tasks->isEmpty()) {
            $this->command?->warn('ModelCoverageSeeder: dependências mínimas ausentes. Execute o DatabaseSeeder base antes da cobertura complementar.');

            return;
        }

        $this->seedAdmins();
        $this->seedCustomerContacts($customer);
        $this->seedKnowledgeCoverage($user);
        $this->seedUserExperience($user, $feedback);
        $this->seedTaskCoverage($user, $project, $tasks);
        $this->seedTicketCoverage($user, $ticket);
        $this->seedWhatsAppCoverage($customer, $ticket);
    }

    private function seedAdmins(): void
    {
        Admin::query()->updateOrCreate(
            ['email' => 'admin.seed@amura.local'],
            [
                'name' => 'Administrador Seed',
                'password' => Hash::make('password'),
            ]
        );
    }

    private function seedCustomerContacts(Customer $customer): void
    {
        $contacts = [
            [
                'name' => $customer->contact_name ?: 'Contato Principal',
                'phone' => $customer->phone ?: '(27) 3333-0000',
                'is_main' => true,
            ],
            [
                'name' => 'Financeiro '.$customer->trade_name,
                'phone' => $customer->telephone_2 ?: '(27) 99999-0000',
                'is_main' => false,
            ],
        ];

        foreach ($contacts as $contact) {
            CompanyContact::query()->updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'phone' => $contact['phone'],
                ],
                [
                    'name' => $contact['name'],
                    'is_main' => $contact['is_main'],
                ]
            );
        }
    }

    private function seedKnowledgeCoverage(User $user): void
    {
        $category = Category::query()->root()->orderBy('category_id')->first()
            ?? Category::query()->orderBy('category_id')->first();

        if (! $category) {
            return;
        }

        $now = now();

        DB::table('solutions')->updateOrInsert(
            ['title' => 'Como acompanhar o status de um chamado'],
            [
                'content' => 'Acompanhe o andamento pelo menu de chamados e verifique atualizações do agente responsável.',
                'searchable_content' => 'acompanhar status chamado agente responsável atendimento',
                'sort_order' => 1,
                'background' => '#0f172a',
                'likes' => 1,
                'dislikes' => 0,
                'author_id' => $user->id,
                'category_id' => $category->category_id,
                'views' => 12,
                'status' => 1,
                'uploads' => json_encode([]),
                'tags' => 'chamado,status,suporte',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $solutionId = DB::table('solutions')
            ->where('title', 'Como acompanhar o status de um chamado')
            ->value('id');

        if (! $solutionId) {
            return;
        }

        DB::table('likes')->updateOrInsert(
            [
                'user_id' => $user->id,
                'solution_id' => $solutionId,
            ],
            [
                'like' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function seedUserExperience(User $user, ?Feedback $feedback): void
    {
        Blink::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'hash' => Str::uuid()->toString(),
                'status' => 1,
            ]
        );

        UserNotification::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'content' => 'Você possui uma nova interação de suporte.',
            ],
            [
                'action' => '/agent/ticket',
                'image' => 'notifications/support.png',
                'status' => 1,
            ]
        );

        UserSetting::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'slug' => 'dashboard.layout',
            ],
            [
                'value' => 'compact',
                'default' => 'classic',
            ]
        );

        if (! $feedback) {
            return;
        }

        DB::table('ticketit_agent_rate')->updateOrInsert(
            ['id' => $feedback->id],
            [
                'name' => 'Avaliação '.$feedback->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('user_ratings')->updateOrInsert(
            [
                'user_id' => $user->id,
                'feedback_id' => $feedback->id,
            ],
            [
                'rating' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function seedTaskCoverage(User $user, Project $project, \Illuminate\Support\Collection $tasks): void
    {
        $task = $tasks->first();
        $referenceTask = $tasks->count() > 1
            ? $tasks->last()
            : Task::query()->whereKeyNot($task->id)->orderBy('id')->first() ?? $task;

        DB::table('task_comments')->updateOrInsert(
            [
                'task_id' => $task->id,
                'content' => 'Comentário inicial gerado pelo seeder complementar.',
            ],
            [
                'author_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('task_attachments')->updateOrInsert(
            [
                'task_id' => $task->id,
                'file_name' => 'escopo-seed.pdf',
            ],
            [
                'file_path' => 'tasks/seed/escopo-seed.pdf',
                'mime_type' => 'application/pdf',
                'size' => 204800,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if ($referenceTask && $referenceTask->id !== $task->id) {
            DB::table('task_references')->updateOrInsert(
                [
                    'task_id' => $task->id,
                    'reference_id' => $referenceTask->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('tasks_users_projects')->updateOrInsert(
            [
                'user_id' => $user->id,
                'project_id' => $project->id,
            ],
            [
                'color' => '#2563eb',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('tasks_notification')->updateOrInsert(
            [
                'user_id' => $user->id,
                'kind' => 'task_status_changed',
                'ref_id' => $task->id,
            ],
            [
                'content' => 'A tarefa recebeu uma atualização de status.',
                'author_id' => $user->id,
                'status' => 1,
                'seen' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('tasks_changelog_versions')->updateOrInsert(
            [
                'name' => 'Seed de cobertura v1.0.0',
                'project_id' => $project->id,
            ],
            [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'reference_date' => now()->startOfDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $versionId = DB::table('tasks_changelog_versions')
            ->where('name', 'Seed de cobertura v1.0.0')
            ->value('id');

        DB::table('tasks_changelogs')->updateOrInsert(
            [
                'project_id' => $project->id,
                'content' => 'Estrutura complementar criada para popular changelog do sistema.',
            ],
            [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'version_id' => $versionId,
                'status' => 1,
                'sort_order' => 1,
                'bold' => false,
                'blank' => false,
                'title' => true,
                'color' => '#0f172a',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function seedTicketCoverage(User $user, Ticket $ticket): void
    {
        $category = Category::query()->root()->orderBy('category_id')->first()
            ?? Category::query()->orderBy('category_id')->first();

        $subCategory = $category
            ? Category::query()
                ->where('parent_id', $category->category_id)
                ->orderBy('category_id')
                ->first()
            : null;

        DB::table('ticketit_audits')->updateOrInsert(
            [
                'ticket_id' => $ticket->id,
                'operation' => 'Seeder complementar populou auditoria inicial',
            ],
            [
                'user_id' => $user->id,
                'event' => 'created',
                'field' => 'status_id',
                'old_value' => null,
                'new_value' => (string) $ticket->status_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('ticketit_attachments')->updateOrInsert(
            [
                'ticket_id' => $ticket->id,
                'name' => 'print-seed.png',
            ],
            [
                'original_name' => 'print-seed.png',
                'disk_path' => 'ticket/attachments/print-seed.png',
                'size' => 1024,
                'mime' => 'image/png',
                'author_id' => $user->id,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('ticket_files')->updateOrInsert(
            [
                'ticket_id' => $ticket->id,
                'name' => 'anexo_legado',
            ],
            [
                'extension' => 'pdf',
                'path' => 'attachment/anexo_legado.pdf',
            ]
        );

        if (! $category) {
            return;
        }

        DB::table('ticket_extra_categories')->updateOrInsert(
            [
                'ticket_id' => $ticket->id,
                'category_id' => $category->category_id,
            ],
            [
                'sub_category_id' => $subCategory?->category_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function seedWhatsAppCoverage(Customer $customer, Ticket $ticket): void
    {
        $conversation = WhatsAppConversation::query()->firstOrCreate(
            ['phone' => '5527999990001'],
            [
                'state' => ConversationState::HUMAN_PENDING->value,
                'payload' => [
                    'company' => $customer->trade_name ?: $customer->name,
                    'contact' => $customer->contact_name,
                ],
                'company_id' => $customer->id,
                'ticket_id' => $ticket->id,
                'last_activity_at' => now(),
            ]
        );

        WhatsAppMessage::query()->firstOrCreate(
            ['provider_message_id' => 'seed-whatsapp-inbound-1'],
            [
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'type' => 'text',
                'body' => 'Preciso de apoio no fechamento do caixa.',
            ]
        );

        WhatsAppMessage::query()->firstOrCreate(
            ['provider_message_id' => 'seed-whatsapp-outbound-1'],
            [
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => 'Recebemos sua solicitação e um agente dará continuidade.',
            ]
        );
    }
}
