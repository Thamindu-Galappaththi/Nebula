{{-- Real-time Payment Dashboard Widget --}}
{{-- Include this in your main dashboard for live updates --}}

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">🔴 Live Payment Activity</h6>
            <span class="badge bg-success" id="liveIndicator">
                <i class="bi bi-circle-fill blink"></i> Live
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="livePaymentFeed" class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
            <div class="list-group-item text-center py-4 text-muted">
                <i class="bi bi-broadcast fs-3 mb-2 d-block"></i>
                <small>Waiting for live payment data...</small>
            </div>
        </div>
    </div>
    <div class="card-footer bg-light border-0 py-2">
        <div class="d-flex justify-content-between align-items-center small text-muted">
            <span id="lastUpdateTime">Last updated: Never</span>
            <button class="btn btn-sm btn-outline-primary" id="refreshLiveDataBtn">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
// Real-time Dashboard Update System
let lastPaymentId = 0;
let updateInterval;

// Initialize real-time updates
document.addEventListener('DOMContentLoaded', function() {
    startLiveUpdates();

    // Event delegation for refresh button
    document.getElementById('refreshLiveDataBtn')?.addEventListener('click', refreshLiveData);
});

function startLiveUpdates() {
    // Fetch new payments every 10 seconds
    updateInterval = setInterval(fetchLivePayments, 10000);
    fetchLivePayments(); // Initial fetch
}

function fetchLivePayments() {
    fetch('/payment/live-feed?last_id=' + lastPaymentId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.payments && data.payments.length > 0) {
            updateLiveFeed(data.payments);
            lastPaymentId = data.last_id;
            updateTimestamp();
        }
    })
    .catch(error => {
        console.error('Error fetching live payments:', error);
        document.getElementById('liveIndicator').innerHTML =
            '<i class="bi bi-circle-fill"></i> Offline';
        document.getElementById('liveIndicator').classList.remove('bg-success');
        document.getElementById('liveIndicator').classList.add('bg-danger');
    });
}

function updateLiveFeed(payments) {
    const feed = document.getElementById('livePaymentFeed');
    if (!feed) return;

    // Remove "waiting" message if exists
    const waitingMsg = feed.querySelector('.text-muted');
    if (waitingMsg) {
        waitingMsg.remove();
    }

    payments.forEach(payment => {
        const item = createPaymentItem(payment);
        feed.insertBefore(item, feed.firstChild);

        // Limit to 20 items
        if (feed.children.length > 20) {
            feed.removeChild(feed.lastChild);
        }
    });
}

function createPaymentItem(payment) {
    const item = document.createElement('div');
    item.className = 'list-group-item list-group-item-action animate__animated animate__fadeInDown';

    const statusColor = payment.status === 'paid' ? 'success' :
                       payment.status === 'pending' ? 'warning' : 'danger';

    const timeAgo = getTimeAgo(payment.created_at);

    item.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-1">
                    <span class="badge bg-${statusColor} me-2">${payment.status}</span>
                    <strong class="text-primary">Student #${payment.student_id}</strong>
                </div>
                <p class="mb-1 small">
                    <i class="bi bi-${getPaymentIcon(payment.payment_method)}"></i>
                    ${payment.payment_method} -
                    <strong>LKR ${parseFloat(payment.total_fee).toLocaleString()}</strong>
                </p>
                <small class="text-muted">${payment.installment_type || 'Miscellaneous'}</small>
            </div>
            <div class="text-end">
                <small class="text-muted">${timeAgo}</small>
            </div>
        </div>
    `;

    return item;
}

function getPaymentIcon(method) {
    const icons = {
        'cash': 'cash',
        'card': 'credit-card',
        'bank_transfer': 'bank',
        'online': 'globe',
        'cheque': 'receipt'
    };
    return icons[method] || 'wallet2';
}

function getTimeAgo(timestamp) {
    const now = new Date();
    const then = new Date(timestamp);
    const seconds = Math.floor((now - then) / 1000);

    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    return Math.floor(seconds / 86400) + 'd ago';
}

function updateTimestamp() {
    const now = new Date();
    document.getElementById('lastUpdateTime').textContent =
        'Last updated: ' + now.toLocaleTimeString();
}

function refreshLiveData(event) {
    fetchLivePayments();

    // Add visual feedback
    const btn = event?.currentTarget || document.getElementById('refreshLiveDataBtn');
    if (!btn) return;

    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Refreshing...';
    btn.disabled = true;

    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }, 1000);
}

// Stop updates when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(updateInterval);
    } else {
        startLiveUpdates();
    }
});
</script>

<style nonce="{{ $cspNonce }}">
.blink {
    animation: blink 2s infinite;
}

@keyframes blink {
    0%, 50%, 100% { opacity: 1; }
    25%, 75% { opacity: 0.3; }
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.list-group-item {
    transition: background-color 0.3s ease;
}

.list-group-item:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

/* Smooth scroll */
#livePaymentFeed {
    scroll-behavior: smooth;
}

/* Animation for new items */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate__animated {
    animation-duration: 0.5s;
}

.animate__fadeInDown {
    animation-name: fadeInDown;
}
</style>
