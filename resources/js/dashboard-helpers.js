// Additional JavaScript functions for dashboard
// This file contains helper functions needed by the dashboard

// Toggle comparison visibility
document.addEventListener('DOMContentLoaded', function() {
    const comparisonToggle = document.getElementById('comparisonToggle');
    const comparisonTypeContainer = document.getElementById('comparisonTypeContainer');
    const customDateToggle = document.getElementById('customDateToggle');
    const periodSelect = document.getElementById('periodSelect');
    const startDateInput = document.getElementById('startDateInput');
    const endDateInput = document.getElementById('endDateInput');

    // Handle comparison toggle
    if (comparisonToggle && comparisonTypeContainer) {
        comparisonToggle.addEventListener('change', function() {
            if (this.checked) {
                comparisonTypeContainer.classList.remove('hidden');
            } else {
                comparisonTypeContainer.classList.add('hidden');
            }
        });
    }

    // Handle custom date toggle
    if (customDateToggle && periodSelect) {
        customDateToggle.addEventListener('change', function() {
            if (this.checked) {
                periodSelect.disabled = true;
                periodSelect.classList.add('bg-gray-100', 'cursor-not-allowed');
            } else {
                periodSelect.disabled = false;
                periodSelect.classList.remove('bg-gray-100', 'cursor-not-allowed');
            }
        });
    }

    // Set default dates for custom range
    if (startDateInput && endDateInput) {
        const today = new Date();
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());

        endDateInput.value = today.toISOString().split('T')[0];
        startDateInput.value = lastMonth.toISOString().split('T')[0];
    }

    // Alert helper function
    window.showAlert = function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;

        const container = document.querySelector('.dashboard-content');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv && alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    }
});

// System access functions (keeping original functionality)
let selectedSystem = null;
let selectedAccount = null;

function accessSystem(system) {
    selectedSystem = system;
    selectedAccount = null;

    // Check for multiple accounts via AJAX
    fetch(`/redirect-to-system?system=${system}&ajax=1`, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'multiple_matches') {
                showAccountSelectionModal(data);
            } else if (data.redirect_url) {
                showFloatingLoading(getSystemName(selectedSystem), data.redirect_url);
            } else {
                showAlert('Terjadi kesalahan: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Gagal menghubungkan ke sistem. Silakan coba lagi.', 'error');
        });
}

function getSystemName(system) {
    const systems = {
        'balai': 'Sistem Balai',
        'reguler': 'Sistem Reguler',
        'suisei': 'Sistem FG/Suisei',
        'tuk': 'Sistem TUK'
    };
    return systems[system] || system;
}

function showAccountSelectionModal(data) {
    document.getElementById('accountModalMessage').innerHTML = `
        <p><strong>${data.count}</strong> akun dengan nama mirip ditemukan di <strong>${getSystemName(selectedSystem)}</strong>.</p>
        <p class="mt-1">Pilih akun yang ingin digunakan:</p>
    `;

    const accountOptions = document.getElementById('accountOptions');
    accountOptions.innerHTML = '';

    data.matches.forEach((account, index) => {
        const accountDiv = document.createElement('div');
        accountDiv.className = 'flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer account-option';
        accountDiv.onclick = () => selectAccount(account, accountDiv);

        accountDiv.innerHTML = `
            <input type="radio" name="account" value="${account.id}" class="mr-3">
            <div class="flex items-center flex-1">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                    <span class="text-sm font-medium text-blue-600">${account.name.charAt(0).toUpperCase()}</span>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-900">${account.name}</p>
                    <p class="text-sm text-gray-500">${account.email}</p>
                </div>
                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                    ${account.role || 'user'}
                </span>
            </div>
        `;

        accountOptions.appendChild(accountDiv);
    });

    document.getElementById('accountSelectionModal').classList.remove('hidden');
}

function selectAccount(account, element) {
    selectedAccount = account;

    // Update visual state
    document.querySelectorAll('.account-option').forEach(opt => {
        opt.classList.remove('bg-blue-50', 'border-blue-500');
    });
    element.classList.add('bg-blue-50', 'border-blue-500');

    // Enable confirm button
    document.getElementById('confirmAccountBtn').disabled = false;
}

function closeAccountModal() {
    document.getElementById('accountSelectionModal').classList.add('hidden');
    selectedSystem = null;
    selectedAccount = null;
}

function confirmAccountSelection() {
    if (!selectedAccount) {
        showAlert('Silakan pilih akun terlebih dahulu', 'warning');
        return;
    }

    // Show loading
    document.getElementById('confirmAccountBtn').disabled = true;
    document.getElementById('confirmAccountBtn').textContent = 'Menghubungkan...';

    const formData = new FormData();
    formData.append('sso_user_id', '{{ Auth::user()->id }}');
    formData.append('system_name', selectedSystem);
    formData.append('selected_user_id', selectedAccount.id);
    formData.append('selected_user_email', selectedAccount.email);
    formData.append('selected_user_name', selectedAccount.name);
    formData.append('selected_user_role', selectedAccount.role);

    fetch('/admin/user-mapping/select-account', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAccountModal();
            showFloatingLoading(getSystemName(selectedSystem), data.redirect_url);
        } else {
            showAlert('Error: ' + data.message, 'error');
            document.getElementById('confirmAccountBtn').disabled = false;
            document.getElementById('confirmAccountBtn').textContent = 'Lanjutkan';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan. Silakan coba lagi.', 'error');
        document.getElementById('confirmAccountBtn').disabled = false;
        document.getElementById('confirmAccountBtn').textContent = 'Lanjutkan';
    });
}

// Floating Loading Modal
function showFloatingLoading(systemName, redirectUrl) {
    document.getElementById('loadingSystemName').textContent = systemName;

    // Show loading modal
    const modal = document.getElementById('floatingLoadingModal');
    modal.classList.remove('hidden');

    // Animate progress bar
    const progressBar = document.getElementById('loadingProgressBar');

    setTimeout(() => {
        progressBar.style.width = '50%';
    }, 500);

    setTimeout(() => {
        progressBar.style.width = '100%';
    }, 1500);

    setTimeout(() => {
        window.location.href = redirectUrl;
    }, 2000);
}