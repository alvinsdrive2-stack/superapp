// Custom Date Picker for Dashboard
class CustomDatePicker {
    constructor() {
        this.currentYear = new Date().getFullYear();
        this.selectedMonth = null;
        this.selectedYear = null;
        this.compareWithLastYear = false;
        this.isOpen = false;

        // Month names in Indonesian
        this.monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        this.init();
    }

    init() {
        // Create month grid
        this.createMonthGrid();

        // Set current year
        document.getElementById('yearDisplay').textContent = this.currentYear;

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#datePickerContainer') && this.isOpen) {
                this.close();
            }
        });
    }

    createMonthGrid() {
        const monthGrid = document.getElementById('monthGrid');
        if (!monthGrid) return;

        monthGrid.innerHTML = '';

        this.monthNames.forEach((month, index) => {
            const monthButton = document.createElement('button');
            monthButton.className = 'p-2 text-sm border rounded-lg transition-all hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700';
            monthButton.dataset.month = index;
            monthButton.textContent = month.substring(0, 3);
            monthButton.title = month;

            // Highlight current month
            const now = new Date();
            if (index === now.getMonth() && this.currentYear === now.getFullYear()) {
                monthButton.className += ' bg-blue-100 border-blue-500 text-blue-700 font-semibold';
            }

            monthButton.addEventListener('click', () => this.selectMonth(index));
            monthGrid.appendChild(monthButton);
        });
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

            // Check if dropdown would overflow on the right
            if (container) {
                const rect = container.getBoundingClientRect();
                const panelWidth = 384; // w-96 = 24rem = 384px
                const spaceOnRight = window.innerWidth - rect.right;

                if (spaceOnRight < panelWidth) {
                    // Position to the left if not enough space on right
                    panel.style.right = 'auto';
                    panel.style.left = '0';
                    panel.setAttribute('data-position', 'left');
                } else {
                    // Default position to the right
                    panel.style.left = 'auto';
                    panel.style.right = '0';
                    panel.setAttribute('data-position', 'right');
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
            icon.classList.remove('rotate-180');
            this.isOpen = false;
        }
    }

    selectMonth(monthIndex) {
        // Remove previous selection
        document.querySelectorAll('#monthGrid button').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
        });

        // Add selection to clicked month
        const selectedButton = document.querySelector(`#monthGrid button[data-month="${monthIndex}"]`);
        if (selectedButton) {
            selectedButton.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
        }

        this.selectedMonth = monthIndex;
    }

    selectQuickPeriod(type) {
        const now = new Date();

        switch(type) {
            case 'currentPhase':
                const currentMonth = now.getMonth();
                this.selectedYear = now.getFullYear();
                this.selectedMonth = currentMonth >= 6 ? 6 : 0; // Start of phase
                this.updatePeriodText(`Fase ${currentMonth >= 6 ? '2' : '1'} 2024`);
                break;

            case 'currentMonth':
                this.selectedYear = now.getFullYear();
                this.selectedMonth = now.getMonth();
                this.updatePeriodText(`${this.monthNames[this.selectedMonth]} ${this.selectedYear}`);
                break;

            case 'last3Months':
                this.selectedYear = now.getFullYear();
                this.selectedMonth = Math.max(0, now.getMonth() - 2); // 3 months ago
                this.updatePeriodText('3 Bulan Terakhir');
                break;

            case 'ytd':
                this.selectedYear = now.getFullYear();
                this.selectedMonth = 0; // January
                this.updatePeriodText('Year to Date');
                break;
        }

        // Update year display
        document.getElementById('yearDisplay').textContent = this.selectedYear || now.getFullYear();

        // Update month grid
        this.highlightSelectedMonth();

        // Auto apply after quick select
        setTimeout(() => this.apply(), 300);
    }

    highlightSelectedMonth() {
        document.querySelectorAll('#monthGrid button').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
        });

        if (this.selectedMonth !== null) {
            const selectedButton = document.querySelector(`#monthGrid button[data-month="${this.selectedMonth}"]`);
            if (selectedButton) {
                selectedButton.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
            }
        }
    }

    changeYear(direction) {
        const display = document.getElementById('yearDisplay');
        let year = parseInt(display.textContent);
        year += direction;
        display.textContent = year;
        this.selectedYear = year;

        // Recreate month grid to highlight current month correctly
        this.createMonthGrid();

        // Re-select if we have a selected month
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
        this.updatePeriodText(periodText);

        // Show/hide comparison badge
        const badge = document.getElementById('comparisonBadge');
        if (this.compareWithLastYear) {
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        // Trigger data refresh
        this.triggerDataLoad();

        // Close picker
        this.close();
    }

    updatePeriodText(text) {
        const textElement = document.getElementById('selectedPeriodText');
        if (textElement) {
            textElement.textContent = text;
        }
    }

    triggerDataLoad() {
        // Convert selected period to format for API
        let apiParams = {};

        if (this.selectedMonth !== null && this.selectedYear !== null) {
            // Specific month
            const monthDate = new Date(this.selectedYear, this.selectedMonth, 1);
            apiParams.month = monthDate.toLocaleString('id-ID', { month: 'long', year: 'numeric' });
        } else {
            // Phase data
            apiParams.phase = 'current';
        }

        // Add comparison flag
        if (this.compareWithLastYear) {
            apiParams.compare = true;
        }

        // Trigger the load function from dashboard-charts
        if (window.dashboardCharts) {
            window.dashboardCharts.loadWithCustomParams(apiParams);
        }
    }
}

// Initialize custom date picker
let customDatePicker;

document.addEventListener('DOMContentLoaded', () => {
    customDatePicker = new CustomDatePicker();
});

// Global functions for onclick handlers
window.toggleDatePicker = () => customDatePicker.toggle();
window.closeDatePicker = () => customDatePicker.close();
window.applyDateSelection = () => customDatePicker.apply();
window.selectQuickPeriod = (type) => customDatePicker.selectQuickPeriod(type);
window.changeYear = (dir) => customDatePicker.changeYear(dir);