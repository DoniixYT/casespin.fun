// Function to show notifications (using a consistent, global-friendly version)
function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) {
        console.warn('Notification container not found. Using alert() as fallback.');
        alert(message);
        return;
    }

    const notification = document.createElement('div');
    const notificationId = 'notification-' + Date.now();
    notification.id = notificationId;

    const typeClasses = {
        'success': 'bg-green-600 border-green-500',
        'error': 'bg-red-600 border-red-500',
        'warning': 'bg-yellow-600 border-yellow-500',
        'info': 'bg-blue-600 border-blue-500'
    };

    const typeIcons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };

    notification.className = `${typeClasses[type] || typeClasses.info} text-white px-6 py-4 rounded-lg shadow-2xl border-l-4 transform translate-x-full transition-all duration-300 ease-in-out max-w-sm`;

    notification.innerHTML = `
        <div class="flex items-center">
            <i class="${typeIcons[type] || typeIcons.info} text-xl mr-3"></i>
            <div class="flex-1">
                <p class="font-medium">${message}</p>
            </div>
            <button onclick="removeNotification('${notificationId}')" class="ml-3 text-white hover:text-gray-200 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    container.appendChild(notification);

    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 10);

    setTimeout(() => {
        removeNotification(notificationId);
    }, 4000);
}

function removeNotification(notificationId) {
    const notification = document.getElementById(notificationId);
    if (notification) {
        notification.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}

// Function to update user balance in the UI (header + mobile + buttons state)
function updateBalance(newBalance) {
    const val = parseFloat(newBalance);
    const headerEl = document.querySelector('#header-balance');
    if (headerEl) {
        headerEl.textContent = '$' + val.toFixed(2);
    }
    const mobileEl = document.querySelector('#mobile-balance');
    if (mobileEl) {
        mobileEl.textContent = '$' + val.toFixed(2);
    }

    // Update single case page button if present
    const singleBtn = document.getElementById('open-case-btn');
    if (singleBtn) {
        const p = parseFloat(singleBtn.getAttribute('data-case-price') || '0');
        const insufficient = val < p;
        singleBtn.disabled = insufficient;
        singleBtn.classList.toggle('opacity-50', insufficient);
        singleBtn.classList.toggle('cursor-not-allowed', insufficient);
    }

    // Update case listing buttons if they expose data-case-price
    document.querySelectorAll('button[data-case-price]').forEach(btn => {
        if (btn === singleBtn) return; // already handled
        const p = parseFloat(btn.getAttribute('data-case-price') || '0');
        const insufficient = val < p;
        btn.disabled = insufficient;
        btn.classList.toggle('opacity-50', insufficient);
        btn.classList.toggle('cursor-not-allowed', insufficient);
    });
}


