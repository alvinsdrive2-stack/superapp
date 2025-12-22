import Chart from 'chart.js/auto';
import './dashboard-data-service.js';

class OptimizedDashboard {
    constructor() {
        this.charts = {};
        this.dataService = window.dashboardDataService;
        this.filters = {
            period_type: '6_months',
            group_by: 'month',
            include_comparison: false,
            comparison_type: 'previous_period',
            max_provinces: 10,
            start_date: null,
            end_date: null,
            region_filter: null
        };
        this.refreshInterval = null;
        this.timeUpdateInterval = null;
        this.monthCheckInterval = null;
        this.lastMonth = null;
        this.currentTimeElement = null;
        this.currentMonth = '';
        this.currentYear = '';
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

    async setupDashboard() {
        this.updateTimezone();
        this.setupFilterControls();
        this.startTimeUpdate();
        this.startMonthCheck();

        // Preload data
        await this.dataService.preloadData();

        // Load dashboard
        await this.loadDashboardData();

        // Start auto-refresh
        this.startAutoRefresh();
    }

    updateTimezone() {
        const now = new Date();
        this.currentMonth = now.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', month: 'long' });
        this.currentYear = now.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', year: 'numeric' });
        this.lastMonth = this.currentMonth;
    }

    startTimeUpdate() {
        this.currentTimeElement = document.getElementById('currentTime');
        this.updateCurrentTime();

        if (this.timeUpdateInterval) {
            clearInterval(this.timeUpdateInterval);
        }

        this.timeUpdateInterval = setInterval(() => this.updateCurrentTime(), 1000);
    }

    updateCurrentTime() {
        if (this.currentTimeElement) {
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
            this.currentTimeElement.textContent = now.toLocaleString('id-ID', options);
        }
    }

    startMonthCheck() {
        if (this.monthCheckInterval) {
            clearInterval(this.monthCheckInterval);
        }

        this.monthCheckInterval = setInterval(() => {
            const now = new Date();
            const currentMonth = now.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', month: 'long' });

            if (currentMonth !== this.lastMonth) {
                this.lastMonth = currentMonth;
                this.updateTimezone();
            }
        }, 60000);
    }

    setupFilterControls() {
        // Period selector
        const periodSelect = document.getElementById('periodSelect');
        if (periodSelect) {
            periodSelect.value = this.filters.period_type;
            periodSelect.addEventListener('change', (e) => {
                this.filters.period_type = e.target.value;
                this.applyFilters();
            });
        }

        // Group by selector
        const groupBySelect = document.getElementById('groupBySelect');
        if (groupBySelect) {
            groupBySelect.value = this.filters.group_by;
            groupBySelect.addEventListener('change', (e) => {
                this.filters.group_by = e.target.value;
                this.applyFilters();
            });
        }

        // Comparison toggle
        const comparisonToggle = document.getElementById('comparisonToggle');
        if (comparisonToggle) {
            comparisonToggle.checked = this.filters.include_comparison;
            comparisonToggle.addEventListener('change', (e) => {
                this.filters.include_comparison = e.target.checked;
                this.applyFilters();
            });
        }

        // Comparison type selector
        const comparisonTypeSelect = document.getElementById('comparisonTypeSelect');
        if (comparisonTypeSelect) {
            comparisonTypeSelect.value = this.filters.comparison_type;
            comparisonTypeSelect.addEventListener('change', (e) => {
                this.filters.comparison_type = e.target.value;
                if (this.filters.include_comparison) {
                    this.applyFilters();
                }
            });
        }

        // Max provinces selector
        const maxProvincesSelect = document.getElementById('maxProvincesSelect');
        if (maxProvincesSelect) {
            maxProvincesSelect.value = this.filters.max_provinces;
            maxProvincesSelect.addEventListener('change', (e) => {
                this.filters.max_provinces = parseInt(e.target.value);
                this.applyFilters();
            });
        }

        // Region filter
        const regionFilterSelect = document.getElementById('regionFilterSelect');
        if (regionFilterSelect) {
            regionFilterSelect.value = this.filters.region_filter || '';
            regionFilterSelect.addEventListener('change', (e) => {
                this.filters.region_filter = e.target.value || null;
                this.applyFilters();
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
                this.applyFilters();
            });

            startDateInput.addEventListener('change', () => {
                this.filters.start_date = startDateInput.value;
                this.applyFilters();
            });

            endDateInput.addEventListener('change', () => {
                this.filters.end_date = endDateInput.value;
                this.applyFilters();
            });
        }
    }

    async loadDashboardData() {
        this.showLoadingState();

        try {
            // Get all data from service (cached if available)
            const allData = await this.dataService.fetchAllData();

            // Apply client-side filters
            await this.applyClientSideFilters(allData);

            this.hideLoadingState();

        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showError('Gagal memuat data: ' + error.message);
            this.hideLoadingState();
        }
    }

    async applyClientSideFilters(allData) {
        // Filter time series data
        const timeSeriesData = this.dataService.filterTimeSeries(allData, this.filters);

        // Filter province data
        const provinceData = this.dataService.filterProvinceData(allData, this.filters);

        // Get comparison data if needed
        let comparisonData = null;
        if (this.filters.include_comparison) {
            comparisonData = this.dataService.getComparisonData(allData, this.filters);
        }

        // Update all charts with filtered data
        await this.updateAllCharts(timeSeriesData, provinceData, comparisonData);

        // Update metrics
        this.updateKeyMetrics(allData, provinceData);

        // Update KPI cards
        this.updateKPICards(allData.kpi_metrics);
    }

    async applyFilters() {
        // Apply filters instantly with cached data
        if (this.dataService.currentData) {
            await this.applyClientSideFilters(this.dataService.currentData);
        } else {
            // If no cached data, fetch first
            await this.loadDashboardData();
        }
    }

    async updateAllCharts(timeSeriesData, provinceData, comparisonData) {
        // Update main time series chart
        await this.updateTimeSeriesChart(timeSeriesData);

        // Update province charts
        await this.updateProvinceCharts(provinceData);

        // Update province cards
        this.updateProvinceCards(provinceData?.top_8_for_cards || []);

        // Update progress bars
        this.updateProgressBars(provinceData?.top_3_for_progress || []);

        // Update comparison if available
        if (comparisonData) {
            this.updateComparisonDisplays(comparisonData);
        }
    }

    async updateTimeSeriesChart(timeSeriesData) {
        const ctx = document.getElementById('pencatatanChart');
        if (!ctx || !timeSeriesData) return;

        // Hide skeleton and show canvas
        const skeleton = document.getElementById('mainChartSkeleton');
        if (skeleton) skeleton.style.display = 'none';
        ctx.style.display = 'block';

        // Destroy existing chart
        if (this.charts.pencatatan) {
            this.charts.pencatatan.destroy();
        }

        // Process data for display
        const processedData = this.processTimeSeriesData(timeSeriesData);

        this.charts.pencatatan = new Chart(ctx, {
            type: 'line',
            data: {
                ...processedData,
                datasets: processedData.datasets.map((dataset, index) => ({
                    ...dataset,
                    borderColor: index === 0 ? '#2563eb' : index === 1 ? '#10b981' : '#f59e0b',
                    backgroundColor: index === 0 ? 'rgba(37, 99, 235, 0.1)' : index === 1 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: index === 0 ? '#2563eb' : index === 1 ? '#10b981' : '#f59e0b',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: index === 0 ? '#1d4ed8' : index === 1 ? '#059669' : '#d97706',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 4
                }))
            },
            options: this.getTimeSeriesChartOptions()
        });
    }

    processTimeSeriesData(timeSeriesData) {
        // Process labels to ensure current month is on the right
        const labels = [...timeSeriesData.labels];

        // Convert English month names to Indonesian if needed
        const indonesianLabels = labels.map(label => {
            return label.replace(/Jan/g, 'Januari')
                      .replace(/Feb/g, 'Februari')
                      .replace(/Mar/g, 'Maret')
                      .replace(/Apr/g, 'April')
                      .replace(/May/g, 'Mei')
                      .replace(/Jun/g, 'Juni')
                      .replace(/Jul/g, 'Juli')
                      .replace(/Aug/g, 'Agustus')
                      .replace(/Sep/g, 'September')
                      .replace(/Oct/g, 'Oktober')
                      .replace(/Nov/g, 'November')
                      .replace(/Dec/g, 'Desember');
        });

        return {
            labels: indonesianLabels,
            datasets: timeSeriesData.datasets || []
        };
    }

    async updateProvinceCharts(provinceData) {
        const ctx = document.getElementById('provinceBarChart');
        if (!ctx || !provinceData?.top_10) return;

        // Hide skeleton and show canvas
        const skeleton = document.getElementById('provinceChartSkeleton');
        if (skeleton) skeleton.style.display = 'none';
        ctx.style.display = 'block';

        // Destroy existing chart
        if (this.charts.province) {
            this.charts.province.destroy();
        }

        // Prepare data for chart
        const topProvinces = provinceData.top_10;
        const labels = topProvinces.map(p => p.name);

        // Create datasets for each province with monthly data
        const datasets = topProvinces.map((province, index) => {
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

            // Use monthly data if available, otherwise use total
            const data = province.monthly_data || [province.total];

            return {
                label: province.name,
                data: data,
                backgroundColor: colors[index % colors.length],
                borderColor: colors[index % colors.length].replace('0.8', '1'),
                borderWidth: 2,
                borderRadius: 4,
                barPercentage: 0.8,
                categoryPercentage: 0.9
            };
        });

        // Generate month labels based on data
        const monthLabels = datasets[0]?.data.map((_, i) => {
            if (this.filters.group_by === 'quarter') {
                return `Q${i + 1}`;
            } else {
                return `Bulan ${i + 1}`;
            }
        }) || [];

        this.charts.province = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: datasets
            },
            options: this.getProvinceChartOptions()
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
                ${provinces.map((province, index) => {
                    const trendIcon = province.trend > 0 ? 'fa-arrow-up text-green-500' :
                                    province.trend < 0 ? 'fa-arrow-down text-red-500' :
                                    'fa-minus text-gray-500';

                    const trendColor = province.trend > 0 ? 'text-green-600' :
                                     province.trend < 0 ? 'text-red-600' :
                                     'text-gray-600';

                    return `
                        <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-all">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-sm font-semibold text-gray-900">${province.name}</div>
                                <div class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">
                                    #${index + 1}
                                </div>
                            </div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-gray-600">Total</div>
                                <div class="text-lg font-bold text-gray-900">
                                    ${province.total.toLocaleString('id-ID')}
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="text-xs text-gray-500">Trend</div>
                                <div class="flex items-center space-x-1 text-sm ${trendColor} font-medium">
                                    <i class="fas ${trendIcon}"></i>
                                    <span>${province.trend > 0 ? '+' : ''}${Math.abs(province.trend).toFixed(1)}%</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
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

    updateComparisonDisplays(comparisonData) {
        // Update comparison displays
        Object.keys(comparisonData).forEach(source => {
            const comparison = comparisonData[source];
            const elementId = source.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();

            this.updateTrendDisplay(elementId, comparison.change);
        });
    }

    updateKeyMetrics(allData, provinceData) {
        // Update Top Region
        const topRegionEl = document.getElementById('topRegion');
        if (topRegionEl && provinceData?.top_10?.[0]) {
            topRegionEl.textContent = provinceData.top_10[0].name;
        }

        // Update time series metrics
        if (allData.time_series) {
            this.updateTimeSeriesMetrics(allData.time_series);
        }
    }

    updateTimeSeriesMetrics(timeSeriesData) {
        if (!timeSeriesData.datasets) return;

        // Calculate totals from all datasets
        let totalAll = 0;
        let allValues = [];

        timeSeriesData.datasets.forEach(dataset => {
            const values = dataset.data;
            allValues.push(...values);
            totalAll += values.reduce((sum, val) => sum + val, 0);
        });

        const avgAll = Math.round(totalAll / allValues.length);
        const maxAll = Math.max(...allValues);

        // Calculate trend
        let trend = 0;
        if (allValues.length >= 2) {
            const current = allValues[allValues.length - 1];
            const previous = allValues[allValues.length - 2];
            trend = previous > 0 ? ((current - previous) / previous * 100) : 0;
        }

        // Update DOM elements with animation
        this.animateNumber('totalIzin', totalAll);
        this.animateNumber('avgIzin', avgAll);
        this.animateNumber('maxIzin', maxAll);

        // Update trend
        const trendElement = document.getElementById('trendIzin');
        const trendPercentage = document.getElementById('trendPercentage');
        if (trendElement && trendPercentage) {
            const trendText = trend >= 0 ? `+${trend.toFixed(1)}%` : `${trend.toFixed(1)}%`;
            const trendIcon = trend >= 0 ? 'fa-arrow-up text-green-500' : 'fa-arrow-down text-red-500';
            const trendColor = trend >= 0 ? 'text-green-600' : 'text-red-600';

            trendPercentage.textContent = trendText;
            trendElement.className = `text-xl font-bold ${trendColor}`;
            trendElement.querySelector('i').className = `fas ${trendIcon} mr-1`;
        }
    }

    updateTrendDisplay(sourceId, trend) {
        const elements = document.querySelectorAll(`[data-trend="${sourceId}"]`);
        elements.forEach(el => {
            const icon = el.querySelector('.trend-icon');
            const valueEl = el.querySelector('.trend-value');

            if (icon) {
                icon.className = `fas trend-icon ${trend > 0 ? 'fa-arrow-up text-green-500' : trend < 0 ? 'fa-arrow-down text-red-500' : 'fa-minus text-gray-500'}`;
            }

            if (valueEl) {
                valueEl.textContent = `${trend > 0 ? '+' : ''}${trend.toFixed(1)}%`;
            }
        });
    }

    updateKPICards(kpiMetrics) {
        console.log('Updating KPI cards with data:', kpiMetrics);

        if (!kpiMetrics) {
            console.log('No KPI metrics data available');
            return;
        }

        // Get current year for display
        const currentYear = new Date().getFullYear();
        const previousYear = currentYear - 1;

        // Update each KPI metric
        Object.keys(kpiMetrics).forEach(metricKey => {
            const metric = kpiMetrics[metricKey];
            const element = document.querySelector(`[data-metric="${metricKey}"]`);

            console.log(`Updating ${metricKey}:`, metric, 'Element found:', !!element);

            if (element) {
                // Update value with animation
                const targetValue = metric.current || 0;
                const startValue = parseInt(element.textContent.replace(/,/g, '')) || 0;

                // Animate the number
                this.animateKPIValue(element, startValue, targetValue);

                // Update year comparison display
                const metricCard = element.parentElement;
                const previousYearEl = metricCard.querySelector('.previous-year');
                const currentYearEl = metricCard.querySelector('.current-year');

                if (previousYearEl) {
                    previousYearEl.textContent = `${previousYear}: ${metric.previous.toLocaleString('id-ID')}`;
                }

                if (currentYearEl) {
                    currentYearEl.textContent = `${currentYear}: ${metric.current.toLocaleString('id-ID')}`;
                }

                // Update trend indicator
                const trendContainer = metricCard.querySelector('.flex.items-center');
                if (trendContainer) {
                    const icon = trendContainer.querySelector('i');
                    const text = trendContainer.querySelector('.change-percent');

                    if (metric.change_type === 'increase') {
                        icon.className = 'fas fa-arrow-up mr-1';
                        text.textContent = `+${metric.change}% dari tahun lalu`;
                        trendContainer.className = 'mt-2 flex items-center text-sm text-green-600';
                    } else if (metric.change_type === 'decrease') {
                        icon.className = 'fas fa-arrow-down mr-1';
                        text.textContent = `${metric.change}% dari tahun lalu`;
                        trendContainer.className = 'mt-2 flex items-center text-sm text-red-600';
                    } else {
                        icon.className = 'fas fa-minus mr-1';
                        text.textContent = `${metric.change}% dari tahun lalu`;
                        trendContainer.className = 'mt-2 flex items-center text-sm text-gray-600';
                    }
                }
            }
        });
    }

    animateKPIValue(element, start, end) {
        const duration = 1000;
        const startTime = performance.now();

        const updateNumber = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentValue = Math.round(start + (end - start) * easeOutQuart);

            element.textContent = currentValue.toLocaleString('id-ID');

            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        };

        requestAnimationFrame(updateNumber);
    }

  animateNumber(elementId, targetValue) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const startValue = parseInt(element.textContent.replace(/,/g, '')) || 0;
        const duration = 1000;
        const startTime = performance.now();

        const updateNumber = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentValue = Math.round(startValue + (targetValue - startValue) * easeOutQuart);

            element.textContent = currentValue.toLocaleString('id-ID');

            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        };

        requestAnimationFrame(updateNumber);
    }

    getTimeSeriesChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            layout: {
                padding: {
                    left: 5,
                    right: 25,
                    top: 20,
                    bottom: 5
                }
            },
            plugins: {
                title: {
                    display: false
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    align: 'center',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 13,
                            weight: '500',
                            family: 'system-ui, -apple-system, sans-serif'
                        },
                        color: '#374151',
                        boxWidth: 12,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(255, 255, 255, 0.98)',
                    titleColor: '#111827',
                    bodyColor: '#374151',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    padding: 16,
                    cornerRadius: 12,
                    displayColors: true,
                    titleFont: {
                        size: 15,
                        weight: '600',
                        family: 'system-ui, -apple-system, sans-serif'
                    },
                    bodyFont: {
                        size: 13,
                        family: 'system-ui, -apple-system, sans-serif'
                    },
                    bodySpacing: 4
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(243, 244, 246, 0.8)',
                        drawBorder: false,
                        lineWidth: 1
                    },
                    ticks: {
                        precision: 0,
                        font: {
                            size: 12,
                            weight: '500',
                            family: 'system-ui, -apple-system, sans-serif'
                        },
                        color: '#6b7280',
                        padding: 10,
                        callback: function(value) {
                            if (value === 0) return '0';
                            if (value >= 1000) return `${(value/1000).toFixed(1)}K`;
                            return value.toLocaleString('id-ID');
                        }
                    },
                    title: {
                        display: true,
                        text: 'Jumlah Izin',
                        font: {
                            size: 13,
                            weight: '600',
                            family: 'system-ui, -apple-system, sans-serif'
                        },
                        color: '#374151',
                        padding: { top: 0, bottom: 10 }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12,
                            weight: '500',
                            family: 'system-ui, -apple-system, sans-serif'
                        },
                        color: '#6b7280',
                        padding: 10,
                        maxRotation: 0
                    },
                    title: {
                        display: true,
                        text: `Periode ${this.filters.period_type === '3_months' ? '3 Bulan' : this.filters.period_type === '12_months' ? '12 Bulan' : '6 Bulan'} Terakhir`,
                        font: {
                            size: 13,
                            weight: '600',
                            family: 'system-ui, -apple-system, sans-serif'
                        },
                        color: '#374151',
                        padding: { top: 10, bottom: 0 }
                    }
                }
            }
        };
    }

    getProvinceChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                title: {
                    display: false
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 12
                        },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'rectRounded'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    displayColors: true
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    stacked: false,
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    },
                    title: {
                        display: true,
                        text: this.filters.group_by === 'quarter' ? 'Kuartal' : 'Bulan',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    stacked: false,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    },
                    title: {
                        display: true,
                        text: 'Jumlah Izin',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
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
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }

        // Auto-refresh every 5 minutes with fresh data
        this.refreshInterval = setInterval(async () => {
            await this.dataService.fetchAllData(true); // Force refresh
            await this.loadDashboardData();
        }, 300000);
    }

    destroy() {
        // Clear intervals
        if (this.refreshInterval) clearInterval(this.refreshInterval);
        if (this.timeUpdateInterval) clearInterval(this.timeUpdateInterval);
        if (this.monthCheckInterval) clearInterval(this.monthCheckInterval);

        // Destroy charts
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
    }
}

// Global functions for backward compatibility
window.refreshProvinceStats = function() {
    if (window.optimizedDashboard) {
        window.optimizedDashboard.applyFilters();
    }
};

window.toggleProvinceChartType = function() {
    if (window.optimizedDashboard && window.optimizedDashboard.charts.province) {
        const chart = window.optimizedDashboard.charts.province;
        chart.config.options.indexAxis = chart.config.options.indexAxis === 'y' ? 'x' : 'y';
        chart.update();
    }
};

// Initialize optimized dashboard
window.optimizedDashboard = new OptimizedDashboard();