@extends('inc.app')

@section('title', 'NEBULA | Developer Dashboard')

@section('content')

<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha384-/o6I2CkkWC//PSjvWC/eYN7l3xM3tJm8ZzVkCOfp//W05QcE3mlGskpoHB6XqI+B" crossorigin="anonymous">

<style nonce="{{ $cspNonce }}">
    .tab-btn {
        padding: 10px 18px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        border: 1px solid #cfcfcf;
        background: var(--tab-bg, white);
        color: var(--tab-text, black);
        margin-right: 8px;
        margin-bottom: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    
    .tab-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .tab-btn.active {
        background: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }

    body.dark-mode .tab-btn {
        --tab-bg: #2c2f36;
        --tab-text: #e4e4e4;
        border-color: #555;
    }
    
    body.dark-mode .tab-btn.active {
        background: #0d6efd;
        color: white;
    }

    .tab-content {
        display: none;
        position: relative;
    }
    
    .tab-content.active {
        display: block;
    }

    .spinner {
        width: 60px;
        height: 60px;
        border: 6px solid #cfd0d1;
        border-top-color: #0d6efd;
        border-radius: 50%;
        animation: spin 0.9s linear infinite;
        margin: 40px auto;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .fullscreen-btn {
        position: absolute;
        top: 8px;
        right: 15px;
        z-index: 20;
        background: #0d6efd;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        transition: background 0.2s;
    }
    
    .fullscreen-btn:hover {
        background: #0b5ed7;
    }

    .fullscreen-mode iframe {
        height: calc(100vh - 100px) !important;
    }

    iframe {
        transition: none;
        background: transparent;
    }

    /* Multi split */
    .multi-container {
        display: grid;
        grid-template-columns: 1fr;
        width: 100%;
        min-height: 0;
        border: 2px solid #aaa;
        gap: 10px;
        margin-top: 20px;
        padding: 8px;
        background: #f8f9fa;
    }

    body.dark-mode .multi-container {
        background: #1a1d23;
        border-color: #555;
    }

    .multi-box {
        display: flex;
        flex-direction: column;
        min-width: 0;
        min-height: 420px;
        height: 70vh;
        border: 1px solid #ccc;
        position: relative;
        overflow: hidden;
        background: white;
    }

    body.dark-mode .multi-box {
        background: #2c2f36;
        border-color: #555;
    }

    .multi-box iframe {
        width: 100%;
        flex: 1;
        min-height: 0;
        border: none;
    }

    .selector {
        position: static;
        z-index: 50;
        width: 100%;
        max-width: 100%;
        padding: 8px 10px;
        border: 0;
        border-bottom: 1px solid #ccc;
        border-radius: 0;
        background: #fff;
    }

    body.dark-mode .selector {
        background: #2c2f36;
        color: #e4e4e4;
        border-bottom-color: #555;
    }

    .multi-view-buttons .btn {
        margin-right: 0;
    }

    @media (max-width: 767.98px) {
        .multi-view-buttons .btn {
            width: 100%;
        }
    }

    @media (min-width: 768px) {
        .multi-container[data-count="2"],
        .multi-container[data-count="4"] {
            grid-template-columns: 1fr 1fr;
        }

        .multi-container[data-count="2"] {
            height: 80vh;
        }

        .multi-container[data-count="3"] {
            grid-template-columns: 1fr 1fr;
        }

        .multi-container[data-count="3"] .multi-box:last-child {
            grid-column: 1 / -1;
        }

        .multi-container[data-count="2"] .multi-box,
        .multi-container[data-count="3"] .multi-box,
        .multi-container[data-count="4"] .multi-box {
            height: auto;
            min-height: 360px;
        }

        .multi-container[data-count="4"] {
            grid-template-rows: 1fr 1fr;
            height: 90vh;
        }
    }

    @media (min-width: 1200px) {
        .multi-container[data-count="3"] {
            grid-template-columns: 1fr 1fr 1fr;
            height: 80vh;
        }

        .multi-container[data-count="3"] .multi-box:last-child {
            grid-column: auto;
        }

        .multi-container[data-count="3"] .multi-box {
            height: auto;
            min-height: 0;
        }
    }
</style>

<div class="container-fluid">

    <div class="card shadow-sm p-4 mb-4 bg-white">
        <h3 class="fw-bold m-0">Developer Dashboard</h3>
        <small class="text-muted">Access all role dashboards from a single interface</small>
    </div>

    <!-- TAB BUTTONS -->
    <div class="d-flex flex-wrap mb-3">
        <button class="tab-btn active" data-tab="dgm"><i class="fa-solid fa-user-tie"></i> DGM</button>
        <button class="tab-btn" data-tab="pa1"><i class="fa-solid fa-user-gear"></i> PA L1</button>
        <button class="tab-btn" data-tab="pa2"><i class="fa-solid fa-users-gear"></i> PA L2</button>
        <button class="tab-btn" data-tab="counselor"><i class="fa-solid fa-user-graduate"></i> Counselor</button>
        <button class="tab-btn" data-tab="marketing"><i class="fa-solid fa-bullhorn"></i> Marketing</button>
        <button class="tab-btn" data-tab="librarian"><i class="fa-solid fa-book"></i> Librarian</button>
        <button class="tab-btn" data-tab="hostel"><i class="fa-solid fa-house"></i> Hostel</button>
        <button class="tab-btn" data-tab="project"><i class="fa-solid fa-file-code"></i> Project Tutor</button>
        <button class="tab-btn" data-tab="bursar"><i class="fa-solid fa-money-bill"></i> Bursar</button>
        <button class="tab-btn" data-tab="devtools"><i class="fa-solid fa-code"></i> Developer</button>
    </div>

    <!-- MULTI VIEW BUTTONS -->
    <div class="mb-3 d-flex flex-wrap gap-2 multi-view-buttons">
        <button type="button" class="btn btn-dark" onclick="twoView()">Two View</button>
        <button type="button" class="btn btn-secondary" onclick="threeView()">Three View</button>
        <button type="button" class="btn btn-primary" onclick="fourView()">Four View</button>
    </div>

    <!-- TAB CONTENT LOADER -->
    @php
        $tabs = [
            'dgm' => route('dgmdashboard') . '?embed=1',
            'pa1' => route('admin.l1.dashboard') . '?embed=1',
            'pa2' => route('program.admin.l2.dashboard') . '?embed=1',
            'counselor' => route('student.counselor.dashboard') . '?embed=1',
            'marketing' => route('marketing.manager.dashboard') . '?embed=1',
            'librarian' => route('librarian.dashboard') . '?embed=1',
            'hostel' => route('hostel.manager.dashboard') . '?embed=1',
            'project' => route('project.tutor.dashboard') . '?embed=1',
            'bursar' => route('bursar.dashboard') . '?embed=1',
        ];

    @endphp

    @foreach($tabs as $id => $url)
        <div id="tab-{{ $id }}" class="tab-content {{ $id === 'dgm' ? 'active' : '' }}">
            <button class="fullscreen-btn" onclick="toggleFullscreen('{{ $id }}')">
                <i class="fa-solid fa-expand"></i> Fullscreen
            </button>

            <div id="spinner-{{ $id }}" class="spinner"></div>

            <iframe id="frame-{{ $id }}" 
                    src="{{ $url }}"
                    class="w-100"
                    style="height:640px; border:none; background:transparent;"
                    data-loaded="false">
            </iframe>
        </div>
    @endforeach

    <div id="tab-devtools" class="tab-content">
        <div class="alert alert-info mt-3">
            <i class="fa-solid fa-wrench"></i> Developer Tools Coming Soon
        </div>
    </div>

    <!-- MULTI SPLIT AREA -->
    <div id="multi-area"></div>

</div>

<script nonce="{{ $cspNonce }}">
// Dashboard routes mapping
const dashboardRoutes = {
    'dgm': '{{ route('dgmdashboard') }}',
    'pa1': '{{ route('admin.l1.dashboard') }}',
    'pa2': '{{ route('program.admin.l2.dashboard') }}',
    'counselor': '{{ route('student.counselor.dashboard') }}',
    'marketing': '{{ route('marketing.manager.dashboard') }}',
    'librarian': '{{ route('librarian.dashboard') }}',
    'hostel': '{{ route('hostel.manager.dashboard') }}',
    'project': '{{ route('project.tutor.dashboard') }}',
    'bursar': '{{ route('bursar.dashboard') }}'
};

// Initialize tab buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            showTab(this.getAttribute('data-tab'));
        });
    });

    document.querySelectorAll('iframe[id^="frame-"]').forEach(watchIframe);

    window.addEventListener('message', function(e) {
        if (e.origin !== window.location.origin || !e.data || e.data.type !== 'nebula-embed-height') {
            return;
        }
        document.querySelectorAll('iframe').forEach(function(iframe) {
            if (iframe.contentWindow === e.source) {
                applyIframeHeight(iframe, e.data.height);
            }
        });
    });

    window.addEventListener('resize', requestVisibleIframeRemeasure);
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', requestVisibleIframeRemeasure);
    }
});

// Keyboard shortcuts
const dashboardKeys = {
    '1': 'dgm', '2': 'pa1', '3': 'pa2', '4': 'counselor',
    '5': 'marketing', '6': 'librarian', '7': 'hostel',
    '8': 'project', '9': 'bursar', '0': 'devtools'
};

document.addEventListener('keydown', function(e) {
    // Only trigger if no input is focused
    if (document.activeElement.tagName === 'INPUT' || 
        document.activeElement.tagName === 'TEXTAREA' ||
        document.activeElement.tagName === 'SELECT') {
        return;
    }
    
    if (dashboardKeys[e.key]) {
        showTab(dashboardKeys[e.key]);
    }
});

// Tab switch function
function showTab(id) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show selected tab
    const selectedTab = document.getElementById('tab-' + id);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    // Update button states
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const activeBtn = document.querySelector(`.tab-btn[data-tab="${id}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }

    const multiArea = document.getElementById('multi-area');
    if (multiArea) {
        multiArea.innerHTML = '';
    }

    autoResizeIframe(id);
}

// Spinner hide
function hideSpinner(id) {
    const spinner = document.getElementById('spinner-' + id);
    if (spinner) {
        spinner.style.display = 'none';
    }
}

// Fullscreen toggle
function toggleFullscreen(id) {
    const block = document.getElementById('tab-' + id);
    if (block) {
        block.classList.toggle('fullscreen-mode');
        
        const btn = block.querySelector('.fullscreen-btn i');
        if (btn) {
            if (block.classList.contains('fullscreen-mode')) {
                btn.className = 'fa-solid fa-compress';
            } else {
                btn.className = 'fa-solid fa-expand';
                requestIframeRemeasure(block.querySelector('iframe'));
            }
        }
    }
}

let remeasureTimer = null;

function applyIframeHeight(iframe, height) {
    if (!iframe || iframe.closest('.fullscreen-mode')) {
        return;
    }
    const nextHeight = Math.max(Math.ceil(Number(height) || 0), 320);
    const current = parseInt(iframe.style.height, 10) || 0;
    if (Math.abs(current - nextHeight) < 2) {
        return;
    }
    iframe.style.height = nextHeight + 'px';
}

function requestIframeRemeasure(iframe) {
    if (!iframe || !iframe.contentWindow) {
        return;
    }
    try {
        iframe.contentWindow.postMessage({ type: 'nebula-embed-remeasure' }, window.location.origin);
    } catch (e) {
        resizeIframeToContent(iframe);
    }
}

function requestVisibleIframeRemeasure() {
    if (remeasureTimer) {
        clearTimeout(remeasureTimer);
    }
    remeasureTimer = setTimeout(function() {
        document.querySelectorAll('.tab-content.active iframe, .multi-box iframe').forEach(requestIframeRemeasure);
    }, 120);
}

function watchIframe(iframe) {
    if (!iframe || iframe.dataset.watched === 'true') {
        return;
    }
    iframe.dataset.watched = 'true';

    iframe.addEventListener('load', function() {
        const id = this.id.replace('frame-', '');
        if (id) {
            hideSpinner(id);
            this.setAttribute('data-loaded', 'true');
        }
        requestIframeRemeasure(this);
    });

    if (typeof ResizeObserver !== 'undefined') {
        let lastWidth = iframe.clientWidth;
        new ResizeObserver(function() {
            const width = iframe.clientWidth;
            if (Math.abs(width - lastWidth) < 2) {
                return;
            }
            lastWidth = width;
            requestIframeRemeasure(iframe);
        }).observe(iframe);
    }
}

function resizeIframeToContent(iframe) {
    if (!iframe) {
        return;
    }
    try {
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        if (!doc || !doc.body) {
            return;
        }
        const content = doc.getElementById('pageContent') || doc.body;
        applyIframeHeight(iframe, Math.max(content.scrollHeight, content.offsetHeight));
    } catch (e) {
        // Ignore cross-origin frames
    }
}

function autoResizeIframe(id) {
    const iframe = document.getElementById('frame-' + id);
    if (iframe && iframe.getAttribute('data-loaded') === 'true') {
        requestIframeRemeasure(iframe);
    }
}

function embedUrl(route) {
    return route + (route.includes('?') ? '&' : '?') + 'embed=1';
}

function getDashboardOptions() {
    return `
        <option value="${embedUrl(dashboardRoutes.dgm)}">DGM</option>
        <option value="${embedUrl(dashboardRoutes.pa1)}">PA L1</option>
        <option value="${embedUrl(dashboardRoutes.pa2)}">PA L2</option>
        <option value="${embedUrl(dashboardRoutes.counselor)}">Counselor</option>
        <option value="${embedUrl(dashboardRoutes.marketing)}">Marketing</option>
        <option value="${embedUrl(dashboardRoutes.librarian)}">Librarian</option>
        <option value="${embedUrl(dashboardRoutes.hostel)}">Hostel</option>
        <option value="${embedUrl(dashboardRoutes.project)}">Project Tutor</option>
        <option value="${embedUrl(dashboardRoutes.bursar)}">Bursar</option>
    `;
}

function twoView() {
    createMultiView(2);
}

function threeView() {
    createMultiView(3);
}

function fourView() {
    createMultiView(4);
}

function createMultiView(count) {
    const area = document.getElementById('multi-area');
    area.innerHTML = '';

    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    const container = document.createElement('div');
    container.className = 'multi-container';
    container.setAttribute('data-count', String(count));

    const defaultSrc = embedUrl(dashboardRoutes.dgm);

    for (let i = 1; i <= count; i++) {
        const box = document.createElement('div');
        box.className = 'multi-box';

        const select = document.createElement('select');
        select.className = 'selector';
        select.setAttribute('aria-label', 'Select dashboard ' + i);
        select.innerHTML = getDashboardOptions();
        select.value = defaultSrc;
        select.onchange = function() {
            changeMultiFrame(this);
        };

        const iframe = document.createElement('iframe');
        iframe.src = defaultSrc;
        iframe.title = 'Dashboard pane ' + i;
        iframe.style.border = 'none';
        iframe.style.background = 'transparent';
        iframe.style.height = '640px';

        box.appendChild(select);
        box.appendChild(iframe);
        container.appendChild(box);
        watchIframe(iframe);
    }

    area.appendChild(container);
}

function changeMultiFrame(selectObj) {
    const iframe = selectObj.parentElement.querySelector('iframe');
    if (iframe) {
        iframe.src = selectObj.value;
    }
}

</script>

@endsection
