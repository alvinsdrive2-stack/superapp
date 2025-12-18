import Chart from 'chart.js/auto';

class DashboardCharts {
    constructor() {
        this.pencatatanChart = null;
        this.provinceBarChart = null;
        this.provinceChartType = 'bar'; // 'bar' for vertical, 'horizontalBar' for horizontal
        this.loading = false;
        this.refreshInterval = null;
        this.timeUpdateInterval = null;
        this.monthCheckInterval = null;
        this.lastMonth = null;
        this.currentTimeElement = null;
        this.debounceRefresh = null;
        this.updateTimezone();
        this.init();
        this.startTimeUpdate();
        this.startMonthCheck();
    }

    updateTimezone() {
        const now = new Date();
        this.currentMonth = now.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', month: 'long' });
        this.currentYear = now.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', year: 'numeric' });
        this.lastMonth = now.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', month: 'long' });
    }

    startTimeUpdate() {
        // Update current time display
        this.currentTimeElement = document.getElementById('currentTime');
        this.updateCurrentTime();

        // Clear existing interval if any
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
        // Clear existing interval if any
        if (this.monthCheckInterval) {
            clearInterval(this.monthCheckInterval);
        }

        // Check for month change every minute
        this.monthCheckInterval = setInterval(() => {
            const now = new Date();
            const currentMonth = now.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', month: 'long' });

            if (currentMonth !== this.lastMonth) {
                this.lastMonth = currentMonth;
                this.updateTimezone();
                this.initCustomDatePicker();
            }
        }, 60000); // Check every minute
    }

    initCustomDatePicker() {
        // Custom date picker is already initialized in custom-date-picker.js
        // This method is called when month changes
    }

    async init() {
        // Initialize Chart.js when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initPencatatanIzinChart();
                this.initProvinceBarChart();
            });
        } else {
            this.initPencatatanIzinChart();
            this.initProvinceBarChart();
        }
    }

    async initPencatatanIzinChart() {
        // Prevent multiple initializations
        if (this.loading) {
            console.log('Chart already loading, skipping...');
            return;
        }

        const ctx = document.getElementById('pencatatanChart');
        if (!ctx) {
            console.error('Canvas element for pencatatan chart not found');
            return;
        }

        // Store reference to canvas container for error handling
        this.canvasContainer = ctx.parentElement;

        // Show loading state
        this.showLoading();

        try {
            console.log('Starting chart initialization...');

            // Fetch data from API
            const response = await fetch('/api/dashboard/pencatatan-izin/time-series?months=6', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            // Debug: Log the API response
            console.log('API Response:', result);

            if (!result.success) {
                throw new Error(result.message || 'Failed to fetch data');
            }

            // Debug: Check if data exists
            if (!result.data) {
                console.error('No data in API response');
                this.showError('Tidak ada data yang tersedia');
                return;
            }

            console.log('Chart data:', result.data);

            // Create or update chart
            await this.createOrUpdateChart(ctx, result.data);

            // Start auto-refresh
            this.startAutoRefresh();

        } catch (error) {
            console.error('Error loading pencatatan chart:', error);
            this.showError(error.message);
        }
    }

    async createOrUpdateChart(originalCtx, data) {
        // Hide skeleton and show canvas
        const skeleton = document.getElementById('mainChartSkeleton');
        if (skeleton) {
            skeleton.style.display = 'none';
        }

        const canvas = document.getElementById('pencatatanChart');
        if (canvas) {
            canvas.style.display = 'block';
        }

        // Recreate canvas element if it was replaced by loading/error state
        if (this.canvasContainer) {
            const ctx = this.canvasContainer.querySelector('#pencatatanChart');
            if (!ctx) {
                console.error('Failed to find canvas element');
                return;
            }

            if (this.pencatatanChart) {
                // Destroy existing chart and create new one
                this.pencatatanChart.destroy();
            }

            // Process data to ensure current month is on the right
            const processedData = this.processChartData(data);

            const chartConfig = {
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
                options: {
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
                            bodySpacing: 4,
                            callbacks: {
                                title: function(context) {
                                    const isCurrentMonth = context[0].label.includes(dashboardCharts.currentMonth);
                                    return `${context[0].label}${isCurrentMonth ? ' (Bulan Ini)' : ''}`;
                                },
                                label: function(context) {
                                    const value = context.parsed.y;
                                    return `${context.dataset.label}: ${value.toLocaleString('id-ID')} izin`;
                                },
                                afterLabel: function(context) {
                                    const datasetData = context.dataset.data;
                                    const currentIndex = datasetData.indexOf(context.raw);
                                    if (currentIndex > 0) {
                                        const prevValue = datasetData[currentIndex - 1];
                                        const change = ((context.raw - prevValue) / prevValue * 100).toFixed(1);
                                        const changeSymbol = change >= 0 ? '↑' : '↓';
                                        return `${changeSymbol} ${change >= 0 ? '+' : ''}${change}% vs bulan lalu`;
                                    }
                                    return 'Data pertama';
                                }
                            }
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
                                maxRotation: 0,
                                callback: function(value, index) {
                                    // Get the label directly from processedData
                                    const label = processedData.labels[index];
                                    if (typeof label === 'string') {
                                        const isCurrentMonth = label.includes(this.currentMonth);
                                        const monthName = label.split(' ')[0]; // Show only month name
                                        return isCurrentMonth ? `${monthName} ●` : monthName;
                                    }
                                    return label;
                                }.bind(this)
                            },
                            title: {
                                display: true,
                                text: `Periode 6 Bulan Terakhir - ${this.currentMonth} ${this.currentYear}`,
                                font: {
                                    size: 13,
                                    weight: '600',
                                    family: 'system-ui, -apple-system, sans-serif'
                                },
                                color: '#374151',
                                padding: { top: 10, bottom: 0 }
                            }
                        }
                    },
                    elements: {
                        point: {
                            radius: 6,
                            hoverRadius: 8,
                            borderWidth: 3,
                            hoverBorderWidth: 4
                        },
                        line: {
                            borderWidth: 3,
                            tension: 0.3,
                            capBezierPoints: true
                        }
                    },
                    animation: {
                        duration: 800, // Faster animation
                        easing: 'easeOutCubic', // More responsive easing
                        onComplete: function() {
                            // Don't trigger another update to prevent recursion
                            // Chart.js will handle hover states properly
                        }
                    },
                    // Reduce unnecessary redraws
                    events: ['mousemove', 'mouseout', 'click', 'touchstart', 'touchmove'],
                    hover: {
                        mode: 'index',
                        intersect: false,
                        animationDuration: 100, // Faster hover animation
                        onHover: function(event, activeElements) {
                            // Ensure cursor changes on hover
                            event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                        }
                    }
                }
            };

            // Create new chart
            this.pencatatanChart = new Chart(ctx, chartConfig);

            // Chart.js handles all hover events natively

            // Update statistics
            this.updateStatistics(processedData);

            // Store chart data for key metrics (will be updated with real API data)
            this.chartData = processedData;

            // Always use province chart data for consistency with trend chart
            await this.loadProvinceStatisticsFromChart();
        }

        this.hideLoading();
        this.loadKeyMetrics();
    }

    updateKeyMetrics(provinceData, chartData) {
        // Update Top Region
        const topRegionEl = document.getElementById('topRegion');
        if (topRegionEl && provinceData) {
            const topProvince = Object.entries(provinceData)
                .sort(([,a], [,b]) => b.total - a.total)[0];
            if (topProvince) {
                topRegionEl.textContent = topProvince[0];
            }
        }

        // Update Trend Balai
        if (chartData && chartData.datasets) {
            const balaiData = chartData.datasets.find(d => d.label === 'Balai');
            if (balaiData && balaiData.data.length >= 2) {
                this.updateTrendDisplay('balai', balaiData.data);
            }

            // Update Trend FG/Suisei
            const fgData = chartData.datasets.find(d => d.label === 'FG/Suisei');
            if (fgData && fgData.data.length >= 2) {
                this.updateTrendDisplay('fg', fgData.data);
            }

            // Update Trend Reguler
            const regulerData = chartData.datasets.find(d => d.label === 'Reguler');
            if (regulerData && regulerData.data.length >= 2) {
                this.updateTrendDisplay('reguler', regulerData.data);
            }
        }
    }

    updateTrendDisplay(type, data) {
        const currentMonth = data[data.length - 1];
        const previousMonth = data[data.length - 2];

        let trendValue = '0%';
        let trendIcon = 'fa-minus';
        let trendColor = 'text-gray-500';

        if (previousMonth > 0) {
            const change = ((currentMonth - previousMonth) / previousMonth * 100).toFixed(1);
            trendValue = `${change > 0 ? '+' : ''}${change}%`;
            trendIcon = change > 0 ? 'fa-arrow-up' : change < 0 ? 'fa-arrow-down' : 'fa-minus';
            trendColor = change > 0 ? 'text-green-500' : change < 0 ? 'text-red-500' : 'text-gray-500';
        }

        const prefix = type === 'fg' ? 'fg' : type;
        const trendEl = document.getElementById(`trend${prefix.charAt(0).toUpperCase() + prefix.slice(1)}`);
        const trendValueEl = document.getElementById(`${prefix}TrendValue`);

        if (trendEl) {
            const icon = trendEl.querySelector('i');
            if (icon) {
                icon.className = `fas ${trendIcon} mr-2 ${trendColor}`;
            }
        }

        if (trendValueEl) {
            trendValueEl.textContent = trendValue;
        }
    }

    async loadKeyMetrics() {
        try {
            // Use sample province data for demonstration
            const sampleProvinceData = {
                'DKI Jakarta': { total: 2456, trend: 12.5 },
                'Jawa Barat': { total: 1823, trend: 8.3 },
                'Jawa Tengah': { total: 1456, trend: -2.1 }
            };

            // Use actual chart data if available
            const chartData = this.chartData || {
                datasets: [
                    { label: 'Balai', data: [320, 377] },
                    { label: 'FG/Suisei', data: [180, 215] },
                    { label: 'Reguler', data: [420, 385] }
                ]
            };

            this.updateKeyMetrics(sampleProvinceData, chartData);

        } catch (error) {
            console.error('Error loading key metrics:', error);
        }
    }

    async loadProvinceStatisticsFromChart() {
        try {
            // Always use monthly-province-chart API (24 months data)
            const response = await fetch('/api/dashboard/monthly-province-chart', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data) {
                // Convert chart data to province statistics format with trend calculation
                const provinceData = this.convertChartDataToProvinceStatsWithTrend(result.data);
                await this.renderProvinceStatistics(provinceData);
            } else {
                console.error('Failed to load province chart data:', result.message);
                await this.showSampleProvinceData();
            }

        } catch (error) {
            console.error('Error loading province statistics from chart:', error);
            await this.showSampleProvinceData();
        }
    }

    filterDataByPhase(chartData, currentMonth, currentYear) {
        if (!chartData || !chartData.labels || !chartData.datasets) {
            return chartData;
        }

        // Determine which months to include based on current phase
        const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                           'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        let targetMonths;
        if (currentMonth >= 1 && currentMonth <= 6) {
            // Phase 1: Januari - Juni
            targetMonths = monthNames.slice(0, 6);
        } else {
            // Phase 2: Juli - Desember
            targetMonths = monthNames.slice(6);
        }

        // Filter the data
        const filteredLabels = [];
        const labelIndices = [];

        chartData.labels.forEach((label, index) => {
            // Extract month from label (assuming format like "Jan 2025" or "Januari 2025")
            const monthMatch = label.match(/([A-Za-z]+)/);
            if (monthMatch) {
                const monthName = monthMatch[1];
                if (targetMonths.some(m => m.toLowerCase().startsWith(monthName.toLowerCase()))) {
                    filteredLabels.push(label);
                    labelIndices.push(index);
                }
            }
        });

        // Filter datasets to only include target months
        const filteredDatasets = chartData.datasets.map(dataset => ({
            ...dataset,
            data: labelIndices.map(index => dataset.data[index])
        }));

        return {
            ...chartData,
            labels: filteredLabels,
            datasets: filteredDatasets
        };
    }

    async loadProvinceStatistics() {
        try {
            const monthSelect = document.getElementById('provinceMonth');
            let selectedMonth = null;

            if (monthSelect) {
                // Check if we have a custom month set in dataset
                if (monthSelect.dataset.selectedMonth) {
                    selectedMonth = monthSelect.dataset.selectedMonth;
                } else if (monthSelect.value && monthSelect.value !== '' && monthSelect.value !== 'separator') {
                    // Convert YYYY-MM format to month name
                    if (monthSelect.value.includes('-')) {
                        const [year, month] = monthSelect.value.split('-');
                        const monthDate = new Date(year, month - 1, 1);
                        selectedMonth = monthDate.toLocaleString('id-ID', { month: 'long', year: 'numeric' });
                    }
                }
            }

            // If still no month selected, use current month
            if (!selectedMonth) {
                selectedMonth = this.currentMonth;
            }

            const url = selectedMonth ?
                `/api/dashboard/province-stats?month=${encodeURIComponent(selectedMonth)}` :
                '/api/dashboard/province-stats';

            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                await this.renderProvinceStatistics(result.data);
            } else {
                console.error('Failed to load province statistics:', result.message);
                this.showProvinceError('Gagal memuat data provinsi');
            }

        } catch (error) {
            console.error('Error loading province statistics:', error);
            // Show sample data if API fails
            await this.showSampleProvinceData();
        }
    }

    convertChartDataToProvinceStatsWithTrend(chartData) {
        const provinceStats = {};

        if (!chartData || !chartData.datasets || !chartData.labels) {
            return provinceStats;
        }

        // Get current date for trend calculation
        const today = new Date();
        const currentMonth = today.getMonth(); // 0-11
        const currentYear = today.getFullYear();

        // Process each dataset (each dataset represents a province)
        chartData.datasets.forEach(dataset => {
            const provinceName = dataset.label;
            const dataValues = dataset.data || [];

            if (!provinceName) return;

            // For default behavior, use month comparison (current vs last month)
            let currentMonthCount = 0;
            let lastMonthCount = 0;

            // Find current month data
            const currentLabelIndex = chartData.labels.findIndex(label => {
                const labelDate = new Date(label);
                return labelDate.getMonth() === currentMonth && labelDate.getFullYear() === currentYear;
            });

            if (currentLabelIndex !== -1 && dataValues[currentLabelIndex] !== undefined) {
                currentMonthCount = dataValues[currentLabelIndex];
            }

            // Find last month data
            const previousMonth = currentMonth === 0 ? 11 : currentMonth - 1;
            const previousMonthYear = currentMonth === 0 ? currentYear - 1 : currentYear;

            const lastLabelIndex = chartData.labels.findIndex(label => {
                const labelDate = new Date(label);
                return labelDate.getMonth() === previousMonth && labelDate.getFullYear() === previousMonthYear;
            });

            if (lastLabelIndex !== -1 && dataValues[lastLabelIndex] !== undefined) {
                lastMonthCount = dataValues[lastLabelIndex];
            }

            // Calculate trend
            const trend = lastMonthCount > 0 ?
                Math.round(((currentMonthCount - lastMonthCount) / lastMonthCount) * 100) : 0;

            provinceStats[provinceName] = {
                total: currentMonthCount,
                trend: trend,
                mode: 'month', // Default to month comparison
                balai: 0,
                reguler: 0,
                fg: 0
            };
        });

        // Add breakdown data if available from API
        this.fetchProvinceBreakdown().then(breakdown => {
            if (breakdown) {
                Object.keys(provinceStats).forEach(province => {
                    if (breakdown[province]) {
                        provinceStats[province].balai = breakdown[province].balai || 0;
                        provinceStats[province].reguler = breakdown[province].reguler || 0;
                        provinceStats[province].fg = breakdown[province].fg || 0;
                    }
                });
            }
        }).catch(error => {
            console.error('Error fetching province breakdown:', error);
        });

        return provinceStats;
    }

    async fetchProvinceStatsWithTrend() {
        try {
            // Get current date to determine phase
            const today = new Date();
            const currentMonth = today.getMonth() + 1;
            const currentYear = today.getFullYear();

            let startDate, endDate;

            if (currentMonth >= 1 && currentMonth <= 6) {
                // Phase 1: January - June
                startDate = new Date(currentYear, 0, 1); // January 1
                endDate = new Date(currentYear, 5, 30); // June 30
            } else {
                // Phase 2: July - December
                startDate = new Date(currentYear, 6, 1); // July 1
                endDate = new Date(currentYear, 11, 31); // December 31
            }

            // Format dates for API
            const monthLabel = endDate.toLocaleString('id-ID', { month: 'long', year: 'numeric' });

            // Fetch province stats from API with proper trend calculation
            const response = await fetch(`/api/dashboard/province-stats?month=${encodeURIComponent(monthLabel)}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success && result.data) {
                    return result.data;
                }
            }
        } catch (error) {
            console.error('Error fetching province stats with trend:', error);
        }

        return null;
    }

    // Method to handle custom date picker params
    async loadWithCustomParams(params) {
        try {
            // Show loading
            const container = document.getElementById('provinceStatsContainer');
            const progressContainer = document.getElementById('topProvincesProgress');

            if (container) {
                container.style.opacity = '0.5';
                container.style.transition = 'opacity 0.3s ease';
            }

            if (progressContainer) {
                progressContainer.style.opacity = '0.5';
                progressContainer.style.transition = 'opacity 0.3s ease';
            }

            // Always load from monthly-province-chart API with 2 years data
            const url = '/api/dashboard/monthly-province-chart';

            // Fetch data
            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success && result.data) {
                    // Process data based on comparison type
                    const provinceData = this.processChartDataWithComparison(result.data, params);
                    await this.renderProvinceStatistics(provinceData);
                }
            }

        } catch (error) {
            console.error('Error loading custom params data:', error);
        } finally {
            // Restore opacity
            const container = document.getElementById('provinceStatsContainer');
            const progressContainer = document.getElementById('topProvincesProgress');

            if (container) {
                container.style.opacity = '1';
            }

            if (progressContainer) {
                progressContainer.style.opacity = '1';
            }
        }
    }

    processChartDataWithComparison(chartData, params) {
        if (!chartData || !chartData.labels || !chartData.datasets) {
            return {};
        }

        const today = new Date();
        const currentMonth = today.getMonth();
        const currentYear = today.getFullYear();

        let currentPeriodData = {};
        let previousPeriodData = {};
        let comparisonText = '';

        if (params.compare_type === 'year') {
            // Phase comparison with last year
            const phaseMonths = currentMonth >= 1 && currentMonth <= 6 ?
                [0, 1, 2, 3, 4, 5] : // Jan-Jun
                [6, 7, 8, 9, 10, 11]; // Jul-Dec

            // Get data for current phase
            chartData.datasets.forEach(dataset => {
                const provinceName = dataset.label;
                let currentTotal = 0;
                let previousTotal = 0;

                // Current year phase data
                phaseMonths.forEach((monthIndex, i) => {
                    const currentPeriodLabel = today.toLocaleString('id-ID', { month: 'short', year: 'numeric' });
                    const labelIndex = chartData.labels.findIndex(label => {
                        const labelDate = new Date(label);
                        return labelDate.getMonth() === monthIndex && labelDate.getFullYear() === currentYear;
                    });

                    if (labelIndex !== -1 && dataset.data[labelIndex] !== undefined) {
                        currentTotal += dataset.data[labelIndex];
                    }

                    // Previous year same phase data
                    const prevYearLabelIndex = chartData.labels.findIndex(label => {
                        const labelDate = new Date(label);
                        return labelDate.getMonth() === monthIndex && labelDate.getFullYear() === currentYear - 1;
                    });

                    if (prevYearLabelIndex !== -1 && dataset.data[prevYearLabelIndex] !== undefined) {
                        previousTotal += dataset.data[prevYearLabelIndex];
                    }
                });

                const trend = previousTotal > 0 ?
                    Math.round(((currentTotal - previousTotal) / previousTotal) * 100) : 0;

                currentPeriodData[provinceName] = {
                    total: currentTotal,
                    trend: trend,
                    mode: 'year',
                    balai: 0,
                    reguler: 0,
                    fg: 0
                };
            });

            comparisonText = 'vs tahun lalu';

        } else if (params.compare_type === 'month') {
            // Month comparison with last month
            const previousMonth = currentMonth === 0 ? 11 : currentMonth - 1;
            const previousMonthYear = currentMonth === 0 ? currentYear - 1 : currentYear;

            chartData.datasets.forEach(dataset => {
                const provinceName = dataset.label;
                let currentMonthCount = 0;
                let lastMonthCount = 0;

                // Current month data
                const currentLabelIndex = chartData.labels.findIndex(label => {
                    const labelDate = new Date(label);
                    return labelDate.getMonth() === currentMonth && labelDate.getFullYear() === currentYear;
                });

                if (currentLabelIndex !== -1 && dataset.data[currentLabelIndex] !== undefined) {
                    currentMonthCount = dataset.data[currentLabelIndex];
                }

                // Last month data
                const lastLabelIndex = chartData.labels.findIndex(label => {
                    const labelDate = new Date(label);
                    return labelDate.getMonth() === previousMonth && labelDate.getFullYear() === previousMonthYear;
                });

                if (lastLabelIndex !== -1 && dataset.data[lastLabelIndex] !== undefined) {
                    lastMonthCount = dataset.data[lastLabelIndex];
                }

                const trend = lastMonthCount > 0 ?
                    Math.round(((currentMonthCount - lastMonthCount) / lastMonthCount) * 100) : 0;

                currentPeriodData[provinceName] = {
                    total: currentMonthCount,
                    trend: trend,
                    mode: 'month',
                    balai: 0,
                    reguler: 0,
                    fg: 0
                };
            });

            comparisonText = 'vs bulan lalu';
        }

        return currentPeriodData;
    }

    getPhaseLabel(year, monthIndex) {
        const phase = monthIndex < 6 ? 1 : 2;
        const phaseMonths = phase === 1 ? 'Jan-Jun' : 'Jul-Des';
        return `Fase ${phase}: ${phaseMonths} ${year}`;
    }

    shouldShowNextYear() {
        // Only show next year if we're in the last 3 months of current year
        const today = new Date();
        const month = today.getMonth();
        return month >= 9; // October, November, December
    }

    async handleMonthChange(selectedMonth) {
        // Show loading animation
        const refreshIcon = document.getElementById('refreshIcon');
        if (refreshIcon) {
            refreshIcon.classList.add('animate-spin');
        }

        // Add loading state to cards
        const container = document.getElementById('provinceStatsContainer');
        const progressContainer = document.getElementById('topProvincesProgress');

        if (container) {
            container.style.opacity = '0.5';
            container.style.transition = 'opacity 0.3s ease';
        }

        if (progressContainer) {
            progressContainer.style.opacity = '0.5';
            progressContainer.style.transition = 'opacity 0.3s ease';
        }

        try {
            if (selectedMonth === '' || selectedMonth === 'separator') {
                // Load phase data (default behavior)
                await this.loadProvinceStatistics();
            } else if (selectedMonth && selectedMonth.includes('-')) {
                // Format: YYYY-MM, convert to month name for API
                const [year, month] = selectedMonth.split('-');
                const monthDate = new Date(year, month - 1, 1);
                const monthName = monthDate.toLocaleString('id-ID', { month: 'long', year: 'numeric' });

                // Update the month select value for API
                if (this.monthSelect) {
                    this.monthSelect.dataset.selectedMonth = monthName;
                }

                // Load specific month data
                await this.loadProvinceStatistics();
            } else {
                // Fallback to phase data
                await this.loadProvinceStatistics();
            }
        } finally {
            // Remove loading animation
            if (refreshIcon) {
                refreshIcon.classList.remove('animate-spin');
            }

            if (container) {
                container.style.opacity = '1';
            }

            if (progressContainer) {
                progressContainer.style.opacity = '1';
            }
        }
    }

    async renderProvinceStatistics(provinceData) {
        const container = document.getElementById('provinceStatsContainer');
        const progressContainer = document.getElementById('topProvincesProgress');
        const statsSkeleton = document.getElementById('provinceStatsSkeleton');
        const progressSkeleton = document.getElementById('progressSkeleton');

        if (!container || !progressContainer) return;

        // Hide skeletons and show actual content
        if (statsSkeleton) {
            statsSkeleton.classList.add('hidden');
        }
        if (progressSkeleton) {
            progressSkeleton.classList.add('hidden');
        }
        container.classList.remove('hidden');
        progressContainer.classList.remove('hidden');

        // Store the chart data for progress bars
        const provinceChartData = provinceData;

        // Sort provinces by total (descending)
        const sortedProvinces = Object.entries(provinceData)
            .sort(([,a], [,b]) => b.total - a.total);

        const top8Provinces = sortedProvinces.slice(0, 8);
        const top3Provinces = sortedProvinces.slice(0, 3);
        const maxValue = top3Provinces[0][1].total;

        // Render province cards (top 8) using grid layout - show current month total and trend
        container.innerHTML = `<div class="grid grid-cols-1 lg:grid-cols-2 gap-3">` + top8Provinces.map(([province, data], index) => {
            const trendIcon = data.trend > 0 ? 'fa-arrow-up text-green-500' :
                            data.trend < 0 ? 'fa-arrow-down text-red-500' :
                            'fa-minus text-gray-500';

            const trendColor = data.trend > 0 ? 'text-green-600' :
                             data.trend < 0 ? 'text-red-600' :
                             'text-gray-600';

            const trendBgColor = data.trend > 0 ? 'bg-green-100' :
                              data.trend < 0 ? 'bg-red-100' :
                              'bg-gray-100';

            return `
                <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm font-semibold text-gray-900">${province}</div>
                        <div class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">
                            #${index + 1}
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-gray-600">Bulan ini</div>
                        <div class="text-lg font-bold text-gray-900">
                            ${data.total.toLocaleString('id-ID')}
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="text-xs text-gray-500">
                            ${data.mode === 'month' ? 'vs bulan lalu' : 'vs tahun lalu'}
                        </div>
                        <div class="flex items-center space-x-1 text-sm ${trendColor} font-medium">
                            <i class="fas ${trendIcon}"></i>
                            <span>${data.trend > 0 ? '+' : ''}${Math.abs(data.trend).toFixed(1)}%</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('') + `</div>`;

        // Render progress bars using the same province data (6 months total)
        progressContainer.innerHTML = top3Provinces.map(([province, data], index) => {
            const percentage = Math.round((data.total / maxValue) * 100);
            const progressColors = ['blue', 'green', 'orange'];
            const color = progressColors[index];

            return `
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900">${index + 1}. ${province}</span>
                        <span class="text-sm font-bold text-${color}-600">${data.total.toLocaleString('id-ID')}</span>
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

        // Update regional summary
        this.updateRegionalSummary(provinceData);
    }

    updateRegionalSummary(provinceData) {
        const regions = {
            'Jawa': ['DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'Banten', 'DI Yogyakarta'],
            'Sumatera': ['Sumatera Utara', 'Sumatera Barat', 'Sumatera Selatan', 'Riau', 'Kepulauan Riau', 'Jambi', 'Bengkulu', 'Lampung', 'Bangka Belitung'],
            'Kalimantan': ['Kalimantan Timur', 'Kalimantan Selatan', 'Kalimantan Tengah', 'Kalimantan Barat', 'Kalimantan Utara'],
            'Sulawesi': ['Sulawesi Selatan', 'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Tenggara', 'Sulawesi Barat', 'Gorontalo']
        };

        const regionTotals = {};
        let grandTotal = 0;

        Object.entries(regions).forEach(([region, provinces]) => {
            regionTotals[region] = 0;
            provinces.forEach(province => {
                if (provinceData[province]) {
                    regionTotals[region] += provinceData[province].total;
                }
            });
            grandTotal += regionTotals[region];
        });

        // Update DOM elements
        Object.entries(regionTotals).forEach(([region, total]) => {
            const elementId = region.toLowerCase().replace(' ', '');
            const element = document.getElementById(`${elementId}Total`);
            if (element) {
                const percentage = grandTotal > 0 ? ((total / grandTotal) * 100).toFixed(0) : 0;
                element.textContent = total.toLocaleString('id-ID');

                // Update percentage text
                const container = element.parentElement;
                const percentageElement = container.querySelector('.text-xs');
                if (percentageElement) {
                    percentageElement.textContent = `${percentage}% dari total`;
                }
            }
        });
    }

    async showSampleProvinceData() {
        // Sample data for demonstration
        const sampleData = {
            'DKI Jakarta': { total: 2456, trend: 12.5 },
            'Jawa Barat': { total: 1823, trend: 8.3 },
            'Jawa Tengah': { total: 1456, trend: -2.1 },
            'Jawa Timur': { total: 1634, trend: 15.7 },
            'Banten': { total: 987, trend: 5.2 },
            'Sumatera Utara': { total: 756, trend: -1.8 },
            'Sulawesi Selatan': { total: 623, trend: 9.4 },
            'Kalimantan Timur': { total: 534, trend: 3.1 }
        };

        await this.renderProvinceStatistics(sampleData);
    }

    showProvinceError(message) {
        const container = document.getElementById('provinceStatsContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="col-span-4 text-center py-8">
                <div class="text-red-500 text-4xl mb-2">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p class="text-sm text-gray-600">${message}</p>
                <button onclick="refreshProvinceStats()"
                        class="mt-3 px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Coba Lagi
                </button>
            </div>
        `;
    }

    processChartData(data) {
        console.log('Processing chart data:', data);

        if (!data.labels || !data.datasets) {
            console.error('Missing data.labels or data.datasets');
            return data;
        }

        // Create month order with current month at the end
        const monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        // Get current month index (0-11)
        const currentMonthIndex = new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta', month: 'numeric' }) - 1;

        // Create array starting from 6 months ago, ending with current month
        const orderedMonths = [];
        for (let i = 5; i >= 0; i--) {
            const monthIndex = (currentMonthIndex - i + 12) % 12;
            const year = monthIndex > currentMonthIndex ? this.currentYear - 1 : this.currentYear;
            orderedMonths.push(`${monthNames[monthIndex]} ${year}`);
        }

        // Reverse the order so current month (December) is on the right
        const reversedLabels = [...data.labels].reverse().map(label => {
            // Convert English month names to Indonesian
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

        // Also reverse the data for each dataset to match the new label order
        const reversedDatasets = data.datasets.map(dataset => ({
            ...dataset,
            data: [...dataset.data].reverse()
        }));

        const reorderedData = {
            labels: reversedLabels,
            datasets: reversedDatasets
        };

        console.log('Reordered data:', reorderedData);
        return reorderedData;
    }

    extractProvinceDataFromChart(chartData) {
        const provinceData = {};

        if (!chartData || !chartData.datasets) {
            return provinceData;
        }

        // Process each dataset to extract province data
        chartData.datasets.forEach(dataset => {
            // Skip if it's not a province dataset (check if label exists)
            if (!dataset.label || ['Total'].includes(dataset.label)) {
                return;
            }

            const provinceName = dataset.label;
            const dataValues = dataset.data || [];

            // Calculate total from all months
            const total = dataValues.reduce((sum, value) => {
                const numValue = typeof value === 'object' ? (value.y || 0) : (value || 0);
                return sum + numValue;
            }, 0);

            // Calculate trend (compare last 3 months with previous 3 months)
            let trend = 0;
            if (dataValues.length >= 6) {
                const last3 = dataValues.slice(-3).reduce((sum, value) => {
                    const numValue = typeof value === 'object' ? (value.y || 0) : (value || 0);
                    return sum + numValue;
                }, 0);

                const prev3 = dataValues.slice(-6, -3).reduce((sum, value) => {
                    const numValue = typeof value === 'object' ? (value.y || 0) : (value || 0);
                    return sum + numValue;
                }, 0);

                trend = prev3 > 0 ? ((last3 - prev3) / prev3) * 100 : 0;
            }

            provinceData[provinceName] = {
                total: total,
                trend: Math.round(trend * 10) / 10, // Round to 1 decimal place
                balai: 0, // Will be populated if needed
                reguler: 0, // Will be populated if needed
                fg: 0 // Will be populated if needed
            };
        });

        return provinceData;
    }

    updateStatistics(data) {
        if (!data.datasets || data.datasets.length === 0) return;

        // Calculate statistics from all datasets
        let totalAll = 0;
        let maxAll = 0;
        let allValues = [];

        data.datasets.forEach(dataset => {
            const values = dataset.data.map(item => typeof item === 'object' ? item.y : item);
            allValues.push(...values);
            totalAll += values.reduce((sum, val) => sum + val, 0);
            const datasetMax = Math.max(...values);
            if (datasetMax > maxAll) maxAll = datasetMax;
        });

        const avgAll = Math.round(totalAll / allValues.length);

        // Calculate trend (compare last 3 months with previous 3 months)
        let trend = 0;
        if (allValues.length >= 6) {
            const recentMonths = allValues.slice(-3);
            const previousMonths = allValues.slice(-6, -3);
            const recentAvg = recentMonths.reduce((a, b) => a + b, 0) / recentMonths.length;
            const previousAvg = previousMonths.reduce((a, b) => a + b, 0) / previousMonths.length;
            trend = ((recentAvg - previousAvg) / previousAvg) * 100;
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

    showLoading() {
        this.loading = true;
        // Show skeleton for main chart
        const skeleton = document.getElementById('mainChartSkeleton');
        if (skeleton) {
            skeleton.style.display = 'block';
        }
        const canvas = document.getElementById('pencatatanChart');
        if (canvas) {
            canvas.style.display = 'none';
        }
    }

    hideLoading() {
        this.loading = false;
    }

    showError(message) {
        if (!this.canvasContainer) {
            console.error('Cannot show error: canvas container not found');
            return;
        }

        this.canvasContainer.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="text-red-500 text-4xl mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p class="text-sm text-gray-600">Failed to load data</p>
                    <p class="text-xs text-gray-500 mt-1">${message}</p>
                    <button onclick="window.location.reload()"
                            class="mt-3 px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition-colors">
                        Retry
                    </button>
                </div>
            </div>
        `;
    }

    startAutoRefresh() {
        // Clear existing interval
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }

        // Set auto-refresh every 5 minutes (300000 ms)
        this.refreshInterval = setInterval(async () => {
            if (!this.loading) {
                await this.refreshChart();
            }
        }, 300000);
    }

    async refreshChart() {
        if (!this.canvasContainer || this.loading) return;

        // Debounce refresh calls to prevent multiple simultaneous requests
        if (this.debounceRefresh) {
            clearTimeout(this.debounceRefresh);
        }

        this.debounceRefresh = setTimeout(async () => {
            try {
                const response = await fetch('/api/dashboard/pencatatan-izin', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    await this.createOrUpdateChart(null, result.data);
                    this.showRefreshNotification();
                }

            } catch (error) {
                console.error('Error refreshing chart:', error);
            }
        }, 300); // 300ms debounce
    }

    showRefreshNotification() {
        // Create a subtle notification
        const notification = document.createElement('div');
        notification.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-3 py-2 rounded-lg shadow-lg text-sm';
        notification.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Data updated';
        notification.style.zIndex = '9999';

        document.body.appendChild(notification);

        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    async initProvinceBarChart() {
        const ctx = document.getElementById('provinceBarChart');
        if (!ctx) {
            console.error('Canvas element for province bar chart not found');
            return;
        }

        try {
            console.log('Initializing province bar chart...');

            // Fetch monthly province data from API
            const response = await fetch('/api/dashboard/monthly-province-chart', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.createOrUpdateProvinceBarChart(ctx, result.data);
            } else {
                console.error('Failed to load province chart data:', result.message);
                this.showProvinceBarChartError('Gagal memuat data provinsi');
            }

        } catch (error) {
            console.error('Error initializing province bar chart:', error);
            this.showProvinceBarChartSample();
        }
    }

    createOrUpdateProvinceBarChart(ctx, data) {
        // Hide skeleton and show canvas
        const skeleton = document.getElementById('provinceChartSkeleton');
        if (skeleton) {
            skeleton.style.display = 'none';
        }
        ctx.style.display = 'block';

        // Destroy existing chart if it exists
        if (this.provinceBarChart) {
            this.provinceBarChart.destroy();
        }

        // Process data for better display
        const processedData = this.processProvinceChartData(data);

        const chartConfig = {
            type: this.provinceChartType === 'horizontal' ? 'bar' : 'bar',
            data: processedData,
            options: {
                indexAxis: this.provinceChartType === 'horizontal' ? 'y' : 'x',
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
                        displayColors: true,
                        callbacks: {
                            title: function(context) {
                                return `Provinsi: ${context[0].dataset.label}`;
                            },
                            label: function(context) {
                                const month = context.label;
                                const count = context.parsed.y || context.parsed.x;
                                return `${month}: ${count.toLocaleString('id-ID')} izin`;
                            }
                        }
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
                            display: this.provinceChartType === 'vertical',
                            text: 'Bulan',
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
                            display: this.provinceChartType === 'horizontal',
                            text: 'Jumlah Izin',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        };

        this.provinceBarChart = new Chart(ctx, chartConfig);
    }

    processProvinceChartData(data) {
        if (!data.labels || !data.datasets) {
            console.error('Missing province chart data');
            return data;
        }

        // This is now showing actual provinces from database
        // Colors are already assigned by the backend, just ensure they look good
        const processedDatasets = data.datasets.map((dataset, index) => {
            return {
                ...dataset,
                borderWidth: 2,
                borderRadius: this.provinceChartType === 'horizontal' ? 2 : 1,
                borderSkipped: false,
                // For horizontal chart, make bars more visible
                barPercentage: this.provinceChartType === 'horizontal' ? 0.8 : 0.9,
                categoryPercentage: this.provinceChartType === 'horizontal' ? 0.9 : 0.8
            };
        });

        return {
            labels: data.labels,
            datasets: processedDatasets
        };
    }

    showProvinceBarChartError(message) {
        const ctx = document.getElementById('provinceBarChart');
        if (!ctx) return;

        const canvasContainer = ctx.parentElement;
        canvasContainer.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="text-red-500 text-3xl mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p class="text-sm text-gray-600">${message}</p>
                    <button onclick="dashboardCharts.initProvinceBarChart()"
                            class="mt-3 px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                        <i class="fas fa-redo mr-2"></i>Coba Lagi
                    </button>
                </div>
            </div>
        `;
    }

    async refreshProvinceBarChart() {
        // Refresh province bar chart with same data as main chart
        if (this.chartData) {
            const ctx = document.getElementById('provinceBarChart');
            if (ctx) {
                this.createOrUpdateProvinceBarChart(ctx, this.chartData);
            }
        }
    }

    showProvinceBarChartSample() {
        // Sample data for top provinces from database
        const sampleData = {
            labels: ['Jul 2025', 'Agu 2025', 'Sep 2025', 'Okt 2025', 'Nov 2025', 'Des 2025'],
            datasets: [
                {
                    label: 'DKI Jakarta',
                    data: [450, 520, 480, 590, 650, 720],
                    backgroundColor: 'rgba(79, 70, 229, 0.8)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Jawa Barat',
                    data: [380, 420, 390, 460, 510, 580],
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Jawa Tengah',
                    data: [290, 320, 310, 380, 420, 480],
                    backgroundColor: 'rgba(245, 158, 11, 0.8)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Jawa Timur',
                    data: [340, 360, 350, 420, 470, 530],
                    backgroundColor: 'rgba(236, 72, 153, 0.8)',
                    borderColor: 'rgba(236, 72, 153, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Banten',
                    data: [180, 210, 200, 250, 290, 340],
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Sumatera Utara',
                    data: [150, 170, 160, 200, 230, 270],
                    backgroundColor: 'rgba(55, 48, 163, 0.8)',
                    borderColor: 'rgba(55, 48, 163, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Sulawesi Selatan',
                    data: [120, 140, 130, 170, 190, 220],
                    backgroundColor: 'rgba(107, 33, 168, 0.8)',
                    borderColor: 'rgba(107, 33, 168, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Kalimantan Timur',
                    data: [100, 120, 110, 140, 160, 190],
                    backgroundColor: 'rgba(14, 165, 233, 0.8)',
                    borderColor: 'rgba(14, 165, 233, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Sumatera Barat',
                    data: [90, 110, 100, 130, 150, 180],
                    backgroundColor: 'rgba(251, 146, 60, 0.8)',
                    borderColor: 'rgba(251, 146, 60, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Riau',
                    data: [80, 95, 90, 120, 140, 170],
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 2,
                    borderRadius: 4
                }
            ]
        };

        const ctx = document.getElementById('provinceBarChart');
        if (ctx) {
            this.createOrUpdateProvinceBarChart(ctx, sampleData);
        }
    }

    destroy() {
        // Clear all intervals
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }

        if (this.timeUpdateInterval) {
            clearInterval(this.timeUpdateInterval);
            this.timeUpdateInterval = null;
        }

        if (this.monthCheckInterval) {
            clearInterval(this.monthCheckInterval);
            this.monthCheckInterval = null;
        }

        if (this.debounceRefresh) {
            clearTimeout(this.debounceRefresh);
            this.debounceRefresh = null;
        }

        // Destroy charts
        if (this.pencatatanChart) {
            this.pencatatanChart.destroy();
            this.pencatatanChart = null;
        }

        if (this.provinceBarChart) {
            this.provinceBarChart.destroy();
            this.provinceBarChart = null;
        }

        // Clear references
        this.canvasContainer = null;
        this.currentTimeElement = null;
    }
}

// Initialize when DOM is ready
const dashboardCharts = new DashboardCharts();

// Make it available globally for manual refresh if needed
window.dashboardCharts = dashboardCharts;

// Global function for refreshing province stats - uses custom date picker
window.refreshProvinceStats = async function() {
    if (window.customDatePicker) {
        window.customDatePicker.apply();
    }
};

// Global function for toggling province chart type
window.toggleProvinceChartType = function() {
    // Show skeleton while switching chart type
    const skeleton = document.getElementById('provinceChartSkeleton');
    const canvas = document.getElementById('provinceBarChart');

    if (skeleton) {
        skeleton.style.display = 'block';
    }
    if (canvas) {
        canvas.style.display = 'none';
    }

    dashboardCharts.provinceChartType = dashboardCharts.provinceChartType === 'horizontal' ? 'vertical' : 'horizontal';

    // Update button text
    const chartTypeText = document.getElementById('chartTypeText');
    if (chartTypeText) {
        chartTypeText.textContent = dashboardCharts.provinceChartType === 'horizontal' ? 'Horizontal' : 'Vertical';
    }

    // Reinitialize the province chart
    dashboardCharts.initProvinceBarChart();
};