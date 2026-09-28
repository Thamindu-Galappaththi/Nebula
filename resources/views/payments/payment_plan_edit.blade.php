@extends('inc.app')
@section('title','Edit Payment Plan')
@section('content')

<div id="payment-plan-edit" class="container-fluid px-2 px-md-3">
    @php
        $planId = is_scalar($plan->id ?? null) ? (string) $plan->id : '0';
        $planCourseId = is_scalar($plan->course_id ?? null) ? (string) $plan->course_id : '';
        $planIntakeId = is_scalar($plan->intake_id ?? null) ? (string) $plan->intake_id : '';
        $planLocation = is_scalar($plan->location ?? null) ? (string) $plan->location : '';
        $planRegistrationFee = is_scalar($plan->registration_fee ?? null) ? (string) $plan->registration_fee : '';
        $planLocalFee = is_scalar($plan->local_fee ?? null) ? (string) $plan->local_fee : '';
        $planInternationalFee = is_scalar($plan->international_fee ?? null) ? (string) $plan->international_fee : '';
        $planInternationalCurrency = is_scalar($plan->international_currency ?? null) ? (string) $plan->international_currency : '';
        $planSsclTax = is_scalar($plan->sscl_tax ?? null) ? (string) $plan->sscl_tax : '';
        $planBankCharges = is_scalar($plan->bank_charges ?? null) ? (string) $plan->bank_charges : '';
        $planDiscount = is_scalar($plan->discount ?? null) ? (string) $plan->discount : '';
        $safeCourses = collect($courses ?? [])->map(function ($course) {
            return [
                'id' => is_scalar($course->course_id ?? null) ? (string) $course->course_id : '',
                'name' => is_scalar($course->course_name ?? null) ? (string) $course->course_name : '',
            ];
        });
        $safeIntakes = collect($intakes ?? [])->map(function ($intake) {
            return [
                'id' => is_scalar($intake->intake_id ?? null) ? (string) $intake->intake_id : '',
                'batch' => is_scalar($intake->batch ?? null) ? (string) $intake->batch : '',
            ];
        });
        $campusLabel = function ($loc) {
            return in_array($loc, ['Welisara', 'Moratuwa', 'Peradeniya'], true)
                ? 'Nebula Institute of Technology - ' . $loc
                : ($loc ?: '—');
        };
        $installmentRows = collect($installments ?? [])->values();
    @endphp
    @php
        $renderValue = function ($value) {
            if (is_scalar($value) || $value === null) {
                return (string) $value;
            }

            if ($value instanceof \Stringable) {
                return (string) $value;
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };
    @endphp
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Edit Payment Plan</h2>

            {{-- Validation errors --}}
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $renderValue($error) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('payment.plan.update', $planId) }}">
                @csrf
                @method('PUT')

                {{-- Location --}}
                <div class="mb-3">
                    <label class="form-label" for="edit_location">Location</label>
                    <select name="location" id="edit_location" class="form-select" required>
                        @foreach(['Welisara','Moratuwa','Peradeniya'] as $loc)
                            <option value="{{ $loc }}" @selected($planLocation == $loc)>{{ $campusLabel($loc) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Course --}}
                <div class="mb-3">
                    <label class="form-label" for="edit_course_id">Course</label>
                    <select name="course_id" id="edit_course_id" class="form-select" required>
                        @foreach($safeCourses as $courseOption)
                            <option value="{{ $courseOption['id'] }}" @selected($planCourseId == $courseOption['id'])>{{ $courseOption['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Intake --}}
                <div class="mb-3">
                    <label class="form-label" for="edit_intake_id">Intake</label>
                    <select name="intake_id" id="edit_intake_id" class="form-select">
                        <option value="">None</option>
                        @foreach($safeIntakes as $intakeOption)
                            <option value="{{ $intakeOption['id'] }}" @selected($planIntakeId == $intakeOption['id'])>
                                {{ $intakeOption['batch'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Registration Fee --}}
                <div class="mb-3">
                    <label class="form-label" for="edit_registration_fee">Registration Fee</label>
                    <input type="number" id="edit_registration_fee" name="registration_fee" class="form-control" value="{{ $planRegistrationFee }}" required min="0" step="0.01">
                </div>

                {{-- Local Fee --}}
                <div class="mb-3">
                    <label class="form-label" for="localFee">Local Fee</label>
                    <input type="number" id="localFee" name="local_fee" class="form-control" value="{{ $planLocalFee }}" required min="0" step="0.01">
                </div>

                {{-- Franchise Fee --}}
                <div class="mb-3">
                    <label class="form-label" for="internationalFee">Franchise Fee</label>
                    <input type="number" id="internationalFee" name="international_fee" class="form-control" value="{{ $planInternationalFee }}" required min="0" step="0.01">
                </div>

                {{-- Currency --}}
                <div class="mb-3">
                    <label class="form-label" for="edit_international_currency">Currency</label>
                    <input type="text" id="edit_international_currency" name="international_currency" class="form-control" value="{{ $planInternationalCurrency }}" required>
                </div>

                {{-- SSCL Tax --}}
                <div class="mb-3">
                    <label class="form-label" for="edit_sscl_tax">SSCL Tax</label>
                    <input type="number" id="edit_sscl_tax" name="sscl_tax" class="form-control" value="{{ $planSsclTax }}" min="0" step="0.01">
                </div>

                {{-- Bank Charges --}}
                <div class="mb-3">
                    <label class="form-label" for="edit_bank_charges">Bank Charges</label>
                    <input type="number" id="edit_bank_charges" name="bank_charges" class="form-control" value="{{ $planBankCharges }}" min="0" step="0.01">
                </div>

                {{-- Apply Discount --}}
                <div class="mb-3 form-check">
                    <input type="checkbox" name="apply_discount" value="1" class="form-check-input" id="applyDiscountCheckbox"
                           {{ $plan->apply_discount ? 'checked' : '' }}>
                    <label class="form-check-label" for="applyDiscountCheckbox">Apply Full Payment Discount</label>
                </div>

                {{-- Full Payment Discount --}}
                <div class="mb-3">
                    <label class="form-label" for="edit_discount">Full Payment Discount (%)</label>
                    <input type="number" class="form-control" id="edit_discount" name="discount" value="{{ $planDiscount }}" min="0" step="0.01">
                </div>

                {{-- Installment Plan --}}
                <div class="mb-3 form-check">
                    <input type="checkbox" name="installment_plan" value="1" class="form-check-input" id="installmentPlanCheckbox"
                           {{ $plan->installment_plan ? 'checked' : '' }}>
                    <label class="form-check-label" for="installmentPlanCheckbox">Enable Installment Plan</label>
                </div>

                {{-- Installments --}}
                <div class="mb-3">
                    <label class="form-label">Installments</label>

                    <div class="payment-plan-installments-wrap">
                        <table class="table table-bordered bg-white mb-0 payment-plan-installments">
                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>Due Date</th>
                                    <th>Local (LKR)</th>
                                    <th>International ({{ $planInternationalCurrency }})</th>
                                    <th>Tax?</th>
                                </tr>
                            </thead>
                            <tbody id="installmentsTableBody">
                                @foreach($installmentRows as $i => $inst)
                                    @php
                                        $dueDate = is_scalar($inst['due_date'] ?? null) ? (string) ($inst['due_date'] ?? '') : '';
                                        $localAmount = is_scalar($inst['local_amount'] ?? null) ? (string) ($inst['local_amount'] ?? '') : '';
                                        $internationalAmount = is_scalar($inst['international_amount'] ?? null) ? (string) ($inst['international_amount'] ?? '') : '';
                                    @endphp
                                    <tr class="installment-row">
                                        <td data-label="No.">{{ $i+1 }}</td>
                                        <td data-label="Due Date"><input type="date" name="installments[{{ $i }}][due_date]" value="{{ $dueDate }}" class="form-control"></td>
                                        <td data-label="Local (LKR)"><input type="number" step="0.01" name="installments[{{ $i }}][local_amount]" value="{{ $localAmount }}" class="form-control"></td>
                                        <td data-label="International ({{ $planInternationalCurrency }})"><input type="number" step="0.01" name="installments[{{ $i }}][international_amount]" value="{{ $internationalAmount }}" class="form-control"></td>
                                        <td class="text-center" data-label="Tax?">
                                            <input type="checkbox" name="installments[{{ $i }}][apply_tax]" value="1" @checked(!empty($inst['apply_tax']))>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div id="installmentsEmpty" class="text-center text-muted py-3{{ $installmentRows->isEmpty() ? '' : ' d-none' }}">
                        No installments defined
                    </div>

                    <div class="small text-muted my-2" id="installmentTotalsNote">
                        Installment totals will sync to the fee fields before save.
                    </div>

                    <div class="d-flex flex-wrap gap-2 payment-plan-installment-actions">
                        <button type="button" class="btn btn-sm btn-primary btn-add-installment-row">+ Add Row</button>
                        <button type="button" class="btn btn-sm btn-danger btn-remove-last-row">Remove Last</button>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 payment-plan-edit-actions">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('payment.plan.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- JS for dynamic rows --}}
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-add-installment-row')) {
        addInstallmentRow();
    } else if (e.target.closest('.btn-remove-last-row')) {
        removeLastRow();
    }
});

function installmentRowHtml(index) {
    const currency = @json($planInternationalCurrency);
    return `
        <td data-label="No.">${index + 1}</td>
        <td data-label="Due Date"><input type="date" name="installments[${index}][due_date]" class="form-control"></td>
        <td data-label="Local (LKR)"><input type="number" step="0.01" name="installments[${index}][local_amount]" class="form-control"></td>
        <td data-label="International (${currency})"><input type="number" step="0.01" name="installments[${index}][international_amount]" class="form-control"></td>
        <td class="text-center" data-label="Tax?"><input type="checkbox" name="installments[${index}][apply_tax]" value="1"></td>
    `;
}

function installmentRows() {
    return document.querySelectorAll('#installmentsTableBody tr.installment-row');
}

function toggleInstallmentsEmpty() {
    const empty = document.getElementById('installmentsEmpty');
    if (!empty) return;
    empty.classList.toggle('d-none', installmentRows().length > 0);
}

function addInstallmentRow() {
    const tbody = document.getElementById('installmentsTableBody');
    const index = installmentRows().length;
    const row = tbody.insertRow();
    row.className = 'installment-row';
    row.innerHTML = installmentRowHtml(index);
    toggleInstallmentsEmpty();
    syncFeeFieldsFromInstallments();
}

function removeLastRow() {
    const rows = installmentRows();
    if (rows.length > 0) {
        rows[rows.length - 1].remove();
    }
    toggleInstallmentsEmpty();
    syncFeeFieldsFromInstallments();
}

function syncFeeFieldsFromInstallments() {
    const installmentPlanEnabled = document.getElementById('installmentPlanCheckbox')?.checked;
    if (!installmentPlanEnabled) {
        return;
    }

    let totalLocal = 0;
    let totalInternational = 0;

    document.querySelectorAll('#installmentsTableBody input[name$="[local_amount]"]').forEach(function (input) {
        totalLocal += parseFloat(input.value || '0') || 0;
    });

    document.querySelectorAll('#installmentsTableBody input[name$="[international_amount]"]').forEach(function (input) {
        totalInternational += parseFloat(input.value || '0') || 0;
    });

    const localFeeInput = document.getElementById('localFee');
    const internationalFeeInput = document.getElementById('internationalFee');

    if (localFeeInput) {
        localFeeInput.value = totalLocal.toFixed(2);
    }

    if (internationalFeeInput) {
        internationalFeeInput.value = totalInternational.toFixed(2);
    }
}

document.addEventListener('input', function (e) {
    if (e.target.closest('#installmentsTableBody')) {
        syncFeeFieldsFromInstallments();
    }
});

document.addEventListener('change', function (e) {
    if (e.target.id === 'installmentPlanCheckbox') {
        syncFeeFieldsFromInstallments();
    }
});

window.addEventListener('DOMContentLoaded', function () {
    syncFeeFieldsFromInstallments();
});
</script>

<style nonce="{{ $cspNonce ?? '' }}">
#payment-plan-edit,
#payment-plan-edit .card,
#payment-plan-edit .card-body {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    overflow: visible;
}
body:has(#payment-plan-edit) .body-wrapper > .container-fluid {
    max-width: 100%;
    overflow-x: hidden;
}
#payment-plan-edit h2 {
    overflow-wrap: break-word;
}
#payment-plan-edit .form-select,
#payment-plan-edit .form-control,
#payment-plan-edit .nebula-select,
#payment-plan-edit .nebula-select-toggle {
    width: 100%;
    max-width: 100%;
}
.payment-plan-installments-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.payment-plan-installments {
    min-width: 640px;
}
.payment-plan-edit-actions .btn {
    min-width: 8rem;
}
@media (max-width: 767.98px) {
    #payment-plan-edit h2 {
        font-size: 1.25rem;
    }
    #payment-plan-edit .card-body {
        padding: 1rem 0.75rem;
    }
    #payment-plan-edit .form-control,
    #payment-plan-edit .form-select,
    #payment-plan-edit .nebula-select-toggle {
        font-size: 16px;
    }
    .payment-plan-installments-wrap {
        overflow: visible;
    }
    .payment-plan-installments {
        min-width: 0;
        border: 0;
    }
    .payment-plan-installments thead {
        display: none;
    }
    .payment-plan-installments,
    .payment-plan-installments tbody,
    .payment-plan-installments tr,
    .payment-plan-installments td {
        display: block;
        width: 100%;
        max-width: 100%;
        box-shadow: none !important;
        white-space: normal;
    }
    .payment-plan-installments tr.installment-row {
        margin-bottom: 0.85rem;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 0.75rem 0.9rem;
        background: #fff;
    }
    .payment-plan-installments td {
        text-align: left !important;
        border: 0 !important;
        border-bottom: 1px solid #f1f3f5 !important;
        padding: 0.5rem 0;
        overflow-wrap: break-word;
        word-break: normal;
    }
    .payment-plan-installments td:last-child {
        border-bottom: 0 !important;
    }
    .payment-plan-installments td[data-label]::before {
        content: attr(data-label);
        display: block;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 0.2rem;
    }
    .payment-plan-installment-actions,
    .payment-plan-edit-actions {
        flex-direction: column;
    }
    .payment-plan-installment-actions .btn,
    .payment-plan-edit-actions .btn {
        width: 100%;
        margin: 0;
    }
}
</style>

@endsection
