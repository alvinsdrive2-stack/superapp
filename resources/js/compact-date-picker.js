// Compact Date Picker - Simple & Functional
class CompactDatePicker {
    constructor() {
        this.currentYear = new Date().getFullYear();
        this.selectedMonth = null;
        this.selectedYear = null;
        this.compareWithLastYear = false;
        this.isOpen = false;

        this.monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        this.init();
    }

    init() {
        // Auto-select today - default to "Bulan Ini"
        const today = new Date();
        this.selectedMonth = today.getMonth();
        this.selectedYear = today.getFullYear();
        this.mode = 'month'; // 'current' for current period, 'month' for current month only (default)

        this.renderOptions();
        this.updateDisplay();
        this.loadData(); // Auto-load data on init

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#datePickerContainer') && this.isOpen) {
                this.close();
            }
        });
    }

    renderOptions() {
        const optionsContainer = document.getElementById('monthsGrid');
        if (!optionsContainer) return;

        optionsContainer.innerHTML = '';

        // Option 1: Periode ini (current phase)
        const currentPeriodBtn = document.createElement('button');
        currentPeriodBtn.className = 'w-full px-4 py-3 text-left border rounded-lg transition-all hover:bg-blue-50 hover:border-blue-300 mb-2';
        currentPeriodBtn.innerHTML = `
            <div class="font-semibold text-blue-700">Periode Saat Ini</div>
            <div class="text-xs text-gray-600">Bandingkan dengan periode sama tahun lalu</div>
        `;
        currentPeriodBtn.addEventListener('click', () => {
            this.selectPeriod('current');
        });
        optionsContainer.appendChild(currentPeriodBtn);

        // Option 2: Bulan ini (current month only)
        const currentMonthBtn = document.createElement('button');
        currentMonthBtn.className = 'w-full px-4 py-3 text-left border rounded-lg transition-all hover:bg-blue-50 hover:border-blue-300';
        currentMonthBtn.innerHTML = `
            <div class="font-semibold text-blue-700">Bulan Ini</div>
            <div class="text-xs text-gray-600">Bandingkan dengan bulan lalu</div>
        `;
        currentMonthBtn.addEventListener('click', () => {
            this.selectPeriod('month');
        });
        optionsContainer.appendChild(currentMonthBtn);
    }

    selectPeriod(mode) {
        this.mode = mode;
        this.updateDisplay();
        this.loadData();
        this.close();
    }

  updateDisplay() {
        const header = document.getElementById('currentMonthYear');
        const textEl = document.getElementById('selectedPeriodText');

        if (this.mode === 'current') {
            const today = new Date();
            const currentMonth = today.getMonth();
            if (currentMonth >= 1 && currentMonth <= 6) {
                if (header) header.textContent = `Fase 1 ${today.getFullYear()}`;
                if (textEl) textEl.textContent = 'Fase 1 (Jan-Jun)';
            } else {
                if (header) header.textContent = `Fase 2 ${today.getFullYear()}`;
                if (textEl) textEl.textContent = 'Fase 2 (Jul-Des)';
            }
        } else {
            const today = new Date();
            const monthName = today.toLocaleString('id-ID', { month: 'long', year: 'numeric' });
            if (header) header.textContent = monthName;
            if (textEl) textEl.textContent = monthName;
        }

        // Always show comparison badge
        const badge = document.getElementById('comparisonBadge');
        if (badge) {
            badge.classList.remove('hidden');
            badge.textContent = this.mode === 'current' ? 'vs tahun lalu' : 'vs bulan lalu';
        }
    }

    selectMonth(monthIndex, year) {
        // Remove previous selection
        document.querySelectorAll('#monthsGrid button').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
        });

        // Add selection
        const selectedButton = document.querySelector(`#monthsGrid button[data-month="${monthIndex}"][data-year="${year}"]`);
        if (selectedButton) {
            selectedButton.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
        }

        this.selectedMonth = monthIndex;
        this.selectedYear = year;

        // Update year display if needed
        if (year !== this.currentYear) {
            this.currentYear = year;
            this.updateHeader();
        }
    }

    selectQuickOption(type) {
        const today = new Date();

        switch(type) {
            case 'currentPhase':
                const currentMonth = today.getMonth();
                this.selectedMonth = currentMonth >= 6 ? 6 : 0; // Start of phase
                this.selectedYear = today.getFullYear();
                break;

            case 'currentMonth':
                this.selectedMonth = today.getMonth();
                this.selectedYear = today.getFullYear();
                break;

            case 'last3Months':
                this.selectedMonth = Math.max(0, today.getMonth() - 2);
                this.selectedYear = today.getFullYear();
                break;

            case 'ytd':
                this.selectedMonth = 0; // January
                this.selectedYear = today.getFullYear();
                break;
        }

        // Update display
        this.highlightSelectedMonth();

        // Auto apply after quick select
        setTimeout(() => this.apply(), 200);
    }

    highlightSelectedMonth() {
        // Clear all selections
        document.querySelectorAll('#monthsGrid button').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
        });

        // Add selection
        if (this.selectedMonth !== null && this.selectedYear !== null) {
            const selectedButton = document.querySelector(`#monthsGrid button[data-month="${this.selectedMonth}"][data-year="${this.selectedYear}"]`);
            if (selectedButton) {
                selectedButton.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
            }
        }

        // Update year if needed
        if (this.selectedYear && this.selectedYear !== this.currentYear) {
            this.currentYear = this.selectedYear;
            this.updateHeader();
        }
    }

    changeYear(direction) {
        const display = document.getElementById('currentMonthYear');
        if (display) {
            let year = parseInt(display.textContent);
            year += direction;
            display.textContent = year;
            this.currentYear = year;
        }

        // Re-render months with new year
        this.renderMonths();

        // Re-select if we have a selection
        if (this.selectedMonth !== null) {
            this.highlightSelectedMonth();
        }
    }

    apply() {
        const compareCheckbox = document.getElementById('compareWithLastYear');
        this.compareWithLastYear = compareCheckbox ? compareCheckbox.checked : false;

        // Get display text
        let periodText = '';
        if (this.selectedMonth !== null && this.selectedYear !== null) {
            periodText = `${this.monthNames[this.selectedMonth]} ${this.selectedYear}`;
        } else {
            periodText = 'Fase Saat Ini';
        }

        // Update display
        const textEl = document.getElementById('selectedPeriodText');
        if (textEl) {
            textEl.textContent = periodText;
        }

        // Show/hide comparison badge
        const badge = document.getElementById('comparisonBadge');
        if (this.compareWithLastYear) {
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        // Load data
        this.loadData();

        // Close picker
        this.close();
    }

    loadData() {
        let params = {};
        const today = new Date();

        if (this.mode === 'current') {
            // Current phase - compare with same phase last year
            const currentMonth = today.getMonth();
            if (currentMonth >= 1 && currentMonth <= 6) {
                params.period = 'phase1';
                params.year = today.getFullYear();
            } else {
                params.period = 'phase2';
                params.year = today.getFullYear();
            }
            params.compare_type = 'year';
        } else {
            // Current month - compare with last month
            params.month = today.toLocaleString('id-ID', { month: 'long', year: 'numeric' });
            params.compare_type = 'month';
        }

        params.compare = 'true';

        // Trigger data load
        if (window.dashboardCharts) {
            window.dashboardCharts.loadWithCustomParams(params);
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        const panel = document.getElementById('datePickerPanel');
        const icon = document.getElementById('dropdownIcon');
        const container = document.getElementById('datePickerContainer');

        if (panel) {
            panel.classList.remove('hidden');
            panel.classList.add('animate-fade-in');
            icon.classList.add('rotate-180');

            // Check position
            if (container) {
                const rect = container.getBoundingClientRect();
                const panelWidth = 320; // w-80 = 20rem = 320px
                const spaceOnRight = window.innerWidth - rect.right;

                if (spaceOnRight < panelWidth) {
                    panel.style.right = 'auto';
                    panel.style.left = '0';
                } else {
                    panel.style.left = 'auto';
                    panel.style.right = '0';
                }
            }

            this.isOpen = true;
        }
    }

    close() {
        const panel = document.getElementById('datePickerPanel');
        const icon = document.getElementById('dropdownIcon');

        if (panel) {
            panel.classList.add('hidden');
            panel.classList.remove('animate-fade-in');
            icon.classList.remove('rotate-180');
            this.isOpen = false;
        }
    }
}

// Initialize compact date picker
let compactDatePicker;

document.addEventListener('DOMContentLoaded', () => {
    compactDatePicker = new CompactDatePicker();
});

// Global functions
window.toggleDatePicker = () => compactDatePicker.toggle();
window.closeDatePicker = () => compactDatePicker.close();
window.applySelection = () => compactDatePicker.apply();
window.selectQuickOption = (type) => compactDatePicker.selectQuickOption(type);
window.changeMonth = (dir) => compactDatePicker.changeYear(dir);