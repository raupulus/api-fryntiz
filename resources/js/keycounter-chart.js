import {
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Title,
    Tooltip,
    Legend,
    Filler,
);

function readChartThemeColors() {
    const styles = getComputedStyle(document.documentElement);
    const isDark = document.documentElement.classList.contains('dark');

    return {
        textColor: (styles.getPropertyValue('--color-on-surface') || (isDark ? '#e6edf3' : '#1e293b')).trim(),
        mutedColor: (styles.getPropertyValue('--color-on-surface-variant') || (isDark ? '#94a3b8' : '#64748b')).trim(),
        gridColor: isDark ? 'rgba(148, 163, 184, 0.16)' : 'rgba(15, 23, 42, 0.1)',
    };
}

function normalizeDatasets(rawDatasets) {
    if (!Array.isArray(rawDatasets)) {
        return [];
    }

    return rawDatasets.map((entry) => ({
        ...entry,
        fill: entry.fill === 'false' ? false : Boolean(entry.fill),
        tension: typeof entry.lineTension === 'number' ? entry.lineTension : (entry.tension ?? 0.25),
    }));
}

function initKeycounterChart() {
    const canvas = document.getElementById('line-chart');
    const dataScript = document.getElementById('keycounter-chart-data');

    if (!canvas || !dataScript) {
        return;
    }

    let payload;
    try {
        payload = JSON.parse(dataScript.textContent || '{}');
    } catch {
        payload = {};
    }

    const labels = Array.isArray(payload.labels) ? payload.labels : [];
    const datasets = normalizeDatasets(payload.datasets);
    const xLabel = typeof payload.xLabel === 'string' ? payload.xLabel : '';

    const initialTheme = readChartThemeColors();

    const chartInstance = new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: initialTheme.textColor,
                    },
                },
                title: {
                    display: true,
                    text: 'Gráfica de pulsaciones diarias por dispositivos',
                    color: initialTheme.textColor,
                },
            },
            scales: {
                y: {
                    ticks: {
                        color: initialTheme.mutedColor,
                    },
                    grid: {
                        color: initialTheme.gridColor,
                    },
                    title: {
                        display: true,
                        text: 'Pulsaciones Totales',
                        color: initialTheme.textColor,
                    },
                },
                x: {
                    ticks: {
                        color: initialTheme.mutedColor,
                    },
                    grid: {
                        color: initialTheme.gridColor,
                    },
                    title: {
                        display: true,
                        text: xLabel,
                        color: initialTheme.textColor,
                    },
                },
            },
        },
    });

    new MutationObserver(() => {
        const theme = readChartThemeColors();
        chartInstance.options.plugins.title.color = theme.textColor;
        chartInstance.options.plugins.legend.labels.color = theme.textColor;
        chartInstance.options.scales.y.ticks.color = theme.mutedColor;
        chartInstance.options.scales.y.grid.color = theme.gridColor;
        chartInstance.options.scales.y.title.color = theme.textColor;
        chartInstance.options.scales.x.ticks.color = theme.mutedColor;
        chartInstance.options.scales.x.grid.color = theme.gridColor;
        chartInstance.options.scales.x.title.color = theme.textColor;
        chartInstance.update();
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
}

function initPeriodFiltersAndLocalHour() {
    const yearSelect = document.querySelector('select[name="year"]');
    const monthSelect = document.querySelector('select[name="month"]');

    if (yearSelect && monthSelect) {
        yearSelect.addEventListener('change', function () {
            const selectedYear = parseInt(this.value, 10);
            const currentYear = new Date().getFullYear();
            const currentMonth = new Date().getMonth() + 1;

            Array.from(monthSelect.options).forEach((option) => {
                const monthVal = parseInt(option.value, 10);
                option.disabled = (selectedYear === currentYear && monthVal > currentMonth);
            });

            if (selectedYear === currentYear && parseInt(monthSelect.value, 10) > currentMonth) {
                monthSelect.value = String(currentMonth);
            }
        });
    }

    const topHourEl = document.querySelector('[data-top-hour-utc]');
    const target = document.getElementById('top-hour-local');
    if (topHourEl && target) {
        const utcHour = parseInt(topHourEl.getAttribute('data-top-hour-utc') || '0', 10);
        const localDate = new Date(Date.UTC(2000, 0, 1, utcHour, 0, 0));
        const localHour = String(localDate.getHours()).padStart(2, '0');
        target.textContent = `${localHour}:00`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initKeycounterChart();
    initPeriodFiltersAndLocalHour();
});
