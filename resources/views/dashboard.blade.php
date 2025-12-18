<x-app-layout>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-glow: 0 0 20px rgba(102, 126, 234, 0.4);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .dashboard-container {
            position: relative;
            overflow-x: hidden;
        }

        .dashboard-background {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            opacity: 0.05;
        }

        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            opacity: 0.03;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            background: var(--primary-gradient);
            border-radius: 50%;
            top: -200px;
            right: -100px;
            animation: float 20s ease-in-out infinite;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            background: var(--secondary-gradient);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            bottom: -150px;
            left: -100px;
            animation: float 15s ease-in-out infinite reverse;
        }

        .shape-3 {
            width: 200px;
            height: 200px;
            background: var(--success-gradient);
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            top: 50%;
            right: 10%;
            animation: rotate 25s linear infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-30px) rotate(120deg); }
            66% { transform: translateY(30px) rotate(240deg); }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-glow);
        }

        .metric-card {
            position: relative;
            padding: 2rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .metric-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .metric-card:hover {
            transform: translateY(-8px) scale(1.02);
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
        }

        .metric-icon::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: inherit;
            filter: blur(10px);
            opacity: 0.5;
            z-index: -1;
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .chart-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102,126,234,0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .chart-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .chart-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .custom-select {
            padding: 0.75rem 1.5rem;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            background: white;
            font-size: 0.9rem;
            position: relative;
            z-index: 11;
            pointer-events: auto !important;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
        }

        .custom-select:hover {
            border-color: rgba(102, 126, 234, 0.4);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .custom-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .comparison-toggle {
            display: flex;
            background: rgba(241, 245, 249, 0.8);
            border-radius: 12px;
            padding: 4px;
            position: relative;
            z-index: 10;
        }

        .toggle-btn {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            z-index: 11;
            pointer-events: auto !important;
            color: #64748b;
        }

        .toggle-btn.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 12px;
        }

        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }

        .chart-legend {
            display: flex;
            gap: 2rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .data-tooltip {
            position: absolute;
            background: rgba(30, 41, 59, 0.95);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .data-tooltip.visible {
            opacity: 1;
        }

        .refresh-btn {
            position: relative;
            padding: 0.75rem 1.5rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .refresh-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .refresh-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .refresh-btn.spinning i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .main-chart-area {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .bottom-charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        @media (max-width: 1024px) {
            .main-chart-area {
                grid-template-columns: 1fr;
            }

            .bottom-charts {
                grid-template-columns: 1fr;
            }
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }
        .stagger-4 { animation-delay: 0.4s; }
    </style>

    <div class="dashboard-container">
        <div class="dashboard-background"></div>
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8 fade-in">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    Analytics Dashboard
                </h1>
                <p class="text-xl text-gray-600">
                    Real-time insights from all LSP Gatensi databases
                </p>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="glass-card metric-card fade-in stagger-1">
                    <div class="metric-icon" style="background: var(--primary-gradient);">
                        <i class="fas fa-database text-white"></i>
                    </div>
                    <div class="metric-value" data-metric="total-pencatatan">-</div>
                    <div class="text-gray-600 font-medium">Total Records</div>
                    <div class="mt-4 flex items-center text-sm text-green-600">
                        <i class="fas fa-arrow-up mr-1"></i>
                        <span>All Systems</span>
                    </div>
                </div>

                <div class="glass-card metric-card fade-in stagger-2">
                    <div class="metric-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-building text-white"></i>
                    </div>
                    <div class="metric-value" data-metric="balai">-</div>
                    <div class="text-gray-600 font-medium">Balai System</div>
                    <div class="mt-4 flex items-center text-sm text-blue-600">
                        <i class="fas fa-circle mr-1"></i>
                        <span>Active</span>
                    </div>
                </div>

                <div class="glass-card metric-card fade-in stagger-3">
                    <div class="metric-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div class="metric-value" data-metric="reguler">-</div>
                    <div class="text-gray-600 font-medium">Reguler System</div>
                    <div class="mt-4 flex items-center text-sm text-green-600">
                        <i class="fas fa-circle mr-1"></i>
                        <span>Active</span>
                    </div>
                </div>

                <div class="glass-card metric-card fade-in stagger-4">
                    <div class="metric-icon" style="background: var(--secondary-gradient);">
                        <i class="fas fa-star text-white"></i>
                    </div>
                    <div class="metric-value" data-metric="fg">-</div>
                    <div class="text-gray-600 font-medium">FG System</div>
                    <div class="mt-4 flex items-center text-sm text-green-600">
                        <i class="fas fa-circle mr-1"></i>
                        <span>Active</span>
                    </div>
                </div>
            </div>

            <!-- Main Charts Area -->
            <div class="main-chart-area">
                <!-- Time Series Chart -->
                <div class="chart-container fade-in stagger-1">
                    <div class="chart-header">
                        <div>
                            <h2 class="chart-title">Trend Analysis</h2>
                            <p class="text-gray-600 mt-1">Pencatatan Izin per Bulan</p>
                        </div>
                        <div class="chart-controls" style="position: relative; z-index: 10; pointer-events: auto;">
                            <div class="comparison-toggle" style="position: relative; z-index: 11;">
                                <button class="toggle-btn active" data-comparison="year" style="position: relative; z-index: 12; pointer-events: auto !important; cursor: pointer;">Year vs Year</button>
                                <button class="toggle-btn" data-comparison="month" style="position: relative; z-index: 12; pointer-events: auto !important; cursor: pointer;">Monthly</button>
                                <button class="toggle-btn" data-comparison="daterange" style="position: relative; z-index: 12; pointer-events: auto !important; cursor: pointer;">Date Range</button>
                            </div>
                            <div class="date-controls" id="globalDateControls" style="display: none; gap: 0.5rem; align-items: center; flex-wrap: wrap; position: relative; z-index: 12; pointer-events: auto !important;">
                                <input type="date" id="startDate" class="custom-select" style="padding: 0.5rem; min-width: 150px;" value="{{ now()->subYear()->format('Y-m-d') }}">
                                <span style="margin: 0 0.5rem;">to</span>
                                <input type="date" id="endDate" class="custom-select" style="padding: 0.5rem; min-width: 150px;" value="{{ now()->format('Y-m-d') }}">
                                <select id="globalYearSelector" class="custom-select" style="min-width: 100px;">
                                    <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                    <option value="{{ date('Y') - 1 }}">{{ date('Y') - 1 }}</option>
                                    <option value="{{ date('Y') - 2 }}">{{ date('Y') - 2 }}</option>
                                    <option value="{{ date('Y') - 3 }}">{{ date('Y') - 3 }}</option>
                                </select>
                                <button id="applyFilters" class="toggle-btn" style="margin-left: 0.5rem; background: #667eea; color: white; min-width: 80px;">Apply</button>
                            </div>
                            <select class="custom-select" id="yearSelector" style="position: relative; z-index: 11; pointer-events: auto !important; cursor: pointer;">
                                <option value="{{ date('Y') }}">{{ date('Y') }} vs {{ date('Y') - 1 }}</option>
                                <option value="{{ date('Y') - 1 }}">{{ date('Y') - 1 }} vs {{ date('Y') - 2 }}</option>
                                <option value="{{ date('Y') - 2 }}">{{ date('Y') - 2 }} vs {{ date('Y') - 3 }}</option>
                            </select>
                        </div>
                    </div>
                    <div style="height: 400px; position: relative;">
                        <canvas id="mainTimeChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #667eea;"></div>
                            <span>Current Year</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f093fb;"></div>
                            <span>Previous Year</span>
                        </div>
                    </div>
                </div>

                <!-- System Distribution -->
                <div class="chart-container fade-in stagger-2">
                    <div class="chart-header">
                        <div>
                            <h2 class="chart-title">Distribution</h2>
                            <p class="text-gray-600 mt-1">By Database System</p>
                        </div>
                    </div>
                    <div style="height: 400px; position: relative;">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bottom Charts -->
            <div class="bottom-charts">
                <!-- Province Ranking -->
                <div class="chart-container fade-in stagger-3">
                    <div class="chart-header">
                        <div>
                            <h2 class="chart-title">Top Provinces Performance</h2>
                            <p class="text-gray-600 mt-1">Monthly trends for top 10 provinces</p>
                        </div>
                        <button class="refresh-btn" onclick="refreshCharts()">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Refresh
                        </button>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="provinceChart"></canvas>
                    </div>
                </div>

                <!-- Province Rankings -->
                <div class="chart-container fade-in stagger-4">
                    <div class="chart-header">
                        <div>
                            <h2 class="chart-title">Province Rankings</h2>
                            <p class="text-gray-600 mt-1">Top 5 provinces by records</p>
                        </div>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global chart configuration
        if (typeof Chart !== 'undefined') {
            Chart.defaults.font.family = 'system-ui, -apple-system, BlinkMacSystemFont, sans-serif';
            Chart.defaults.plugins.legend.labels.usePointStyle = true;
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(30, 41, 59, 0.95)';
            Chart.defaults.plugins.tooltip.titleColor = '#fff';
            Chart.defaults.plugins.tooltip.bodyColor = '#fff';
            Chart.defaults.plugins.tooltip.padding = 12;
            Chart.defaults.plugins.tooltip.cornerRadius = 12;
            Chart.defaults.plugins.tooltip.displayColors = true;
        }

        // Comparison toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-btn');
            const yearSelector = document.getElementById('yearSelector');
            const globalDateControls = document.getElementById('globalDateControls');
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            const globalYearSelector = document.getElementById('globalYearSelector');
            const applyFilters = document.getElementById('applyFilters');

            let currentComparison = 'normal'; // Default to normal mode (show all data)

            // Cache untuk menyimpan data
            const dataCache = new Map();
            let loadingPromises = new Map();

            // Function untuk create cache key
            function getCacheKey(type, params = {}) {
                return `${type}_${JSON.stringify(params)}`;
            }

            // Function untuk get cached data
            async function getCachedData(cacheKey, fetchFunction) {
                if (dataCache.has(cacheKey)) {
                    console.log('Using cached data for:', cacheKey);
                    return dataCache.get(cacheKey);
                }

                if (loadingPromises.has(cacheKey)) {
                    console.log('Data is loading:', cacheKey);
                    return loadingPromises.get(cacheKey);
                }

                const promise = fetchFunction();

                loadingPromises.set(cacheKey, promise);

                try {
                    const data = await promise;

                    // Cache successful responses
                    dataCache.set(cacheKey, data);
                    // Cache for 5 minutes
                    setTimeout(() => dataCache.delete(cacheKey), 300000);

                    return data;
                } catch (error) {
                    loadingPromises.delete(cacheKey);
                    throw error;
                } finally {
                    loadingPromises.delete(cacheKey);
                }
            }

            console.log('Initializing comparison toggle...');
            console.log('Toggle buttons found:', toggleButtons.length);
            console.log('Year selector found:', !!yearSelector);
            console.log('Global date controls found:', !!globalDateControls);

            // Set initial active state
            updateActiveButton();

            // Hide/show controls based on comparison type
            if (yearSelector) {
                yearSelector.style.display = currentComparison === 'year' ? 'block' : 'none';
            }

            if (globalDateControls) {
                globalDateControls.style.display = currentComparison === 'daterange' ? 'flex' : 'none';
            }

            // Add click event listeners to toggle buttons
            toggleButtons.forEach(button => {
                console.log('Adding click listener to button:', button.dataset.comparison);
                button.addEventListener('click', async function() {
                    console.log('Button clicked:', this.dataset.comparison);

                    // Remove active class from all buttons
                    toggleButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');

                    // Update comparison type
                    currentComparison = this.dataset.comparison;
                    console.log('Current comparison changed to:', currentComparison);

                    // Update year selector visibility
                    if (yearSelector) {
                        yearSelector.style.display = currentComparison === 'year' ? 'block' : 'none';
                    }

                    // Update global date controls visibility
                    if (globalDateControls) {
                        globalDateControls.style.display = currentComparison === 'daterange' ? 'flex' : 'none';
                    }

                    // Refresh charts with new comparison type
                    console.log('Refreshing charts with comparison type:', currentComparison);
                    const allData = await refreshChartsWithComparison(currentComparison);
                    // Update other charts with same data (no need to fetch again)
                    updateOtherChartsWithExistingData(allData, currentComparison);
                });
            });

            // Track previous values to prevent redundant calls
            let previousYearValue = yearSelector ? yearSelector.value : null;
            let previousDateRange = { start: null, end: null };

            // Add change event listener to year selector
            if (yearSelector) {
                yearSelector.addEventListener('change', function() {
                    const currentYearValue = this.value;
                    console.log('Year selector changed to:', currentYearValue);

                    // Only refresh if value actually changed
                    if (previousYearValue !== currentYearValue && currentComparison === 'year') {
                        previousYearValue = currentYearValue;
                        console.log('Refreshing charts for year comparison');

                        // Only clear relevant cache entries
                        const keysToDelete = [];
                        dataCache.forEach((value, key) => {
                            if (key.includes('year_comparison') || key.includes('province_ranking')) {
                                keysToDelete.push(key);
                            }
                        });
                        keysToDelete.forEach(key => dataCache.delete(key));

                        refreshChartsWithComparison('year');
                    }
                });
            }

            // Add change event listener to Apply button
            if (applyFilters) {
                applyFilters.addEventListener('click', async function() {
                    console.log('Apply filters clicked');
                    if (currentComparison === 'daterange') {
                        const start = startDate.value;
                        const end = endDate.value;
                        console.log(`Date range: ${start} to ${end}`);

                        // Only refresh if date range actually changed
                        if (previousDateRange.start !== start || previousDateRange.end !== end) {
                            previousDateRange = { start, end };

                            // Only clear relevant cache entries
                            const keysToDelete = [];
                            dataCache.forEach((value, key) => {
                                if (key.includes('time_series') || key.includes('province_ranking')) {
                                    keysToDelete.push(key);
                                }
                            });
                            keysToDelete.forEach(key => dataCache.delete(key));

                            const allData = await refreshChartsWithComparison('daterange');
                            updateOtherChartsWithExistingData(allData, 'daterange');
                        }
                    }
                });
            }

            // Add change event listener to global year selector
            if (globalYearSelector) {
                globalYearSelector.addEventListener('change', async function() {
                    console.log('Global year selector changed to:', this.value);
                    // Clear cache when year changes
                    dataCache.clear();
                    // Auto apply when year changes
                    if (currentComparison === 'daterange' && applyFilters) {
                        applyFilters.click();
                    } else {
                        const allData = await refreshChartsWithComparison(currentComparison);
                        updateOtherChartsWithExistingData(allData, currentComparison);
                    }
                });
            }

            function updateActiveButton() {
                toggleButtons.forEach(btn => {
                    // If currentComparison is 'normal', activate 'year' button by default
                    const shouldBeActive = (currentComparison === 'normal' && btn.dataset.comparison === 'year') ||
                                        btn.dataset.comparison === currentComparison;

                    if (shouldBeActive) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }

            async function refreshChartsWithComparison(comparisonType) {
                try {
                    // Show loading state
                    showLoadingState();
                    const mainChart = document.getElementById('mainTimeChart');
                    if (mainChart) {
                        mainChart.style.opacity = '0.5';
                    }

                    // Determine API endpoint and parameters based on comparison type
                    let apiUrl = '';
                    let params = {};
                    let cacheKey = '';

                    if (comparisonType === 'year' || comparisonType === 'normal') {
                        const selectedYear = yearSelector ? parseInt(yearSelector.value) : {{ date('Y') }};
                        const previousYear = selectedYear - 1;
                        apiUrl = `/api/dashboard/pencatatan-izin/year-comparison`;
                        params = { year: selectedYear, previousYear: previousYear };
                        cacheKey = getCacheKey('year_comparison', params);
                        updateLegend('Year vs Year', selectedYear, previousYear);
                    } else if (comparisonType === 'month') {
                        apiUrl = '/api/dashboard/pencatatan-izin/monthly-comparison';
                        params = {};
                        cacheKey = getCacheKey('monthly_comparison', {});
                        updateLegend('Monthly', null, null);
                    } else if (comparisonType === 'daterange') {
                        const start = startDate.value;
                        const end = endDate.value;
                        const year = globalYearSelector ? globalYearSelector.value : {{ date('Y') }};
                        apiUrl = `/api/dashboard/pencatatan-izin/time-series`;
                        params = { start_date: start, end_date: end };
                        cacheKey = getCacheKey('time_series', params);
                        updateLegend('Date Range', start, end);
                    }

                    // Build query string
                    const queryString = Object.keys(params).length > 0 ? '?' + new URLSearchParams(params).toString() : '';
                    const fullUrl = apiUrl + queryString;

                    // Check cache first
                    let result = await getCachedData(cacheKey, async () => {
                        const response = await fetch(fullUrl, {
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

                        return response.json();
                    });

                    if (result.success) {
                        // Update main time series chart
                        updateMainTimeChart(result.data, comparisonType);

                        // Update other charts with cached data if available
                        const allData = { distribution: result.data, province: null };

                        // Try to get province data from cache
                        const provinceCacheKey = comparisonType === 'year' ?
                            getCacheKey('province_ranking', { limit: 5 }) :
                            getCacheKey('province_ranking', { limit: 10 });

                        try {
                            const provinceData = await getCachedData(provinceCacheKey, async () => {
                                const response = await fetch(`/api/dashboard/province-ranking?limit=${comparisonType === 'year' ? 5 : 10}`);
                                return response.json();
                            });

                            if (provinceData.success) {
                                allData.province = provinceData.data;
                            }
                        } catch (provError) {
                            console.warn('Failed to load province data:', provError);
                        }

                        // Update all charts at once
                        updateOtherChartsWithExistingData(allData, comparisonType);

                        // Return data for other functions that need it
                        return allData;
                    } else {
                        throw new Error(result.message || 'Failed to fetch data');
                    }

                } catch (error) {
                    console.error('Error refreshing charts:', error);
                    // Show error message to user
                    showErrorNotification('Gagal memuat data: ' + error.message);
                    return null;
                } finally {
                    // Remove loading state
                    hideLoadingState();
                    const mainChart = document.getElementById('mainTimeChart');
                    if (mainChart) {
                        mainChart.style.opacity = '1';
                    }
                }
            }

            function updateLegend(comparisonType, currentYear, previousYear) {
                const legendContainer = document.querySelector('.chart-legend');
                if (!legendContainer) return;

                if (comparisonType === 'Year vs Year') {
                    legendContainer.innerHTML = `
                        <div class="legend-item">
                            <div class="legend-color" style="background: #667eea;"></div>
                            <span>${currentYear}</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f093fb;"></div>
                            <span>${previousYear}</span>
                        </div>
                    `;
                } else if (comparisonType === 'Monthly') {
                    const now = new Date();
                    const currentMonthName = now.toLocaleString('id-ID', { month: 'long' });
                    const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1);
                    const lastMonthName = lastMonth.toLocaleString('id-ID', { month: 'long' });

                    legendContainer.innerHTML = `
                        <div class="legend-item">
                            <div class="legend-color" style="background: #667eea;"></div>
                            <span>${currentMonthName}</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f093fb;"></div>
                            <span>${lastMonthName}</span>
                        </div>
                    `;
                } else {
                    // Normal mode - show database sources
                    legendContainer.innerHTML = `
                        <div class="legend-item">
                            <div class="legend-color" style="background: #3B82F6;"></div>
                            <span>Balai</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #10B981;"></div>
                            <span>Reguler</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #F59E0B;"></div>
                            <span>FG</span>
                        </div>
                    `;
                }
            }

            function updateMainTimeChart(data, comparisonType) {
                const ctx = document.getElementById('mainTimeChart');
                if (!ctx || !data) return;

                // Destroy existing chart if it exists
                if (window.mainTimeChartInstance) {
                    window.mainTimeChartInstance.destroy();
                    window.mainTimeChartInstance = null;
                }

                // Also destroy any existing chart from professional dashboard
                if (window.professionalDashboard && window.professionalDashboard.charts.mainTime) {
                    window.professionalDashboard.charts.mainTime.destroy();
                    window.professionalDashboard.charts.mainTime = null;
                }

                // Destroy any chart instance that might be attached to the canvas
                const existingChart = Chart.getChart(ctx);
                if (existingChart) {
                    existingChart.destroy();
                }

                // Process data based on comparison type
                let processedData = data;

                if (comparisonType === 'month') {
                    // For monthly comparison, we want current vs previous month
                    processedData = processMonthlyData(data);
                }

                window.mainTimeChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: processedData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false // We're using custom legend
                            },
                            tooltip: {
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
                                callbacks: {
                                    label: function(context) {
                                        const value = context.parsed.y;
                                        const label = comparisonType === 'year' ?
                                            context.dataset.label :
                                            context.dataset.label;
                                        return `${label}: ${value.toLocaleString('id-ID')} izin`;
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
                                    maxRotation: 0
                                },
                                title: {
                                    display: true,
                                    text: comparisonType === 'year' ?
                                        `Perbandingan Tahun` :
                                        `Bulan Ini vs Bulan Lalu`,
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
                            duration: 800,
                            easing: 'easeOutCubic'
                        }
                    }
                });
            }

            function processMonthlyData(data) {
                // Process data for monthly comparison (current month vs previous month)
                if (!data || !data.datasets) return data;

                const now = new Date();
                const currentMonth = now.toLocaleString('id-ID', { month: 'long' });
                const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1);
                const lastMonthName = lastMonth.toLocaleString('id-ID', { month: 'long' });

                // Update labels to show current and previous month
                const labels = [lastMonthName, currentMonth];

                // Process datasets
                const processedDatasets = data.datasets.map((dataset, index) => ({
                    ...dataset,
                    data: dataset.data.slice(-2), // Get last 2 months
                    borderColor: index === 0 ? '#667eea' : '#f093fb',
                    backgroundColor: index === 0 ? 'rgba(102, 126, 234, 0.1)' : 'rgba(240, 147, 251, 0.1)',
                    pointBackgroundColor: index === 0 ? '#667eea' : '#f093fb',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }));

                return {
                    labels: labels,
                    datasets: processedDatasets
                };
            }

            function showErrorNotification(message) {
                // Create error notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <span>${message}</span>
                    </div>
                `;

                document.body.appendChild(notification);

                // Remove notification after 5 seconds
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }

            // Initialize with default data
            refreshChartsWithComparison(currentComparison);

            // Initialize other charts with current comparison
            console.log('Initializing other charts with comparison:', currentComparison);
            initializeOtherCharts(currentComparison);
        });

        // Update other charts with existing data (for performance optimization)
        function updateOtherChartsWithExistingData(allData, comparisonType) {
            console.log('Updating other charts with existing data:', comparisonType);

            try {
                // Update Distribution Chart
                if (allData.distribution) {
                    if (comparisonType === 'year') {
                        const yearSelector = document.getElementById('yearSelector');
                        const distYearSelector = document.getElementById('distYearSelector');
                        const selectedYear = yearSelector ? parseInt(yearSelector.value) : {{ date('Y') }};
                        const distYear = distYearSelector ? parseInt(distYearSelector.value) : selectedYear;
                        createDistributionComparisonChart(allData.distribution, distYear, distYear - 1);
                    } else if (comparisonType === 'month') {
                        createMonthlyComparisonChart(allData.distribution);
                    } else {
                        createDistributionYearChart(allData.distribution);
                    }
                }

                // Update Province Charts
                if (allData.province) {
                    if (comparisonType === 'year') {
                        const yearSelector = document.getElementById('yearSelector');
                        const selectedYear = yearSelector ? parseInt(yearSelector.value) : {{ date('Y') }};
                        createProvinceComparisonChart(allData.province.rankings || allData.province, selectedYear);
                    } else {
                        createExtendedProvinceChart(allData.province.rankings || allData.province.slice(0, 10));
                    }
                }

                console.log('All charts updated successfully');
            } catch (error) {
                console.error('Error updating other charts:', error);
            }
        }

        // Show loading state
        function showLoadingState() {
            const loader = document.getElementById('chartLoader');
            if (loader) {
                loader.classList.remove('hidden');
            }
        }

        // Hide loading state
        function hideLoadingState() {
            const loader = document.getElementById('chartLoader');
            if (loader) {
                loader.classList.add('hidden');
            }
        }

        // Initialize Distribution, Province, and Daily charts (with caching)
        async function initializeOtherCharts(comparisonType = null) {
            try {
                console.log('Initializing other charts with comparison type:', comparisonType);

                if (comparisonType === 'year') {
                    // Load comparison data for year mode
                    const yearSelector = document.getElementById('yearSelector');
                    const distYearSelector = document.getElementById('distYearSelector');
                    const selectedYear = yearSelector ? parseInt(yearSelector.value) : {{ date('Y') }};
                    const distYear = distYearSelector ? parseInt(distYearSelector.value) : selectedYear;

                    const distCacheKey = getCacheKey('year_comparison', { year: distYear, previousYear: distYear - 1 });
                    const provCacheKey = getCacheKey('province_ranking', { limit: 5 });

                    const [distResult, provResult] = await Promise.all([
                        getCachedData(distCacheKey, async () => {
                            const response = await fetch(`/api/dashboard/pencatatan-izin/year-comparison?year=${distYear}&previousYear=${distYear - 1}`);
                            return response.json();
                        }),
                        getCachedData(provCacheKey, async () => {
                            const response = await fetch(`/api/dashboard/province-ranking?limit=5`);
                            return response.json();
                        })
                    ]);

                    if (distResult.success) {
                        createDistributionComparisonChart(distResult.data, distYear, distYear - 1);
                    }
                    if (provResult.success) {
                        createProvinceComparisonChart(provResult.data.rankings, selectedYear);
                    }

                } else if (comparisonType === 'month') {
                    // Load comparison data for month mode
                    const distCacheKey = getCacheKey('monthly_comparison', {});
                    const provCacheKey = getCacheKey('province_ranking', { limit: 10 });

                    const [distResult, provResult] = await Promise.all([
                        getCachedData(distCacheKey, async () => {
                            const response = await fetch('/api/dashboard/pencatatan-izin/monthly-comparison');
                            return response.json();
                        }),
                        getCachedData(provCacheKey, async () => {
                            const response = await fetch('/api/dashboard/province-ranking?limit=10');
                            return response.json();
                        })
                    ]);

                    if (distResult.success) {
                        createMonthlyComparisonChart(distResult.data);
                    }
                    if (provResult.success) {
                        createProvinceChart(provResult.data.rankings);
                        createExtendedProvinceChart(provResult.data.rankings.slice(0, 10));
                    }

                } else {
                    // Normal mode - get data for 1 year (same as Trend Analysis)
                    const distCacheKey = getCacheKey('time_series', {});
                    const provCacheKey = getCacheKey('province_ranking', { limit: 10 });

                    const [totals, provinces] = await Promise.all([
                        getCachedData(distCacheKey, async () => {
                            const response = await fetch(`/api/dashboard/pencatatan-izin/time-series`);
                            return response.json();
                        }),
                        getCachedData(provCacheKey, async () => {
                            const response = await fetch(`/api/dashboard/province-ranking?limit=10`);
                            return response.json();
                        })
                    ]);

                    // For Distribution, we need to create a year-based chart from time-series data
                    if (totals.success && totals.data && totals.data.datasets) {
                        createDistributionYearChart(totals.data);
                    } else {
                        console.error('Failed to get distribution data:', totals);
                        createDistributionYearChart({ datasets: {} });
                    }

                    // Initialize Province Rankings Chart
                    if (provinces.success && provinces.data && provinces.data.rankings) {
                        createExtendedProvinceChart(provinces.data.rankings.slice(0, 10));
                    } else {
                        console.error('Failed to get province data:', provinces);
                        createExtendedProvinceChart([]);
                    }
                }

            } catch (error) {
                console.error('Error initializing other charts:', error);
                showErrorNotification('Gagal memuat data chart lainnya: ' + error.message);
            }
        }

        function createDistributionChart(data) {
            const ctx = document.getElementById('distributionChart');
            if (!ctx) return;

            // Destroy existing chart
            const existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Balai', 'Reguler', 'FG'],
                    datasets: [{
                        data: [data.balai, data.reguler, data.fg],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.raw / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.raw.toLocaleString('id-ID')} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function createDistributionYearChart(data) {
            const ctx = document.getElementById('distributionChart');
            if (!ctx) return;

            // Destroy existing chart
            const existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }

            // Use time series data for 1 year
            const labels = data.labels;
            const balaiData = data.datasets.balai || [];
            const regulerData = data.datasets.reguler || [];
            const fgData = data.datasets.fg || [];

            // Calculate totals for each platform over the year
            const balaiTotal = balaiData.reduce((a, b) => a + b, 0);
            const regulerTotal = regulerData.reduce((a, b) => a + b, 0);
            const fgTotal = fgData.reduce((a, b) => a + b, 0);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Balai', 'Reguler', 'FG'],
                    datasets: [{
                        label: `Total Records (Last Year)`,
                        data: [balaiTotal, regulerTotal, fgTotal],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)'
                        ],
                        borderWidth: 2,
                        borderRadius: 8
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
                                    const platform = context.label;
                                    const value = context.raw.toLocaleString('id-ID');
                                    const total = balaiTotal + regulerTotal + fgTotal;
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${platform}: ${value} records (${percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Records (Last 12 Months)'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000) return (value/1000).toFixed(1) + 'K';
                                    return value.toLocaleString('id-ID');
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Database Platforms'
                            }
                        }
                    }
                }
            });
        }

        function createProvinceChart(data) {
            const ctx = document.getElementById('provinceChart');
            if (!ctx) return;

            // Destroy existing chart
            const existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }

            // Get selected year from global controls
            const globalYearSelector = document.getElementById('globalYearSelector');
            const selectedYear = globalYearSelector ? globalYearSelector.value : new Date().getFullYear();

            // Generate 12 months labels for the selected year
            const months = [];
            const startDate = new Date(selectedYear, 0, 1); // January 1st of selected year
            for (let i = 0; i < 12; i++) {
                const monthDate = new Date(startDate.getFullYear(), startDate.getMonth() + i, 1);
                months.push(monthDate.toLocaleString('id-ID', { month: 'short', year: 'numeric' }));
            }

            // Create datasets for top 5 provinces
            const top5 = data.slice(0, 5);
            const datasets = top5.map((province, index) => {
                // Generate sample data for each month (we'll get this from API)
                const dataPoints = new Array(12).fill(0);
                // Fill some sample data for demonstration
                const baseValue = province.total;
                for (let i = 0; i < 12; i++) {
                    dataPoints[i] = Math.floor(baseValue * (0.5 + Math.random() * 0.5) + (i * 2));
                }

                const colors = [
                    'rgba(102, 126, 234, 1)',
                    'rgba(240, 147, 251, 1)',
                    'rgba(59, 130, 246, 1)',
                    'rgba(254, 225, 64, 1)',
                    'rgba(250, 112, 154, 1)'
                ];

                return {
                    label: province.provinsi,
                    data: dataPoints,
                    borderColor: colors[index],
                    backgroundColor: colors[index] + '33',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                };
            });

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: datasets
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
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw.toLocaleString('id-ID')} records`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Records Count'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000) return (value/1000).toFixed(1) + 'K';
                                    return value.toLocaleString('id-ID');
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: `Monthly Performance - ${selectedYear}`
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function createExtendedProvinceChart(data) {
            const ctx = document.getElementById('dailyChart');
            if (!ctx) return;

            // Destroy existing chart
            const existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }

            const labels = data.map((p, i) => `#${i + 1} ${p.provinsi}`);
            const values = data.map(p => p.total);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Records',
                        data: values,
                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        hoverBackgroundColor: 'rgba(99, 102, 241, 0.9)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Horizontal bar chart for better readability
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const rank = context.dataIndex + 1;
                                    const province = data[context.dataIndex].provinsi;
                                    return `${province}: ${context.raw.toLocaleString('id-ID')} records`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            title: {
                                display: true,
                                text: 'Total Records'
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                autoSkip: false
                            }
                        }
                    }
                }
            });
        }

        // Comparison chart functions
        function createDistributionComparisonChart(data, currentYear, previousYear) {
            const ctx = document.getElementById('distributionChart');
            if (!ctx) return;

            // Destroy existing chart
            const existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }

            // Get data for each platform for the selected year
            const months = data.labels;
            const balaiData = data.datasets.find(d => d.label.toLowerCase() === 'balai')?.data || [];
            const regulerData = data.datasets.find(d => d.label.toLowerCase() === 'reguler')?.data || [];
            const fgData = data.datasets.find(d => d.label.toLowerCase() === 'fg')?.data || [];

            // Calculate totals for each platform
            const balaiTotal = balaiData.reduce((a, b) => a + b, 0);
            const regulerTotal = regulerData.reduce((a, b) => a + b, 0);
            const fgTotal = fgData.reduce((a, b) => a + b, 0);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Balai', 'Reguler', 'FG'],
                    datasets: [{
                        label: `Total Records ${currentYear}`,
                        data: [balaiTotal, regulerTotal, fgTotal],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)'
                        ],
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Horizontal bar chart
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const platform = context.label;
                                    const value = context.raw.toLocaleString('id-ID');
                                    const total = balaiTotal + regulerTotal + fgTotal;
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${platform}: ${value} records (${percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Records'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000) return (value/1000).toFixed(1) + 'K';
                                    return value.toLocaleString('id-ID');
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Database Platforms'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function createMonthlyComparisonChart(data) {
            const ctx = document.getElementById('distributionChart');
            if (!ctx) return;

            // Destroy existing chart
            const existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }

            // Extract data for each platform
            const balaiDataset = data.datasets.find(d => d.label.toLowerCase() === 'balai');
            const regulerDataset = data.datasets.find(d => d.label.toLowerCase() === 'reguler');
            const fgDataset = data.datasets.find(d => d.label.toLowerCase() === 'fg');

            const now = new Date();
            const currentMonthName = now.toLocaleString('id-ID', { month: 'long' });
            const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1);
            const lastMonthName = lastMonth.toLocaleString('id-ID', { month: 'long' });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Balai', 'Reguler', 'FG'],
                    datasets: [
                        {
                            label: lastMonthName,
                            data: [
                                balaiDataset ? balaiDataset.data[0] : 0,
                                regulerDataset ? regulerDataset.data[0] : 0,
                                fgDataset ? fgDataset.data[0] : 0
                            ],
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 2,
                            borderRadius: 8
                        },
                        {
                            label: currentMonthName,
                            data: [
                                balaiDataset ? balaiDataset.data[1] : 0,
                                regulerDataset ? regulerDataset.data[1] : 0,
                                fgDataset ? fgDataset.data[1] : 0
                            ],
                            backgroundColor: 'rgba(16, 185, 129, 0.8)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 2,
                            borderRadius: 8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const platform = context.label;
                                    const month = context.dataset.label;
                                    const value = context.raw.toLocaleString('id-ID');
                                    return `${month} - ${platform}: ${value} records`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Records'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000) return (value/1000).toFixed(1) + 'K';
                                    return value.toLocaleString('id-ID');
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Database Platforms'
                            }
                        }
                    }
                }
            });
        }

        function createProvinceComparisonChart(data, year) {
            const ctx = document.getElementById('provinceChart');
            if (!ctx) return;

            // Destroy existing chart
            const existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }

            const top5 = data.slice(0, 5);
            const labels = top5.map((p, i) => `#${i + 1} ${p.provinsi}`);
            const values = top5.map(p => p.total);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: `Total Records - ${year}`,
                        data: values,
                        backgroundColor: [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(240, 147, 251, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(254, 225, 64, 0.8)',
                            'rgba(250, 112, 154, 0.8)'
                        ],
                        borderRadius: 8,
                        borderWidth: 0
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
                                    return `Total: ${context.raw.toLocaleString('id-ID')} records (${year})`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            title: {
                                display: true,
                                text: 'Total Records'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    </script>
</x-app-layout>