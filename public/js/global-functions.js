// Global functions for SSO System Access
// These functions are used across multiple pages

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
    const modal = document.getElementById('accountSelectionModal');
    const messageDiv = document.getElementById('accountModalMessage');
    const accountOptions = document.getElementById('accountOptions');

    if (!modal || !messageDiv || !accountOptions) {
        console.error('Required modal elements not found');
        return;
    }

    messageDiv.innerHTML = `
        <p><strong>${data.count}</strong> akun dengan nama mirip ditemukan di <strong>${getSystemName(selectedSystem)}</strong>.</p>
        <p class="mt-1">Pilih akun yang ingin digunakan:</p>
    `;

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

    modal.classList.remove('hidden');
}

function selectAccount(account, element) {
    selectedAccount = account;

    // Update visual state
    document.querySelectorAll('.account-option').forEach(opt => {
        opt.classList.remove('bg-blue-50', 'border-blue-500');
    });
    element.classList.add('bg-blue-50', 'border-blue-500');

    // Enable confirm button
    const confirmBtn = document.getElementById('confirmAccountBtn');
    if (confirmBtn) {
        confirmBtn.disabled = false;
    }
}

function closeAccountModal() {
    const modal = document.getElementById('accountSelectionModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    selectedSystem = null;
    selectedAccount = null;
}

function confirmAccountSelection() {
    if (!selectedAccount) {
        showAlert('Silakan pilih akun terlebih dahulu', 'warning');
        return;
    }

    // Show loading
    const confirmBtn = document.getElementById('confirmAccountBtn');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Menghubungkan...';
    }

    const formData = new FormData();
    formData.append('sso_user_id', document.querySelector('meta[name="user-id"]')?.getAttribute('content') || '');
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
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Lanjutkan';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan. Silakan coba lagi.', 'error');
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Lanjutkan';
        }
    });
}

// Floating Loading Modal
function showFloatingLoading(systemName, redirectUrl) {
    const modal = document.getElementById('floatingLoadingModal');
    const loadingName = document.getElementById('loadingSystemName');
    const progressBar = document.getElementById('loadingProgressBar');

    if (!modal || !loadingName || !progressBar) {
        console.error('Required floating modal elements not found');
        return;
    }

    loadingName.textContent = systemName;

    // Show loading modal
    modal.classList.remove('hidden');

    // Animate progress bar
    progressBar.style.width = '0%';

    setTimeout(() => {
        progressBar.style.width = '50%';
    }, 500);

    setTimeout(() => {
        progressBar.style.width = '100%';
    }, 1500);

    setTimeout(() => {
        // Open in new tab
        window.open(redirectUrl, '_blank');

        // Hide loading modal
        const modal = document.getElementById('floatingLoadingModal');
        if (modal) {
            modal.classList.add('hidden');
        }

        // Reset progress bar
        const progressBar = document.getElementById('loadingProgressBar');
        if (progressBar) {
            progressBar.style.width = '0%';
        }
    }, 2000);
}

// Alert helper function
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;

    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}