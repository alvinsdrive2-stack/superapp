import Chart from 'chart.js/auto';

class ProfessionalDashboard {
    constructor() {
        this.charts = {};
        this.data = {};
        this.comparisonMode = 'year'; // 'year' or 'month'
        this.selectedYear = 2023;
        this.init();
    }

    async init() {
        console.log('Initializing Professional Dashboard...');
        await this.loadData();
        this.initCharts();
        this.setupEventListeners();
        this.startAnimations();
    }

    async loadData() {
        try {
            const [totalsResponse, chartResponse, provinceResponse, dailyResponse] = await Promise.all([
                fetch('/api/dashboard/totals'),
                fetch('/api/dashboard/chart-data?months=12'),
                fetch('/api/dashboard/province-ranking?limit=10'),
                fetch('/api/dashboard/daily-stats?days=7')
            ]);

            this.data = {
                totals: await totalsResponse.json(),
                chart: await chartResponse.json(),
                provinces: await provinceResponse.json(),
                daily: await dailyResponse.json()
            };

            // Generate comparison data (year-over-year)
            this.generateComparisonData();

        } catch (error) {
            console.error('Failed to load data:', error);
        }
    }

    generateComparisonData() {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Simulate previous year data (in real app, this would come from API)
        this.data.previousYear = {
            labels: months,
            datasets: this.data.chart.data.timeSeries.datasets.map(dataset => ({
                ...dataset,
                data: dataset.data.map(value => Math.floor(value * 0.8 + Math.random() * 200)),
                borderColor: dataset.borderColor.replace(')', ', 0.5)').replace('rgb', 'rgba'),
                backgroundColor: dataset.backgroundColor.replace(')', ', 0.3)').replace('rgb', 'rgba'),
            }))
        };
    }

    initCharts() {
        this.updateKPICards();
        this.createMainTimeChart();
        this.createDistributionChart();
        this.createProvinceChart();
        this.createDailyChart();
    }

    updateKPICards() {
        if (!this.data.totals.success) return;

        const totals = this.data.totals.data;
        const kpiValues = document.querySelectorAll('[data-metric]');

        kpiValues.forEach(element => {
            const metric = element.getAttribute('data-metric');
            if (totals[metric] !== undefined) {
                this.animateValue(element, 0, totals[metric], 1500);
            }
        });
    }

    animateValue(element, start, end, duration) {
        const isNegative = end < 0;
        const absEnd = Math.abs(end);
        const startTime = performance.now();

        const updateValue = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentValue = Math.floor(start + (absEnd - start) * easeOutQuart);

            element.textContent = (isNegative ? '-' : '') + currentValue.toLocaleString('id-ID');

            if (progress < 1) {
                requestAnimationFrame(updateValue);
            }
        };

        requestAnimationFrame(updateValue);
    }

    createMainTimeChart() {
        const ctx = document.getElementById('mainTimeChart');
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.charts.mainTime) {
            this.charts.mainTime.destroy();
            this.charts.mainTime = null;
        }

        const currentData = this.data.chart.data.timeSeries;
        const previousData = this.data.previousYear;

        this.charts.mainTime = new Chart(ctx, {
            type: 'line',
            data: {
                labels: currentData.labels,
                datasets: [
                    {
                        label: 'Current Year',
                        data: currentData.datasets[0].data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                    },
                    {
                        label: 'Previous Year',
                        data: previousData.datasets[0].data,
                        borderColor: '#f093fb',
                        backgroundColor: 'rgba(240, 147, 251, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#f093fb',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        padding: 16,
                        borderRadius: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    createDistributionChart() {
        const ctx = document.getElementById('distributionChart');
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.charts.distribution) {
            this.charts.distribution.destroy();
            this.charts.distribution = null;
        }

        const totals = this.data.totals.data;

        this.charts.distribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Balai', 'Reguler', 'FG'],
                datasets: [{
                    data: [totals.balai, totals.reguler, totals.fg],
                    backgroundColor: [
                        'rgba(79, 172, 254, 0.8)',
                        'rgba(250, 112, 154, 0.8)',
                        'rgba(254, 225, 64, 0.8)'
                    ],
                    borderColor: [
                        'rgba(79, 172, 254, 1)',
                        'rgba(250, 112, 154, 1)',
                        'rgba(254, 225, 64, 1)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed.toLocaleString('id-ID') + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    createProvinceChart() {
        const ctx = document.getElementById('provinceChart');
        if (!ctx || !this.data.provinces.success) return;

        // Destroy existing chart if it exists
        if (this.charts.province) {
            this.charts.province.destroy();
            this.charts.province = null;
        }

        const provinceData = this.data.provinces.data.rankings.slice(0, 5);
        const colors = [
            'rgba(102, 126, 234, 0.8)',
            'rgba(240, 147, 251, 0.8)',
            'rgba(79, 172, 254, 0.8)',
            'rgba(254, 225, 64, 0.8)',
            'rgba(250, 112, 154, 0.8)'
        ];

        this.charts.province = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: provinceData.map((p, i) => `#${i + 1} ${p.provinsi}`),
                datasets: [{
                    label: 'Total Records',
                    data: provinceData.map(p => p.total),
                    backgroundColor: colors,
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Total: ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    createDailyChart() {
        const ctx = document.getElementById('dailyChart');
        if (!ctx || !this.data.daily.success) return;

        // Destroy existing chart if it exists
        if (this.charts.daily) {
            this.charts.daily.destroy();
            this.charts.daily = null;
        }

        const dailyData = this.data.daily.data.daily_stats;
        const last7Days = Object.keys(dailyData).slice(-7);
        const labels = last7Days.map(date => {
            const d = new Date(date);
            return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' });
        });

        const totals = last7Days.map(date =>
            dailyData[date].balai + dailyData[date].reguler + dailyData[date].fg
        );

        this.charts.daily = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Records',
                    data: totals,
                    backgroundColor: totals.map((_, i) =>
                        `rgba(102, 126, 234, ${0.4 + (i * 0.1)})`
                    ),
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Records: ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    }

    setupEventListeners() {
        // Comparison toggle buttons
        const toggleBtns = document.querySelectorAll('.toggle-btn');
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                toggleBtns.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');

                this.comparisonMode = e.target.dataset.comparison;
                this.updateMainChart();
            });
        });

        // Year selector
        const yearSelector = document.getElementById('yearSelector');
        if (yearSelector) {
            yearSelector.addEventListener('change', (e) => {
                this.selectedYear = parseInt(e.target.value);
                this.updateMainChart();
            });
        }

        // Refresh button
        const refreshBtn = document.querySelector('.refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', (e) => {
                e.target.classList.add('spinning');
                this.refreshCharts().finally(() => {
                    setTimeout(() => {
                        e.target.classList.remove('spinning');
                    }, 1000);
                });
            });
        }

        // KPI card interactions
        const metricCards = document.querySelectorAll('.metric-card');
        metricCards.forEach(card => {
            card.addEventListener('click', () => {
                this.showKPIModal(card);
            });
        });
    }

    updateMainChart() {
        // In a real implementation, this would fetch new data based on comparison mode
        console.log(`Updating chart for ${this.comparisonMode} comparison, year ${this.selectedYear}`);

        // For demo purposes, just regenerate the chart with animation
        const ctx = document.getElementById('mainTimeChart');
        if (ctx && this.charts.mainTimeChart) {
            this.charts.mainTimeChart.destroy();
            this.createMainTimeChart();
        }
    }

    async refreshCharts() {
        console.log('Refreshing all charts...');

        // Reload all data
        await this.loadData();

        // Destroy existing charts
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });

        // Recreate charts
        this.initCharts();
    }

    showKPIModal(card) {
        const metric = card.querySelector('[data-metric]').getAttribute('data-metric');
        const value = card.querySelector('[data-metric]').textContent;

        console.log(`KPI Modal for ${metric}: ${value}`);
        // In a real implementation, this would show a detailed modal
    }

    startAnimations() {
        // Add hover effects to metric cards
        const metricCards = document.querySelectorAll('.metric-card');
        metricCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in');
        });

        // Add parallax effect to floating shapes
        document.addEventListener('mousemove', (e) => {
            const shapes = document.querySelectorAll('.shape');
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;

            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 10;
                shape.style.transform = `translate(${x * speed}px, ${y * speed}px)`;
            });
        });
    }

    destroy() {
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.professionalDashboard = new ProfessionalDashboard();
});

// Global refresh function
window.refreshCharts = () => {
    if (window.professionalDashboard) {
        window.professionalDashboard.refreshCharts();
    }
};

export default ProfessionalDashboard;