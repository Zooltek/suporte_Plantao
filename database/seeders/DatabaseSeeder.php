<?php

namespace Database\Seeders;

use Database\Seeders\Crm\Feedback\ElementSeeder;
// Importação dos Seeders com Namespaces Corretos (PSR-4)
use Database\Seeders\Crm\Feedback\ElementTypeSeeder;
use Database\Seeders\Helpdesk\Chat\OriginSeeder;
use Database\Seeders\Helpdesk\Ticketit\CategorySeeder;
use Database\Seeders\Helpdesk\Ticketit\PrioritySeeder;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;
use Database\Seeders\Schedule\CadastroModuleSeeder;
use Database\Seeders\Schedule\ContabilModuleSeeder;
use Database\Seeders\Schedule\EstoqueModuleSeeder;
use Database\Seeders\Schedule\FinanceiroModuleSeeder;
use Database\Seeders\Schedule\InstalacaoModuleSeeder;
use Database\Seeders\Schedule\OrcamentoReservaModuleSeeder;
use Database\Seeders\Schedule\ImplantacaoAdminSeeder;
use Database\Seeders\Schedule\OutrosModuleSeeder;
use Database\Seeders\Schedule\VendasModuleSeeder;
use Database\Seeders\Ticket\AttendanceSeeder;
use Database\Seeders\Ticket\CommentSeeder;
use Database\Seeders\Ticket\TicketIssueSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. INFRAESTRUTURA BASE (Tabelas que não dependem de ninguém)
            DepartmentSeeder::class,
            StateSeeder::class,

            // 2. ROLES E PERMISSÕES (Antes dos usuários)
            RoleAndPermissionSeeder::class,

            // 3. USUÁRIOS (Dependem de Departments + Roles)
            ProfilesUserSeeder::class,
            AdminUserSeeder::class,

            // 3. ESTRUTURA DE CLIENTES (Dependem de States, Groups e Admin)
            CustomerGroupSeeder::class,
            SoftwareSeeder::class,
            CustomerSeeder::class,
            ContractSeeder::class,
            RetaguardaSeeder::class,

            // 4. SUPORTE E HELPDESK (Ticketit e Scopes)
            TicketitOriginSeeder::class,
            WhatsAppAutomationSeeder::class,
            LabelsSeeder::class,
            StatusSeeder::class,
            PrioritySeeder::class,
            CategorySeeder::class,
            TicketitSeeder::class,
            OriginSeeder::class,
            ScheduleTypeSeeder::class,
            ScheduleSeeder::class,
            ImplantacaoSeeder::class,
            VendasModuleSeeder::class,
            InstalacaoModuleSeeder::class,
            FinanceiroModuleSeeder::class,
            OrcamentoReservaModuleSeeder::class,
            CadastroModuleSeeder::class,
            EstoqueModuleSeeder::class,
            ContabilModuleSeeder::class,
            OutrosModuleSeeder::class,
            ImplantacaoAdminSeeder::class,

            // Produtos e Projetos reais da empresa
            TasksProjectSeeder::class,
            TaskSeeder::class,
            TaskReportSeeder::class,

            // 5. CRM E FEEDBACK (Dependem de Usuários e Clientes)
            CrmSeeder::class,
            CrmFeedbackSeeder::class,
            ElementTypeSeeder::class,
            ElementSeeder::class,

            // 6. CONTEÚDO DE TICKETS (Dependem de ticketit existente)
            AttendanceSeeder::class,
            TicketIssueSeeder::class,
            CommentSeeder::class,
            AdminReportsSeeder::class,

            // 7. KNOWLEDGE BASE
            KnowledgeSeeder::class,
            ModelCoverageSeeder::class,

        ]);

        $this->command->info('Sucesso: Hierarquia de banco de dados respeitada e populada!');
    }
}
