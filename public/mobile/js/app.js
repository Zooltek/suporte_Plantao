/**
 * App Mobile de Chamados de Plantão (Offline-First)
 * Gerencia a interface, cronômetro de sobreaviso, autocomplete e sincronização.
 */

// ─── ESTADO GLOBAL DA APLICAÇÃO ──────────────────────────────────────
const state = {
    activeTab: 'tab-shift',
    activeShift: null,
    shiftTimerInterval: null,
    categories: [],
    statuses: [],
    agents: [],
    activeAgentId: 1,
    serverUrl: '',
};

// ─── GERADOR DE UUID V4 ──────────────────────────────────────────────
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// ─── FORMATAÇÃO E HELPERS ────────────────────────────────────────────
function formatMinutes(minutes) {
    const mins = Math.max(0, parseInt(minutes) || 0);
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return `${h}h ${String(m).padStart(2, '0')}m`;
}

function formatTimer(seconds) {
    const s = Math.max(0, parseInt(seconds) || 0);
    const hours = Math.floor(s / 3600);
    const minutes = Math.floor((s % 3600) / 60);
    const secs = s % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

function toLocalDatetimeString(date) {
    const d = new Date(date);
    const offset = d.getTimezoneOffset() * 60000;
    const local = new Date(d.getTime() - offset);
    return local.toISOString().slice(0, 16);
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = '✅';
    if (type === 'error') icon = '❌';
    if (type === 'warning') icon = '⚠️';

    toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ─── INICIALIZAÇÃO DO APP ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await window.db.init();
        initNavigation();
        initNetworkMonitor();
        initCustomerSearch();
        initTimeControls();
        initAttendanceForm();
        initShiftControls();
        initSyncSettings();

        // Carregar configurações locais salvas (URL padrão inteligente para PWA e APK)
        const defaultServer = (window.location.origin.startsWith('http://') && !window.location.origin.includes('localhost'))
            ? window.location.origin
            : 'http://192.168.0.198:8095';

        state.serverUrl = await window.db.getSetting('server_url', defaultServer);
        document.getElementById('serverUrlInput').value = state.serverUrl;

        state.activeAgentId = await window.db.getSetting('active_agent_id', 1);

        // Carregar dados mestre locais
        await loadLocalMasterData();

        // Restaurar plantão ativo se houver
        await restoreActiveShift();

        // Atualizar lista e métricas
        await refreshDashboard();
        await refreshAttendanceList();
        await updateDiagnostics();

        // Sincronização automática silenciosa de clientes e categorias em segundo plano (sem exigir clique manual)
        autoSyncMasterDataInBackground();
    } catch (err) {
        console.error('[App] Erro na inicialização:', err);
        showToast('Erro ao carregar banco local: ' + err.message, 'error');
    }
});

// ─── NAVEGAÇÃO ENTRE ABAS ────────────────────────────────────────────
function initNavigation() {
    const navItems = document.querySelectorAll('.bottom-nav .nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const tabId = item.getAttribute('data-tab');
            switchTab(tabId);
        });
    });
}

function switchTab(tabId) {
    state.activeTab = tabId;

    // Atualizar botões
    document.querySelectorAll('.bottom-nav .nav-item').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
    });

    // Atualizar seções
    document.querySelectorAll('.tab-content').forEach(section => {
        section.classList.toggle('active', section.id === tabId);
    });

    if (tabId === 'tab-list') refreshAttendanceList();
    if (tabId === 'tab-shift') refreshDashboard();
    if (tabId === 'tab-sync') updateDiagnostics();
}

// ─── MONITOR DE CONEXÃO (ONLINE / OFFLINE) ───────────────────────────
function initNetworkMonitor() {
    const statusBox = document.getElementById('networkStatus');
    const statusText = document.getElementById('networkText');

    function updateStatus() {
        const isOnline = navigator.onLine;
        statusBox.className = `network-badge ${isOnline ? 'online' : 'offline'}`;
        statusText.textContent = isOnline ? 'Online' : 'Offline';
    }

    window.addEventListener('online', () => {
        updateStatus();
        showToast('Conexão restabelecida! Pronto para sincronizar.', 'success');
    });

    window.addEventListener('offline', () => {
        updateStatus();
        showToast('Modo Offline ativado. Registros salvos no celular.', 'warning');
    });

    updateStatus();
}

// ─── CONTROLE DO PLANTÃO (SOBREAVISO) ────────────────────────────────
function initShiftControls() {
    const btnToggle = document.getElementById('btnToggleShift');

    btnToggle.addEventListener('click', async () => {
        if (!state.activeShift) {
            // Iniciar novo plantão
            const newShift = {
                uuid: generateUUID(),
                user_id: state.activeAgentId,
                started_at: new Date().toISOString(),
                ended_at: null,
                total_standby_minutes: 0,
                total_worked_minutes: 0,
                status: 'active',
                synced: false,
            };

            await window.db.saveShift(newShift);
            await window.db.setSetting('active_shift_uuid', newShift.uuid);
            state.activeShift = newShift;

            startShiftTimer();
            updateShiftUI(true);
            showToast('Plantão de sobreaviso iniciado com sucesso!', 'success');
        } else {
            // Encerrar plantão
            if (!confirm('Deseja realmente encerrar este turno de plantão?')) return;

            const now = new Date();
            state.activeShift.ended_at = now.toISOString();
            state.activeShift.status = 'finished';

            // Calcular horas finais
            const attendances = await window.db.getAttendancesByShift(state.activeShift.uuid);
            const workedMins = attendances.reduce((acc, a) => acc + (a.duration_minutes || 0), 0);
            const totalMins = Math.max(0, Math.floor((now - new Date(state.activeShift.started_at)) / 60000));
            
            state.activeShift.total_worked_minutes = workedMins;
            state.activeShift.total_standby_minutes = Math.max(0, totalMins - workedMins);

            await window.db.saveShift(state.activeShift);
            await window.db.setSetting('active_shift_uuid', null);

            stopShiftTimer();
            state.activeShift = null;
            updateShiftUI(false);
            showToast('Plantão encerrado e horas consolidadas!', 'success');
        }

        await refreshDashboard();
    });

    const btnHoliday = document.getElementById('btnToggleHolidayMode');
    if (btnHoliday) {
        btnHoliday.addEventListener('click', () => {
            state.forceHolidayMode = !state.forceHolidayMode;
            updateShiftUI(!!state.activeShift);
            refreshDashboard();
            showToast(state.forceHolidayMode 
                ? 'Modo Domingo / Feriado ativado (controle manual)' 
                : 'Modo Escala Fixa ativado (cálculo automático)', 'success');
        });
    }
}

async function restoreActiveShift() {
    const activeUuid = await window.db.getSetting('active_shift_uuid', null);
    if (activeUuid) {
        const shift = await window.db.getShift(activeUuid);
        if (shift && shift.status === 'active') {
            state.activeShift = shift;
            startShiftTimer();
            updateShiftUI(true);
            return;
        }
    }
    updateShiftUI(false);
}

function startShiftTimer() {
    stopShiftTimer();
    const timerElem = document.getElementById('shiftTimer');

    function tick() {
        if (!state.activeShift) return;
        const diffSecs = Math.floor((new Date() - new Date(state.activeShift.started_at)) / 1000);
        timerElem.textContent = formatTimer(diffSecs);
    }

    tick();
    state.shiftTimerInterval = setInterval(tick, 1000);
}

function stopShiftTimer() {
    if (state.shiftTimerInterval) {
        clearInterval(state.shiftTimerInterval);
        state.shiftTimerInterval = null;
    }
    document.getElementById('shiftTimer').textContent = '00:00:00';
}

function getScheduleForDate(date) {
    const d = new Date(date);
    const day = d.getDay(); // 0 = Dom, 6 = Sab, 1-5 = Seg-Sex
    const hour = d.getHours();
    const minute = d.getMinutes();
    const curTime = hour + (minute / 60);

    if (state.forceHolidayMode || day === 0) {
        return {
            type: 'manual',
            title: 'Domingo / Feriado (Horário Variável)',
            desc: 'Horário flexível. Utilize o botão de Iniciar e Encerrar plantão para registrar.',
            grossMinutes: 0,
            isShiftNow: !!state.activeShift,
        };
    } else if (day === 6) {
        // Sábado: 09:00 às 21:00 (12 horas = 720 min)
        const isNow = curTime >= 9 && curTime < 21;
        return {
            type: 'auto',
            title: '📅 Sábado: 09:00 às 21:00 (12h)',
            desc: 'Escala automática de Sábado. O sobreaviso é computado automaticamente sem você precisar lembrar de ativar.',
            grossMinutes: 720,
            isShiftNow: isNow,
        };
    } else {
        // Segunda a Sexta: 18:00 às 21:00 (3 horas = 180 min)
        const isNow = curTime >= 18 && curTime < 21;
        return {
            type: 'auto',
            title: '📅 Seg a Sex: 18:00 às 21:00 (3h)',
            desc: 'Escala automática dos dias úteis. O sobreaviso é computado automaticamente sem você precisar lembrar de ativar.',
            grossMinutes: 180,
            isShiftNow: isNow,
        };
    }
}

function updateShiftUI(isActive) {
    const autoBox = document.getElementById('shiftAutoBox');
    const manualBox = document.getElementById('shiftManualBox');
    const badge = document.getElementById('shiftBadge');
    const schedule = getScheduleForDate(new Date());

    const btnHoliday = document.getElementById('btnToggleHolidayMode');
    if (btnHoliday) {
        btnHoliday.textContent = state.forceHolidayMode 
            ? '🔄 Voltar para Escala Fixa Automática' 
            : '⚡ Ativar Modo Feriado / Horário Manual';
    }

    if (schedule.type === 'auto') {
        // Modo Automático (Seg-Sex e Sábado)
        if (autoBox) autoBox.style.display = 'block';
        if (manualBox) manualBox.style.display = 'none';

        document.getElementById('shiftAutoScheduleTitle').textContent = schedule.title;
        document.getElementById('shiftAutoDescription').innerHTML = `🛡️ <strong>Plantão Automático:</strong> ${schedule.desc}`;
        document.getElementById('shiftAutoGross').textContent = formatMinutes(schedule.grossMinutes);
        
        const timeStatus = document.getElementById('shiftAutoTimeStatus');
        if (schedule.isShiftNow) {
            timeStatus.textContent = '🟢 Dentro do Horário de Plantão';
            timeStatus.style.color = 'var(--success)';
            badge.className = 'badge badge-synced';
            badge.textContent = 'Em Plantão';
        } else {
            timeStatus.textContent = '⏱️ Escala Programada';
            timeStatus.style.color = 'var(--text-muted)';
            badge.className = 'badge';
            badge.textContent = 'Escala Fixa';
        }
    } else {
        // Modo Manual (Domingos e Feriados)
        if (autoBox) autoBox.style.display = 'none';
        if (manualBox) manualBox.style.display = 'block';

        const box = document.getElementById('shiftManualBox');
        const text = document.getElementById('shiftStatusText');
        const btn = document.getElementById('btnToggleShift');
        const details = document.getElementById('shiftDetails');

        if (isActive) {
            box.classList.add('active');
            badge.className = 'badge badge-synced';
            badge.textContent = 'Ativo';
            text.textContent = 'Em Sobreaviso (Manual)';
            btn.className = 'btn btn-danger';
            btn.textContent = '⏹ Encerrar Plantão';
            const startFormatted = new Date(state.activeShift.started_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            details.textContent = `Plantão iniciado às ${startFormatted}. Tempo em sobreaviso sendo computado.`;
        } else {
            box.classList.remove('active');
            badge.className = 'badge';
            badge.textContent = 'Inativo';
            text.textContent = 'Domingo / Feriado (Variável)';
            btn.className = 'btn btn-success';
            btn.textContent = '▶ Iniciar Plantão';
            details.textContent = 'Horário variável. Toque no botão para registrar o início e o término exatos.';
        }
    }
}

// ─── DASHBOARD & RESUMO DE HORAS COM FATORES TRABALHISTAS ─────────
async function refreshDashboard() {
    const allAttendances = await window.db.getAllAttendances();
    const pendingItems = await window.db.getPendingSyncItems();

    // Filtro de período selecionado pelo usuário
    const periodSelect = document.getElementById('selPeriodFilter');
    const periodFilter = periodSelect ? periodSelect.value : 'shift';

    let filteredAttendances = allAttendances;
    const now = new Date();

    if (periodFilter === 'shift' || periodFilter === 'today') {
        const todayStr = now.toISOString().slice(0, 10);
        if (state.activeShift) {
            // Atendimentos associados ao plantão atual ou do dia
            filteredAttendances = allAttendances.filter(a => 
                a.shift_uuid === state.activeShift.uuid || 
                a.started_at.startsWith(todayStr) ||
                new Date(a.started_at) >= new Date(state.activeShift.started_at)
            );
        } else {
            // Regime de escala automática: considera os atendimentos do dia de hoje
            filteredAttendances = allAttendances.filter(a => a.started_at.startsWith(todayStr));
        }
    } else if (periodFilter === 'month') {
        const monthStr = now.toISOString().slice(0, 7);
        filteredAttendances = allAttendances.filter(a => a.started_at.startsWith(monthStr));
    }

    const workedMins = filteredAttendances.reduce((acc, a) => acc + (a.duration_minutes || 0), 0);

    // ─── CÁLCULO DE SOBREAVISO BRUTO E LÍQUIDO PELA ESCALA DA EMPRESA ───
    let grossStandbyMins = 0;

    if (periodFilter === 'today' || periodFilter === 'shift') {
        const schedule = getScheduleForDate(now);
        if (schedule.type === 'auto') {
            // Seg-Sex: 180 min (3h) | Sábado: 720 min (12h)
            grossStandbyMins = schedule.grossMinutes;
        } else if (state.activeShift) {
            // Domingo/Feriado: calculado pelo tempo decorrido do plantão manual
            grossStandbyMins = Math.floor((now - new Date(state.activeShift.started_at)) / 60000);
        }
    } else {
        // Período estendido: calcula o sobreaviso bruto para as datas contempladas
        const distinctDays = new Set(filteredAttendances.map(a => a.started_at.slice(0, 10)));
        distinctDays.forEach(dayStr => {
            const d = new Date(dayStr + 'T12:00:00');
            const sched = getScheduleForDate(d);
            grossStandbyMins += sched.grossMinutes;
        });
        const todayStr = now.toISOString().slice(0, 10);
        if (!distinctDays.has(todayStr)) {
            const schedToday = getScheduleForDate(now);
            grossStandbyMins += schedToday.grossMinutes;
        }
    }

    // Sobreaviso líquido = tempo total de plantão menos o tempo gasto em atendimentos
    const standbyMins = Math.max(0, grossStandbyMins - workedMins);

    document.getElementById('metricStandby').textContent = formatMinutes(standbyMins);
    document.getElementById('metricWorked').textContent = formatMinutes(workedMins);
    document.getElementById('metricCount').textContent = filteredAttendances.length;
    document.getElementById('metricPending').textContent = pendingItems.totalPending;

    // ─── APURAÇÃO COM OS FATORES TRABALHISTAS ESPECÍFICOS ────────────
    let weekdayMins = 0;   // Semana: x 1.5
    let saturdayMins = 0;  // Sábado: x 1.75
    let sundayMins = 0;    // Domingo / Feriado: x 2.0

    filteredAttendances.forEach(att => {
        const d = new Date(att.started_at);
        const dayOfWeek = d.getDay(); // 0 = Domingo, 6 = Sábado, 1-5 = Seg-Sex
        const mins = att.duration_minutes || 0;

        if (dayOfWeek === 0) {
            sundayMins += mins;
        } else if (dayOfWeek === 6) {
            saturdayMins += mins;
        } else {
            weekdayMins += mins;
        }
    });

    // Horas equivalentes apuradas com multiplicadores
    const eqStandbyHours = (standbyMins / 60) * 0.333333;
    const eqWeekdayHours = (weekdayMins / 60) * 1.50;
    const eqSaturdayHours = (saturdayMins / 60) * 1.75;
    const eqSundayHours = (sundayMins / 60) * 2.00;

    const totalRawMins = standbyMins + workedMins;
    const totalPayableHours = eqStandbyHours + eqWeekdayHours + eqSaturdayHours + eqSundayHours;

    // Atualizar tabela de fatores no DOM
    document.getElementById('laborRawStandby').textContent = formatMinutes(standbyMins);
    document.getElementById('laborEqStandby').textContent = eqStandbyHours.toFixed(2).replace('.', ',') + 'h';

    document.getElementById('laborRawWeekday').textContent = formatMinutes(weekdayMins);
    document.getElementById('laborEqWeekday').textContent = eqWeekdayHours.toFixed(2).replace('.', ',') + 'h';

    document.getElementById('laborRawSaturday').textContent = formatMinutes(saturdayMins);
    document.getElementById('laborEqSaturday').textContent = eqSaturdayHours.toFixed(2).replace('.', ',') + 'h';

    document.getElementById('laborRawSunday').textContent = formatMinutes(sundayMins);
    document.getElementById('laborEqSunday').textContent = eqSundayHours.toFixed(2).replace('.', ',') + 'h';

    document.getElementById('laborRawTotal').textContent = formatMinutes(totalRawMins);
    document.getElementById('laborEqTotal').textContent = totalPayableHours.toFixed(2).replace('.', ',') + 'h';

    // Simulação financeira com base no valor da hora normal
    const rateInput = document.getElementById('inputHourlyRate');
    const hourlyRate = parseFloat(rateInput.value) || 0;
    const estimatedCost = totalPayableHours * hourlyRate;
    document.getElementById('laborEstimatedCost').textContent = 'R$ ' + estimatedCost.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Ouvinte para recalcular valor em R$ em tempo real caso o usuário altere a taxa
    if (!rateInput.dataset.listenerAttached) {
        rateInput.dataset.listenerAttached = 'true';
        rateInput.addEventListener('input', () => {
            const currentRate = parseFloat(rateInput.value) || 0;
            const cost = totalPayableHours * currentRate;
            document.getElementById('laborEstimatedCost').textContent = 'R$ ' + cost.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        });
    }

    // Atualizar badge da barra inferior
    const navBadge = document.getElementById('navPendingBadge');
    if (pendingItems.totalPending > 0) {
        navBadge.style.display = 'flex';
        navBadge.textContent = pendingItems.totalPending;
    } else {
        navBadge.style.display = 'none';
    }
}

// ─── AUTOCOMPLETE DE CLIENTES (OFFLINE) ──────────────────────────────
function initCustomerSearch() {
    const input = document.getElementById('custSearchInput');
    const resultsBox = document.getElementById('autocompleteResults');
    const selectedIdInput = document.getElementById('selectedCustomerId');
    const chkManual = document.getElementById('chkManualCustomer');
    const manualBox = document.getElementById('manualCustomerBox');

    let debounceTimer = null;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();

        if (query.length < 2) {
            resultsBox.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(async () => {
            const results = await window.db.searchCustomers(query, 10);
            renderAutocomplete(results);
        }, 150);
    });

    function renderAutocomplete(list) {
        resultsBox.innerHTML = '';
        if (list.length === 0) {
            resultsBox.innerHTML = '<div class="autocomplete-item"><div class="autocomplete-item-title">Nenhum cliente encontrado</div><div class="autocomplete-item-sub">Marque o checkbox abaixo para cliente novo.</div></div>';
            resultsBox.style.display = 'block';
            return;
        }

        list.forEach(c => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `
                <div class="autocomplete-item-title">${c.code ? `[${c.code}] ` : ''}${c.trade_name || c.name}</div>
                <div class="autocomplete-item-sub">${c.name} ${c.contact ? `• Contato: ${c.contact}` : ''}</div>
            `;
            item.addEventListener('click', () => {
                input.value = `${c.code ? `[${c.code}] ` : ''}${c.trade_name || c.name}`;
                selectedIdInput.value = c.id;
                if (c.contact) document.getElementById('contactName').value = c.contact;
                resultsBox.style.display = 'none';
            });
            resultsBox.appendChild(item);
        });

        resultsBox.style.display = 'block';
    }

    // Fechar ao clicar fora
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });

    // Checkbox de cliente manual
    chkManual.addEventListener('change', () => {
        const isManual = chkManual.checked;
        manualBox.style.display = isManual ? 'block' : 'none';
        input.disabled = isManual;
        if (isManual) {
            input.value = '';
            selectedIdInput.value = '';
            resultsBox.style.display = 'none';
        }
    });
}

// ─── CONTROLES DE HORÁRIO ────────────────────────────────────────────
function initTimeControls() {
    const timeStarted = document.getElementById('timeStarted');
    const timeEnded = document.getElementById('timeEnded');
    const btnNowStart = document.getElementById('btnNowStart');
    const btnNowEnd = document.getElementById('btnNowEnd');

    const now = new Date();
    timeStarted.value = toLocalDatetimeString(new Date(now.getTime() - 15 * 60000)); // 15 min atrás
    timeEnded.value = toLocalDatetimeString(now);

    btnNowStart.addEventListener('click', () => {
        timeStarted.value = toLocalDatetimeString(new Date());
    });

    btnNowEnd.addEventListener('click', () => {
        timeEnded.value = toLocalDatetimeString(new Date());
    });

    // Ouvinte para filtro de período no resumo de horas
    const periodSelect = document.getElementById('selPeriodFilter');
    if (periodSelect) {
        periodSelect.addEventListener('change', () => {
            refreshDashboard();
        });
    }
}

// ─── FORMULÁRIO DE ATENDIMENTO ───────────────────────────────────────
function initAttendanceForm() {
    const form = document.getElementById('attendanceForm');
    const selCat = document.getElementById('selCategory');
    const selSub = document.getElementById('selSubCategory');

    selCat.addEventListener('change', () => {
        const catId = parseInt(selCat.value);
        selSub.innerHTML = '<option value="">Selecione a subcategoria...</option>';

        const cat = state.categories.find(c => c.id === catId);
        if (cat && cat.subcategories && cat.subcategories.length > 0) {
            cat.subcategories.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name;
                selSub.appendChild(opt);
            });
            selSub.disabled = false;
        } else {
            selSub.innerHTML = '<option value="0">Sem subcategoria</option>';
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const isManual = document.getElementById('chkManualCustomer').checked;
        const customerId = isManual ? null : document.getElementById('selectedCustomerId').value;
        const manualName = document.getElementById('manualCustomerName').value.trim();

        if (!isManual && !customerId) {
            showToast('Por favor, selecione um cliente ou marque a opção de cliente avulso.', 'warning');
            return;
        }

        const startedAt = new Date(document.getElementById('timeStarted').value);
        const endedAt = new Date(document.getElementById('timeEnded').value);

        if (endedAt < startedAt) {
            showToast('A hora de fim não pode ser menor que a hora de início!', 'error');
            return;
        }

        const durationMins = Math.max(1, Math.round((endedAt - startedAt) / 60000));

        const attendance = {
            uuid: generateUUID(),
            shift_uuid: state.activeShift ? state.activeShift.uuid : null,
            user_id: state.activeAgentId,
            customer_id: customerId ? parseInt(customerId) : null,
            customer_name_fallback: isManual ? manualName : null,
            customer_display: isManual ? manualName : document.getElementById('custSearchInput').value,
            contact_name: document.getElementById('contactName').value.trim(),
            category_id: parseInt(document.getElementById('selCategory').value) || null,
            sub_category_id: parseInt(document.getElementById('selSubCategory').value) || null,
            started_at: startedAt.toISOString(),
            ended_at: endedAt.toISOString(),
            duration_minutes: durationMins,
            trouble: document.getElementById('txtTrouble').value.trim(),
            solution: document.getElementById('txtSolution').value.trim(),
            is_resolved: document.getElementById('chkResolved').checked,
            status_id: parseInt(document.getElementById('selStatus').value) || 2,
            ticket_id: null,
            synced: false,
            created_at: new Date().toISOString(),
        };

        await window.db.saveAttendance(attendance);

        showToast('Atendimento gravado offline com sucesso!', 'success');
        form.reset();
        initTimeControls();
        document.getElementById('manualCustomerBox').style.display = 'none';
        document.getElementById('custSearchInput').disabled = false;

        await refreshDashboard();
        switchTab('tab-list');
    });
}

// ─── LISTA DE ATENDIMENTOS ───────────────────────────────────────────
async function refreshAttendanceList() {
    const container = document.getElementById('attendancesContainer');
    const counter = document.getElementById('listCounter');
    const list = await window.db.getAllAttendances();

    counter.textContent = `${list.length} registros`;

    if (list.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 30px;">Nenhum atendimento registrado ainda.</p>';
        return;
    }

    container.innerHTML = '';
    list.forEach(item => {
        const card = document.createElement('div');
        card.className = 'attendance-card';

        const clientName = item.customer_display || item.customer_name_fallback || `Cliente #${item.customer_id}`;
        const startTime = new Date(item.started_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const endTime = new Date(item.ended_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const dateStr = new Date(item.started_at).toLocaleDateString([], { day: '2-digit', month: '2-digit' });

        const syncBadge = item.synced
            ? `<span class="badge badge-synced">🟢 Ticket #${item.ticket_id}</span>`
            : `<div style="display:flex; align-items:center;"><span class="badge badge-pending">🟡 Pendente Sync</span><button type="button" onclick="deleteLocalAttendance('${item.uuid}')" title="Excluir lançamento errado" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 6px; padding: 2px 8px; font-size: 11px; cursor: pointer; margin-left: 6px;">🗑️</button></div>`;

        card.innerHTML = `
            <div class="attendance-header">
                <div>
                    <div class="attendance-client">${clientName}</div>
                    <div class="attendance-contact">Contato: ${item.contact_name || 'Não informado'}</div>
                </div>
                ${syncBadge}
            </div>
            <div class="attendance-details">
                <strong>Problema:</strong> ${item.trouble}<br>
                ${item.solution ? `<strong>Solução:</strong> ${item.solution}` : ''}
            </div>
            <div class="attendance-footer">
                <span>📅 ${dateStr} das ${startTime} às ${endTime}</span>
                <span>⏱️ <strong>${item.duration_minutes} min</strong></span>
            </div>
        `;

        container.appendChild(card);
    });
}

// Função global para excluir atendimento local não sincronizado
window.deleteLocalAttendance = async function(uuid) {
    if (!confirm('Deseja realmente excluir este atendimento não sincronizado do aparelho?')) return;
    try {
        await window.db.deleteAttendance(uuid);
        showToast('Atendimento excluído com sucesso!', 'success');
        await refreshDashboard();
        await refreshAttendanceList();
    } catch (err) {
        showToast('Erro ao excluir: ' + err.message, 'error');
    }
};

// ─── CARREGAMENTO DE DADOS MESTRES (LOCAL E REMOTO) ─────────────────
async function loadLocalMasterData() {
    state.categories = await window.db.getCategories();
    state.statuses = await window.db.getStatuses();
    state.agents = await window.db.getAgents();

    // Fallbacks inteligentes caso o técnico nunca tenha baixado antes e esteja sem internet
    if (state.categories.length === 0) {
        state.categories = [
            { id: 1, name: 'Atendimento / Suporte Geral', subcategories: [{ id: 1, name: 'Dúvidas e Operação' }, { id: 2, name: 'Correção de Erro' }, { id: 3, name: 'Emissão Fiscal' }] },
            { id: 2, name: 'Sistema Inoperante / Urgente', subcategories: [{ id: 4, name: 'Parada Total' }, { id: 5, name: 'Banco de Dados / Servidor' }] }
        ];
    }

    if (state.statuses.length === 0) {
        state.statuses = [
            { id: 2, name: 'Resolvido' },
            { id: 1, name: 'Pendente' }
        ];
    }

    if (state.agents.length === 0) {
        state.agents = [
            { id: 1, name: 'Plantonista de Suporte' }
        ];
    }

    // Preencher Select de Categorias
    const selCat = document.getElementById('selCategory');
    selCat.innerHTML = '<option value="">Selecione a categoria...</option>';
    state.categories.forEach(cat => {
        const opt = document.createElement('option');
        opt.value = cat.id;
        opt.textContent = cat.name;
        selCat.appendChild(opt);
    });

    // Preencher Select de Status
    const selStatus = document.getElementById('selStatus');
    selStatus.innerHTML = '';
    state.statuses.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        if (s.name.toLowerCase().includes('resolv') || s.name.toLowerCase().includes('conclu')) {
            opt.selected = true;
        }
        selStatus.appendChild(opt);
    });

    // Preencher Select de Agentes
    const selAgent = document.getElementById('selAgent');
    selAgent.innerHTML = '';
    state.agents.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id;
        opt.textContent = a.name;
        if (a.id === state.activeAgentId) opt.selected = true;
        selAgent.appendChild(opt);
    });

    updateHeaderAgentName();
}

/**
 * Busca dados mestres automaticamente em segundo plano sempre que a rede estiver disponível,
 * sem exigir que o técnico aperte botões ou vá em configurações.
 */
async function autoSyncMasterDataInBackground() {
    try {
        const baseUrl = state.serverUrl || window.location.origin;
        if (!baseUrl.startsWith('http')) return;

        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 3500); // 3.5s timeout silencioso

        const response = await fetch(`${baseUrl.replace(/\/$/, '')}/api/v1/oncall/master-data`, {
            method: 'GET',
            signal: controller.signal
        });
        clearTimeout(timer);

        if (response.ok) {
            const result = await response.json();
            if (result.success && result.data) {
                await window.db.saveMasterData(result.data);
                await window.db.setSetting('last_master_sync', new Date().toISOString());
                await loadLocalMasterData();
                await updateDiagnostics();
                console.log('[AutoSync] Dados mestres e agentes sincronizados em background.');
            }
        }
    } catch (e) {
        // Silencioso: se estiver fora do Wi-Fi da empresa, continua com os dados locais
        console.log('[AutoSync] Servidor offline ou fora do alcance da intranet. Usando cache local.');
    }
}

function updateHeaderAgentName() {
    const agent = state.agents.find(a => a.id === state.activeAgentId);
    document.getElementById('headerAgentName').textContent = agent ? `Agente: ${agent.name}` : 'Agente Selecionado';
}

// ─── SINCRONIZAÇÃO COM A INTRANET ────────────────────────────────────
function initSyncSettings() {
    const btnFetch = document.getElementById('btnFetchMasterData');
    const btnSync = document.getElementById('btnSyncNow');
    const serverInput = document.getElementById('serverUrlInput');
    const selAgent = document.getElementById('selAgent');

    serverInput.addEventListener('change', async () => {
        state.serverUrl = serverInput.value.trim();
        await window.db.setSetting('server_url', state.serverUrl);
        showToast('URL do servidor atualizada!', 'success');
    });

    selAgent.addEventListener('change', async () => {
        state.activeAgentId = parseInt(selAgent.value) || 1;
        await window.db.setSetting('active_agent_id', state.activeAgentId);
        updateHeaderAgentName();
        showToast('Agente ativo alterado!', 'success');
    });

    btnFetch.addEventListener('click', async () => {
        await fetchRemoteMasterData();
    });

    btnSync.addEventListener('click', async () => {
        await syncPendingRecords();
    });
}

async function fetchRemoteMasterData() {
    const btn = document.getElementById('btnFetchMasterData');
    btn.disabled = true;
    btn.textContent = '⏳ Baixando dados mestres...';

    const baseUrl = state.serverUrl || window.location.origin;
    const url = `${baseUrl.replace(/\/$/, '')}/api/v1/oncall/master-data`;

    try {
        const response = await fetch(url, { method: 'GET' });
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Falha ao buscar dados');

        await window.db.saveMasterData(result.data);
        await window.db.setSetting('last_master_sync', new Date().toISOString());

        await loadLocalMasterData();
        await updateDiagnostics();

        showToast(`Dados mestres atualizados! ${result.data.customers.length} clientes carregados.`, 'success');
    } catch (err) {
        console.error('[Sync] Erro no download:', err);
        showToast('Erro ao baixar dados da intranet: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = '📥 Baixar / Atualizar Dados Mestres (Clientes & Categorias)';
    }
}

async function syncPendingRecords() {
    const btn = document.getElementById('btnSyncNow');
    const pending = await window.db.getPendingSyncItems();

    if (pending.totalPending === 0) {
        showToast('Não há nenhum chamado ou plantão pendente para sincronizar.', 'warning');
        return;
    }

    btn.disabled = true;
    btn.textContent = '⏳ Enviando para o servidor...';

    const baseUrl = state.serverUrl || window.location.origin;
    const url = `${baseUrl.replace(/\/$/, '')}/api/v1/oncall/sync`;

    const payload = {
        agent_id: state.activeAgentId,
        shifts: pending.shifts,
        attendances: pending.attendances,
    };

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Falha na sincronização');

        // Atualizar chamados locais com status sincronizado e ID do ticket oficial
        if (result.synced_attendances) {
            for (const synced of result.synced_attendances) {
                const att = await window.db.getAttendance(synced.uuid);
                if (att) {
                    att.synced = true;
                    att.ticket_id = synced.ticket_id;
                    await window.db.saveAttendance(att);
                }
            }
        }

        // Atualizar turnos locais
        if (result.synced_shifts) {
            for (const synced of result.synced_shifts) {
                const shift = await window.db.getShift(synced.uuid);
                if (shift) {
                    shift.synced = true;
                    await window.db.saveShift(shift);
                }
            }
        }

        await window.db.setSetting('last_records_sync', new Date().toISOString());

        showToast(`Sincronização concluída! ${result.synced_attendances?.length || 0} tickets gerados.`, 'success');
        await refreshDashboard();
        await refreshAttendanceList();
        await updateDiagnostics();
    } catch (err) {
        console.error('[Sync] Erro no envio:', err);
        showToast('Erro ao sincronizar com a intranet: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = '🔄 Sincronizar Atendimentos Agora';
    }
}

async function updateDiagnostics() {
    const custCount = await window.db.getCustomerCount();
    const cats = await window.db.getCategories();
    const atts = await window.db.getAllAttendances();
    const lastSync = await window.db.getSetting('last_records_sync', null);

    document.getElementById('diagCustomers').textContent = `${custCount} clientes`;
    document.getElementById('diagCategories').textContent = `${cats.length} categorias`;
    document.getElementById('diagAttendances').textContent = `${atts.length} chamados`;
    document.getElementById('diagLastSync').textContent = lastSync ? new Date(lastSync).toLocaleString() : 'Nunca';
}
