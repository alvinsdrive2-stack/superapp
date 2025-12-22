/**
 * Dashboard Data Service
 * Mengelola pengambilan data dashboard dengan caching dan client-side filtering
 */
class DashboardDataService {
    constructor() {
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 menit cache
        this.currentData = null;
        this.lastFetch = null;
        this.fetching = false;
        this.pendingCallbacks = [];
    }

    /**
     * Ambil semua data dashboard dengan satu request
     */
    async fetchAllData(forceRefresh = false) {
        // Check cache first
        if (!forceRefresh && this.isCacheValid()) {
            return this.currentData;
        }

        // If already fetching, wait for it
        if (this.fetching) {
            return new Promise((resolve) => {
                this.pendingCallbacks.push(resolve);
            });
        }

        this.fetching = true;

        try {
            const response = await fetch('/api/dashboard/complete', {
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

            if (!result.success) {
                throw new Error(result.message || 'Failed to fetch data');
            }

            // Cache the data
            this.currentData = result.data;
            this.lastFetch = Date.now();
            this.cache.set('dashboard', {
                data: result.data,
                timestamp: Date.now()
            });

            // Resolve all pending callbacks
            this.pendingCallbacks.forEach(callback => callback(result.data));
            this.pendingCallbacks = [];

            return result.data;

        } catch (error) {
            console.error('Error fetching dashboard data:', error);

            // Return cached data if available on error
            if (this.currentData) {
                return this.currentData;
            }

            throw error;
        } finally {
            this.fetching = false;
        }
    }

    /**
     * Cek apakah cache masih valid
     */
    isCacheValid() {
        const cached = this.cache.get('dashboard');
        if (!cached) return false;

        return (Date.now() - cached.timestamp) < this.cacheTimeout;
    }

    /**
     * Filter data time series berdasarkan parameter
     */
    filterTimeSeries(data, params = {}) {
        if (!data.time_series) return null;

        const {
            period_type = '6_months',
            start_date,
            end_date,
            group_by = 'month'
        } = params;

        let filtered = { ...data.time_series };

        // Filter berdasarkan periode
        if (period_type === '3_months') {
            // Ambil 3 bulan terakhir
            const cutoffIndex = Math.max(0, filtered.labels.length - 3);
            filtered.labels = filtered.labels.slice(cutoffIndex);
            filtered.datasets = filtered.datasets.map(dataset => ({
                ...dataset,
                data: dataset.data.slice(cutoffIndex)
            }));
        } else if (period_type === '12_months') {
            // Ambil 12 bulan terakhir
            const cutoffIndex = Math.max(0, filtered.labels.length - 12);
            filtered.labels = filtered.labels.slice(cutoffIndex);
            filtered.datasets = filtered.datasets.map(dataset => ({
                ...dataset,
                data: dataset.data.slice(cutoffIndex)
            }));
        }

        // Filter berdasarkan custom date range
        if (start_date && end_date && filtered.labels) {
            const startDate = new Date(start_date);
            const endDate = new Date(end_date);

            const filteredIndices = [];
            filtered.labels.forEach((label, index) => {
                // Assuming label format like "Jan 2025" or "January 2025"
                const labelDate = this.parseLabelDate(label);
                if (labelDate && labelDate >= startDate && labelDate <= endDate) {
                    filteredIndices.push(index);
                }
            });

            if (filteredIndices.length > 0) {
                filtered.labels = filteredIndices.map(i => filtered.labels[i]);
                filtered.datasets = filtered.datasets.map(dataset => ({
                    ...dataset,
                    data: filteredIndices.map(i => dataset.data[i])
                }));
            }
        }

        // Group by quarter jika diminta
        if (group_by === 'quarter') {
            filtered = this.groupDataByQuarter(filtered);
        }

        return filtered;
    }

    /**
     * Filter data provinsi berdasarkan parameter
     */
    filterProvinceData(data, params = {}) {
        if (!data.provinces) return null;

        const {
            max_provinces = 10,
            sort_by = 'total',
            order = 'desc',
            region_filter = null
        } = params;

        let provinces = [...data.provinces.all_provinces];

        // Filter berdasarkan region jika ada
        if (region_filter) {
            provinces = provinces.filter(province =>
                this.getProvinceRegion(province.name) === region_filter
            );
        }

        // Sorting
        provinces.sort((a, b) => {
            const valueA = this.getProvinceValue(a, sort_by);
            const valueB = this.getProvinceValue(b, sort_by);
            return order === 'desc' ? valueB - valueA : valueA - valueB;
        });

        // Limit
        const limitedProvinces = provinces.slice(0, max_provinces);

        // Prepare return data
        return {
            top_10: limitedProvinces,
            top_8_for_cards: limitedProvinces.slice(0, 8),
            top_3_for_progress: limitedProvinces.slice(0, 3),
            all_provinces: provinces
        };
    }

    /**
     * Get comparison data
     */
    getComparisonData(data, params = {}) {
        if (!data.time_series) return null;

        const { comparison_type = 'previous_period' } = params;

        // Implement comparison logic
        const comparisons = this.calculateComparisons(data.time_series, comparison_type);

        return comparisons;
    }

    /**
     * Parse label date
     */
    parseLabelDate(label) {
        // Handle various date formats
        const formats = [
            /(\w{3}) (\d{4})/, // Jan 2025
            /(\w+) (\d{4})/,   // January 2025
            /(\d{4})-(\d{2})/  // 2025-01
        ];

        for (const format of formats) {
            const match = label.match(format);
            if (match) {
                if (format === formats[2]) {
                    // YYYY-MM format
                    return new Date(match[1], match[2] - 1);
                } else {
                    // Month name format
                    const monthNames = {
                        'Jan': 0, 'Januari': 0,
                        'Feb': 1, 'Februari': 1,
                        'Mar': 2, 'Maret': 2,
                        'Apr': 3, 'April': 3,
                        'May': 4, 'Mei': 4,
                        'Jun': 5, 'Juni': 5,
                        'Jul': 6, 'Juli': 6,
                        'Aug': 7, 'Agustus': 7,
                        'Sep': 8, 'September': 8,
                        'Oct': 9, 'Oktober': 9,
                        'Nov': 10, 'November': 10,
                        'Dec': 11, 'Desember': 11
                    };
                    const month = monthNames[match[1]];
                    const year = parseInt(match[2]);
                    return new Date(year, month);
                }
            }
        }

        return null;
    }

    /**
     * Group data by quarter
     */
    groupDataByQuarter(timeSeries) {
        if (!timeSeries.labels || !timeSeries.datasets) return timeSeries;

        const quarters = [];
        const quarterLabels = [];

        // Group labels by quarter
        for (let i = 0; i < timeSeries.labels.length; i += 3) {
            const quarterLabelsGroup = timeSeries.labels.slice(i, i + 3);
            const quarterData = [];

            timeSeries.datasets.forEach(dataset => {
                const quarterSum = dataset.data.slice(i, i + 3).reduce((a, b) => a + b, 0);
                quarterData.push(quarterSum);
            });

            quarters.push(quarterData);

            // Create quarter label
            if (quarterLabelsGroup.length > 0) {
                const firstLabel = quarterLabelsGroup[0];
                const year = firstLabel.match(/\d{4}/)[0];
                const quarter = Math.floor(i / 3) + 1;
                quarterLabels.push(`Q${quarter} ${year}`);
            }
        }

        return {
            labels: quarterLabels,
            datasets: timeSeries.datasets.map((dataset, index) => ({
                ...dataset,
                data: quarters.map(q => q[index])
            }))
        };
    }

    /**
     * Get province region
     */
    getProvinceRegion(provinceName) {
        const regions = {
            'Jawa': ['DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'Banten', 'DI Yogyakarta'],
            'Sumatera': ['Sumatera Utara', 'Sumatera Barat', 'Sumatera Selatan', 'Riau', 'Kepulauan Riau', 'Jambi', 'Bengkulu', 'Lampung', 'Bangka Belitung'],
            'Kalimantan': ['Kalimantan Timur', 'Kalimantan Selatan', 'Kalimantan Tengah', 'Kalimantan Barat', 'Kalimantan Utara'],
            'Sulawesi': ['Sulawesi Selatan', 'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Tenggara', 'Sulawesi Barat', 'Gorontalo'],
            'Bali': ['Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur'],
            'Maluku': ['Maluku', 'Maluku Utara'],
            'Papua': ['Papua', 'Papua Barat', 'Papua Selatan', 'Papua Tengah', 'Papua Pegunungan']
        };

        for (const [region, provinces] of Object.entries(regions)) {
            if (provinces.includes(provinceName)) {
                return region;
            }
        }

        return 'Other';
    }

    /**
     * Get province value for sorting
     */
    getProvinceValue(province, sortBy) {
        switch (sortBy) {
            case 'total':
                return province.total;
            case 'trend':
                return province.trend || 0;
            case 'balai':
                return province.breakdown?.balai || 0;
            case 'reguler':
                return province.breakdown?.reguler || 0;
            case 'fg':
                return province.breakdown?.fg || 0;
            default:
                return province.total;
        }
    }

    /**
     * Calculate comparisons
     */
    calculateComparisons(timeSeries, comparisonType) {
        // Implementation for comparison calculations
        const comparisons = {};

        if (comparisonType === 'previous_period' && timeSeries.datasets) {
            timeSeries.datasets.forEach(dataset => {
                const data = dataset.data;
                if (data.length >= 2) {
                    const current = data[data.length - 1];
                    const previous = data[data.length - 2];
                    const change = previous > 0 ? ((current - previous) / previous * 100) : 0;

                    comparisons[dataset.label] = {
                        current: current,
                        previous: previous,
                        change: change,
                        change_amount: current - previous
                    };
                }
            });
        }

        return comparisons;
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
        this.currentData = null;
        this.lastFetch = null;
    }

    /**
     * Preload data untuk optimasi
     */
    async preloadData() {
        // Load data in background if not cached
        if (!this.isCacheValid()) {
            this.fetchAllData().catch(err => {
                console.log('Background preload failed:', err);
            });
        }
    }
}

// Singleton instance
const dashboardDataService = new DashboardDataService();

// Export untuk digunakan oleh modul lain
if (typeof module !== 'undefined' && module.exports) {
    module.exports = dashboardDataService;
} else {
    window.dashboardDataService = dashboardDataService;
}