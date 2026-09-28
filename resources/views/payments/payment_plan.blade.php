@extends('inc.app')

@section('title', 'NEBULA | Payment Plan')

@section('content')
<div id="payment-plan-create" class="container-fluid px-2 px-md-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <h2 class="mb-4 text-center">Intake Payment Plan</h2>
            <hr>
            <form id="paymentPlanForm" method="POST" action="{{ route('payment.plan.store') }}">
                @csrf
                <div class="row gy-3 mb-3">
                    <label for="location" class="col-12 col-md-3 col-form-label">Location <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9">
                        <select class="form-select" id="location" name="location" required>
                            <option disabled {{ empty($selectedLocation) ? 'selected' : '' }} value="">Choose a location...</option>
                            @foreach(['Welisara', 'Moratuwa', 'Peradeniya'] as $loc)
                                <option value="{{ $loc }}" @selected(($selectedLocation ?? '') === $loc)>
                                    Nebula Institute of Technology - {{ $loc }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row gy-2 mb-3">
                    <label for="course" class="col-12 col-md-3 col-form-label">Course <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9">
                        <select class="form-select" id="course" name="course" required {{ empty($selectedLocation) ? 'disabled' : '' }}>
                            <option selected disabled value="">Select Course...</option>
                            @if(isset($courses) && $courses->count())
                                @foreach($courses as $course)
                                    <option value="{{ $course->course_id }}">
                                        {{ ucfirst($course->course_type) }} – {{ $course->course_name }}
                                    </option>
                                @endforeach
                            @else
                                <option disabled>No courses available for this location</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row gy-2 mb-3">
                    <label for="intake" class="col-12 col-md-3 col-form-label">Intake <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9">
                        <select class="form-select" id="intake" name="intake" required disabled>
                            <option selected disabled value="">Select Intake...</option>
                        </select>
                    </div>
                </div>

                <div class="payment-plan-section rounded border mb-3">
                    <h5 class="mt-0 bg-black p-3 text-white mb-3">Course Fee</h5>
                    <div class="px-3 pb-3">
                        <div class="row gy-2 align-items-center mb-3">
                            <label for="registrationFee" class="col-12 col-md-3 col-form-label fw-bold">Course registration Fee<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9">
                                <div class="input-group flex-nowrap">
                                    <span class="input-group-text bg-primary text-white">LKR</span>
                                    <input type="number" class="form-control bg-light" id="registrationFee" name="registrationFee" placeholder="Enter registration fee" required readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row gy-2 align-items-center mb-3">
                            <label for="localFee" class="col-12 col-md-3 col-form-label fw-bold">Local course Fee<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9">
                                <div class="input-group flex-nowrap">
                                    <span class="input-group-text bg-danger text-white">LKR</span>
                                    <input type="number" class="form-control bg-light" id="localFee" name="localFee" placeholder="Enter local course fee" required readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row gy-2 align-items-center mb-3">
                            <label for="internationalFee" class="col-12 col-md-3 col-form-label fw-bold">Franchise Payment<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9">
                                <div class="input-group flex-nowrap">
                                    <span class="input-group-text bg-danger text-white" id="currencyDisplay">LKR</span>
                                    <input type="number" class="form-control bg-light" id="internationalFee" name="internationalFee" placeholder="Enter international course fee" required readonly>
                                </div>
                                <input type="hidden" id="currency" name="currency" value="LKR">
                            </div>
                        </div>
                        <div class="row gy-2 align-items-center mb-3">
                            <label for="ssclTax" class="col-12 col-md-3 col-form-label fw-bold">SSCL Tax Percentage</label>
                            <div class="col-12 col-md-9">
                                <div class="input-group flex-nowrap">
                                    <input type="number" class="form-control bg-white" id="ssclTax" name="ssclTax" placeholder="Enter SSCL tax percentage" required min="0" max="100" step="0.01">
                                    <span class="input-group-text bg-black text-white">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="row gy-2 align-items-center mb-3">
                            <label for="bankCharges" class="col-12 col-md-3 col-form-label fw-bold">Bank Charges</label>
                            <div class="col-12 col-md-9">
                                <div class="input-group flex-nowrap">
                                    <input type="number" class="form-control bg-white" id="bankCharges" name="bankCharges" placeholder="Enter bank charges" min="0" step="1">
                                    <span class="input-group-text bg-secondary text-white">LKR</span>
                                </div>
                                <small class="form-text text-muted">Enter bank charges only for one installment.</small>
                            </div>
                        </div>
                        <div class="row gy-2 mb-3">
                            <label class="col-12 col-md-3 col-form-label">Apply Full Payment Discount<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 payment-plan-radios">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="applyDiscountYes" name="applyDiscount" value="yes">
                                    <label class="form-check-label" for="applyDiscountYes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="applyDiscountNo" name="applyDiscount" value="no" checked>
                                    <label class="form-check-label" for="applyDiscountNo">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="row gy-2 align-items-center mb-1" id="discountField" hidden>
                            <label for="fullPaymentDiscount" class="col-12 col-md-3 col-form-label fw-bold">Full Payment Discount</label>
                            <div class="col-12 col-md-9">
                                <div class="input-group flex-nowrap">
                                    <input type="text" class="form-control bg-white" id="fullPaymentDiscount" name="fullPaymentDiscount" placeholder="Enter discount percentage">
                                    <span class="input-group-text bg-black text-white">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-plan-section p-3 mb-3 rounded border bg-light-warning">
                    <div class="row gy-2">
                        <label class="col-12 col-md-3 col-form-label">Installment Plan</label>
                        <div class="col-12 col-md-9 payment-plan-radios">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="franchisePaymentYes" name="franchisePayment" value="yes">
                                <label class="form-check-label" for="franchisePaymentYes">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="franchisePaymentNo" name="franchisePayment" value="no" checked>
                                <label class="form-check-label" for="franchisePaymentNo">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3" id="amountField" hidden>
                        <div class="row gy-2 align-items-center">
                            <label for="installments" class="col-12 col-md-3 col-form-label fw-bold">No. of Installments</label>
                            <div class="col-12 col-md-9">
                                <div class="d-flex flex-wrap gap-2">
                                    <input type="number" class="form-control bg-white flex-grow-1" id="installments" name="installment_count" min="1" max="24" placeholder="Enter number of installments">
                                    <button type="button" class="btn btn-primary" id="addRowsBtn">Add</button>
                                </div>
                            </div>
                        </div>
                        <div class="payment-plan-installments-wrap mt-3">
                            <table class="table bg-white rounded border table-bordered mb-0 payment-plan-installments">
                                <thead>
                                    <tr class="bg-warning text-black">
                                        <th scope="col">No.</th>
                                        <th scope="col">Due Date</th>
                                        <th scope="col">Local (Rs.)</th>
                                        <th scope="col" id="internationalHeader">International</th>
                                        <th scope="col">Apply Tax</th>
                                    </tr>
                                </thead>
                                <tbody id="installmentsTableBody"></tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <td colspan="2" class="fw-bold" data-label="Total">Total:</td>
                                        <td id="totalLocalAmount" class="fw-bold" data-label="Local (Rs.)">Rs. 0.00</td>
                                        <td id="totalInternationalAmount" class="fw-bold" data-label="International">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="2" class="fw-bold" data-label="Required">Required:</td>
                                        <td id="requiredLocalAmount" class="fw-bold" data-label="Local (Rs.)">Rs. 0.00</td>
                                        <td id="requiredInternationalAmount" class="fw-bold" data-label="International">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr id="validationRow" hidden>
                                        <td colspan="5">
                                            <div id="validationMessage" class="alert mb-0"></div>
                                        </td>
                                    </tr>
                                    <tr id="autoCompleteRow" hidden>
                                        <td colspan="5" class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="autoCompleteBtn">Auto-complete remaining amounts</button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
const locationReloadUrl = @json(route('payment.plan'));

document.addEventListener('change', function(e) {
    if (e.target.id === 'location') {
        window.location = locationReloadUrl + '?location=' + encodeURIComponent(e.target.value);
    } else if (e.target.name === 'applyDiscount') {
        toggleDiscountField();
    } else if (e.target.name === 'franchisePayment') {
        toggleAmountField();
    }
});

document.addEventListener('click', function(e) {
    if (e.target.id === 'addRowsBtn' || e.target.closest('#addRowsBtn')) {
        addRows();
    } else if (e.target.id === 'autoCompleteBtn' || e.target.closest('#autoCompleteBtn')) {
        autoCompleteRemaining();
    }
});

document.getElementById('ssclTax')?.addEventListener('input', function () {
    validateInput(this);
});
document.getElementById('fullPaymentDiscount')?.addEventListener('input', function () {
    validateInput(this);
});
document.getElementById('bankCharges')?.addEventListener('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
});

function syncCustomSelect(select) {
    if (!select) return;
    const selected = select.options[select.selectedIndex];
    const wrap = select.closest('.nebula-select');
    const toggle = wrap ? wrap.querySelector('.nebula-select-toggle') : null;
    if (toggle) {
        toggle.textContent = selected ? selected.text : '';
        toggle.title = toggle.textContent;
        toggle.disabled = !!select.disabled;
        wrap.classList.toggle('is-disabled', !!select.disabled);
    }
}

function toggleAmountField() {
    const amountField = document.getElementById('amountField');
    const yes = document.getElementById('franchisePaymentYes');
    if (amountField) {
        amountField.hidden = !(yes && yes.checked);
    }
}

function toggleDiscountField() {
    const applyDiscountYes = document.getElementById('applyDiscountYes')?.checked;
    const discountField = document.getElementById('discountField');
    const discountInput = document.getElementById('fullPaymentDiscount');
    if (!discountField || !discountInput) return;

    if (applyDiscountYes) {
        discountField.hidden = false;
        discountInput.setAttribute('required', 'required');
    } else {
        discountField.hidden = true;
        discountInput.removeAttribute('required');
        discountInput.value = '';
    }
}

function validateInput(input) {
    input.value = input.value.replace(/[^0-9.]/g, '');
    const parts = input.value.split('.');
    if (parts.length > 2) {
        input.value = parts[0] + '.' + parts.slice(1).join('');
    }
    if (input.value.startsWith('.')) {
        input.value = '0' + input.value;
    }

    if (input.id === 'ssclTax' || input.id === 'fullPaymentDiscount') {
        const value = parseFloat(input.value);
        if (value > 100) {
            input.value = '100';
            showAutofillToast('Percentage value cannot exceed 100%.');
        }
        if (value < 0) {
            input.value = '0';
        }
    }
}

function addRows() {
    const tableBody = document.getElementById('installmentsTableBody');
    const raw = parseInt(document.getElementById('installments')?.value || '0', 10);
    const numRows = Number.isFinite(raw) ? Math.min(Math.max(raw, 0), 24) : 0;
    tableBody.replaceChildren();

    for (let i = 1; i <= numRows; i++) {
        const row = document.createElement('tr');

        const noCell = document.createElement('td');
        noCell.dataset.label = 'No.';
        noCell.textContent = String(i);

        const dueCell = document.createElement('td');
        dueCell.dataset.label = 'Due Date';
        const dueInput = document.createElement('input');
        dueInput.type = 'date';
        dueInput.className = 'form-control';
        dueInput.id = 'dueDate' + i;
        dueInput.name = 'dueDate' + i;
        dueCell.appendChild(dueInput);

        const localCell = document.createElement('td');
        localCell.dataset.label = 'Local (Rs.)';
        const localInput = document.createElement('input');
        localInput.type = 'number';
        localInput.className = 'form-control';
        localInput.id = 'localAmount' + i;
        localInput.name = 'localAmount' + i;
        localInput.placeholder = '0';
        localInput.addEventListener('input', function () {
            validateInput(this);
            calculateTotals();
        });
        localCell.appendChild(localInput);

        const intlCell = document.createElement('td');
        intlCell.dataset.label = 'International';
        const intlInput = document.createElement('input');
        intlInput.type = 'number';
        intlInput.className = 'form-control';
        intlInput.id = 'internationalAmount' + i;
        intlInput.name = 'internationalAmount' + i;
        intlInput.placeholder = '0';
        intlInput.addEventListener('input', function () {
            validateInput(this);
            calculateTotals();
        });
        intlCell.appendChild(intlInput);

        const taxCell = document.createElement('td');
        taxCell.dataset.label = 'Apply Tax';
        const taxInput = document.createElement('input');
        taxInput.type = 'checkbox';
        taxInput.id = 'applyTax' + i;
        taxInput.name = 'applyTax' + i;
        taxCell.appendChild(taxInput);

        row.append(noCell, dueCell, localCell, intlCell, taxCell);
        tableBody.appendChild(row);
    }

    calculateTotals();
}

function calculateTotals() {
    let totalLocal = 0;
    let totalInternational = 0;

    document.querySelectorAll('input[id^="localAmount"]').forEach(input => {
        totalLocal += parseFloat(input.value || 0);
    });
    document.querySelectorAll('input[id^="internationalAmount"]').forEach(input => {
        totalInternational += parseFloat(input.value || 0);
    });

    document.getElementById('totalLocalAmount').textContent = 'Rs. ' + totalLocal.toFixed(2);
    document.getElementById('totalInternationalAmount').textContent = totalInternational.toFixed(2);

    const localFee = parseFloat(document.getElementById('localFee').value || 0);
    const internationalFee = parseFloat(document.getElementById('internationalFee').value || 0);
    document.getElementById('requiredLocalAmount').textContent = 'Rs. ' + localFee.toFixed(2);
    document.getElementById('requiredInternationalAmount').textContent = internationalFee.toFixed(2);

    validateInstallmentTotals(totalLocal, totalInternational, localFee, internationalFee);
}

function setValidationMessage(lines) {
    const validationMessage = document.getElementById('validationMessage');
    validationMessage.replaceChildren();
    lines.forEach(line => {
        const p = document.createElement('p');
        p.className = 'mb-1';
        p.textContent = line;
        validationMessage.appendChild(p);
    });
}

function validateInstallmentTotals(totalLocal, totalInternational, requiredLocal, requiredInternational) {
    const validationRow = document.getElementById('validationRow');
    const autoCompleteRow = document.getElementById('autoCompleteRow');
    const validationMessage = document.getElementById('validationMessage');

    const localMatch = Math.abs(totalLocal - requiredLocal) <= 0.01;
    const internationalMatch = Math.abs(totalInternational - requiredInternational) <= 0.01;

    if (localMatch && internationalMatch) {
        validationRow.hidden = true;
        autoCompleteRow.hidden = true;
        return;
    }

    validationRow.hidden = false;
    validationMessage.className = 'alert alert-warning mb-0';
    const lines = [];

    if (!localMatch) {
        const localDiff = requiredLocal - totalLocal;
        lines.push('Local amounts mismatch: Total (Rs. ' + totalLocal.toFixed(2) + ') vs Required (Rs. ' + requiredLocal.toFixed(2) + ')');
        lines.push(localDiff > 0
            ? 'Remaining: Rs. ' + localDiff.toFixed(2)
            : 'Excess: Rs. ' + Math.abs(localDiff).toFixed(2));
    }
    if (!internationalMatch) {
        const internationalDiff = requiredInternational - totalInternational;
        lines.push('International amounts mismatch: Total (' + totalInternational.toFixed(2) + ') vs Required (' + requiredInternational.toFixed(2) + ')');
        lines.push(internationalDiff > 0
            ? 'Remaining: ' + internationalDiff.toFixed(2)
            : 'Excess: ' + Math.abs(internationalDiff).toFixed(2));
    }
    setValidationMessage(lines);

    const localDiff = requiredLocal - totalLocal;
    const internationalDiff = requiredInternational - totalInternational;
    const showAuto = (localDiff > 0 && localDiff < requiredLocal * 0.1) || (internationalDiff > 0 && internationalDiff < requiredInternational * 0.1);
    autoCompleteRow.hidden = !showAuto;
}

function autoCompleteRemaining() {
    const localFee = parseFloat(document.getElementById('localFee').value || 0);
    const internationalFee = parseFloat(document.getElementById('internationalFee').value || 0);
    const localInputs = document.querySelectorAll('input[id^="localAmount"]');
    const internationalInputs = document.querySelectorAll('input[id^="internationalAmount"]');

    let totalLocal = 0;
    let totalInternational = 0;
    localInputs.forEach(input => { totalLocal += parseFloat(input.value || 0); });
    internationalInputs.forEach(input => { totalInternational += parseFloat(input.value || 0); });

    const localDiff = localFee - totalLocal;
    const internationalDiff = internationalFee - totalInternational;

    let lastLocalInput = Array.from(localInputs).reverse().find(input => parseFloat(input.value || 0) > 0) || localInputs[0];
    let lastInternationalInput = Array.from(internationalInputs).reverse().find(input => parseFloat(input.value || 0) > 0) || internationalInputs[0];

    if (lastLocalInput && localDiff > 0) {
        lastLocalInput.value = (parseFloat(lastLocalInput.value || 0) + localDiff).toFixed(2);
    }
    if (lastInternationalInput && internationalDiff > 0) {
        lastInternationalInput.value = (parseFloat(lastInternationalInput.value || 0) + internationalDiff).toFixed(2);
    }

    calculateTotals();
}

function updateInternationalHeader(currency) {
    const header = document.getElementById('internationalHeader');
    const display = document.getElementById('currencyDisplay');
    const hidden = document.getElementById('currency');
    const label = currency || 'LKR';
    if (header) header.textContent = 'International (' + label + ')';
    if (display) display.textContent = label;
    if (hidden) hidden.value = label;
}

function autofillFromIntake() {
    const courseId = $('#course').val();
    const location = $('#location').val();
    const intakeId = $('#intake').val();
    if (!courseId || !location || !intakeId) return;

    $.ajax({
        url: '{{ route("get.intake.fees") }}',
        type: 'POST',
        data: {
            _token: $('input[name="_token"]').val(),
            course_id: courseId,
            location: location,
            intake_id: intakeId
        },
        success: function (response) {
            if (response.success) {
                $('#registrationFee').val(response.registration_fee);
                $('#localFee').val(response.course_fee);
                if (response.franchise_payment !== null) {
                    $('#internationalFee').val(response.franchise_payment);
                    updateInternationalHeader(response.franchise_payment_currency);
                }
                $('#ssclTax').val(response.sscl_tax);
                $('#bankCharges').val(response.bank_charges);
                calculateTotals();
            } else {
                showAutofillToast(response.message || 'No intake data found for autofill.');
            }
        },
        error: function (xhr) {
            let msg = 'No intake data found for autofill.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            showAutofillToast(msg);
        }
    });
}

$('#intake').on('change', autofillFromIntake);

$('#course').on('change', function () {
    const courseSelect = document.getElementById('course');
    const intakeSelect = document.getElementById('intake');
    intakeSelect.innerHTML = '';
    intakeSelect.appendChild(new Option('Select Intake...', '', true, true));
    intakeSelect.disabled = true;
    syncCustomSelect(intakeSelect);

    $('#registrationFee, #localFee, #internationalFee, #ssclTax, #bankCharges').val('');

    if ($('#course').val() && $('#location').val()) {
        $.ajax({
            url: '{{ route("intakes.byCourse") }}',
            method: 'POST',
            data: {
                course_id: $('#course').val(),
                location: $('#location').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                intakeSelect.innerHTML = '';
                intakeSelect.appendChild(new Option('Select Intake...', '', true, true));
                if (response.success && Array.isArray(response.data) && response.data.length > 0) {
                    response.data.forEach(function (intake) {
                        const label = intake.batch ? String(intake.batch) : ('Batch ' + intake.intake_id);
                        intakeSelect.appendChild(new Option(label, intake.intake_id));
                    });
                    intakeSelect.disabled = false;
                } else {
                    intakeSelect.appendChild(new Option('No intakes available', '', true, true));
                    intakeSelect.disabled = true;
                }
                syncCustomSelect(courseSelect);
                syncCustomSelect(intakeSelect);
            },
            error: function () {
                intakeSelect.innerHTML = '';
                intakeSelect.appendChild(new Option('Error loading intakes', '', true, true));
                intakeSelect.disabled = true;
                syncCustomSelect(intakeSelect);
                showAutofillToast('Failed to load intakes. Please try again.');
            }
        });
    }
});

function showAutofillToast(message) {
    if (!message) return;
    document.querySelectorAll('.toast-autofill').forEach(el => el.remove());

    const toast = document.createElement('div');
    toast.className = 'toast toast-autofill align-items-center text-white bg-warning border-0 position-fixed bottom-0 end-0 m-3 payment-plan-toast';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    const flex = document.createElement('div');
    flex.className = 'd-flex';
    const body = document.createElement('div');
    body.className = 'toast-body';
    body.textContent = String(message);
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close btn-close-white me-2 m-auto';
    close.setAttribute('data-bs-dismiss', 'toast');
    close.setAttribute('aria-label', 'Close');
    flex.append(body, close);
    toast.appendChild(flex);
    document.body.appendChild(toast);
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 }).show();
}

$('#paymentPlanForm').on('submit', function (e) {
    e.preventDefault();

    const installments = [];
    document.querySelectorAll('#installmentsTableBody tr').forEach((row, index) => {
        const dueDateInput = row.querySelector('input[type="date"]');
        const localAmountInput = row.querySelector('input[id^="localAmount"]');
        const internationalAmountInput = row.querySelector('input[id^="internationalAmount"]');
        const applyTaxInput = row.querySelector('input[type="checkbox"]');
        if (!dueDateInput || !localAmountInput || !internationalAmountInput) return;

        const localAmount = parseFloat(localAmountInput.value || '0') || 0;
        const internationalAmount = parseFloat(internationalAmountInput.value || '0') || 0;
        if (localAmount > 0 || internationalAmount > 0) {
            installments.push({
                installment_number: index + 1,
                due_date: dueDateInput.value || '',
                local_amount: localAmount,
                international_amount: internationalAmount,
                apply_tax: applyTaxInput ? applyTaxInput.checked : false
            });
        }
    });

    if ($('#franchisePaymentYes').is(':checked') && installments.length > 0) {
        const localFee = parseFloat($('#localFee').val() || '0');
        const internationalFee = parseFloat($('#internationalFee').val() || '0');
        let totalLocalAmount = 0;
        let totalInternationalAmount = 0;
        installments.forEach(function (installment) {
            totalLocalAmount += parseFloat(installment.local_amount || 0);
            totalInternationalAmount += parseFloat(installment.international_amount || 0);
        });

        const errors = [];
        if (Math.abs(totalLocalAmount - localFee) > 0.01) {
            errors.push('The sum of local installment amounts (Rs. ' + totalLocalAmount.toFixed(2) + ') must equal the local course fee (Rs. ' + localFee.toFixed(2) + ').');
        }
        if (Math.abs(totalInternationalAmount - internationalFee) > 0.01) {
            errors.push('The sum of international installment amounts (' + totalInternationalAmount.toFixed(2) + ') must equal the franchise payment amount (' + internationalFee.toFixed(2) + ').');
        }
        if (errors.length > 0) {
            showAutofillToast(errors.join(' '));
            return false;
        }
    }

    this.querySelectorAll('input[name="installments"]').forEach(el => el.remove());
    const installmentsInput = document.createElement('input');
    installmentsInput.type = 'hidden';
    installmentsInput.name = 'installments';
    installmentsInput.value = JSON.stringify(installments);
    this.appendChild(installmentsInput);
    this.submit();
});

window.addEventListener('DOMContentLoaded', function () {
    const $form = $('#paymentPlanForm');
    const $location = $('#location');
    const $course = $('#course');
    const $submitBtn = $('#submitBtn');

    function toggleFormFields() {
        const locationSelected = $location.val() !== '' && $location.val() !== null;
        if (!locationSelected) {
            $form.find('input, select, button, textarea').not('#location').prop('disabled', true);
            $submitBtn.prop('disabled', true);
        } else {
            $course.prop('disabled', false);
            $form.find('input:not([readonly]), textarea').not('#location, #course, #intake').prop('disabled', false);
            $form.find('button').prop('disabled', false);
            $form.find('input[type="radio"], input[type="checkbox"]').prop('disabled', false);
            $submitBtn.prop('disabled', false);
        }
        $location.prop('disabled', false);
        syncCustomSelect(document.getElementById('location'));
        syncCustomSelect(document.getElementById('course'));
        syncCustomSelect(document.getElementById('intake'));
    }

    toggleAmountField();
    toggleDiscountField();
    updateInternationalHeader(document.getElementById('currency')?.value || 'LKR');
    toggleFormFields();
});
</script>

<style nonce="{{ $cspNonce }}">
#payment-plan-create,
#payment-plan-create .card,
#payment-plan-create .card-body {
    min-width: 0;
    max-width: 100%;
    overflow: visible;
    height: auto;
    transform: none !important;
}
body:has(#payment-plan-create) .body-wrapper > .container-fluid {
    overflow: visible;
}
#payment-plan-create [class*="col-"] {
    min-width: 0;
}
#payment-plan-create h2 {
    overflow-wrap: anywhere;
}
#payment-plan-create .form-select,
#payment-plan-create .nebula-select,
#payment-plan-create .nebula-select-toggle {
    width: 100%;
    max-width: 100%;
}
#payment-plan-create .form-control {
    max-width: 100%;
}
#payment-plan-create .input-group {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    width: 100%;
}
#payment-plan-create .input-group > .form-control {
    flex: 1 1 auto;
    width: 1%;
    min-width: 0;
    max-width: none;
}
#payment-plan-create .input-group-text {
    flex: 0 0 auto;
    white-space: nowrap;
}
#payment-plan-create .card {
    transition: box-shadow 0.2s;
}
#payment-plan-create .card:hover {
    transform: none !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
}
.payment-plan-radios {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1rem;
    align-items: center;
}
.payment-plan-installments-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.payment-plan-installments {
    min-width: 640px;
}
.payment-plan-toast {
    max-width: min(360px, calc(100vw - 1.5rem));
    z-index: 1080;
}
@media (max-width: 767.98px) {
    #payment-plan-create h2 {
        font-size: 1.25rem;
        text-align: center;
    }
    #payment-plan-create .card-body {
        padding: 1rem 0.75rem;
    }
    #payment-plan-create .form-control,
    #payment-plan-create .form-select,
    #payment-plan-create .nebula-select-toggle {
        font-size: 16px;
    }
    #payment-plan-create .col-form-label {
        padding-bottom: 0.15rem;
    }
    #payment-plan-create .d-flex.flex-wrap > .form-control {
        min-width: 0;
        flex: 1 1 12rem;
    }
    #payment-plan-create #submitBtn,
    #payment-plan-create #autoCompleteBtn {
        width: 100%;
    }
    #payment-plan-create #addRowsBtn {
        width: auto;
        flex: 0 0 auto;
    }
    .payment-plan-installments thead {
        display: none;
    }
    .payment-plan-installments,
    .payment-plan-installments tbody,
    .payment-plan-installments tfoot,
    .payment-plan-installments tr,
    .payment-plan-installments td {
        display: block;
        width: 100%;
        min-width: 0;
    }
    .payment-plan-installments tr {
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
    }
    .payment-plan-installments td {
        text-align: left !important;
        padding: 0.4rem 0;
    }
    .payment-plan-installments td[data-label]::before {
        content: attr(data-label);
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 2px;
        text-transform: uppercase;
    }
}
</style>
@endsection
