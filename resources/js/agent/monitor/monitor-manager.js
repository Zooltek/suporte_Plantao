import Chart from 'chart.js/auto';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';

/**
 * Monitor de Agentes - Lógica de Atualização e Gráficos
 */
globalThis.MonitorActions = {

    chartInstance: null,

    async reloadTable() {
        const container = document.getElementById('monitor-table-container');
        if (!container) return;

        try {
            const currentUrl = new URL(globalThis.location.href);
            currentUrl.searchParams.set('compact', 'true');
            const response = await fetch(currentUrl.toString());

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const html = await response.text();

            // cria um parser temporário para extrair apenas a tabela nova
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTable = doc.getElementById('monitor-table');

            if (newTable && container.querySelector('#monitor-table')) {
                container.querySelector('#monitor-table').innerHTML = newTable.innerHTML;
            }
        } catch (error) {
            console.error('[Monitor] Falha na atualização automática:', error.message);
        }
    },

    /**
     * inicializa o Chart.js e armazena a instância para conformidade com linters
     */
    initChart() {
        const ctx = document.getElementById('myChart');
        if (!ctx) return;

        const labels = JSON.parse(ctx.dataset.labels || '[]');
        const values = JSON.parse(ctx.dataset.values || '[]');

        this.chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Frequência de Chamados',
                    data: values,
                    backgroundColor: 'rgba(0, 62, 177, 0.1)',
                    borderColor: 'rgba(0, 62, 177, 0.8)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    },

    initDatepickers() {
        flatpickr("input[name='start'], input[name='end']", {
            locale: Portuguese,
            dateFormat: "d/m/Y",
            allowInput: true,
            disableMobile: "true"
        });
    }
};

// inicialização ao carregar o DOM
document.addEventListener('DOMContentLoaded', () => {
    globalThis.MonitorActions.initChart();
    globalThis.MonitorActions.initDatepickers();
});