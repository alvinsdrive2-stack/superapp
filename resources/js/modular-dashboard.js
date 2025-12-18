import Chart from 'chart.js/auto';

class ModularDashboard {
    constructor() {
        this.charts = {};
        this.data = null;
        this.loading = false;
        this.filters = {
            period_type: '6_months',
            group_by: 'month',
            include_comparison: false,
            comparison_type: 'previous_period',
            max_provinces: 10,
            start_date: null,
            end_date: null
        };
        this.refreshInterval = null;
        this.timeUpdateInterval = null;
        this.init();
    }

    async init() {
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupDashboard());
        } else {
            this.setupDashboard();
        }
    }

    setupDashboard() {
        this.setupFilterControls();
        this.startAutoRefresh();
        this.loadDashboardData();
        this.startTimeUpdate();
    }

    setupFilterControls() {
        // Period selector
        const periodSelect = document.getElementById('periodSelect');
        if (periodSelect) {
            periodSelect.value = this.filters.period_type;
            periodSelect.addEventListener('change', (e) => {
                this.filters.period_type = e.target.value;
                this.loadDashboardData();
            });
        }

        // Group by selector
        const groupBySelect = document.getElementById('groupBySelect');
        if (groupBySelect) {
            groupBySelect.value = this.filters.group_by;
            groupBySelect.addEventListener('change', (e) => {
                this.filters.group_by = e.target.value;
                this.loadDashboardData();
            });
        }

        // Comparison toggle
        const comparisonToggle = document.getElementById('comparisonToggle');
        if (comparisonToggle) {
            comparisonToggle.checked = this.filters.include_comparison;
            comparisonToggle.addEventListener('change', (e) => {
                this.filters.include_comparison = e.target.checked;
                this.loadDashboardData();
            });
        }

        // Comparison type selector
        const comparisonTypeSelect = document.getElementById('comparisonTypeSelect');
        if (comparisonTypeSelect) {
            comparisonTypeSelect.value = this.filters.comparison_type;
            comparisonTypeSelect.addEventListener('change', (e) => {
                this.filters.comparison_type = e.target.value;
                if (this.filters.include_comparison) {
                    this.loadDashboardData();
                }
            });
        }

        // Custom date range
        const customDateToggle = document.getElementById('customDateToggle');
        const startDateInput = document.getElementById('startDateInput');
        const endDateInput = document.getElementById('endDateInput');

        if (customDateToggle && startDateInput && endDateInput) {
            customDateToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    startDateInput.disabled = false;
                    endDateInput.disabled = false;
                    this.filters.start_date = startDateInput.value;
                    this.filters.end_date = endDateInput.value;
                } else {
                    startDateInput.disabled = true;
                    endDateInput.disabled = true;
                    this.filters.start_date = null;
                    this.filters.end_date = null;
                }
                this.loadDashboardData();
            });

            startDateInput.addEventListener('change', () => {
                this.filters.start_date = startDateInput.value;
                this.loadDashboardData();
            });

            endDateInput.addEventListener('change', () => {
                this.filters.end_date = endDateInput.value;
                this.loadDashboardData();
            });
        }
    }

    async loadDashboardData() {
        if (this.loading) return;

        this.loading = true;
        this.showLoadingState();

        try {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key] !== null) {
                    params.append(key, this.filters[key]);
                }
            });

            const response = await fetch(`/api/dashboard/data?${params}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.data = result.data;
                await this.updateAllCharts();
                this.updateKeyMetrics();
                this.updateSummaryStats();
                this.showInsights(result.data.key_insights || []);
            } else {
                throw new Error(result.message || 'Failed to load data');
            }

        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showError('Gagal memuat data: ' + error.message);
        } finally {
            this.loading = false;
            this.hideLoadingState();
        }
    }

    async updateAllCharts() {
        if (!this.data) return;

        // Update main time series chart
        await this.updateTimeSeriesChart(this.data.time_series);

        // Update province bar chart
        await this.updateProvinceChart(this.data.provinces?.top_10 || []);

        // Update province cards
        this.updateProvinceCards(this.data.provinces?.top_8_for_cards || []);

        // Update progress bars
        this.updateProgressBars(this.data.provinces?.top_3_for_progress || []);

        // Update comparison charts if available
        if (this.data.comparison) {
            this.updateComparisonCharts(this.data.comparison);
        }
    }

    async updateTimeSeriesChart(timeSeriesData) {
        const ctx = document.getElementById('pencatatanChart');
        if (!ctx) return;

        // Hide skeleton and show canvas
        const skeleton = document.getElementById('mainChartSkeleton');
        if (skeleton) skeleton.style.display = 'none';
        ctx.style.display = 'block';

        // Destroy existing chart
        if (this.charts.pencatatan) {
            this.charts.pencatatan.destroy();
        }

        this.charts.pencatatan = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeSeriesData.labels,
                datasets: timeSeriesData.datasets.map(dataset => ({
                    ...dataset,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }))
            },
            options: this.getLineChartOptions()
        });
    }

    async updateProvinceChart(provinceData) {
        const ctx = document.getElementById('provinceBarChart');
        if (!ctx) return;

        // Hide skeleton and show canvas
        const skeleton = document.getElementById('provinceChartSkeleton');
        if (skeleton) skeleton.style.display = 'none';
        ctx.style.display = 'block';

        // Destroy existing chart
        if (this.charts.province) {
            this.charts.province.destroy();
        }

        // Transform data for chart
        const labels = provinceData.map(p => p.name);
        const datasets = provinceData.map((province, index) => {
            const colors = [
                'rgba(79, 70, 229, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(55, 48, 163, 0.8)',
                'rgba(107, 33, 168, 0.8)',
                'rgba(14, 165, 233, 0.8)',
                'rgba(251, 146, 60, 0.8)',
                'rgba(34, 197, 94, 0.8)'
            ];

            // Get monthly data if available
            const data = province.monthly_data || [province.total];

            return {
                label: province.name,
                data: data,
                backgroundColor: colors[index % colors.length],
                borderColor: colors[index % colors.length].replace('0.8', '1'),
                borderWidth: 2,
                borderRadius: 4
            };
        });

        this.charts.province = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: datasets[0]?.data.map((_, i) => `Month ${i + 1}`) || [],
                datasets: datasets
            },
            options: this.getBarChartOptions()
        });
    }

    updateProvinceCards(provinces) {
        const container = document.getElementById('provinceStatsContainer');
        const skeleton = document.getElementById('provinceStatsSkeleton');

        if (!container) return;

        if (skeleton) skeleton.classList.add('hidden');
        container.classList.remove('hidden');

        container.innerHTML = `
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                ${provinces.map((province, index) => `
                    <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-sm font-semibold text-gray-900">${province.name}</div>
                            <div class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">
                                #${province.rank}
                            </div>
                        </div>
                        <div class="text-lg font-bold text-gray-900 mb-2">
                            ${province.total.toLocaleString('id-ID')}
                        </div>
                        <div class="text-xs text-gray-500">
                            Total izin
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    updateProgressBars(provinces) {
        const container = document.getElementById('topProvincesProgress');
        const skeleton = document.getElementById('progressSkeleton');

        if (!container) return;

        if (skeleton) skeleton.classList.add('hidden');
        container.classList.remove('hidden');

        const maxValue = Math.max(...provinces.map(p => p.total));
        const colors = ['blue', 'green', 'orange'];

        container.innerHTML = provinces.map((province, index) => {
            const percentage = Math.round((province.total / maxValue) * 100);
            const color = colors[index];

            return `
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900">${index + 1}. ${province.name}</span>
                        <span class="text-sm font-bold text-${color}-600">${province.total.toLocaleString('id-ID')}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 relative overflow-hidden">
                        <div class="bg-gradient-to-r from-${color}-500 to-${color}-600 h-full rounded-full flex items-center justify-end pr-3 transition-all duration-500"
                             style="width: ${percentage}%">
                            <span class="text-xs font-medium text-white">${percentage}%</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    updateKeyMetrics() {
        if (!this.data) return;

        // Update Top Region
        const topRegionEl = document.getElementById('topRegion');
        if (topRegionEl && this.data.provinces?.top_10?.[0]) {
            topRegionEl.textContent = this.data.provinces.top_10[0].name;
        }

        // Update trends from time series data
        if (this.data.time_series?.trends) {
            const trends = this.data.time_series.trends;
            Object.keys(trends).forEach(source => {
                const trend = trends[source];
                const trendId = source.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();

                // Update trend displays
                this.updateTrendDisplay(trendId, trend);
            });
        }
    }

    updateTrendDisplay(sourceId, trend) {
        const elements = document.querySelectorAll(`[data-trend="${sourceId}"]`);
        elements.forEach(el => {
            const value = trend.percentage_change;
            const icon = el.querySelector('.trend-icon');
            const valueEl = el.querySelector('.trend-value');

            if (icon) {
                icon.className = `fas trend-icon ${value > 0 ? 'fa-arrow-up text-green-500' : value < 0 ? 'fa-arrow-down text-red-500' : 'fa-minus text-gray-500'}`;
            }

            if (valueEl) {
                valueEl.textContent = `${value > 0 ? '+' : ''}${value.toFixed(1)}%`;
            }
        });
    }

    updateSummaryStats() {
        if (!this.data?.summary) return;

        // Update total izin
        const totalEl = document.getElementById('totalIzinSummary');
        if (totalEl) {
            totalEl.textContent = this.data.summary.total_izin.toLocaleString('id-ID');
        }

        // Update other summary stats as needed
    }

    showInsights(insights) {
        const container = document.getElementById('insightsContainer');
        if (!container || !insights.length) return;

        container.innerHTML = insights.map(insight => `
            <div class="alert alert-${insight.type === 'positive' ? 'success' : 'info'} mb-3">
                <h5 class="font-semibold">${insight.title}</h5>
                <p class="text-sm">${insight.message}</p>
            </div>
        `).join('');
    }

    getLineChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    align: 'center'
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.98)',
                    titleColor: '#111827',
                    bodyColor: '#374151',
                    borderColor: '#e5e7eb',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah Izin'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Periode'
                    }
                }
            }
        };
    }

    getBarChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah Izin'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Periode'
                    }
                }
            }
        };
    }

    showLoadingState() {
        // Show skeleton loaders
        document.querySelectorAll('[id$="Skeleton"]').forEach(el => {
            el.style.display = 'block';
        });

        // Hide canvases
        document.querySelectorAll('canvas').forEach(el => {
            el.style.display = 'none';
        });
    }

    hideLoadingState() {
        // Hide skeleton loaders
        document.querySelectorAll('[id$="Skeleton"]').forEach(el => {
            el.style.display = 'none';
        });
    }

    showError(message) {
        // Show error in a user-friendly way
        const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

        const container = document.querySelector('.dashboard-content');
        if (container) {
            container.insertAdjacentHTML('afterbegin', alertHtml);
        }
    }

    startAutoRefresh() {
        // Clear existing interval
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }

        // Auto-refresh every 5 minutes
        this.refreshInterval = setInterval(() => {
            if (!this.loading) {
                this.loadDashboardData();
            }
        }, 300000);
    }

    startTimeUpdate() {
        // Update current time display
        this.timeUpdateInterval = setInterval(() => {
            const timeEl = document.getElementById('currentTime');
            if (timeEl) {
                const now = new Date();
                const options = {
                    timeZone: 'Asia/Jakarta',
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                };
                timeEl.textContent = now.toLocaleString('id-ID', options);
            }
        }, 1000);
    }

    destroy() {
        // Clear intervals
        if (this.refreshInterval) clearInterval(this.refreshInterval);
        if (this.timeUpdateInterval) clearInterval(this.timeUpdateInterval);

        // Destroy charts
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
    }
}

// Global functions for backward compatibility
window.refreshProvinceStats = function() {
    if (window.modularDashboard) {
        window.modularDashboard.loadDashboardData();
    }
};

window.toggleProvinceChartType = function() {
    if (window.modularDashboard && window.modularDashboard.charts.province) {
        const chart = window.modularDashboard.charts.province;
        chart.config.options.indexAxis = chart.config.options.indexAxis === 'y' ? 'x' : 'y';
        chart.update();
    }
};

// Initialize dashboard
window.modularDashboard = new ModularDashboard();