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

        .filter-section {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .filter-item label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
        }

        .filter-item select,
        .filter-item input {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            transition: all 0.2s;
        }

        .filter-item select:focus,
        .filter-item input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
            background: #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch input:checked + .toggle-slider {
            transform: translateX(24px);
            background: #6366f1;
        }

        .toggle-switch input:checked ~ .toggle-switch {
            background: #6366f1;
        }

        .skeleton {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .performance-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(34, 197, 94, 0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cache-indicator {
            background: rgba(59, 130, 246, 0.9);
        }

        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .chart-container {
            position: relative;
            height: 400px;
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 1rem;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
    </style>

    <div class="dashboard-container py-6">
        <!-- Performance Indicator -->
        <div id="performanceIndicator" class="performance-indicator" style="display: none;">
            <i class="fas fa-bolt"></i>
            <span id="performanceText">Loading...</span>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Filter Dashboard</h3>
                <div class="flex items-center gap-2 text-sm">
                    <span id="cacheStatus" class="cache-indicator px-2 py-1 rounded-full text-white" style="display: none;">
                        <i class="fas fa-database mr-1"></i>Cached
                    </span>
                    <button onclick="window.optimizedDashboard.dataService.clearCache(); window.optimizedDashboard.loadDashboardData();"
                            class="text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-sync-alt mr-1"></i>Refresh Cache
                    </button>
                </div>
            </div>

            <div class="filter-group">
                <!-- Period Filter -->
                <div class="filter-item">
                    <label for="periodSelect">Periode</label>
                    <select id="periodSelect" class="w-40">
                        <option value="3_months">3 Bulan</option>
                        <option value="6_months" selected>6 Bulan</option>
                        <option value="12_months">12 Bulan</option>
                    </select>
                </div>

                <!-- Group By -->
                <div class="filter-item">
                    <label for="groupBySelect">Kelompokkan</label>
                    <select id="groupBySelect" class="w-32">
                        <option value="month" selected>Bulan</option>
                        <option value="quarter">Kuartal</option>
                    </select>
                </div>

                <!-- Max Provinces -->
                <div class="filter-item">
                    <label for="maxProvincesSelect">Jumlah Provinsi</label>
                    <select id="maxProvincesSelect" class="w-32">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                    </select>
                </div>

                <!-- Region Filter -->
                <div class="filter-item">
                    <label for="regionFilterSelect">Filter Regional</label>
                    <select id="regionFilterSelect" class="w-40">
                        <option value="">Semua Regional</option>
                        <option value="Jawa">Jawa</option>
                        <option value="Sumatera">Sumatera</option>
                        <option value="Kalimantan">Kalimantan</option>
                        <option value="Sulawesi">Sulawesi</option>
                        <option value="Bali">Bali & Nusa Tenggara</option>
                        <option value="Maluku">Maluku</option>
                        <option value="Papua">Papua</option>
                    </select>
                </div>

                <!-- Comparison Toggle -->
                <div class="filter-item">
                    <label class="flex items-center gap-2">
                        <span>Perbandingan</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="comparisonToggle">
                            <span class="toggle-slider"></span>
                        </label>
                    </label>
                </div>

                <!-- Comparison Type (shown when comparison is enabled) -->
                <div class="filter-item" id="comparisonTypeContainer" style="display: none;">
                    <label for="comparisonTypeSelect">Tipe Perbandingan</label>
                    <select id="comparisonTypeSelect" class="w-40">
                        <option value="previous_period" selected>Periode Sebelumnya</option>
                        <option value="year_over_year">Year over Year</option>
                    </select>
                </div>

                <!-- Custom Date Range -->
                <div class="filter-item">
                    <label class="flex items-center gap-2">
                        <span>Range Tanggal</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="customDateToggle">
                            <span class="toggle-slider"></span>
                        </label>
                    </label>
                </div>

                <!-- Date Pickers (shown when custom date is enabled) -->
                <div class="filter-item" id="customDateContainer" style="display: none;">
                    <div class="flex gap-2">
                        <div>
                            <label for="startDateInput" class="text-xs">Dari</label>
                            <input type="date" id="startDateInput" class="w-36" disabled>
                        </div>
                        <div>
                            <label for="endDateInput" class="text-xs">Sampai</label>
                            <input type="date" id="endDateInput" class="w-36" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- KPI Grid - Year-over-Year Comparison -->
            <div class="kpi-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Records -->
                <div class="glass-card metric-card fade-in">
                    <div class="metric-icon" style="background: var(--primary-gradient);">
                        <i class="fas fa-database text-white"></i>
                    </div>
                    <div class="metric-value" data-metric="total_pencatatan">-</div>
                    <div class="text-gray-600 font-medium">Total Records</div>
                    <div class="mt-2 text-xs text-gray-500">
                        <span class="previous-year">-</span> → <span class="current-year">-</span>
                    </div>
                    <div class="mt-2 flex items-center text-sm text-green-600">
                        <i class="fas fa-arrow-up mr-1"></i>
                        <span class="change-percent">Loading...</span>
                    </div>
                </div>

                <!-- Balai System -->
                <div class="glass-card metric-card fade-in">
                    <div class="metric-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-building text-white"></i>
                    </div>
                    <div class="metric-value" data-metric="balai">-</div>
                    <div class="text-gray-600 font-medium">Balai System</div>
                    <div class="mt-2 text-xs text-gray-500">
                        <span class="previous-year">-</span> → <span class="current-year">-</span>
                    </div>
                    <div class="mt-2 flex items-center text-sm text-blue-600">
                        <i class="fas fa-circle mr-1"></i>
                        <span class="change-percent">Loading...</span>
                    </div>
                </div>

                <!-- Reguler System -->
                <div class="glass-card metric-card fade-in">
                    <div class="metric-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div class="metric-value" data-metric="reguler">-</div>
                    <div class="text-gray-600 font-medium">Reguler System</div>
                    <div class="mt-2 text-xs text-gray-500">
                        <span class="previous-year">-</span> → <span class="current-year">-</span>
                    </div>
                    <div class="mt-2 flex items-center text-sm text-green-600">
                        <i class="fas fa-circle mr-1"></i>
                        <span class="change-percent">Loading...</span>
                    </div>
                </div>

                <!-- FG System -->
                <div class="glass-card metric-card fade-in">
                    <div class="metric-icon" style="background: var(--secondary-gradient);">
                        <i class="fas fa-star text-white"></i>
                    </div>
                    <div class="metric-value" data-metric="fg">-</div>
                    <div class="text-gray-600 font-medium">FG System</div>
                    <div class="mt-2 text-xs text-gray-500">
                        <span class="previous-year">-</span> → <span class="current-year">-</span>
                    </div>
                    <div class="mt-2 flex items-center text-sm text-green-600">
                        <i class="fas fa-circle mr-1"></i>
                        <span class="change-percent">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Time Series Chart -->
            <div class="chart-container mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Trend Pencatatan Izin</h3>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        <span id="currentTime">-</span>
                    </div>
                </div>
                <div class="relative">
                    <div id="mainChartSkeleton" class="skeleton absolute inset-0 rounded-lg"></div>
                    <canvas id="pencatatanChart" style="display: none;"></canvas>
                </div>
            </div>

            <!-- Province Charts and Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Province Bar Chart -->
                <div class="chart-container">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Provinsi Teratas</h3>
                        <button onclick="window.toggleProvinceChartType()"
                                class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="fas fa-exchange-alt mr-1"></i>
                            <span id="chartTypeText">Vertical</span>
                        </button>
                    </div>
                    <div class="relative" style="height: 350px;">
                        <div id="provinceChartSkeleton" class="skeleton absolute inset-0 rounded-lg"></div>
                        <canvas id="provinceBarChart" style="display: none;"></canvas>
                    </div>
                </div>

                <!-- Progress Bars -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Top 3 Provinsi</h3>
                        <button onclick="window.refreshProvinceStats()"
                                class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="fas fa-sync-alt mr-1" id="refreshIcon"></i>Refresh
                        </button>
                    </div>
                    <div id="progressSkeleton" class="skeleton h-64 rounded-lg mb-4"></div>
                    <div id="topProvincesProgress" class="space-y-4" style="display: none;">
                        <!-- Progress bars will be inserted here -->
                    </div>
                </div>
            </div>

            <!-- Province Cards -->
            <div class="mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistik Provinsi</h3>
                <div id="provinceStatsSkeleton" class="skeleton h-96 rounded-lg"></div>
                <div id="provinceStatsContainer" class="hidden">
                    <!-- Province cards will be inserted here -->
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
        }

        // Performance monitoring
        let startTime = performance.now();

        // Show performance indicator
        function showPerformanceIndicator(text, type = 'success') {
            const indicator = document.getElementById('performanceIndicator');
            const textEl = document.getElementById('performanceText');

            indicator.style.display = 'flex';
            indicator.style.background = type === 'success'
                ? 'rgba(34, 197, 94, 0.9)'
                : 'rgba(59, 130, 246, 0.9)';
            textEl.textContent = text;

            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }

        // Show cache status
        function showCacheStatus(isCached) {
            const cacheStatus = document.getElementById('cacheStatus');
            if (isCached) {
                cacheStatus.style.display = 'inline-flex';
            } else {
                cacheStatus.style.display = 'none';
            }
        }

        // Load optimized dashboard
        window.addEventListener('load', async () => {
            try {
                const module = await import('/resources/js/optimized-dashboard.js');

                // Show loading time
                const loadTime = (performance.now() - startTime).toFixed(0);
                showPerformanceIndicator(`Dashboard loaded in ${loadTime}ms`, 'success');

            } catch (error) {
                console.error('Error loading optimized dashboard:', error);
                showPerformanceIndicator('Error loading dashboard', 'error');
            }
        });

        // Show comparison type when comparison is enabled
        document.getElementById('comparisonToggle')?.addEventListener('change', function(e) {
            const container = document.getElementById('comparisonTypeContainer');
            container.style.display = e.target.checked ? 'block' : 'none';
        });

        // Show custom date when enabled
        document.getElementById('customDateToggle')?.addEventListener('change', function(e) {
            const container = document.getElementById('customDateContainer');
            const startDate = document.getElementById('startDateInput');
            const endDate = document.getElementById('endDateInput');

            if (e.target.checked) {
                container.style.display = 'block';
                startDate.disabled = false;
                endDate.disabled = false;
            } else {
                container.style.display = 'none';
                startDate.disabled = true;
                endDate.disabled = true;
            }
        });
    </script>
</x-app-layout>