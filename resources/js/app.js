import './bootstrap';
import './confirm-modal';
import Alpine from 'alpinejs';
import mask from '@alpinejs/mask';
import collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';
import dayjs from 'dayjs';
import 'dayjs/locale/pt-br';
import localizedFormat from 'dayjs/plugin/localizedFormat';
dayjs.extend(localizedFormat);
dayjs.locale('pt-br');
import Toast from './utils/toast';
import { flashCenter } from './ui/flash-center';

// 1. Importações unificadas
import { userManager, userRow } from './admin/user-manager';
import { categoryManager, categoryRow, createCategoryForm } from './admin/category/categories.js';
import { agentManager, agentForm, agentEditForm } from './admin/helpdesk/agent.js';
import { ticketitCategoryManager, ticketitCategoryForm, ticketitCategoryEditForm } from './admin/helpdesk/category.js';
import { configurationManager, configurationForm, configurationEditForm } from './admin/helpdesk/configuration.js';
import { slaSettingsForm } from './admin/helpdesk/sla.js';
import { feedbackManager } from './admin/crm/feedback.js';
import { crmTabs } from './crm/tabs.js';
import { ticketIndex } from './agent/tickets/index.js';
import { ticketShow } from './agent/tickets/show.js';
import { ticketForm } from './agent/tickets/form.js';
import { attendanceManager } from './agent/tickets/attendances.js';
import { ticketAudit } from './agent/tickets/audit.js';
import { ticketIssues } from './agent/tickets/issues.js';
import { ticketAttachments } from './agent/tickets/attachments.js';
import { knowledgeIndex } from './agent/knowledge/index.js';
import { mobileTasks } from './tasks/mobile-tasks.js';
import { taskNotifications } from './tasks/task-notifications.js';
import { adminLayout } from './admin/admin-layout.js';
import { confirmModal } from './confirm-modal-component.js';
import { obsBlock } from './agent/schedule/obs-block.js';
import { recordCreate } from './agent/schedule/record-create.js';
import { taskReportPrint } from './tasks/report.js';

Alpine.plugin(mask);
Alpine.plugin(collapse);

// 2. Registrar componentes do Alpine antes do Start
Alpine.data('userManager', userManager);
Alpine.data('userRow', userRow);
Alpine.data('categoryManager', categoryManager);
Alpine.data('categoryRow', categoryRow);
Alpine.data('createCategoryForm', createCategoryForm);
Alpine.data('agentManager', agentManager);
Alpine.data('agentForm', agentForm);
Alpine.data('agentEditForm', agentEditForm);
Alpine.data('categoryManagerHelpdesk', ticketitCategoryManager);
Alpine.data('ticketitCategoryForm', ticketitCategoryForm);
Alpine.data('ticketitCategoryEditForm', ticketitCategoryEditForm);
Alpine.data('configurationManager', configurationManager);
Alpine.data('configurationForm', configurationForm);
Alpine.data('configurationEditForm', configurationEditForm);
Alpine.data('slaSettingsForm', slaSettingsForm);
Alpine.data('feedbackManager', feedbackManager);
Alpine.data('crmTabs', crmTabs);
Alpine.data('ticketIndex', ticketIndex);
Alpine.data('ticketShow', ticketShow);
Alpine.data('ticketForm', ticketForm);
Alpine.data('attendanceManager', attendanceManager);
Alpine.data('ticketAudit', ticketAudit);
Alpine.data('ticketIssues', ticketIssues);
Alpine.data('ticketAttachments', ticketAttachments);
Alpine.data('knowledgeIndex', knowledgeIndex);
Alpine.data('mobileTasks', mobileTasks);
Alpine.data('taskNotifications', taskNotifications);
Alpine.data('adminLayout', adminLayout);
Alpine.data('confirmModal', confirmModal);
Alpine.data('obsBlock', obsBlock);
Alpine.data('recordCreate', recordCreate);
Alpine.data('taskReportPrint', taskReportPrint);
Alpine.data('flashCenter', flashCenter);

// 3. Atribuições Globais
globalThis.Alpine = Alpine;
globalThis.Chart = Chart;
globalThis.dayjs = dayjs;
globalThis.AppToast = Toast;
globalThis.iziToast = {
    success: (options = {}) => Toast.success(options),
    error: (options = {}) => Toast.error(options),
    warning: (options = {}) => Toast.warning(options),
    info: (options = {}) => Toast.info(options),
};

Alpine.start();

/**
 * Gerenciamento de Tema (Ocean/Light)
 */
document.addEventListener("DOMContentLoaded", () => {
    const html = document.documentElement;
    const btn = document.getElementById('theme-toggle');

    if (!btn) return;

    btn.addEventListener('click', () => {
        html.classList.toggle('ocean');

        if (html.classList.contains('ocean')) {
            localStorage.setItem('theme', 'ocean');
        } else {
            localStorage.setItem('theme', 'light');
        }
    });

    globalThis.AppHelpers = {
        formatCNPJ(value) {
            return value.replaceAll(/\D/g, '')
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2')
                .substring(0, 18);
        }
    };
});
