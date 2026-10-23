<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NEBULA | Document Submission</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/favicon.jpg') }}" />
    <link nonce="{{ $cspNonce }}" href="{{ asset('css/styles.min.css') }}" rel="stylesheet">
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
    @php
        $idTypeLabel = str_ireplace(' id', ' ID', isset($student) ? ($student->id_type ?? 'National ID') : 'National ID');
    @endphp
    <style nonce="{{ $cspNonce }}">
        html {
            height: 100%;
        }
        html, body {
            min-height: 100%;
            min-height: 100dvh;
        }
        body {
            margin: 0;
            background: #eef3fb;
            overflow-x: hidden;
        }
        .portal-shell {
            min-height: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            max-width: 880px;
            width: 100%;
            margin: 0 auto;
            padding: clamp(1rem, 3vh, 1.75rem) 1.15rem 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            box-sizing: border-box;
        }
        body.is-status .portal-shell {
            max-width: 640px;
            padding: 1.5rem 1.25rem;
            justify-content: center;
        }
        .portal-logo {
            max-width: 176px;
            margin: 0 auto 0.85rem;
            display: block;
        }
        body.is-status .portal-logo {
            max-width: 188px;
            margin-bottom: 1.15rem;
        }
        .portal-card {
            width: 100%;
            border: 0;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(35, 64, 142, 0.08);
        }
        .portal-card .card-body {
            padding: 1.5rem 1.75rem;
        }
        body.is-status .portal-card .card-body {
            padding: 2.4rem 2.2rem 2.25rem;
        }
        .portal-status-title {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
            margin: 0 0 0.8rem;
            color: #1b2c5b;
        }
        .portal-status-copy {
            font-size: 1.05rem;
            line-height: 1.65;
            margin: 0;
        }
        .portal-title { font-size: 1.2rem; margin-bottom: 0.3rem; }
        .portal-intro { font-size: 0.9rem; margin-bottom: 0.7rem; }
        .step-dots { gap: 0.5rem !important; margin-bottom: 0.75rem !important; }
        .step-dot {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dbe4f5;
            color: #23408e;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .step-dot.is-active { background: #23408e; color: #fff; }
        .step-panel { display: none; }
        .step-panel.is-active { display: block; }
        .step-panel h2 { font-size: 1.05rem; margin-bottom: 0.55rem; }
        .step-panel .form-label { margin-bottom: 0.25rem; font-size: 0.86rem; }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem 0.85rem;
            margin-bottom: 0.7rem;
        }
        .details-grid .span-2 { grid-column: 1 / -1; }
        .preview-list { display: grid; gap: 0.55rem; font-size: 0.9rem; }
        .preview-item {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 0.55rem;
            align-items: baseline;
        }
        .preview-file-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem 0.7rem;
        }
        .preview-file-link {
            background: none;
            border: 0;
            padding: 0;
            color: #23408e;
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 0.15em;
            cursor: pointer;
            word-break: break-word;
            text-align: left;
        }
        .preview-file-link:hover,
        .preview-file-link:focus {
            color: #1f3f9a;
        }
        .portal-preview-image {
            max-height: 70vh;
            object-fit: contain;
        }
        .portal-preview-frame {
            width: 100%;
            height: 70vh;
            border: 0;
            border-radius: 12px;
            background: #f7f9fd;
        }
        .portal-actions { padding-top: 0.4rem; }
        .instruction-list {
            list-style: none;
            margin: 0 0 1rem;
            padding: 0;
        }
        .instruction-list li {
            position: relative;
            padding-left: 1.2rem;
            margin-bottom: 0.55rem;
            line-height: 1.55;
        }
        .instruction-list li:last-child {
            margin-bottom: 0;
        }
        .instruction-list li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.55em;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #23408e;
        }
        @media (max-width: 991.98px) {
            .portal-logo {
                max-width: 176px;
                margin-bottom: 0.85rem;
            }
            body.is-form .portal-shell {
                min-height: 100vh;
                min-height: 100dvh;
                justify-content: flex-start;
                padding: 0.85rem 0.7rem max(0.9rem, env(safe-area-inset-bottom));
            }
            body.is-form .portal-card .card-body {
                padding: 1.15rem 1.2rem;
            }
            body.is-form .portal-card,
            body.is-form .portal-card .card-body,
            body.is-form #documentSubmitForm,
            body.is-form .step-panel.is-active {
                flex: none;
                min-height: 0;
                height: auto;
            }
            body.is-form .step-panel.is-active {
                display: block;
            }
            body.is-form .portal-actions {
                margin-top: 0.85rem;
                padding-top: 0.15rem;
            }
            body.is-status .portal-shell { padding: 1.1rem 0.85rem; }
            body.is-status .portal-card .card-body { padding: 1.7rem 1.25rem 1.6rem; }
            .portal-status-title { font-size: 1.3rem; }
            .details-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 767.98px) {
            .portal-actions .btn { width: 100%; }
        }
        .swal2-container { z-index: 20000; }
    </style>
</head>
<body class="{{ ($state ?? '') === 'form' ? 'is-form' : 'is-status' }}">
    <div class="portal-shell">
        <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula" class="portal-logo">
        <div class="card portal-card">
            <div class="card-body">
                @if($state === 'invalid')
                    <h1 class="portal-status-title text-center">This link is not valid</h1>
                    <p class="text-muted text-center portal-status-copy">The document submission link is missing, incorrect, or has already been removed. Please contact the staff member who sent it.</p>
                @elseif($state === 'expired')
                    <h1 class="portal-status-title text-center">This link has expired</h1>
                    <p class="text-muted text-center portal-status-copy">The submission window for this unique link has ended. Ask your Nebula staff contact to send a new link if you still need to upload documents.</p>
                @elseif($state === 'submitted')
                    <h1 class="portal-status-title text-center">Documents submitted</h1>
                    <p class="text-muted text-center portal-status-copy">Thank you. Your documents were received and the relevant staff members have been notified. Keep a copy of your files for your own records. Nebula will automatically delete the uploaded copies after seven days.</p>
                @else
                    <h1 class="h4 text-center portal-title">Submit your documents</h1>
                    <p class="text-center text-muted portal-intro">This page is only for {{ $student->full_name ?? 'the invited student' }}. Do not share this link.</p>

                    @if(session('error'))
                        <div class="alert alert-danger py-2">{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="d-flex justify-content-center step-dots">
                        <span class="step-dot is-active" data-step-dot="1">1</span>
                        <span class="step-dot" data-step-dot="2">2</span>
                        <span class="step-dot" data-step-dot="3">3</span>
                        <span class="step-dot" data-step-dot="4">4</span>
                    </div>

                    <form method="POST" action="{{ route('student.documents.portal.submit', ['token' => $token]) }}" enctype="multipart/form-data" id="documentSubmitForm">
                        @csrf

                        <div class="step-panel is-active" data-step="1">
                            <h2 class="h5">Instructions</h2>
                            <ul class="text-muted instruction-list">
                                <li>Use this page only if you received the link from Nebula staff.</li>
                                <li>Confirm your details, then upload clear copies of your NIC and birth certificate.</li>
                                <li>Accepted files: PDF, JPG or PNG, up to {{ number_format(($maxFileKb ?? 5120) / 1024, 0) }} MB each.</li>
                                <li>Review everything before you submit. After seven days, Nebula deletes the uploaded copies automatically.</li>
                            </ul>
                            <div class="portal-actions d-flex justify-content-end">
                                <button type="button" class="btn btn-primary" data-next-step="2">Continue</button>
                            </div>
                        </div>

                        <div class="step-panel" data-step="2">
                            <h2 class="h5">Your details</h2>
                            <div class="details-grid">
                                <div>
                                    <label class="form-label" for="full_name">Full name</label>
                                    <input class="form-control" id="full_name" name="full_name" value="{{ old('full_name', $student->full_name ?? '') }}" required>
                                </div>
                                <div>
                                    <label class="form-label" for="id_value">{{ $idTypeLabel }}</label>
                                    <input class="form-control" id="id_value" name="id_value" value="{{ old('id_value', $student->id_value ?? '') }}" required>
                                </div>
                                <div>
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $student->email ?? '') }}" required>
                                </div>
                                <div>
                                    <label class="form-label" for="phone">Mobile number</label>
                                    <input class="form-control" id="phone" name="phone" value="{{ old('phone', $student->mobile_phone ?? $student->whatsapp_phone ?? '') }}" required>
                                </div>
                                <div class="span-2">
                                    <label class="form-label" for="notes">Notes (optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                            <div class="portal-actions d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-outline-secondary" data-prev-step="1">Back</button>
                                <button type="button" class="btn btn-primary" data-next-step="3">Continue</button>
                            </div>
                        </div>

                        <div class="step-panel" data-step="3">
                            <h2 class="h5">Upload documents</h2>
                            <div class="mb-2">
                                <label class="form-label" for="nic_document">National Identity Card</label>
                                <input type="file" class="form-control" id="nic_document" name="nic_document" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="birth_certificate">Birth certificate</label>
                                <input type="file" class="form-control" id="birth_certificate" name="birth_certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="other_documents">Supporting documents (optional, max {{ $maxOtherFiles ?? 3 }})</label>
                                <input type="file" class="form-control" id="other_documents" name="other_documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                            </div>
                            <div class="portal-actions d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-outline-secondary" data-prev-step="2">Back</button>
                                <button type="button" class="btn btn-primary" data-next-step="4">Review</button>
                            </div>
                        </div>

                        <div class="step-panel" data-step="4">
                            <h2 class="h5">Review and submit</h2>
                            <dl class="row mb-2">
                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8" id="reviewName"></dd>
                                <dt class="col-sm-4">{{ $idTypeLabel }}</dt><dd class="col-sm-8" id="reviewId"></dd>
                                <dt class="col-sm-4">Email</dt><dd class="col-sm-8" id="reviewEmail"></dd>
                                <dt class="col-sm-4">Phone</dt><dd class="col-sm-8" id="reviewPhone"></dd>
                            </dl>
                            <div class="preview-list mb-2" id="reviewFiles"></div>
                            <p class="small text-muted mb-0">By submitting, you confirm these files belong to you and may be reviewed by authorized Nebula staff for seven days.</p>
                            <div class="portal-actions d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-outline-secondary" data-prev-step="3">Back</button>
                                <button type="submit" class="btn btn-success" id="finalSubmitBtn">Submit documents</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if($state === 'form')
    <script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
    <script nonce="{{ $cspNonce }}">
        document.addEventListener('DOMContentLoaded', function () {
            const panels = document.querySelectorAll('.step-panel');
            const dots = document.querySelectorAll('[data-step-dot]');
            const maxOther = {{ (int) ($maxOtherFiles ?? 3) }};
            const previewStore = {};
            const previewUrls = [];

            function escapeHtml(value) {
                return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
                });
            }

            function revokePreviewUrls() {
                previewUrls.splice(0).forEach(function (url) {
                    URL.revokeObjectURL(url);
                });
            }

            function filesFrom(input) {
                return input && input.files ? Array.from(input.files) : [];
            }

            function isImage(file) {
                return file && /^image\//.test(file.type);
            }

            function isPdf(file) {
                return file && (file.type === 'application/pdf' || /\.pdf$/i.test(file.name));
            }

            function fileButton(file, key) {
                return '<button type="button" class="preview-file-link" data-preview-key="' + escapeHtml(key) + '">' + escapeHtml(file.name) + '</button>';
            }

            function fileGroup(label, files, prefix) {
                if (!files.length) {
                    return '<div class="preview-item"><strong>' + escapeHtml(label) + ':</strong> <span class="text-muted">None selected</span></div>';
                }

                const buttons = files.map(function (file, index) {
                    const key = prefix + '-' + index;
                    previewStore[key] = file;
                    return fileButton(file, key);
                }).join('');

                return '<div class="preview-item"><strong>' + escapeHtml(label) + ':</strong> <span class="preview-file-list">' + buttons + '</span></div>';
            }

            function buildFileReview() {
                revokePreviewUrls();
                Object.keys(previewStore).forEach(function (key) {
                    delete previewStore[key];
                });

                document.getElementById('reviewFiles').innerHTML =
                    fileGroup('NIC', filesFrom(document.getElementById('nic_document')), 'nic') +
                    fileGroup('Birth certificate', filesFrom(document.getElementById('birth_certificate')), 'birth') +
                    fileGroup('Supporting', filesFrom(document.getElementById('other_documents')), 'other') +
                    '<p class="small text-muted mb-0">Click a file name to view it.</p>';
            }

            function openPreview(file) {
                const url = URL.createObjectURL(file);
                previewUrls.push(url);

                if (isImage(file)) {
                    Swal.fire({
                        title: file.name,
                        imageUrl: url,
                        imageAlt: file.name,
                        confirmButtonText: 'Close',
                        confirmButtonColor: '#23408e',
                        width: '42rem',
                        customClass: { image: 'portal-preview-image' }
                    });
                    return;
                }

                if (isPdf(file)) {
                    Swal.fire({
                        title: file.name,
                        html: '<iframe class="portal-preview-frame" src="' + url + '" title="Document preview"></iframe>',
                        width: '52rem',
                        confirmButtonText: 'Close',
                        confirmButtonColor: '#23408e'
                    });
                    return;
                }

                window.open(url, '_blank', 'noopener');
            }

            function showNotice(title, text) {
                if (!window.Swal) {
                    return;
                }

                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    confirmButtonColor: '#23408e',
                    confirmButtonText: 'OK'
                });
            }

            function showStep(step) {
                panels.forEach(function (panel) {
                    panel.classList.toggle('is-active', Number(panel.dataset.step) === step);
                });
                dots.forEach(function (dot) {
                    dot.classList.toggle('is-active', Number(dot.dataset.stepDot) <= step);
                });
            }

            document.querySelectorAll('[data-next-step]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const next = Number(button.dataset.nextStep);
                    if (next === 3) {
                        const requiredIds = ['full_name', 'id_value', 'email', 'phone'];
                        const missing = requiredIds.some(function (id) {
                            return !document.getElementById(id).value.trim();
                        });
                        if (missing) {
                            showNotice('Missing details', 'Please complete your details before continuing.');
                            return;
                        }
                    }
                    if (next === 4) {
                        const nic = document.getElementById('nic_document');
                        const birth = document.getElementById('birth_certificate');
                        const other = document.getElementById('other_documents');
                        if (!nic.files.length || !birth.files.length) {
                            showNotice('Documents required', 'Please upload your NIC and birth certificate.');
                            return;
                        }
                        if (other.files.length > maxOther) {
                            showNotice('Too many files', 'You can upload a maximum of ' + maxOther + ' supporting documents.');
                            return;
                        }
                        document.getElementById('reviewName').textContent = document.getElementById('full_name').value;
                        document.getElementById('reviewId').textContent = document.getElementById('id_value').value;
                        document.getElementById('reviewEmail').textContent = document.getElementById('email').value;
                        document.getElementById('reviewPhone').textContent = document.getElementById('phone').value;
                        buildFileReview();
                    }
                    showStep(next);
                });
            });

            document.getElementById('reviewFiles').addEventListener('click', function (event) {
                const button = event.target.closest('[data-preview-key]');
                if (!button) {
                    return;
                }
                const file = previewStore[button.dataset.previewKey];
                if (file) {
                    openPreview(file);
                }
            });

            document.querySelectorAll('[data-prev-step]').forEach(function (button) {
                button.addEventListener('click', function () {
                    showStep(Number(button.dataset.prevStep));
                });
            });
        });
    </script>
    @endif
</body>
</html>
