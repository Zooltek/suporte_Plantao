<?php

namespace App\Providers;

use App\Contracts\Helpdesk\Ticketit\SlaServiceInterface;
use App\Contracts\Repositories\AgentRepositoryInterface;
use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Contracts\Repositories\AttachmentRepositoryInterface;
use App\Contracts\Repositories\AttendanceRepositoryInterface;
use App\Contracts\Repositories\BoletoRepositoryInterface;
use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\ChangelogRepositoryInterface;
use App\Contracts\Repositories\ChatBotRepositoryInterface;
use App\Contracts\Repositories\ChatRepositoryInterface;
use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\CrmDashboardRepositoryInterface;
use App\Contracts\Repositories\CrmElementRepositoryInterface;
use App\Contracts\Repositories\CustomerGroupRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\DashboardRepositoryInterface;
use App\Contracts\Repositories\DepartmentRepositoryInterface;
use App\Contracts\Repositories\ElementTypeRepositoryInterface;
use App\Contracts\Repositories\FeedbackRepositoryInterface;
use App\Contracts\Repositories\HelpdeskCategoryRepositoryInterface;
use App\Contracts\Repositories\HelpdeskCompanyRepositoryInterface;
use App\Contracts\Repositories\HelpdeskNotificationRepositoryInterface;
use App\Contracts\Repositories\HelpdeskRepositoryInterface;
use App\Contracts\Repositories\HomeRepositoryInterface;
use App\Contracts\Repositories\ImplantacaoRepositoryInterface;
use App\Contracts\Repositories\KnowledgeBaseRepositoryInterface;
use App\Contracts\Repositories\KnowledgeRepositoryInterface;
use App\Contracts\Repositories\LabelRepositoryInterface;
use App\Contracts\Repositories\MonitorRepositoryInterface;
use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Contracts\Repositories\ProfileRepositoryInterface;
use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Contracts\Repositories\RatChecklistRepositoryInterface;
use App\Contracts\Repositories\RatGroupRepositoryInterface;
use App\Contracts\Repositories\RatModuleRepositoryInterface;
use App\Contracts\Repositories\RecordRepositoryInterface;
use App\Contracts\Repositories\ReportRepositoryInterface;
use App\Contracts\Repositories\ScheduleElementRepositoryInterface;
use App\Contracts\Repositories\ScheduleModuleConfigRepositoryInterface;
use App\Contracts\Repositories\ScheduleRepositoryInterface;
use App\Contracts\Repositories\ScheduleTypeRepositoryInterface;
use App\Contracts\Repositories\SettingsRepositoryInterface;
use App\Contracts\Repositories\SlaRepositoryInterface;
use App\Contracts\Repositories\SolutionRepositoryInterface;
use App\Contracts\Repositories\StateRepositoryInterface;
use App\Contracts\Repositories\TaskCommentRepositoryInterface;
use App\Contracts\Repositories\TaskReportRepositoryInterface;
use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Contracts\Repositories\TicketAuditRepositoryInterface;
use App\Contracts\Repositories\TicketCommentRepositoryInterface;
use App\Contracts\Repositories\TicketIssueRepositoryInterface;
use App\Contracts\Repositories\TicketQueryRepositoryInterface;
use App\Contracts\Repositories\TicketRepositoryInterface;
use App\Contracts\Repositories\TicketShowRepositoryInterface;
use App\Contracts\Repositories\UserPermissionRepositoryInterface;
use App\Contracts\Repositories\UserProjectRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\VersionRepositoryInterface;
use App\Contracts\Repositories\WhatsAppRepositoryInterface;
use App\Contracts\Repositories\WhatsAppTicketRepositoryInterface;
use App\Contracts\WhatsApp\WhatsAppProviderContract;
use App\Models\Ticket\Ticket;
use App\Observers\TicketObserver;
use App\Repositories\AgentRepository;
use App\Repositories\AtendimentoRepository;
use App\Repositories\AttachmentRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\BoletoRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ChangelogRepository;
use App\Repositories\ChatBotRepository;
use App\Repositories\ChatRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\CrmDashboardRepository;
use App\Repositories\CrmElementRepository;
use App\Repositories\CustomerGroupRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\ElementTypeRepository;
use App\Repositories\FeedbackRepository;
use App\Repositories\HelpdeskCategoryRepository;
use App\Repositories\HelpdeskCompanyRepository;
use App\Repositories\HelpdeskNotificationRepository;
use App\Repositories\HelpdeskRepository;
use App\Repositories\HomeRepository;
use App\Repositories\ImplantacaoRepository;
use App\Repositories\KnowledgeBaseRepository;
use App\Repositories\KnowledgeRepository;
use App\Repositories\LabelRepository;
use App\Repositories\MonitorRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\RatChecklistRepository;
use App\Repositories\RatGroupRepository;
use App\Repositories\RatModuleRepository;
use App\Repositories\RecordRepository;
use App\Repositories\ReportRepository;
use App\Repositories\ScheduleElementRepository;
use App\Repositories\ScheduleModuleConfigRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\ScheduleTypeRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\SlaRepository;
use App\Repositories\SolutionRepository;
use App\Repositories\StateRepository;
use App\Repositories\TaskCommentRepository;
use App\Repositories\TaskReportRepository;
use App\Repositories\TaskRepository;
use App\Repositories\TicketAuditRepository;
use App\Repositories\TicketCommentRepository;
use App\Repositories\TicketIssueRepository;
use App\Repositories\TicketQueryRepository;
use App\Repositories\TicketRepository;
use App\Repositories\TicketShowRepository;
use App\Repositories\UserPermissionRepository;
use App\Repositories\UserProjectRepository;
use App\Repositories\UserRepository;
use App\Repositories\VersionRepository;
use App\Repositories\WhatsAppRepository;
use App\Repositories\WhatsAppTicketRepository;
use App\Services\Helpdesk\Ticketit\SlaService;
use App\Services\WhatsApp\Providers\EvolutionApiProvider;
use App\Services\WhatsApp\Providers\GenericWhatsAppProvider;
use App\Support\Vite\NetworkAwareVite;
use App\View\Composers\TasksSidebarComposer;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Vite::class, static function (): Vite {
            return (new NetworkAwareVite)
                ->useManifestFilename('manifest.runtime.json');
        });

        $this->app->bind(SlaServiceInterface::class, SlaService::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(FeedbackRepositoryInterface::class, FeedbackRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(HelpdeskRepositoryInterface::class, HelpdeskRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
        $this->app->bind(LabelRepositoryInterface::class, LabelRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(TicketQueryRepositoryInterface::class, TicketQueryRepository::class);
        $this->app->bind(ChangelogRepositoryInterface::class, ChangelogRepository::class);
        $this->app->bind(ImplantacaoRepositoryInterface::class, ImplantacaoRepository::class);
        $this->app->bind(VersionRepositoryInterface::class, VersionRepository::class);
        $this->app->bind(TicketCommentRepositoryInterface::class, TicketCommentRepository::class);
        $this->app->bind(TaskCommentRepositoryInterface::class, TaskCommentRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(ChatBotRepositoryInterface::class, ChatBotRepository::class);
        $this->app->bind(CrmDashboardRepositoryInterface::class, CrmDashboardRepository::class);
        $this->app->bind(ElementTypeRepositoryInterface::class, ElementTypeRepository::class);
        $this->app->bind(RatChecklistRepositoryInterface::class, RatChecklistRepository::class);
        $this->app->bind(RatGroupRepositoryInterface::class, RatGroupRepository::class);
        $this->app->bind(RatModuleRepositoryInterface::class, RatModuleRepository::class);
        $this->app->bind(ScheduleModuleConfigRepositoryInterface::class, ScheduleModuleConfigRepository::class);
        $this->app->bind(ScheduleTypeRepositoryInterface::class, ScheduleTypeRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Agent module repositories
        $this->app->bind(MonitorRepositoryInterface::class, MonitorRepository::class);
        $this->app->bind(RecordRepositoryInterface::class, RecordRepository::class);
        $this->app->bind(ScheduleRepositoryInterface::class, ScheduleRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
        $this->app->bind(TicketShowRepositoryInterface::class, TicketShowRepository::class);
        $this->app->bind(UserPermissionRepositoryInterface::class, UserPermissionRepository::class);
        $this->app->bind(CrmElementRepositoryInterface::class, CrmElementRepository::class);
        $this->app->bind(AgentRepositoryInterface::class, AgentRepository::class);

        // New repository bindings (sprint003 refactoring)
        $this->app->bind(KnowledgeRepositoryInterface::class, KnowledgeRepository::class);
        $this->app->bind(AttachmentRepositoryInterface::class, AttachmentRepository::class);
        $this->app->bind(TicketAuditRepositoryInterface::class, TicketAuditRepository::class);
        $this->app->bind(TicketIssueRepositoryInterface::class, TicketIssueRepository::class);
        $this->app->bind(SlaRepositoryInterface::class, SlaRepository::class);
        $this->app->bind(UserProjectRepositoryInterface::class, UserProjectRepository::class);
        $this->app->bind(TaskReportRepositoryInterface::class, TaskReportRepository::class);

        // Sprint004 — new service repositories
        $this->app->bind(AttendanceRepositoryInterface::class, AttendanceRepository::class);
        $this->app->bind(ScheduleElementRepositoryInterface::class, ScheduleElementRepository::class);
        $this->app->bind(WhatsAppRepositoryInterface::class, WhatsAppRepository::class);
        $this->app->bind(WhatsAppTicketRepositoryInterface::class, WhatsAppTicketRepository::class);

        // Helpdesk module repositories
        $this->app->bind(AtendimentoRepositoryInterface::class, AtendimentoRepository::class);
        $this->app->bind(BoletoRepositoryInterface::class, BoletoRepository::class);
        $this->app->bind(HelpdeskCategoryRepositoryInterface::class, HelpdeskCategoryRepository::class);
        $this->app->bind(ChatRepositoryInterface::class, ChatRepository::class);
        $this->app->bind(HelpdeskCompanyRepositoryInterface::class, HelpdeskCompanyRepository::class);
        $this->app->bind(HomeRepositoryInterface::class, HomeRepository::class);
        $this->app->bind(KnowledgeBaseRepositoryInterface::class, KnowledgeBaseRepository::class);
        $this->app->bind(HelpdeskNotificationRepositoryInterface::class, HelpdeskNotificationRepository::class);
        $this->app->bind(ProfileRepositoryInterface::class, ProfileRepository::class);
        $this->app->bind(SolutionRepositoryInterface::class, SolutionRepository::class);

        // Integração financeiro — cadastro/situação de clientes
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(CustomerGroupRepositoryInterface::class, CustomerGroupRepository::class);
        $this->app->bind(StateRepositoryInterface::class, StateRepository::class);

        // Binding do provedor WhatsApp — troca de provedor sem alterar lógica de negócio.
        // Para ativar a Evolution-API: WHATSAPP_PROVIDER=evolution no .env
        $this->app->bind(WhatsAppProviderContract::class, function () {
            return match (config('whatsapp.provider', 'generic')) {
                'evolution' => new EvolutionApiProvider,
                default => new GenericWhatsAppProvider,
            };
        });

        // Telescope só existe em dev (require-dev). Registra o provider local apenas
        // quando o pacote estiver presente para evitar fatal em produção (--no-dev).
        if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Ticket::observe(TicketObserver::class);

        View::composer('tasks.layouts.master', TasksSidebarComposer::class);

        // Atualização imediata do contador de usuários online em logins/logouts
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function (\Illuminate\Auth\Events\Login $event): void {
                if ($event->user && isset($event->user->id)) {
                    \App\Services\Auth\UserOnlineTracker::hit((int) $event->user->id, force: true);
                }
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            function (\Illuminate\Auth\Events\Logout $event): void {
                if ($event->user && isset($event->user->id)) {
                    \App\Services\Auth\UserOnlineTracker::forget((int) $event->user->id);
                }
            }
        );
    }
}
