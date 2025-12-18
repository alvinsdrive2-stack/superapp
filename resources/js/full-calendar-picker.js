// Full Featured Calendar Date Picker
class FullCalendarPicker {
    constructor() {
        this.currentDate = new Date();
        this.selectedDate = null;
        this.startDate = null;
        this.endDate = null;
        this.mode = 'range'; // range, single, phase
        this.isOpen = false;
        this.isSelectingRange = false;

        // Month names in Indonesian
        this.monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        this.init();
    }

    init() {
        this.renderCalendar();
        this.setMode('range');

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#datePickerContainer') && this.isOpen) {
                this.close();
            }
        });
    }

    setMode(mode) {
        this.mode = mode;

        // Update button styles
        document.querySelectorAll('[id$="ModeBtn"]').forEach(btn => {
            btn.classList.remove('bg-white/30');
            btn.classList.add('bg-white/20');
        });

        const activeBtn = document.getElementById(mode + 'ModeBtn');
        if (activeBtn) {
            activeBtn.classList.remove('bg-white/20');
            activeBtn.classList.add('bg-white/30');
        }

        // Update UI based on mode
        const rangeDisplay = document.getElementById('dateRangeDisplay');
        if (mode === 'range') {
            rangeDisplay.classList.remove('hidden');
        } else {
            rangeDisplay.classList.add('hidden');
        }

        this.updateSelectionInfo();
    }

    renderCalendar() {
        this.updateHeader();
        this.renderDays();
    }

    updateHeader() {
        const header = document.getElementById('currentMonthYear');
        if (header) {
            header.textContent = `${this.monthNames[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;
        }
    }

    renderDays() {
        const daysContainer = document.getElementById('calendarDays');
        if (!daysContainer) return;

        daysContainer.innerHTML = '';

        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        // Get first day of month (0 = Sunday)
        const firstDay = new Date(year, month, 1).getDay();

        // Get number of days in month
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Get previous month's last days for padding
        const prevMonth = new Date(year, month, 0);
        const daysInPrevMonth = prevMonth.getDate();

        // Render previous month's trailing days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            const dayEl = this.createDayElement(day, 'prev', prevMonth.getFullYear(), prevMonth.getMonth());
            daysContainer.appendChild(dayEl);
        }

        // Render current month's days
        for (let day = 1; day <= daysInMonth; day++) {
            const dayEl = this.createDayElement(day, 'current', year, month);
            daysContainer.appendChild(dayEl);
        }

        // Render next month's leading days
        const totalCells = daysContainer.children.length;
        const remainingCells = 42 - totalCells; // 6 rows x 7 days
        for (let day = 1; day <= remainingCells; day++) {
            const nextMonth = new Date(year, month + 1, 1);
            const dayEl = this.createDayElement(day, 'next', nextMonth.getFullYear(), nextMonth.getMonth());
            daysContainer.appendChild(dayEl);
        }
    }

    createDayElement(day, type, year, month) {
        const dayEl = document.createElement('button');
        dayEl.className = 'p-2 text-sm rounded-lg transition-all hover:bg-blue-50 relative';
        dayEl.textContent = day;

        const date = new Date(year, month, day);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        date.setHours(0, 0, 0, 0);

        // Style based on type
        if (type === 'prev' || type === 'next') {
            dayEl.classList.add('text-gray-400');
        } else {
            dayEl.classList.add('text-gray-700');
        }

        // Highlight today
        if (date.getTime() === today.getTime()) {
            dayEl.classList.add('bg-blue-100', 'text-blue-700', 'font-bold');
            dayEl.innerHTML = `<span class="absolute top-0.5 right-0.5 w-1.5 h-1.5 bg-blue-500 rounded-full"></span>${day}`;
        }

        // Highlight selected dates
        if (this.startDate && date.getTime() === this.startDate.getTime()) {
            dayEl.classList.add('bg-blue-500', 'text-white');
        }

        if (this.endDate && date.getTime() === this.endDate.getTime()) {
            dayEl.classList.add('bg-blue-500', 'text-white');
        }

        // Highlight range
        if (this.startDate && this.endDate && date > this.startDate && date < this.endDate) {
            dayEl.classList.add('bg-blue-100');
        }

        // Add click handler
        dayEl.addEventListener('click', () => this.handleDayClick(date));

        return dayEl;
    }

    handleDayClick(date) {
        if (this.mode === 'single') {
            this.selectedDate = date;
            this.startDate = null;
            this.endDate = null;
        } else if (this.mode === 'range') {
            if (!this.startDate || (this.startDate && this.endDate)) {
                // Start new range
                this.startDate = date;
                this.endDate = null;
                this.isSelectingRange = true;
            } else {
                // Complete range
                if (date < this.startDate) {
                    this.endDate = this.startDate;
                    this.startDate = date;
                } else {
                    this.endDate = date;
                }
                this.isSelectingRange = false;
            }
        }

        this.renderCalendar();
        this.updateSelectionInfo();
    }

    updateSelectionInfo() {
        const infoEl = document.getElementById('selectionInfo');
        const startDisplay = document.getElementById('startDateDisplay');
        const endDisplay = document.getElementById('endDateDisplay');
        const rangeDisplay = document.getElementById('dateRangeDisplay');

        if (!infoEl) return;

        if (this.mode === 'single' && this.selectedDate) {
            infoEl.innerHTML = `<i class="fas fa-calendar-day mr-1"></i> Dipilih: ${this.formatDate(this.selectedDate)}`;
        } else if (this.mode === 'range' && this.startDate) {
            if (this.endDate) {
                const days = Math.ceil((this.endDate - this.startDate) / (1000 * 60 * 60 * 24)) + 1;
                infoEl.innerHTML = `<i class="fas fa-calendar-week mr-1"></i> Dipilih: ${days} hari`;
                if (startDisplay) startDisplay.textContent = this.formatDate(this.startDate);
                if (endDisplay) endDisplay.textContent = this.formatDate(this.endDate);
                rangeDisplay.classList.remove('hidden');
            } else {
                infoEl.innerHTML = `<i class="fas fa-arrow-right mr-1"></i> Pilih tanggal akhir`;
            }
        } else {
            infoEl.innerHTML = `<i class="fas fa-info-circle mr-1"></i> Pilih tanggal untuk melihat data`;
        }
    }

    formatDate(date) {
        return date.toLocaleString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    formatDateShort(date) {
        return date.toLocaleString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    previousMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() - 1);
        this.renderCalendar();
    }

    nextMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() + 1);
        this.renderCalendar();
    }

    goToToday() {
        this.currentDate = new Date();
        this.renderCalendar();
    }

    clearSelection() {
        this.selectedDate = null;
        this.startDate = null;
        this.endDate = null;
        this.isSelectingRange = false;
        this.renderCalendar();
        this.updateSelectionInfo();
    }

    selectPreset(preset) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        switch(preset) {
            case 'today':
                this.startDate = today;
                this.endDate = today;
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                this.startDate = yesterday;
                this.endDate = yesterday;
                break;
            case 'thisWeek':
                const weekStart = new Date(today);
                weekStart.setDate(today.getDate() - today.getDay());
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekStart.getDate() + 6);
                this.startDate = weekStart;
                this.endDate = weekEnd;
                break;
            case 'lastWeek':
                const lastWeekStart = new Date(today);
                lastWeekStart.setDate(today.getDate() - today.getDay() - 7);
                const lastWeekEnd = new Date(lastWeekStart);
                lastWeekEnd.setDate(lastWeekStart.getDate() + 6);
                this.startDate = lastWeekStart;
                this.endDate = lastWeekEnd;
                break;
            case 'thisMonth':
                this.startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                this.endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                break;
            case 'lastMonth':
                this.startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                this.endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            case 'last30Days':
                this.endDate = today;
                this.startDate = new Date(today);
                this.startDate.setDate(today.getDate() - 29);
                break;
            case 'last90Days':
                this.endDate = today;
                this.startDate = new Date(today);
                this.startDate.setDate(today.getDate() - 89);
                break;
        }

        this.setMode('range');
        this.renderCalendar();
        this.updateSelectionInfo();
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
                const panelWidth = 420; // w-[420px]
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

    apply() {
        const compareCheckbox = document.getElementById('compareWithLastYear');
        const compareWithLastYear = compareCheckbox ? compareCheckbox.checked : false;

        // Update display text
        const textEl = document.getElementById('selectedPeriodText');
        let periodText = '';

        if (this.mode === 'single' && this.selectedDate) {
            periodText = this.formatDateShort(this.selectedDate);
        } else if (this.mode === 'range' && this.startDate && this.endDate) {
            if (this.startDate.getTime() === this.endDate.getTime()) {
                periodText = this.formatDateShort(this.startDate);
            } else {
                periodText = `${this.formatDateShort(this.startDate)} - ${this.formatDateShort(this.endDate)}`;
            }
        } else if (this.mode === 'phase') {
            const currentMonth = new Date().getMonth();
            const currentYear = new Date().getFullYear();
            periodText = `Fase ${currentMonth < 6 ? '1' : '2'} (${currentYear})`;
        }

        if (textEl) {
            textEl.textContent = periodText || 'Pilih Periode';
        }

        // Show/hide comparison badge
        const badge = document.getElementById('comparisonBadge');
        if (compareWithLastYear && (this.selectedDate || (this.startDate && this.endDate))) {
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        // Trigger data load
        this.loadData(compareWithLastYear);

        // Close picker
        this.close();
    }

    loadData(compareWithLastYear) {
        let params = {};

        if (this.mode === 'single' && this.selectedDate) {
            params.date = this.formatDateForAPI(this.selectedDate);
        } else if (this.mode === 'range' && this.startDate && this.endDate) {
            params.start_date = this.formatDateForAPI(this.startDate);
            params.end_date = this.formatDateForAPI(this.endDate);
        }

        if (compareWithLastYear) {
            params.compare = 'true';
        }

        // Trigger the load function from dashboard-charts
        if (window.dashboardCharts) {
            window.dashboardCharts.loadWithCustomParams(params);
        }
    }

    formatDateForAPI(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
}

// Initialize full calendar picker
let fullCalendarPicker;

document.addEventListener('DOMContentLoaded', () => {
    fullCalendarPicker = new FullCalendarPicker();
});

// Global functions for onclick handlers
window.toggleDatePicker = () => fullCalendarPicker.toggle();
window.closeDatePicker = () => fullCalendarPicker.close();
window.applyDateSelection = () => fullCalendarPicker.apply();
window.setPickerMode = (mode) => fullCalendarPicker.setMode(mode);
window.previousMonth = () => fullCalendarPicker.previousMonth();
window.nextMonth = () => fullCalendarPicker.nextMonth();
window.goToToday = () => fullCalendarPicker.goToToday();
window.clearSelection = () => fullCalendarPicker.clearSelection();
window.selectPreset = (preset) => fullCalendarPicker.selectPreset(preset);