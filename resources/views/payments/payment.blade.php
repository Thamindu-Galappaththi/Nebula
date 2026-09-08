@extends('inc.app')

@section('title', 'NEBULA | Student Payment Plan')

@section('content')

<style nonce="{{ $cspNonce }}">
/* Toast Notification Styles */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.toast {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-bottom: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
    border-left: 4px solid;
    min-width: 300px;
}

.toast.show {
    transform: translateX(0);
}

.toast.success {
    border-left-color: #10b981;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.toast.error {
    border-left-color: #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.toast.warning {
    border-left-color: #f59e0b;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
}

.toast.info {
    border-left-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.toast-icon {
    width: 24px;
    height: 24px;
    margin-right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.toast.success .toast-icon {
    background: #10b981;
    color: white;
}

.toast.error .toast-icon {
    background: #ef4444;
    color: white;
}

.toast.warning .toast-icon {
    background: #f59e0b;
    color: white;
}

.toast.info .toast-icon {
    background: #3b82f6;
    color: white;
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    margin-bottom: 4px;
    color: #1f2937;
}

.toast-message {
    color: #6b7280;
    font-size: 14px;
}

.toast-close {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
    margin-left: 8px;
}

.toast-close:hover {
    color: #374151;
}

/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }

    #printableSlip, #printableSlip * {
        visibility: visible;
    }

    #printableSlip {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 20px;
    }

    .payment-slip-template {
        max-width: none !important;
        margin: 0 !important;
        border: none !important;
    }
}

/* Payment Slip Template Styles */
.payment-slip-template {
    background: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.payment-slip-template h2 {
    color: #1f2937;
    font-weight: bold;
}

.payment-slip-template h3 {
    color: #374151;
    font-weight: 600;
}

.payment-slip-template p {
    margin: 8px 0;
    line-height: 1.5;
}

.payment-slip-template table {
    border: 1px solid #ddd;
}

.payment-slip-template th,
.payment-slip-template td {
    padding: 12px;
    border: 1px solid #ddd;
}

.payment-slip-template th {
    background-color: #f8f9fa;
    font-weight: 600;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.toast.slide-in {
    animation: slideIn 0.3s ease-out;
}

.toast.slide-out {
    animation: slideOut 0.3s ease-in;
}

.lds-ring {
    display: inline-block;
    position: relative;
    width: 80px;
    height: 80px;
}
.lds-ring div {
    box-sizing: border-box;
    display: block;
    position: absolute;
    width: 64px;
    height: 64px;
    margin: 8px;
    border: 8px solid #007bff;
    border-radius: 50%;
    animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    border-color: #007bff transparent transparent transparent;
}
.lds-ring div:nth-child(1) {
    animation-delay: -0.45s;
}
.lds-ring div:nth-child(2) {
    animation-delay: -0.3s;
}
.lds-ring div:nth-child(3) {
    animation-delay: -0.15s;
}
@keyframes lds-ring {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
#spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

  .slt-formula { display:inline-flex; align-items:center; gap:.5rem; }
  .slt-formula .fraction { display:inline-flex; flex-direction:column; align-items:center; line-height:1; font-variant-numeric: tabular-nums; }
  .slt-formula .top,.slt-formula .bottom { display:block; }
  .slt-formula .bar { display:block; width:100%; border-top:1px solid currentColor; margin:.15rem 0; }
  .slt-formula .times { white-space:nowrap; }

  .slt-formula {
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 6px;
  background: #f5f5f5;
  border-radius: 6px;
  font-size: 14px;
}

.slt-formula .fraction {
  display: inline-block;
  text-align: center;
  vertical-align: middle;
  line-height: 1.2;
}

.slt-formula .fraction .top {
  display: block;
}

.slt-formula .fraction .bar {
  border-top: 1px solid #000;
  display: block;
  width: 100%;
  margin: 2px 0;
}

.slt-formula .fraction .bottom {
  display: block;
}
.math-formula {
  text-align: center;
  font-size: 18px;
  font-weight: bold;
  margin: 15px 0;
}

.math-formula .fraction {
  display: inline-block;
  text-align: center;
  vertical-align: middle;
  line-height: 1.2;
  margin: 0 4px;
}

.math-formula .fraction .top {
  display: block;
}

.math-formula .fraction .bar {
  border-top: 1px solid #000;
  display: block;
  width: 100%;
  margin: 2px 0;
}

.math-formula .fraction .bottom {
  display: block;
}

.math-formula .times {
  margin-left: 6px;
}

/* Keep payment container height tied to visible tab content */
#payment-page-card,
#paymentTabContent {
    min-height: 0 !important;
    height: auto !important;
}

/* Prevent tab panes from becoming invisible when only .active is applied */
#paymentTabContent .tab-pane.fade {
    transition: none;
}

#paymentTabContent .tab-pane.fade:not(.show) {
    opacity: 1;
}
</style>


<div class="container-fluid">
    <div class="card" id="payment-page-card">
        <div class="card-body">
            <h2 class="text-center mb-4">Student Payment Plan</h2>
            <hr>

            <!-- Spinner and Toast containers -->
            <div id="spinner-overlay" style="display:none;"><div class="lds-ring"><div></div><div></div><div></div><div></div></div></div>
            <div id="toastContainer" aria-live="polite" aria-atomic="true" style="position: fixed; top: 10px; right: 10px; z-index: 1000;"></div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs" id="paymentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active bg-primary text-white" id="payment-plans-tab" data-bs-toggle="tab" data-bs-target="#payment-plans" type="button" role="tab" aria-controls="payment-plans" aria-selected="true">
                        <i class="ti ti-calendar me-2"></i>Payment Plans
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="generate-slips-tab" data-bs-toggle="tab" data-bs-target="#generate-slips" type="button" role="tab" aria-controls="generate-slips" aria-selected="false">
                        <i class="ti ti-receipt me-2"></i>Generate Slips
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="update-records-tab" data-bs-toggle="tab" data-bs-target="#update-records" type="button" role="tab" aria-controls="update-records" aria-selected="false">
                        <i class="ti ti-edit me-2"></i>Update Records
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="slt-loan-tab" data-bs-toggle="tab" data-bs-target="#slt-loan" type="button" role="tab" aria-controls="slt-loan" aria-selected="false">
                        <i class="ti ti-building-bank me-2"></i>SLT Loan Receivables
                    </button>
                </li>
                <!-- <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payment-summary-tab" data-bs-toggle="tab" data-bs-target="#payment-summary" type="button" role="tab" aria-controls="payment-summary" aria-selected="false">
                        <i class="ti ti-chart-pie me-2"></i>Payment Summary
                    </button>
                </li> -->
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="paymentTabContent">
                <!-- Payment Plans Tab -->
                <div class="tab-pane fade show active" id="payment-plans" role="tabpanel" aria-labelledby="payment-plans-tab">
                    <div class="mt-4">
                        <!-- Filters -->
                        <div class="mb-4">
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Student NIC <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="plan-student-nic" placeholder="Enter Student NIC" required>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Course <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-select filter-param" id="plan-course" name="course_id" required>
                                        <option selected disabled value="">Select a Course</option>
                                        @foreach($courses as $course)
                                            <option value="{{ $course->course_id }}">{{ $course->course_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>

                        <!-- Payment Plan Creation Form -->
                        <div class="mt-4" id="paymentPlanFormSection">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Create Payment Plan</h5>
                                </div>
                                <div class="card-body">
                                    <form id="createPaymentPlanForm">
                                        <!-- Student Data Status Indicator -->
                                        <div id="student-data-status" class="alert alert-warning mb-3" style="display: none;">
                                            <i class="ti ti-alert-circle me-2"></i>
                                            <strong>No Student Data Loaded:</strong> Please load student details first before creating a payment plan.
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Student Information</label>
                                                <div class="mb-2">
                                                    <strong>Name:</strong> <span id="student-name-display">-</span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Student ID:</strong> <span id="student-id-display">-</span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Course:</strong> <span id="course-name-display">-</span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Intake:</strong> <span id="intake-name-display">-</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Fee Structure</label>
                                                <div class="mb-2">
                                                    <strong>Local Course Fee:</strong> <span id="course-fee-display">-</span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Registration Fee:</strong> <span id="registration-fee-display">-</span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Franchise Fee:</strong> <span class="franchise-amount-display">-</span>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Total Fee:</strong> <span id="total-amount-display">-</span>
                                                </div>

                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Payment Plan Type <span class="text-danger">*</span></label>
                                                <select class="form-select" id="payment-plan-type" name="payment_plan_type" required>
                                                    <option value="">Select Payment Plan</option>
                                                    <option value="installments">Installments</option>
                                                    <option value="full">Full Payment</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Local Fee Discounts</label>
                                                <div id="discounts-container">
                                                    <div class="discount-item mb-2">
                                                        <select class="form-select discount-select" name="discounts[]">
                                                            <option value="">No Discount</option>
                                                            <!-- Discounts will be loaded dynamically -->
                                                        </select>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-discount-btn">
                                                    <i class="ti ti-plus"></i> Add Another Discount
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Registration Fee Discount</label>
                                                <select class="form-select" id="registration-fee-discount" name="registration_fee_discount">
                                                    <option value="">No Registration Fee Discount</option>
                                                    <!-- Registration fee discounts will be loaded dynamically -->
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold">SLT Loan Applied <span class="text-danger">*</span></label>
                                                <select class="form-select" id="slt-loan-applied" name="slt_loan_applied">
                                                    <option value="no">No SLT Loan</option>
                                                    <option value="yes">Yes - SLT Loan Applied</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold">SLT Loan Amount</label>
                                                <input type="number" class="form-control" id="slt-loan-amount" name="slt_loan_amount" min="0" step="0.01" placeholder="Enter SLT loan amount" disabled>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold">Loan Starts From Installment</label>
                                                <input type="number" class="form-control" id="slt-loan-start-installment" name="slt_loan_start_installment" min="1" step="1" placeholder="e.g. 3" disabled>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold">Loan Taken Years</label>
                                                <input type="number" class="form-control" id="slt-loan-years" name="slt_loan_years" min="1" max="50" step="1" placeholder="e.g. 4" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-4">
    <label class="form-label fw-bold">Final Amount After Discount & Loan</label>
    <div class="input-group">
        <input type="text" class="form-control" id="final-amount" name="final_amount" readonly>
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#finalAmountBreakdownModal">
            View Breakdown
        </button>
    </div>
</div>

                                            <!-- Final Amount Breakdown Modal -->
<div class="modal fade" id="finalAmountBreakdownModal" tabindex="-1" aria-labelledby="breakdownModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="breakdownModalLabel">Final Amount Calculation Breakdown</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="breakdown-modal-body">
        <!-- Steps will be injected here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <label class="form-label fw-bold">Installment Details</label>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered" id="installmentTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Installment #</th>
                                                                <th>Due Date</th>
                                                                <th>Amount</th>
                                                                <th>Discount</th>
                                                                <th>SLT Loan</th>
                                                                <th>Final Amount</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="installmentTableBody">
                                                        </tbody>
                                                    </table>
                                                    <div id="formulaModal" class="modal" style="display:none; position:fixed; top:0; left:0;
                                                        width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1050; align-items:center; justify-content:center;">
                                                    <div style="background:#fff; padding:20px; border-radius:8px; width:500px; max-width:90%;">
                                                        <h4>SLT Loan Formula</h4>
                                                        <div id="formulaExplanation"></div>
                                                        <div style="text-align:right; margin-top:15px;">
                                                        <button type="button" class="btn btn-secondary btn-close-formula-modal">
                                                            Close
                                                        </button>
                                                        </div>
                                                    </div>
                                                    </div>


                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 text-center">
                                                <button type="button" class="btn btn-primary btn-create-payment-plan">
                                                    <i class="ti ti-check me-2"></i>Submit
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-reset-payment-plan">
                                                    <i class="ti ti-refresh me-2"></i>Reset
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- keep your existing section markup -->
                        <!-- Existing Payment Plans Table -->
                        <div class="mt-4" id="existingPaymentPlansSection" style="display:none;">
                        <h4 class="text-center mb-3">Existing Payment Plans</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Student NIC</th>
                                <th>Course</th>
                                <th>Payment Plan Type</th>
                                <th>Total Amount</th>
                                <th>Installments</th>
                                <th>Status</th>
                                <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="existingPaymentPlansTableBody"></tbody>
                            </table>
                        </div>
                        </div>

                    </div>
                </div>

                <!-- Generate Slips Tab -->
                <div class="tab-pane fade" id="generate-slips" role="tabpanel" aria-labelledby="generate-slips-tab">
                    <div class="mt-4">
                        <!-- Filters -->
                        <div class="mb-4">
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Student ID <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="slip-student-id" placeholder="Enter Student ID / NIC" required>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Course <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="slip-course" required>
                                        <option value="" selected disabled>Select Course</option>
                                        @if(isset($courses))
                                            @foreach($courses as $course)
                                                <option value="{{ $course->course_id }}">{{ $course->course_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Payment Type <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="slip-payment-type" required disabled>
    <option value="" selected disabled>Select Payment Type</option>
    <option value="course_fee">Course Fee</option>
    <option value="franchise_fee">Franchise Fee</option>
    <option value="registration_fee">Registration Fee</option>
</select>

                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Payment Effective Date</label>
                                <div class="col-sm-10">
                                    <input type="date" class="form-control" id="payment-effective-date"
                                        value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                                    <small class="form-text text-muted">
                                        Enter the actual date the student made the payment.
                                        Late fees are calculated from this date, not the date you are updating the system.
                                    </small>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center" id="currencyConversionRow" style="display: none;">
                                <label class="col-sm-2 col-form-label fw-bold">Currency Conversion Rate <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <span class="input-group-text">1</span>
                                        <select class="form-select" id="currency-from" style="max-width: 80px;" disabled>
                                           <!-- Removed the Dropdown by Savindu -->
                                        </select>
                                        <span class="input-group-text">=</span>
                                        <input type="number" class="form-control" id="currency-conversion-rate" placeholder="Enter conversion rate (e.g., 320)" step="0.01" min="0" value="320" oninput="recalculateLKRAmounts()">
                                        <span class="input-group-text">LKR</span>
                                    </div>
                                    <small class="form-text text-muted">Enter the current exchange rate to convert franchise fees to LKR</small>
                                </div>
                            </div>
                            <!-- SSCL & Bank Charges (only for Franchise Fee) -->
                            <div id="franchiseChargesRow" style="display:none; margin-top:20px;">

                                <!-- SSCL Tax -->
                                <div class="row mb-3 align-items-center">
                                    <label class="col-sm-2 col-form-label fw-bold">SSCL Tax</label>
                                    <div class="col-sm-10 d-flex">
                                        <select class="form-select me-2" id="sscl-type" style="max-width: 120px;">
                                            <option value="amount" selected>Amount</option>
                                            <option value="percentage">%</option>
                                        </select>
                                        <input type="number" class="form-control" id="sscl-value"
                                            placeholder="Enter SSCL (e.g. 2000 or 5)" step="0.01" min="0" value="0"
                                            oninput="recalculateSSCL()">
                                    </div>
                                </div>

                                <!-- Calculated SSCL in LKR -->
                                <div class="row mb-3 align-items-center">
                                    <label class="col-sm-2 col-form-label fw-bold">SSCL Tax (LKR)</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="sscl-tax-amount" value="0" readonly>
                                    </div>
                                </div>

                                <!-- Bank Charges (still fixed for now) -->
                                <div class="row mb-3 align-items-center">
                                    <label class="col-sm-2 col-form-label fw-bold">Bank Charges (LKR)</label>
                                    <div class="col-sm-10">
                                        <input type="number" class="form-control" id="bank-charges"
                                            placeholder="Enter Bank Charges" step="0.01" min="0" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details Table -->
                        <div class="mt-4" id="paymentDetailsSection" style="display:none;">
                            <h4 class="text-center mb-3">Payment Details</h4>
                            <div id="conversionRateWarning" class="alert alert-warning" style="display: none;">
                                <i class="ti ti-alert-triangle me-2"></i>
                                <strong>Note:</strong> Please enter a currency conversion rate above to see LKR amounts for franchise fee payments.
                            </div>
                            <div id="conversionRateInfo" class="alert alert-info" style="display: none;">
                                <i class="ti ti-info-circle me-2"></i>
                                <strong>Conversion Rate:</strong> <span id="currentConversionRate">320</span> LKR per <span id="currentCurrency">USD</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="paymentDetailsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Select</th>
                                            <th>Installment #</th>
                                            <th>Due Date</th>
                                            <th id="amountHeader">Amount</th>
                                            <th id="lkrAmountHeader" style="display:none;">Amount (LKR)</th>
                                            <th>Late Fee</th>
                                            <!-- <th>Paid Date</th> -->
                                            <th>Status</th>
                                            <!-- <th>Receipt No</th> -->
                                        </tr>
                                    </thead>
                                    <tbody id="paymentDetailsTableBody">
                                        <!-- Payment details will be loaded here -->
                                    </tbody>
                                </table>


                            </div>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-primary" id="generateSlipBtn" disabled>
                                    <i class="ti ti-receipt me-2"></i>Generate Payment Slip
                                </button>
                            </div>
                        </div>

                        <!-- Generated Slip Preview -->
                        <div class="mt-4" id="slipPreviewSection" style="display:none;">
                            <h4 class="text-center mb-3">Payment Slip Preview</h4>
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Student Information</h5>
                                            <p><strong>Student ID:</strong> <span id="slip-student-id-display"></span></p>
                                            <p><strong>Student Name:</strong> <span id="slip-student-name-display"></span></p>
                                            <p><strong>Course:</strong> <span id="slip-course-display"></span></p>
                                            <p><strong>Intake:</strong> <span id="slip-intake-display"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Payment Information</h5>
                                            <p><strong>Payment Type:</strong> <span id="slip-payment-type-display"></span></p>
                                            <p><strong>Installment Amount:</strong> <span id="slip-amount-display"></span></p>

                                            <div id="franchiseAmountsSection" style="display:none;">
                                                <p><strong>SSCL Tax:</strong> <span id="slip-sscl-amount"></span></p>
                                                <p><strong>Bank Charges:</strong> <span id="slip-bank-amount"></span></p>
                                                <p><strong>Total Amount:</strong> <span id="slip-final-amount"></span></p>
                                            </div>


                                            <p><strong>Installment #:</strong> <span id="slip-installment-display"></span></p>
                                            <p><strong>Due Date:</strong> <span id="slip-due-date-display"></span></p>
                                            <p><strong>Date:</strong> <span id="slip-date-display"></span></p>
                                            <p><strong>Receipt No:</strong> <span id="slip-receipt-no-display"></span></p>
                                        </div>
                                    </div>
                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-success me-2" id="printPaymentSlipBtn">
                                            <i class="ti ti-printer me-2"></i>Print Slip
                                        </button>
                                        <button type="button" class="btn btn-info me-2" id="downloadPaymentSlipBtn">
                                            <i class="ti ti-download me-2"></i>Download PDF
                                        </button>

                                        <button type="button" class="btn btn-danger btn-sm" id="delete-slip-btn" style="display:none;">
                                            <i class="ti ti-trash me-2"></i>Delete Slip
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Print-friendly Payment Slip Template -->
                        <div id="printableSlip" style="display:none;">
                            <div class="payment-slip-template" style="max-width: 800px; margin: 0 auto; padding: 20px; border: 2px solid #000; font-family: Arial, sans-serif;">
                                <!-- Header -->
                                <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
                                    <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula Logo" style="height: 60px; margin-bottom: 10px;">
                                    <h2 style="margin: 0; color: #333;">SLTMOBITEL NEBULA INSTITUTE OF TECHNOLOGY</h2>
                                    <p style="margin: 5px 0; font-size: 14px;">Payment Slip</p>
                                    <p style="margin: 5px 0; font-size: 12px;">Generated on: <span id="print-generated-date"></span></p>
                                </div>

                                <!-- Student Information -->
                                <div style="margin-bottom: 30px;">
                                    <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 15px;">Student Information</h3>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div>
                                            <p><strong>Student ID:</strong> <span id="print-student-id"></span></p>
                                            <p><strong>Student Name:</strong> <span id="print-student-name"></span></p>
                                            <p><strong>Course:</strong> <span id="print-course"></span></p>
                                        </div>
                                        <div>
                                            <p><strong>Intake:</strong> <span id="print-intake"></span></p>
                                            <p><strong>Location:</strong> <span id="print-location"></span></p>
                                            <p><strong>Registration Date:</strong> <span id="print-registration-date"></span></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Information -->
                                <div style="margin-bottom: 30px;">
                                    <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 15px;">Payment Information</h3>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div>
                                            <p><strong>Payment Type:</strong> <span id="print-payment-type"></span></p>
                                            <p><strong>Installment #:</strong> <span id="print-installment"></span></p>
                                            <p><strong>Due Date:</strong> <span id="print-due-date"></span></p>
                                        </div>
                                        <div>
                                            <p><strong>Amount:</strong> <span id="print-amount"></span></p>
                                            <p><strong>Receipt No:</strong> <span id="print-receipt-no"></span></p>
                                            <p><strong>Valid Until:</strong> <span id="print-valid-until"></span></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Details Table -->
                                <div style="margin-bottom: 30px;">
                                    <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 15px;">Payment Breakdown</h3>
                                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                        <thead>
                                            <tr style="background-color: #f8f9fa;">
                                                <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Description</th>
                                                <th style="border: 1px solid #ddd; padding: 10px; text-align: right;">Amount (LKR)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td style="border: 1px solid #ddd; padding: 10px;">Course Fee</td>
                                                <td style="border: 1px solid #ddd; padding: 10px; text-align: right;" id="print-course-fee">0.00</td>
                                            </tr>
                                            <tr>
                                                <td style="border: 1px solid #ddd; padding: 10px;">Franchise Fee</td>
                                                <td style="border: 1px solid #ddd; padding: 10px; text-align: right;" id="print-franchise-fee">0.00</td>
                                            </tr>
                                            <tr>
                                                <td style="border: 1px solid #ddd; padding: 10px;">Registration Fee</td>
                                                <td style="border: 1px solid #ddd; padding: 10px; text-align: right;" id="print-registration-fee">0.00</td>
                                            </tr>
                                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                                <td style="border: 1px solid #ddd; padding: 10px;">Total Amount</td>
                                                <td style="border: 1px solid #ddd; padding: 10px; text-align: right;" id="print-total-amount">0.00</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Instructions -->
                                <div style="margin-bottom: 30px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff;">
                                    <h4 style="margin: 0 0 10px 0; color: #007bff;">Payment Instructions</h4>
                                    <ol style="margin: 0; padding-left: 20px;">
                                        <li>Please present this slip when making payment</li>
                                        <li>Payment can be made in cash or bank transfer</li>
                                        <li>Keep this slip for your records</li>
                                        <li>Return the paid slip to the office for record update</li>
                                        <li>This slip is valid for 7 days from the date of issue</li>
                                    </ol>
                                </div>

                                <!-- Footer -->
                                <div style="text-align: center; border-top: 2px solid #000; padding-top: 20px; margin-top: 30px;">
                                    <p style="margin: 5px 0; font-size: 12px;">© 2024 SLTMOBITEL NEBULA INSTITUTE OF TECHNOLOGY. All rights reserved.</p>
                                    <p style="margin: 5px 0; font-size: 10px;">This is a computer-generated document. No signature required.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Records Tab -->
<div class="tab-pane fade" id="update-records" role="tabpanel" aria-labelledby="update-records-tab">
    <div class="mt-4">
        <!-- Filters -->
        <div class="mb-4">
            <div class="row mb-3 align-items-center">
                <label class="col-sm-2 col-form-label fw-bold">Student NIC <span class="text-danger">*</span></label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="update-student-nic" placeholder="Enter Student NIC" required>
                </div>
            </div>
            <div class="row mb-3 align-items-center">
                <label class="col-sm-2 col-form-label fw-bold">Course <span class="text-danger">*</span></label>
                <div class="col-sm-10">
                    <select class="form-select" id="update-course" required>
                        <option value="" selected disabled>Select a Course</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-center">
                    <button type="button" class="btn btn-primary" id="loadPaymentRecordsBtn">
                        <i class="ti ti-search me-2"></i>Load Payment Records
                    </button>
                </div>
            </div>
        </div>

        <!-- Payment Records Table -->
        <div class="mt-4" id="paymentRecordsSection" style="display:none;">
            <h4 class="text-center mb-3">Payment Records</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Payment Type</th>
                            <th>Installment #</th>
                            <th>Amount</th>
                            <th>Late Fee</th>
                            <th>Approved Late Fee</th>
                            <th>Total Fee</th>
                            <th>Remaining</th>
                            <th>Payment Method</th>
                            <th>Payment Date</th>
                            <th>Receipt No</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="paymentRecordsTableBody">
                        <!-- JS will append rows here -->

                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3" id="updateSaveBtnSection" style="display:none;">
                <button type="button" class="btn btn-success" id="updatePaymentRecordsBtn">
                    <i class="ti ti-device-floppy me-2"></i>Update Records
                </button>
            </div>
        </div>
    </div>
</div>

                <!-- SLT Loan Receivables Tab -->
                <div class="tab-pane fade" id="slt-loan" role="tabpanel" aria-labelledby="slt-loan-tab">
                    <div class="mt-4">
                        <form id="slt-loan-receivable-form">
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Student NIC <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="slt-loan-student-nic" placeholder="Enter Student NIC" required>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Course <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="slt-loan-course" required disabled>
                                        <option value="" selected disabled>Select a Course</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Loan Taken Years</label>
                                <div class="col-sm-10">
                                    <input type="number" class="form-control" id="sltLoanYears" readonly>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">No of Loan Installments</label>
                                <div class="col-sm-10">
                                    <input type="number" class="form-control" id="sltLoanInstallmentCount" readonly>
                                    <small class="form-text text-muted">Calculated as loan taken years × 12 months.</small>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Apply From Installment</label>
                                <div class="col-sm-10">
                                    <input type="number" class="form-control" id="sltLoanStartInstallment" readonly>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">SLT Loan Amount</label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <span class="input-group-text">LKR</span>
                                        <input type="number" class="form-control" id="sltPlanLoanAmount" readonly>
                                    </div>
                                    <small class="form-text text-muted">Total SLT loan amount from the payment plan.</small>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">SLT Loan Receivable Amount (Monthly)</label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <span class="input-group-text">LKR</span>
                                        <input type="number" class="form-control" id="sltLoanAmount" min="0" step="0.01" readonly>
                                    </div>
                                    <small class="form-text text-muted">Calculated as SLT loan amount ÷ no of loan installments.</small>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Payment Effective Date <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="date" class="form-control" id="sltLoanEffectiveDate" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                    <small class="form-text text-muted">Enter the actual date the SLT receivable payment is effective.</small>
                                </div>
                            </div>
                            <div class="alert alert-info" id="sltLoanSummary" style="display:none;"></div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-success">
                                    <i class="ti ti-device-floppy me-2"></i>Update SLT Receivable
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

<!-- Pay Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Make a Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="pay-payment-id">

        <div class="mb-3">
          <label class="form-label">Amount to Pay</label>
          <input type="number" class="form-control" id="pay-amount" min="1">
        </div>

        <div class="mb-3">
          <label class="form-label">Payment Method</label>
          <select class="form-select" id="pay-method">
            <option value="Cash">Cash</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cheque">Cheque</option>
            <option value="Credit Card">Credit Card</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Payment Date</label>
          <input type="date" class="form-control" id="pay-date" value="{{ date('Y-m-d') }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Remarks</label>
          <textarea class="form-control" id="pay-remarks"></textarea>
        </div>

        <!-- Optional slip upload -->
        <div class="mb-3">
          <label class="form-label">Upload Payment Slip (Optional)</label>
          <input type="file" class="form-control" id="pay-slip" accept=".jpg,.jpeg,.png,.pdf">
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="submitPaymentBtn">Confirm Payment</button>
      </div>
    </div>
  </div>
</div>


                <!-- Payment Summary Tab -->
                <div class="tab-pane fade" id="payment-summary" role="tabpanel" aria-labelledby="payment-summary-tab">
                    <div class="mt-4">
                        <!-- Filters -->
                        <div class="mb-4">
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Student NIC <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="summary-student-nic" placeholder="Enter Student NIC" required>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-2 col-form-label fw-bold">Course <span class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="summary-course" required>
                                        <option value="" selected disabled>Select a Course</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="button" class="btn btn-primary" id="generatePaymentSummaryBtn">
                                        <i class="ti ti-chart-pie me-2"></i>Generate Summary
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div class="mt-4" id="paymentSummarySection" style="display:none;">
                            <h4 class="text-center mb-3">Payment Summary</h4>

                            <!-- Student Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="ti ti-user me-2"></i>Student Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Student ID:</strong> <span id="summary-student-id"></span></p>
                                            <p><strong>Student Name:</strong> <span id="summary-student-name"></span></p>
                                            <p><strong>Course:</strong> <span id="summary-course-name"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Registration Date:</strong> <span id="summary-registration-date"></span></p>
                                            <p><strong>Total Course Fee:</strong> <span id="summary-total-course-fee"></span></p>
                                            <p><strong>Total Paid:</strong> <span id="summary-total-paid"></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <h5>Total Amount</h5>
                                            <h3 id="total-amount">Rs. 0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h5>Total Paid</h5>
                                            <h3 id="total-paid">Rs. 0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body text-center">
                                            <h5>Outstanding</h5>
                                            <h3 id="total-outstanding">Rs. 0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h5>Payment Rate</h5>
                                            <h3 id="payment-rate">0%</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Details Table -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="ti ti-list me-2"></i>Payment Details by Type
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Local Course Fee Table -->
                                    <div class="mb-4">
                                        <h6 class="text-primary mb-3">
                                            <i class="ti ti-book me-2"></i>Local Course Fee
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Total Amount</th>
                                                        <th>Paid Amount</th>
                                                        <th>Outstanding</th>
                                                        <th>Paid Date</th>
                                                        <th>Due Date</th>
                                                        <th>Receipt No</th>
                                                        <th>Uploaded Receipt</th>
                                                        <th>Installments</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="courseFeeTableBody">
                                                    <tr><td colspan="8" class="text-center text-muted">No course fee data available</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Franchise Payments Table -->
                                    <div class="mb-4">
                                        <h6 class="text-success mb-3">
                                            <i class="ti ti-building me-2"></i>Franchise Payments
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Total Amount</th>
                                                        <th>Paid Amount</th>
                                                        <th>Outstanding</th>
                                                        <th>Paid Date</th>
                                                        <th>Due Date</th>
                                                        <th>Receipt No</th>
                                                        <th>Uploaded Receipt</th>
                                                        <th>Installments</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="franchiseFeeTableBody">
                                                    <tr><td colspan="8" class="text-center text-muted">No franchise fee data available</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Registration Fee Table -->
                                    <div class="mb-4">
                                        <h6 class="text-info mb-3">
                                            <i class="ti ti-file-text me-2"></i>Registration Fee
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Total Amount</th>
                                                        <th>Paid Amount</th>
                                                        <th>Outstanding</th>
                                                        <th>Paid Date</th>
                                                        <th>Due Date</th>
                                                        <th>Receipt No</th>
                                                        <th>Uploaded Receipt</th>
                                                        <th>Installments</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="registrationFeeTableBody">
                                                    <tr><td colspan="8" class="text-center text-muted">No registration fee data available</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Hostel Fee Table -->
                                    <div class="mb-4">
                                        <h6 class="text-warning mb-3">
                                            <i class="ti ti-home me-2"></i>Hostel Fee
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Total Amount</th>
                                                        <th>Paid Amount</th>
                                                        <th>Outstanding</th>
                                                        <th>Paid Date</th>
                                                        <th>Due Date</th>
                                                        <th>Receipt No</th>
                                                        <th>Uploaded Receipt</th>
                                                        <th>Installments</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="hostelFeeTableBody">
                                                    <tr><td colspan="8" class="text-center text-muted">No hostel fee data available</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Library Fee Table -->
                                    <div class="mb-4">
                                        <h6 class="text-secondary mb-3">
                                            <i class="ti ti-library me-2"></i>Library Fee
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Total Amount</th>
                                                        <th>Paid Amount</th>
                                                        <th>Outstanding</th>
                                                        <th>Paid Date</th>
                                                        <th>Due Date</th>
                                                        <th>Receipt No</th>
                                                        <th>Uploaded Receipt</th>
                                                        <th>Installments</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="libraryFeeTableBody">
                                                    <tr><td colspan="8" class="text-center text-muted">No library fee data available</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Other Fees Table -->
                                    <div class="mb-4">
                                        <h6 class="text-dark mb-3">
                                            <i class="ti ti-plus me-2"></i>Other
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Total Amount</th>
                                                        <th>Paid Amount</th>
                                                        <th>Outstanding</th>
                                                        <th>Paid Date</th>
                                                        <th>Due Date</th>
                                                        <th>Receipt No</th>
                                                        <th>Uploaded Receipt</th>
                                                        <th>Installments</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="otherFeeTableBody">
                                                    <tr><td colspan="8" class="text-center text-muted">No other fee data available</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
let paymentPlans = [];
let paymentRecords = [];
let paymentSummary = {};

// Event delegation for payment buttons
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-create-payment-plan')) {
        console.log('Submit button clicked');
        createPaymentPlan();
    } else if (e.target.closest('.btn-reset-payment-plan')) {
        resetPaymentPlanForm();
    } else if (e.target.closest('.btn-close-formula-modal')) {
        document.getElementById('formulaModal').style.display = 'none';
    } else if (e.target.closest('#generateSlipBtn')) {
        generatePaymentSlip();
    } else if (e.target.closest('#printPaymentSlipBtn')) {
        printPaymentSlip();
    } else if (e.target.closest('#downloadPaymentSlipBtn')) {
        downloadPaymentSlip();
    } else if (e.target.closest('#loadPaymentRecordsBtn')) {
        loadPaymentRecords();
    } else if (e.target.closest('#submitPaymentBtn')) {
        submitPayment();
    } else if (e.target.closest('#updatePaymentRecordsBtn')) {
        updatePaymentRecords();
    } else if (e.target.closest('#generatePaymentSummaryBtn')) {
        generatePaymentSummary();
    } else if (e.target.closest('.toast-close')) {
        const closeBtn = e.target.closest('.toast-close');
        const toastId = closeBtn.dataset.toastId;
        if (toastId) {
            removeToast(toastId);
        }
    } else if (e.target.closest('.slt-formula')) {
        const formula = e.target.closest('.slt-formula');
        const Ai = formula.dataset.formulaAi;
        const L = formula.dataset.formulaL;
        const LminusS = formula.dataset.formulaLminusS;
        showFormulaModal(Ai, L, LminusS);
    } else if (e.target.closest('.btn-edit-payment-plan')) {
        const btn = e.target.closest('.btn-edit-payment-plan');
        const index = btn.dataset.planIndex;
        editPaymentPlan(index);
    } else if (e.target.closest('.btn-view-payment-history')) {
        const btn = e.target.closest('.btn-view-payment-history');
        const index = btn.dataset.planIndex;
        viewPaymentHistory(index);
    } else if (e.target.closest('.btn-open-pay-modal')) {
        const btn = e.target.closest('.btn-open-pay-modal');
        const paymentId = btn.dataset.paymentId;
        const remainingAmount = btn.dataset.remainingAmount;
        openPayModal(paymentId, remainingAmount);
    } else if (e.target.closest('.btn-confirm-save-payment-record')) {
        confirmSavePaymentRecord();
    }
});

// Event delegation for payment plan select change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('payment-plan-select')) {
        const index = e.target.dataset.planIndex;
        const value = e.target.value;
        updatePaymentPlan(index, value);
    } else if (e.target.classList.contains('payment-checkbox')) {
        enableGenerateButton();
    }
});

// Event delegation for slip inputs
document.getElementById('slip-student-id')?.addEventListener('change', function() {
    checkStudentAndCourse();
});

document.getElementById('slip-course')?.addEventListener('change', function() {
    loadIntakesForCourse();
    if (document.getElementById('slip-payment-type')?.value) {
        loadPaymentDetails();
    }
});

document.getElementById('slip-payment-type')?.addEventListener('change', function() {
    loadPaymentDetails();
    setTimeout(toggleLateFeeColumn, 300);
});

document.getElementById('payment-effective-date')?.addEventListener('change', function() {
    if (window.paymentDetailsDataRaw) {
        renderPaymentDetailsTable(window.paymentDetailsDataRaw, window.paymentDetailsPaymentType || 'course_fee');
    }
});

document.getElementById('currency-from')?.addEventListener('change', function() {
    updateConversionLabel();
});

document.getElementById('update-student-nic')?.addEventListener('change', function() {
    loadStudentCoursesForUpdate();
});

document.getElementById('slt-loan-student-nic')?.addEventListener('change', function() {
    loadStudentCoursesForSltLoan();
});

document.getElementById('slt-loan-course')?.addEventListener('change', function() {
    loadSltLoanPlanDetails();
});

document.getElementById('summary-student-nic')?.addEventListener('change', function() {
    loadStudentCoursesForSummary();
});

// Toast Notification Functions
function showSuccessMessage(message) {
    showToast('Success', message, 'success');
}

function showErrorMessage(message) {
    showToast('Error', message, 'error');
}

function showWarningMessage(message) {
    showToast('Warning', message, 'warning');
}

function showInfoMessage(message) {
    showToast('Info', message, 'info');
}

// Toast notification function
function showToast(title, message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    const toastId = 'toast-' + Date.now();

    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    toast.className = `toast ${type}`;
    toast.id = toastId;
    toast.innerHTML = `
        <div class="toast-icon">
            ${icons[type]}
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" data-toast-id="${toastId}">
            ×
        </button>
    `;

    container.appendChild(toast);

    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Auto remove after 5 seconds
    setTimeout(() => {
        removeToast(toastId);
    }, 5000);
}

function removeToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast) {
        toast.classList.add('slide-out');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
}

// Spinner functions
function showSpinner(show) {
    document.getElementById('spinner-overlay').style.display = show ? 'flex' : 'none';
}

// Load discounts from backend
function loadDiscounts() {
    // Prevent multiple simultaneous calls
    if (window.isLoadingDiscounts) {
        console.log('loadDiscounts already in progress, skipping...');
        return;
    }

    window.isLoadingDiscounts = true;
    console.log('Loading discounts...');

    // Load local course fee discounts for the first discount dropdown
    fetch('/payment/get-discounts?category=local_course_fee', {
        method: 'GET',
        headers: {'Content-Type': 'application/json'}
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update all discount selects with local course fee discounts only
            const discountSelects = document.querySelectorAll('.discount-select');
            discountSelects.forEach(select => {
                // Store current selection before resetting
                const currentValue = select.value;

                // Only reset if the select is empty or has no options (first time loading)
                if (!select.value || select.options.length <= 1) {
                    select.innerHTML = '<option value="">No Discount</option>';

                    data.discounts.forEach(discount => {
                        const valueDisplay = discount.type === 'percentage' ?
                            `${discount.name} (${discount.value}%)` :
                            `${discount.name} (LKR ${discount.value.toLocaleString()})`;
                        select.innerHTML += `<option value="${discount.id}" data-type="${discount.type}" data-value="${discount.value}">${valueDisplay}</option>`;
                    });

                    // Restore previous selection if it exists
                    if (currentValue) {
                        select.value = currentValue;
                    }
                }
            });
        } else {
            console.error('Failed to load local course fee discounts:', data.message);
        }
    })
    .catch(error => {
        console.error('Error loading local course fee discounts:', error);
    })
    .finally(() => {
        window.isLoadingDiscounts = false;
    });

    // Load registration fee discounts for the registration fee dropdown
    fetch('/payment/get-discounts?category=registration_fee', {
        method: 'GET',
        headers: {'Content-Type': 'application/json'}
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Load registration fee discounts
            const registrationFeeDiscountSelect = document.getElementById('registration-fee-discount');
            if (registrationFeeDiscountSelect) {
                // Store current selection before resetting
                const currentValue = registrationFeeDiscountSelect.value;

                // Only reset if the select is empty or has no options (first time loading)
                if (!registrationFeeDiscountSelect.value || registrationFeeDiscountSelect.options.length <= 1) {
                    registrationFeeDiscountSelect.innerHTML = '<option value="">No Registration Fee Discount</option>';
                    data.discounts.forEach(discount => {
                        const valueDisplay = discount.type === 'percentage' ?
                            `${discount.name} (${discount.value}%)` :
                            `${discount.name} (LKR ${discount.value.toLocaleString()})`;
                        const option = document.createElement('option');
                        option.value = discount.id;
                        option.textContent = valueDisplay;
                        option.dataset.type = discount.type;
                        option.dataset.value = discount.value;
                        registrationFeeDiscountSelect.appendChild(option);
                    });

                    // Restore previous selection if it exists
                    if (currentValue) {
                        registrationFeeDiscountSelect.value = currentValue;
                    }
                }
            }
        } else {
            console.error('Failed to load registration fee discounts:', data.message);
        }

        autoSelectFullPaymentDiscount();
    })
    .catch(error => {
        console.error('Error loading registration fee discounts:', error);
    })
    .finally(() => {
        window.isLoadingDiscounts = false;
    });
}

function autoSelectFullPaymentDiscount() {
    const planType = document.getElementById('payment-plan-type')?.value;
    const registrationFeeDiscountSelect = document.getElementById('registration-fee-discount');

    if (!registrationFeeDiscountSelect) {
        return;
    }

    if (planType !== 'full') {
        registrationFeeDiscountSelect.value = '';
        return;
    }

    const options = Array.from(registrationFeeDiscountSelect.options || []);
    const fullPaymentOption = options.find(option => {
        const label = (option.textContent || '').trim().toLowerCase();
        return label.includes('full payment discount');
    });

    if (fullPaymentOption) {
        registrationFeeDiscountSelect.value = fullPaymentOption.value;
        calculateFinalAmount();

        if (window.currentStudentData) {
            calculateAndDisplayInstallments();
        }
    }
}



// Load courses for student based on NIC
function loadCoursesForStudent() {
    const studentNic = document.getElementById('plan-student-nic').value;

    if (!studentNic) {
        // Reset course dropdown to show all courses
        document.getElementById('plan-course').innerHTML = '<option selected disabled value="">Select a Course</option>' +
            '@foreach($courses as $course)<option value="{{ $course->course_id }}">{{ $course->course_name }}</option>@endforeach';
        return;
    }

    showSpinner(true);

    // Make API call to get courses for the student
    fetch('/payment/get-student-courses', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            student_nic: studentNic
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const courseSelect = document.getElementById('plan-course');
            courseSelect.innerHTML = '<option selected disabled value="">Select a Course</option>';

            data.courses.forEach(course => {
                courseSelect.innerHTML += `<option value="${course.course_id}">${course.course_name}</option>`;
            });

            if (data.courses.length === 0) {
                showInfoMessage('No courses found for this student.');
            }
        } else {
            showErrorMessage(data.message || 'Failed to load courses for student.');
            // Reset to all courses on error
            document.getElementById('plan-course').innerHTML = '<option selected disabled value="">Select a Course</option>' +
                '@foreach($courses as $course)<option value="{{ $course->course_id }}">{{ $course->course_name }}</option>@endforeach';
        }
    })
    .catch(() => {
        showErrorMessage('An error occurred while loading courses.');
        // Reset to all courses on error
        document.getElementById('plan-course').innerHTML = '<option selected disabled value="">Select a Course</option>' +
            '@foreach($courses as $course)<option value="{{ $course->course_id }}">{{ $course->course_name }}</option>@endforeach';
    })
    .finally(() => showSpinner(false));
}

// Load student and course details for payment plan creation
function loadStudentForPaymentPlan() {
    const studentNic = document.getElementById('plan-student-nic').value;
    const courseId = document.getElementById('plan-course').value;

    if (!studentNic || !courseId) {
        showWarningMessage('Please enter Student NIC and select a Course.');
        return;
    }

    showSpinner(true);

    // Make API call to get student and course details
    fetch('/payment/get-plans', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            student_nic: studentNic,
            course_id: parseInt(courseId)
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP Error: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Populate the form with student and course details
            populatePaymentPlanForm(data.student);
            document.getElementById('paymentPlanFormSection').style.display = '';
            document.getElementById('existingPaymentPlansSection').style.display = '';
            loadExistingPaymentPlans(studentNic, parseInt(courseId));
        } else {
            showErrorMessage(data.message || 'Failed to load student details.');
        }
    })
    .catch((error) => {
        console.error('Error loading student details:', error);
        showErrorMessage(error.message || 'An error occurred while loading student details.');
    })
    .finally(() => showSpinner(false));
}

// Populate payment plan form with student and course details
function populatePaymentPlanForm(studentData) {
    console.log('populatePaymentPlanForm called with studentData:', studentData);

    const courseFee = Number(studentData.course_fee) || 0;           // LKR
    const regFee    = Number(studentData.registration_fee) || 0;     // LKR
    const intlFee   = Number(studentData.international_fee) || 0;    // e.g., USD
    const intlCur   = (studentData.international_currency || 'USD').toUpperCase();

    const fmt2 = (n) => n.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // Basic info
    const nameEl = document.getElementById('student-name-display');
    if (nameEl) nameEl.textContent = studentData.student_name || 'N/A';

    const idEl = document.getElementById('student-id-display');
    if (idEl) idEl.textContent = studentData.student_id || 'N/A';

    const courseEl = document.getElementById('course-name-display');
    if (courseEl) courseEl.textContent = studentData.course_name || 'N/A';

    const intakeEl = document.getElementById('intake-name-display');
    if (intakeEl) intakeEl.textContent = studentData.intake_name || 'N/A';

    // LKR breakdown
    const courseFeeEl = document.getElementById('course-fee-display');
    if (courseFeeEl) courseFeeEl.textContent = 'LKR ' + fmt2(courseFee);

    const regFeeEl = document.getElementById('registration-fee-display');
    if (regFeeEl) regFeeEl.textContent = 'LKR ' + fmt2(regFee);

    // LKR total = course + registration (franchise NOT included)
    const totalAmount = courseFee + regFee;
    const totalEl = document.getElementById('total-amount-display');
    if (totalEl) {
        totalEl.textContent = intlFee > 0
            ? `LKR ${fmt2(totalAmount)} + ${fmt2(intlFee)} ${intlCur}`
            : `LKR ${fmt2(totalAmount)}`;
    }

    // Show franchise fee (with currency)
    document.querySelectorAll('.franchise-amount-display').forEach((frEl) => {
        frEl.textContent = intlFee > 0 ? `${fmt2(intlFee)} ${intlCur}` : '-';
    });



    // Store for later use (and keep a clean split)
    window.currentStudentData = {
    ...studentData,
    total_amount_lkr: totalAmount,
    international_fee: intlFee,
    international_currency: intlCur,
    franchise_display: `${intlFee} ${intlCur}` // example: "500 USD"
};


    // Hide warning if present
    const statusIndicator = document.getElementById('student-data-status');
    if (statusIndicator) statusIndicator.style.display = 'none';

    // Calculate initial final amount
    if (typeof calculateFinalAmount === 'function') calculateFinalAmount();
}


// helpers
const fmtLKR = n => `LKR ${Number(n || 0).toLocaleString()}`;
const badge  = s => `<span class="badge bg-${s==='paid'?'success':(s==='pending'?'warning':'danger')}">${s}</span>`;

const toYmd = (dateObj) => {
    const y = dateObj.getFullYear();
    const m = String(dateObj.getMonth() + 1).padStart(2, '0');
    const d = String(dateObj.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

function normalizeDueDate(raw, installmentNumber = 1, useFallback = true) {
    const value = String(raw || '').trim();

    // Handle YYYY-MM-DD (ISO format from backend)
    const isoMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (isoMatch) {
        return isoMatch[0]; // Return as-is
    }

    // Handle DD/MM/YYYY or MM/DD/YYYY slash format
    const slashMatch = value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (slashMatch) {
        const part1 = Number(slashMatch[1]);
        const part2 = Number(slashMatch[2]);
        const year = Number(slashMatch[3]);

        // Determine if DD/MM or MM/DD by context
        // Assume DD/MM since most of the world uses this format
        // But check if part1 > 12 (must be day, not month)
        let day, month;
        if (part1 > 12) {
            day = part1;
            month = part2;
        } else if (part2 > 12) {
            month = part1;
            day = part2;
        } else {
            // Both could be valid, assume DD/MM (first is day)
            day = part1;
            month = part2;
        }

        if (month >= 1 && month <= 12 && day >= 1 && day <= 31) {
            // Use UTC date to avoid timezone issues
            const ymdStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            return ymdStr;
        }
    }

    // For any other format, try parsing but use UTC-safe method
    if (value.length > 0) {
        // Try ISO date parsing for strings like "2026-05-15T00:00:00Z"
        if (value.includes('T') || value.includes(' ')) {
            const parsed = new Date(value);
            if (!Number.isNaN(parsed.getTime())) {
                return toYmd(parsed);
            }
        }
    }

    if (!useFallback) {
        return '';
    }

    const fallback = new Date();
    fallback.setDate(fallback.getDate() + 7 + (Math.max(1, Number(installmentNumber) || 1) - 1) * 30);
    return toYmd(fallback);
}

function formatDateDmy(input) {
    if (!input) {
        return '-';
    }

    let day, month, year;

    // Handle string input
    if (typeof input === 'string') {
        const str = input.trim();

        // Handle YYYY-MM-DD format (ISO format from backend)
        const isoMatch = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (isoMatch) {
            year = Number(isoMatch[1]);
            month = Number(isoMatch[2]);
            day = Number(isoMatch[3]);
        } else {
            // Try to parse as Date object after extraction
            const dt = new Date(str);
            if (!Number.isNaN(dt.getTime())) {
                day = dt.getDate();
                month = dt.getMonth() + 1;
                year = dt.getFullYear();
            } else {
                return '-';
            }
        }
    } else if (input instanceof Date) {
        day = input.getDate();
        month = input.getMonth() + 1;
        year = input.getFullYear();
    } else {
        return '-';
    }

    if (Number.isNaN(day) || Number.isNaN(month) || Number.isNaN(year)) {
        return '-';
    }

    return `${String(day).padStart(2, '0')}/${String(month).padStart(2, '0')}/${year}`;
}

function formatDueDateForTable(raw, installmentNumber = 1) {
    const ymd = normalizeDueDate(raw, installmentNumber, true);
    if (!ymd) {
        return '-';
    }
    return formatDateDmy(`${ymd}T00:00:00`);
}

function getDiscountBaseAmount() {
    const studentData = window.currentStudentData || {};
    const storedTotal = Number(studentData.total_amount_lkr || 0);

    if (storedTotal > 0) {
        return storedTotal;
    }

    const courseFee = Number(studentData.course_fee || 0);
    const registrationFee = Number(studentData.registration_fee || 0);
    return courseFee + registrationFee;
}

function getSltLoanStartInstallment() {
  const value = parseInt(document.getElementById('slt-loan-start-installment')?.value || '1', 10);
  return Number.isFinite(value) && value > 0 ? value : 1;
}

function allocateSltLoanByStartInstallment(installments, loanAmount, startInstallment) {
  let remainingLoan = Math.max(0, Number(loanAmount) || 0);
  return installments.map((ins, index) => {
    const installmentNumber = Number(ins.installment_number || index + 1);
    if (installmentNumber < startInstallment || remainingLoan <= 0) {
      return 0;
    }

    const available = Math.max(0, Number(ins.discountedAmount || 0));
    const applied = Math.min(available, remainingLoan);
    remainingLoan = Math.max(0, remainingLoan - applied);
    return Math.round(applied * 100) / 100;
  });
}

// Display installments in the table (discounts first, then SLT loan from the selected installment onward)
function displayInstallments(installments) {
  const tbody = document.getElementById('installmentTableBody');
  tbody.innerHTML = '';

  if (!installments || !installments.length) {
    showInstallmentPreview();
    return;
  }

  // helpers
  const N   = v => Number(String(v).replace(/,/g, '')) || 0;
  const r2  = v => Math.round(v * 100) / 100;
  const fmt = n => N(n).toLocaleString();
  const fmt0 = n => N(n).toLocaleString(undefined, {maximumFractionDigits:0}); // 200,000 style

  // 👉 formula HTML builder
  const sltFormulaHTML = (Ai, L, LminusS) => `
  <div class="slt-formula" data-formula-ai="${Ai}" data-formula-l="${L}" data-formula-lminus-s="${LminusS}">
    <span class="fraction">
      <span class="top">${fmt0(Ai)}</span>
      <span class="bar"></span>
      <span class="bottom">${fmt0(L)}</span>
    </span>
    <span class="times">× ${fmt0(LminusS)}</span>
  </div>`;




  // current form state
  const discountSelects  = document.querySelectorAll('.discount-select');
  const sltLoanApplied   = (document.getElementById('slt-loan-applied')?.value || '').toLowerCase();
  const sltLoanAmount    = N(document.getElementById('slt-loan-amount')?.value);

  // collect all discounts
  let pct = 0, fixed = 0;
  discountSelects.forEach((select, index) => {
    if (!select.value) return;
    const opt  = select.options[select.selectedIndex];
    const type = opt.dataset.type;
    const val  = N(opt.dataset.value);
    if (type === 'percentage') pct  += val;
    else if (type === 'amount') fixed += val;
  });

  // Original installment total before discounts.
  const originalLocalTotal = installments.reduce((sum, ins) => sum + N(ins.amount), 0);

  // Discounts should apply to the full local + registration amount.
  const registrationFee = N(window.currentStudentData?.registration_fee || 0);
  const totalFeeForDiscount = Math.max(getDiscountBaseAmount(), originalLocalTotal + registrationFee);

  // apply discounts across installments starting from the latest installment backwards
  const totalDiscount = ((pct > 0) ? (totalFeeForDiscount * pct) / 100 : 0) + fixed;
  const discounted = installments.map((ins) => ({
    ...ins,
    discountedAmount: N(ins.amount),
    discountApplied: 0,
  }));

  let remainingDiscount = Math.max(0, totalDiscount);
  for (let idx = discounted.length - 1; idx >= 0 && remainingDiscount > 0; idx--) {
    const ins = discounted[idx];
    const available = N(ins.discountedAmount);
    const applyNow = Math.min(remainingDiscount, available);
    ins.discountedAmount = Math.max(0, available - applyNow);
    ins.discountApplied = applyNow;
    remainingDiscount -= applyNow;
  }
  // ==============================
// Handle registration fee discount excess
// ===============================
const registrationFeeDiscountSelect = document.getElementById('registration-fee-discount');
if (registrationFeeDiscountSelect && registrationFeeDiscountSelect.value && discounted.length > 0) {
  const opt = registrationFeeDiscountSelect.options[registrationFeeDiscountSelect.selectedIndex];
  const discountType = opt.dataset.type;
  const discountValue = N(opt.dataset.value);

  const regFee = N(window.currentStudentData?.registration_fee || 0);
  let discountAmount = 0;

  if (discountType === 'percentage') {
    discountAmount = regFee * (discountValue / 100);
  } else if (discountType === 'amount') {
    discountAmount = discountValue;
  }

  if (discountAmount > regFee) {
    const excess = discountAmount - regFee;

    // Deduct excess from first installment
    discounted[0].discountedAmount = Math.max(0, discounted[0].discountedAmount - excess);

    // Mark it so discount column shows correctly
    discounted[0].registration_fee_discount_applied = excess;
    discounted[0].registration_fee_discount_note = 'Reg. Fee Excess';
  }
}

  // sum of discounted amounts
  const sumAfterDiscounts = discounted.reduce((s, x) => s + x.discountedAmount, 0);

  // target total by your rule:
  // ΣFi = (ΣAi / ΣAi) * (ΣAi - S)  where Ai = discountedAmount, S = SLT
  // This simplifies to: ΣFi = ΣAi - S (total after discounts minus SLT loan)
  const useLoan = (sltLoanApplied === 'yes' && sltLoanAmount > 0 && sumAfterDiscounts > 0);
  const loanStartInstallment = getSltLoanStartInstallment();
  const loanAllocations = useLoan
    ? allocateSltLoanByStartInstallment(discounted, Math.min(sltLoanAmount, sumAfterDiscounts), loanStartInstallment)
    : discounted.map(() => 0);
  const targetTotal = Math.max(0, sumAfterDiscounts - Math.min(sltLoanAmount, sumAfterDiscounts));
  let runningFinals = 0;

  // build rows; prorate SLT AFTER discounts using originalLocalTotal; fix rounding on last row
  discounted.forEach((ins, idx) => {
    const isLast = idx === discounted.length - 1;
    // Combine regular discount and registration fee discount excess
    let discountText = '-';
    let totalDiscount = ins.discountApplied || 0;

    // Add registration fee discount excess if present
    if (ins.registration_fee_discount_applied > 0) {
      totalDiscount += ins.registration_fee_discount_applied;
    }

    if (totalDiscount > 0) {
      discountText = `LKR ${fmt(totalDiscount)}`;
      if (ins.registration_fee_discount_applied > 0) {
        discountText += ` (${ins.registration_fee_discount_note || 'Reg. Fee Excess'})`;
      }
    }

    let finalAmount = ins.discountedAmount;
    let sltLoanText = '-';

    if (useLoan) {
      const loanAppliedToInstallment = loanAllocations[idx] || 0;
      let Fi;
      if (!isLast) {
        // Apply the formula: (Installment amount after discounts) / (Total sum of installments after discounts) × amount to be paid
        Fi = r2((ins.discountedAmount / sumAfterDiscounts) * targetTotal);
        runningFinals += Fi;
      } else {
        // last row gets remainder to fix rounding drift
        Fi = r2(targetTotal - runningFinals);
      }

      const Ai = ins.discountedAmount;
      const L  = sumAfterDiscounts;
      const S  = targetTotal;

      // 👉 show the final-amount formula in SLT column (e.g., 200,000 / 500,000 × 400,000)
      sltLoanText = loanAppliedToInstallment > 0 ? `LKR ${fmt(loanAppliedToInstallment)}` : '-';

      finalAmount = Math.max(0, r2(ins.discountedAmount - loanAppliedToInstallment));
    }

    const row = `
      <tr>
        <td>${ins.installment_number}</td>
                <td>${formatDueDateForTable(ins.due_date, ins.installment_number)}</td>
        <td>LKR ${fmt(ins.amount)}</td>
        <td>${discountText}</td>
        <td>${sltLoanText}</td>
        <td>LKR ${finalAmount.toLocaleString()}</td>
        <td>
          <span class="badge bg-${getStatusBadgeColor(ins.status)}">${ins.status}</span>
        </td>
      </tr>
    `;
    tbody.insertAdjacentHTML('beforeend', row);
  });
}
function showFormulaModal(Ai, L, LminusS) {
  const S = L - LminusS; // SLT loan amount
  const Fi = Math.round((Ai / L) * LminusS); // Final installment amount

  document.getElementById('formulaExplanation').innerHTML = `
    <h5 style="margin-bottom:10px;">General Formula</h5>
    <div class="math-formula">
      F<sub>i</sub> =
      <span class="fraction">
        <span class="top">A<sub>i</sub></span>
        <span class="bar"></span>
        <span class="bottom">L</span>
      </span>
      × (L − S)
    </div>

    <h6 style="margin-top:20px;">Where:</h6>
<table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
  <thead>
    <tr style="background:#f2f2f2; text-align:left;">
      <th style="padding:6px; border:1px solid #ddd;">Symbol</th>
      <th style="padding:6px; border:1px solid #ddd;">Description</th>
      <th style="padding:6px; border:1px solid #ddd;">Value</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding:6px; border:1px solid #ddd;"><strong>A<sub>i</sub></strong></td>
      <td style="padding:6px; border:1px solid #ddd;">Installment Amount After the Discount</td>
      <td style="padding:6px; border:1px solid #ddd; color:#007bff;">LKR ${Ai.toLocaleString()}</td>
    </tr>
    <tr>
      <td style="padding:6px; border:1px solid #ddd;"><strong>L</strong></td>
      <td style="padding:6px; border:1px solid #ddd;">Total of all installments after discounts (Combined Local + Registration Fee)</td>
      <td style="padding:6px; border:1px solid #ddd; color:#007bff;">LKR ${L.toLocaleString()}</td>
    </tr>
    <tr>
      <td style="padding:6px; border:1px solid #ddd;"><strong>S</strong></td>
      <td style="padding:6px; border:1px solid #ddd;">SLT Loan Amount</td>
      <td style="padding:6px; border:1px solid #ddd; color:#007bff;">LKR ${S.toLocaleString()}</td>
    </tr>
    <tr>
      <td style="padding:6px; border:1px solid #ddd;"><strong>(L − S)</strong></td>
      <td style="padding:6px; border:1px solid #ddd;">Remaining payable total (Without Registration Fee)</td>
      <td style="padding:6px; border:1px solid #ddd; color:#007bff;">LKR ${LminusS.toLocaleString()}</td>
    </tr>
  </tbody>
</table>


    <hr>

    <h6>Applied to this installment:</h6>
    <div class="math-formula" style="background:#f9f9f9; padding:10px; border-radius:6px;">
      <span class="fraction">
        <span class="top">${Ai.toLocaleString()}</span>
        <span class="bar"></span>
        <span class="bottom">${L.toLocaleString()}</span>
      </span>
      × ${LminusS.toLocaleString()}
    </div>

    <p><strong>Final Amount (F<sub>i</sub>):</strong>
      <span style="color:green; font-size:18px;">LKR ${Fi.toLocaleString()}</span>
    </p>
  `;

  document.getElementById('formulaModal').style.display = 'flex';
}


// Show "new plan" editor and try to seed rows from the course/intake plan
function bootstrapNewPlan(studentNic, courseId) {
  // Prevent multiple simultaneous requests
  if (window.isBootstrappingNewPlan) {
    console.log('Bootstrap new plan already in progress...');
    return;
  }

  // Set flag to prevent multiple requests
  window.isBootstrappingNewPlan = true;

  // show the editor
  document.getElementById('paymentPlanFormSection').style.display = '';
  const statusIndicator = document.getElementById('student-data-status');
  if (statusIndicator) statusIndicator.style.display = 'none';

  // Default selection removed

  // Load discounts for payment plan tab
  loadDiscounts();

  // Try to fetch the base installments for this course/intake to prefill editor
  fetchWithTimeout('/payment/get-installments', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    body: JSON.stringify({
      student_nic: String(studentNic),
      course_id: parseInt(courseId, 10)
    })
  }, 15000)
  .then(r => {
    if (!r.ok) {
      throw new Error(`HTTP error! status: ${r.status}`);
    }
    return r.json();
  })
  .then(j => {
    if (j.success && Array.isArray(j.installments) && j.installments.length) {
      // Normalize a bit and store them
      window.baseInstallments = j.installments.map(i => ({
        installment_number: i.installment_number,
        due_date: i.due_date,
        amount: Number(i.amount || 0),
        discount_amount: Number(i.discount_amount || 0),
        slt_loan_amount: Number(i.slt_loan_amount || 0),
        final_amount: Number(i.final_amount ?? i.amount ?? 0),
        status: i.status || 'pending'
      }));
            // Re-render preview using currently selected plan type once base installments are ready.
            if (typeof showInstallmentPreview === 'function') {
                showInstallmentPreview();
            }
    } else {
      // Fallback: no base installments
      window.baseInstallments = null;
            if (typeof showInstallmentPreview === 'function') {
                showInstallmentPreview();
            }
    }
  })
  .catch(() => {
    showInstallmentPreview();
  })
  .finally(() => {
    // Reset the flag
    window.isBootstrappingNewPlan = false;
  });
}


// Load existing payment plans; when none, open editor for a NEW plan
function loadExistingPaymentPlans(studentNic, courseId) {
  // Prevent multiple simultaneous requests
  if (window.isLoadingExistingPlans) {
    console.log('Loading existing plans already in progress...');
    return;
  }

  window.isLoadingExistingPlans = true;
  console.log('Loading existing payment plans for student:', studentNic, 'course:', courseId);

  const section = document.getElementById('existingPaymentPlansSection');
  const tbody   = document.getElementById('existingPaymentPlansTableBody');

  if (!section || !tbody) {
    console.error('Required elements not found for loading existing payment plans');
    return;
  }

  // Show the section immediately
  section.style.display = 'block';

  // Show loading state
  tbody.innerHTML = '<tr><td colspan="9" class="text-center"><i class="ti ti-loader ti-spin me-2"></i>Loading payment plans...</td></tr>';

  if (typeof showSpinner === 'function') showSpinner(true);

  fetchWithTimeout('/payment/existing-plans', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    body: JSON.stringify({ student_nic: String(studentNic), course_id: Number(courseId) })
  }, 15000)
  .then(r => {
    if (!r.ok) {
      throw new Error(`HTTP error! status: ${r.status}`);
    }
    return r.json();
  })
  .then(data => {
    if (!data.success) throw new Error(data.message || 'Failed to load existing plans');

    const plans = data.plans || [];

    // No plans? -> open editor immediately to create a NEW plan
    if (plans.length === 0) {
      section.style.display = 'none';
      // Only bootstrap new plan if we're not in the middle of creating one
      if (!window.isCreatingPaymentPlan && !window.isBootstrappingNewPlan) {
        bootstrapNewPlan(studentNic, courseId);
      }
      return;
    }

    // Clear the table and show plans
    tbody.innerHTML = '';

    // Cache for "Load to editor" (optional)
    window.existingPlansCache = {};
    window.existingPlanMetaCache = {};

    plans.forEach(p => {
      const inst = p.installments || [];
      window.existingPlansCache[p.payment_plan_id] = inst;
      window.existingPlanMetaCache[p.payment_plan_id] = p;

      const fmtLKR = n => `LKR ${(Number(n || 0)).toLocaleString()}`;
      const badge  = s => `<span class="badge bg-${s==='active'?'success':'secondary'}">${s}</span>`;

      tbody.insertAdjacentHTML('beforeend', `
  <tr id="plan-row-${p.payment_plan_id}">
    <td>${p.student_id}</td>
    <td>${p.student_name}</td>
    <td>${p.student_nic}</td>
    <td>${p.course_name}</td>
    <td>${p.payment_plan_type}</td>
    <td>
      <div>${fmtLKR(p.total_amount)}</div>
      <small class="text-muted">Final: ${fmtLKR(p.final_amount)}</small>
    </td>
    <td>
      ${inst.length} installment${inst.length===1?'':'s'}
      <button class="btn btn-link btn-sm p-0 ms-1" type="button"
              data-bs-toggle="collapse" data-bs-target="#plan-${p.payment_plan_id}-inst">View</button>
    </td>
    <td>${badge(p.status)}</td>
    <td class="text-nowrap">
      <button class="btn btn-sm btn-outline-primary"
              data-bs-toggle="collapse" data-bs-target="#plan-${p.payment_plan_id}-inst">Details</button>
      <button class="btn btn-sm btn-primary ms-1 btn-load-plan" data-plan-id="${p.payment_plan_id}">
        <i class="ti ti-edit me-1"></i>Load to Editor
      </button>
      <button class="btn btn-sm btn-danger ms-1 btn-delete-plan" data-plan-id="${p.payment_plan_id}">
        <i class="ti ti-trash me-1"></i>Delete
      </button>
    </td>
  </tr>
  <tr class="collapse" id="plan-${p.payment_plan_id}-inst">
    <td colspan="9">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Due Date</th>
            <th>Amount</th>
            <th>Discount</th>
            <th>SLT Loan</th>
            <th>Final</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          ${inst.map(i => `
            <tr>
              <td>${i.installment_number}</td>
              <td>${i.due_date || '-'}</td>
              <td>LKR ${(i.amount ?? 0).toLocaleString()}</td>
              <td>LKR ${(i.discount_amount ?? 0).toLocaleString()}</td>
              <td>LKR ${(i.slt_loan_amount ?? 0).toLocaleString()}</td>
              <td>LKR ${(i.final_amount ?? 0).toLocaleString()}</td>
              <td>${i.status}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </td>
  </tr>
`);


    });



    section.style.display = 'block';

    // Reset the call count since we successfully loaded plans
    window.loadExistingPlansCallCount = 0;

    // Attach one delegated handler to load plan into editor
    attachLoadToEditorHandler();
    attachDeletePlanHandler();
 // see previous message for the implementation
  })
  .catch(err => {
    console.error('Error loading existing payment plans:', err);
    tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">
      <i class="ti ti-alert-circle me-2"></i>Error loading payment plans: ${err.message}
      <br><small class="text-muted">Please try again or contact support if the issue persists.</small>
    </td></tr>`;
    section.style.display = 'block';
  })
  .finally(() => {
    if (typeof showSpinner === 'function') showSpinner(false);
    // Reset the flag
    window.isLoadingExistingPlans = false;
  });
}


// Attach a single delegated handler for "Load to editor" buttons
function attachLoadToEditorHandler() {
  const tbody = document.getElementById('existingPaymentPlansTableBody');
  if (!tbody) return;

  // Prevent double-binding if this runs more than once
  if (tbody._loadHandlerAttached) return;
  tbody._loadHandlerAttached = true;

  tbody.addEventListener('click', (ev) => {
    const btn = ev.target.closest('.btn-load-plan');
    if (!btn) return;

    const planId = btn.dataset.planId;
    let inst = (window.existingPlansCache && window.existingPlansCache[planId]) || [];
    const planMeta = (window.existingPlanMetaCache && window.existingPlanMetaCache[planId]) || {};
    if (!Array.isArray(inst)) inst = [];

    // Show the editor
    const editor = document.getElementById('paymentPlanFormSection');
    if (editor) editor.style.display = '';

    const status = document.getElementById('student-data-status');
    if (status) status.style.display = 'none';

    // Ensure plan type is set so the table/calculation logic activates
    const planTypeSel = document.getElementById('payment-plan-type');
    if (planTypeSel) {
      planTypeSel.value = 'installments';
      planTypeSel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const sltLoanApplied = document.getElementById('slt-loan-applied');
    const sltLoanAmount = document.getElementById('slt-loan-amount');
    const sltLoanStart = document.getElementById('slt-loan-start-installment');
    const sltLoanYears = document.getElementById('slt-loan-years');
    const hasSltLoan = (planMeta.slt_loan_applied || '').toLowerCase() === 'yes';
    if (sltLoanApplied) sltLoanApplied.value = hasSltLoan ? 'yes' : 'no';
    if (sltLoanAmount) {
      sltLoanAmount.disabled = !hasSltLoan;
      sltLoanAmount.required = hasSltLoan;
      sltLoanAmount.value = hasSltLoan ? Number(planMeta.slt_loan_amount || 0) : 0;
    }
    if (sltLoanStart) {
      sltLoanStart.disabled = !hasSltLoan;
      sltLoanStart.required = hasSltLoan;
      sltLoanStart.value = hasSltLoan ? (planMeta.slt_loan_start_installment || 1) : '';
    }
    if (sltLoanYears) {
      sltLoanYears.disabled = !hasSltLoan;
      sltLoanYears.required = hasSltLoan;
      sltLoanYears.value = hasSltLoan ? (planMeta.slt_loan_years || '') : '';
    }

    // Normalize installments for displayInstallments()
    const rows = inst.map(i => ({
      installment_number: i.installment_number,
      due_date: i.due_date,
      amount: Number(i.amount ?? i.base_amount ?? 0),
      discount_amount: Number(i.discount_amount || 0),
      slt_loan_amount: Number(i.slt_loan_amount || 0),
      final_amount: Number(i.final_amount ?? i.amount ?? 0),
      status: i.status || 'pending'
    }));

    // Render into the editor table
    if (typeof displayInstallments === 'function') {
      displayInstallments(rows);
    }

    // Recompute top-level totals if your UI shows them
    if (typeof calculateFinalAmount === 'function') {
      calculateFinalAmount();
    }
  });
}
function attachDeletePlanHandler() {
  const tbody = document.getElementById('existingPaymentPlansTableBody');
  if (!tbody) return;

  if (tbody._deleteHandlerAttached) return;
  tbody._deleteHandlerAttached = true;

  tbody.addEventListener('click', (ev) => {
    const btn = ev.target.closest('.btn-delete-plan');
    if (!btn) return;

    const planId = btn.dataset.planId;
    if (!confirm("Are you sure you want to delete this payment plan?")) return;

    fetch(`/payment/delete-plan/${planId}`, {
  method: "DELETE",
  headers: {
    "Content-Type": "application/json",
    "X-CSRF-TOKEN": "{{ csrf_token() }}"
  }
})
.then(r => {
  if (!r.ok) throw new Error(`HTTP ${r.status}`);
  return r.json();
})
.then(data => {
  if (!data.success) throw new Error(data.message || "Delete failed");
  document.getElementById(`plan-row-${planId}`)?.remove();
  alert("Payment plan deleted successfully");
})
.catch(err => {
  console.error("Delete failed:", err);
  alert("Error deleting plan: " + err.message);
});

  });
}




// Get badge color based on status
function getStatusBadgeColor(status) {
    switch (status.toLowerCase()) {
        case 'paid':
            return 'success';
        case 'pending':
            return 'warning';
        case 'overdue':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Show preview of installments when no payment plan exists
function showInstallmentPreview() {
    const planType = document.getElementById('payment-plan-type').value;
    const tbody = document.getElementById('installmentTableBody');

    console.log('showInstallmentPreview called with planType:', planType);
    console.log('currentStudentData:', window.currentStudentData);

    if (planType === 'full') {
        // Compute discounts and SLT for full payment using the combined local + registration base.
        const totalAmount = getDiscountBaseAmount();
        const discountSelects = document.querySelectorAll('.discount-select');
        const sltLoanDropdown = document.getElementById('slt-loan-applied');
        const sltLoanApplied = sltLoanDropdown ? sltLoanDropdown.value : 'no';
        const sltLoanAmountInput = document.getElementById('slt-loan-amount');
        const sltLoanAmount = sltLoanAmountInput ? parseFloat(sltLoanAmountInput.value) || 0 : 0;

        let finalAmount = totalAmount;
        let totalDiscountAmount = 0;
        let totalDiscountPercentage = 0;

        // Normal discounts
        discountSelects.forEach((select) => {
            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const discountType = selectedOption.dataset.type;
                const discountValue = parseFloat(selectedOption.dataset.value);

                if (discountType === 'percentage') {
                    totalDiscountPercentage += discountValue;
                } else if (discountType === 'amount') {
                    totalDiscountAmount += discountValue;
                }
            }
        });

        if (totalDiscountPercentage > 0) {
            const pctReduction = finalAmount * totalDiscountPercentage / 100;
            finalAmount -= pctReduction;
            totalDiscountAmount += pctReduction;
        }

        // Registration Fee Discount
        const registrationFeeDiscountSelect = document.getElementById('registration-fee-discount');
        if (registrationFeeDiscountSelect && registrationFeeDiscountSelect.value) {
            const selectedOption = registrationFeeDiscountSelect.options[registrationFeeDiscountSelect.selectedIndex];
            const discountType = selectedOption.dataset.type;
            const discountValue = parseFloat(selectedOption.dataset.value || 0);
            const registrationFee = parseFloat(window.currentStudentData?.registration_fee || 0);
            let discountAmount = 0;

            if (discountType === 'percentage') {
                discountAmount = registrationFee * (discountValue / 100);
            } else if (discountType === 'amount') {
                discountAmount = discountValue;
            }

            if (discountAmount <= registrationFee) {
                finalAmount -= discountAmount;
                totalDiscountAmount += discountAmount;
            } else {
                finalAmount -= registrationFee;
                const excess = discountAmount - registrationFee;
                finalAmount -= excess;
                totalDiscountAmount += registrationFee + excess;
            }
        }

        // SLT Loan
        if (sltLoanApplied === 'yes' && sltLoanAmount > 0) {
            finalAmount -= sltLoanAmount;
        }

        finalAmount = Math.max(0, finalAmount);

        console.log('Showing full payment preview with amount:', totalAmount, 'discount:', totalDiscountAmount, 'slt:', sltLoanAmount, 'final:', finalAmount);
        tbody.innerHTML = `
            <tr>
                <td>1</td>
                <td>${formatDateDmy(new Date())}</td>
                <td>LKR ${totalAmount.toLocaleString()}</td>
                <td>LKR ${totalDiscountAmount.toLocaleString()}</td>
                <td>LKR ${sltLoanAmount.toLocaleString()}</td>
                <td>LKR ${finalAmount.toLocaleString()}</td>
                <td><span class="badge bg-warning">Pending</span></td>
            </tr>
        `;
    } else if (planType === 'installments') {
        if (window.baseInstallments && window.baseInstallments.length) {
            displayInstallments(window.baseInstallments);
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        <i class="ti ti-info-circle me-2"></i>
                        No installment data available for this course and intake.
                    </td>
                </tr>
            `;
        }
    } else {
        console.log('No plan type selected');
        tbody.innerHTML = '';
    }
}

// Calculate and display installments based on current form data
function calculateAndDisplayInstallments() {
    console.log('calculateAndDisplayInstallments called');
    console.log('currentStudentData:', window.currentStudentData);

    if (!window.currentStudentData) {
        console.log('No currentStudentData, showing message');
        const tbody = document.getElementById('installmentTableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Please load student details first.</td></tr>';
        return;
    }

    const planType = document.getElementById('payment-plan-type').value;
    console.log('Selected plan type:', planType);

    if (!planType) {
        console.log('No plan type selected, showing message');
        const tbody = document.getElementById('installmentTableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Please select a payment plan type.</td></tr>';
        return;
    }

    // Re-render current preview so discount/loan changes are reflected immediately.
    showInstallmentPreview();
}
// Calculate final amount after multiple discounts and SLT loan
function calculateFinalAmount() {
    const totalAmount = getDiscountBaseAmount();
    const discountSelects = document.querySelectorAll('.discount-select');
    const planType = document.getElementById('payment-plan-type')?.value;
    const isFullPaymentPlan = planType === 'full';

    const sltLoanDropdown = document.getElementById('slt-loan-applied');
    const sltLoanApplied = sltLoanDropdown ? sltLoanDropdown.value : 'no';

    const sltLoanAmountInput = document.getElementById('slt-loan-amount');
    const sltLoanAmount = sltLoanAmountInput ? parseFloat(sltLoanAmountInput.value) || 0 : 0;

    const finalAmountField = document.getElementById('final-amount');
    const breakdownModalBody = document.getElementById('breakdown-modal-body');

    let finalAmount = totalAmount;
    let totalDiscountAmount = 0;
    let totalDiscountPercentage = 0;
    let breakdownSteps = [`<strong>Base Total (Course Fee + Registration Fee):</strong> LKR ${totalAmount.toLocaleString()}`];

    // ===== Normal discounts =====
    if (isFullPaymentPlan) {
        discountSelects.forEach((select) => {
            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const discountType = selectedOption.dataset.type;
                const discountValue = parseFloat(selectedOption.dataset.value);

                if (discountType === 'percentage') {
                    totalDiscountPercentage += discountValue;
                } else if (discountType === 'amount') {
                    totalDiscountAmount += discountValue;
                }
            }
        });
    }

    if (totalDiscountPercentage > 0) {
        const pctReduction = finalAmount * totalDiscountPercentage / 100;
        finalAmount -= pctReduction;
        breakdownSteps.push(`<strong>-${totalDiscountPercentage}% Discount:</strong> -LKR ${pctReduction.toLocaleString()}`);
    }

    if (totalDiscountAmount > 0) {
        finalAmount -= totalDiscountAmount;
        breakdownSteps.push(`<strong>Fixed Discount:</strong> -LKR ${totalDiscountAmount.toLocaleString()}`);
    }

    // ===== Registration Fee Discount =====
    const registrationFeeDiscountSelect = document.getElementById('registration-fee-discount');
    if (isFullPaymentPlan && registrationFeeDiscountSelect && registrationFeeDiscountSelect.value) {
        const selectedOption = registrationFeeDiscountSelect.options[registrationFeeDiscountSelect.selectedIndex];
        const discountType = selectedOption.dataset.type;
        const discountValue = parseFloat(selectedOption.dataset.value || 0);

        const registrationFee = parseFloat(window.currentStudentData?.registration_fee || 0);
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = registrationFee * (discountValue / 100);
        } else if (discountType === 'amount') {
            discountAmount = discountValue;
        }

        if (discountAmount <= registrationFee) {
            finalAmount -= discountAmount;
            breakdownSteps.push(`<strong>Registration Fee Discount:</strong> -LKR ${discountAmount.toLocaleString()}`);
        } else {
            finalAmount -= registrationFee;
            const excess = discountAmount - registrationFee;
            finalAmount -= excess;
            breakdownSteps.push(`<strong>Registration Fee Wiped:</strong> -LKR ${registrationFee.toLocaleString()} + Excess Applied (-LKR ${excess.toLocaleString()})`);
        }
    }

    // ===== SLT Loan =====
    if (sltLoanApplied === 'yes' && sltLoanAmount > 0) {
        finalAmount -= sltLoanAmount;
        breakdownSteps.push(`<strong>SLT Loan:</strong> -LKR ${sltLoanAmount.toLocaleString()}`);
    }

    // ===== Finalize =====
    finalAmount = Math.max(0, finalAmount);
    breakdownSteps.push(`<strong>Final Amount:</strong> LKR ${finalAmount.toLocaleString()}`);

    if (finalAmountField) {
        finalAmountField.value = 'LKR ' + finalAmount.toLocaleString();
    }
    if (breakdownModalBody) {
        breakdownModalBody.innerHTML = breakdownSteps.join('<br>');
    }

    if (window.currentStudentData) {
        window.currentStudentData.final_amount = finalAmount;
    }
}

function toggleFullPaymentDiscountFields() {
    const planType = document.getElementById('payment-plan-type')?.value;
    const discountsContainer = document.getElementById('discounts-container');
    const registrationFeeDiscountSelect = document.getElementById('registration-fee-discount');
    const addDiscountBtn = document.getElementById('add-discount-btn');

    if (discountsContainer) {
        const discountItems = discountsContainer.querySelectorAll('.discount-item');
        discountItems.forEach((item) => {
            const select = item.querySelector('.discount-select');
            const removeBtn = item.querySelector('.remove-discount-btn');

            if (select) {
                select.disabled = false;
            }

            if (removeBtn) {
                removeBtn.disabled = false;
            }
        });
    }

    if (registrationFeeDiscountSelect) {
        registrationFeeDiscountSelect.disabled = false;
    }

    if (addDiscountBtn) {
        addDiscountBtn.disabled = false;
    }
}

// 🔗 Bind events once only
document.addEventListener('DOMContentLoaded', () => {
    toggleFullPaymentDiscountFields();
    // Registration Fee Discount
    const regFeeDiscount = document.getElementById('registration-fee-discount');
    if (regFeeDiscount) {
        regFeeDiscount.addEventListener('change', () => {
            calculateFinalAmount();
            if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        });
    }

    // SLT Loan Dropdown
    // Add event listener for SLT loan applied
const sltLoanAppliedField = document.getElementById('slt-loan-applied');
if (sltLoanAppliedField) {
    sltLoanAppliedField.addEventListener('change', function() {
        const sltLoanAmountField = document.getElementById('slt-loan-amount');
        const sltLoanStartField = document.getElementById('slt-loan-start-installment');
        const sltLoanYearsField = document.getElementById('slt-loan-years');
        if (this.value === 'yes') {
            sltLoanAmountField.disabled = false;
            sltLoanAmountField.required = true;
            if (sltLoanStartField) {
                sltLoanStartField.disabled = false;
                sltLoanStartField.required = true;
                if (!sltLoanStartField.value) sltLoanStartField.value = '1';
            }
            if (sltLoanYearsField) {
                sltLoanYearsField.disabled = false;
                sltLoanYearsField.required = true;
            }
        } else {
            sltLoanAmountField.disabled = true;
            sltLoanAmountField.required = false;
            sltLoanAmountField.value = '0'; // ✅ SET TO ZERO!
        }
        if (this.value !== 'yes' && sltLoanStartField) {
            sltLoanStartField.disabled = true;
            sltLoanStartField.required = false;
            sltLoanStartField.value = '';
        }
        if (this.value !== 'yes' && sltLoanYearsField) {
            sltLoanYearsField.disabled = true;
            sltLoanYearsField.required = false;
            sltLoanYearsField.value = '';
        }
        calculateFinalAmount();
        if (window.currentStudentData) {
            calculateAndDisplayInstallments();
        }
    });
}

    // SLT Loan Amount Input
    const sltLoanAmount = document.getElementById('slt-loan-amount');
    if (sltLoanAmount) {
        sltLoanAmount.addEventListener('input', function() {
            calculateFinalAmount();
            if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        });
    }

    const sltLoanStart = document.getElementById('slt-loan-start-installment');
    if (sltLoanStart) {
        sltLoanStart.addEventListener('input', function() {
            calculateFinalAmount();
            if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        });
    }

    const sltLoanYears = document.getElementById('slt-loan-years');
    if (sltLoanYears) {
        sltLoanYears.addEventListener('input', function() {
            calculateFinalAmount();
        });
    }

    // Normal Discounts
    document.querySelectorAll('.discount-select').forEach(el => {
        el.addEventListener('change', function() {
            calculateFinalAmount();
            if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        });
    });

    // Run once on load
    calculateFinalAmount();

    const params = new URLSearchParams(window.location.search);
    const studentNic = params.get('student_nic');
    const courseId = params.get('course_id');

    if (studentNic) {
        const nicInput = document.getElementById('update-student-nic');
        const courseSelect = document.getElementById('update-course');

        if (nicInput) {
            nicInput.value = studentNic;
            loadStudentCoursesForUpdate().then((loaded) => {
                if (loaded && courseId) {
                    const option = Array.from(courseSelect.options).find(opt => opt.value === courseId);
                    if (option) {
                        courseSelect.value = courseId;
                        loadPaymentRecords();
                    }
                }
            });
        }
    }
});

// Calculate and display installments
function calculateInstallments() {
    const planType = document.getElementById('payment-plan-type').value;

    if (!planType) {
        return;
    }

    if (!window.currentStudentData) {
        const tbody = document.getElementById('installmentTableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Please load student details first.</td></tr>';
        return;
    }

    // Plan type change should immediately reflect in preview using already-loaded data.
    if (typeof showInstallmentPreview === 'function') {
        showInstallmentPreview();
    }
}

// Create payment plan
function createPaymentPlan() {
  console.log('createPaymentPlan function called');

  // Prevent multiple simultaneous requests
  if (window.isCreatingPaymentPlan) {
    console.log('Payment plan creation already in progress...');
    showWarningMessage('Please wait for the current payment plan creation to complete.');
    return;
  }

  const planType       = document.getElementById('payment-plan-type').value;
  const discountSelects= document.querySelectorAll('.discount-select');
  const sltLoanApplied = document.getElementById('slt-loan-applied').value;
  const sltLoanAmount  = parseFloat(document.getElementById('slt-loan-amount').value || '0');
  const sltLoanStartInstallment = getSltLoanStartInstallment();
  const sltLoanYears = parseInt(document.getElementById('slt-loan-years')?.value || '0', 10);

  console.log('Form validation - currentStudentData:', window.currentStudentData);
  console.log('Form validation - planType:', planType);

  if (!window.currentStudentData || !window.currentStudentData.student_nic) {
    console.log('Validation failed: No student data');
    showErrorMessage('Please load student details first before creating a payment plan.');
    return;
  }
  if (!planType) {
    console.log('Validation failed: No plan type selected');
    showErrorMessage('Please select a payment plan type.');
    return;
  }
  if (sltLoanApplied === 'yes' && (!Number.isFinite(sltLoanYears) || sltLoanYears < 1)) {
    showErrorMessage('Please enter how many years the SLT loan has been taken.');
    return;
  }

  console.log('Form validation passed, proceeding with submission...');

  // Set flag to prevent multiple requests
  window.isCreatingPaymentPlan = true;
  showSpinner(true);

  // Disable submit button to prevent multiple clicks
  const submitBtn = document.querySelector('.btn-create-payment-plan');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ti ti-loader ti-spin me-2"></i>Creating...';
  }

      // collect discounts
    const selectedDiscounts = [];
    if (planType === 'full') {
        discountSelects.forEach((select) => {
            if (select.value) {
                const opt = select.options[select.selectedIndex];
                selectedDiscounts.push({
                    discount_id: parseInt(select.value, 10),
                    discount_type: opt.dataset.type,
                    discount_value: parseFloat(opt.dataset.value || '0')
                });
            }
        });
    }

    // collect registration fee discount
    const registrationFeeDiscountSelect = document.getElementById('registration-fee-discount');
    let registrationFeeDiscount = null;
    if (planType === 'full' && registrationFeeDiscountSelect && registrationFeeDiscountSelect.value) {
        const opt = registrationFeeDiscountSelect.options[registrationFeeDiscountSelect.selectedIndex];
        registrationFeeDiscount = {
            discount_id: parseInt(registrationFeeDiscountSelect.value, 10),
            discount_type: opt.dataset.type,      // "percentage" | "amount"
            discount_value: parseFloat(opt.dataset.value || '0')
        };
    }

  // 1) Get raw plan installments from backend (they already include `final_amount` after discount)
  fetchWithTimeout('/payment/get-installments', {
    method: 'POST',
    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
    body: JSON.stringify({
      student_nic: window.currentStudentData.student_nic,
      course_id: parseInt(window.currentStudentData.course_id, 10)
    })
  }, 15000)
  .then(r => {
    if (!r.ok) {
      throw new Error(`HTTP error! status: ${r.status}`);
    }
    return r.json();
  })
  .then(data => {
    if (!data.success) throw new Error(data.message || 'Failed to get payment plan installments');

        let usedFallbackDueDate = false;

    // Build installments INCLUDING final_amount
const sourceInstallments = (data.installments || []).map(inst => {
  const base = parseFloat(inst.amount || 0);
  const discounted = parseFloat(inst.final_amount || base);
  const discountAmount = Math.max(0, base - discounted);
    const originalDate = normalizeDueDate(inst.due_date, inst.installment_number, false);
    const normalizedDueDate = normalizeDueDate(inst.due_date, inst.installment_number, true);
    if (!originalDate && normalizedDueDate) {
        usedFallbackDueDate = true;
    }

  return {
    installment_number: inst.installment_number,
        due_date: normalizedDueDate,
    amount: base,
    discountedAmount: discounted,
    discount_amount: discountAmount,
    discount_note: inst.discount || null,
    registration_fee_discount_applied: 0,     // always start with 0
    registration_fee_discount_note: null,
    slt_loan_amount: 0,
    final_amount: discounted,
    status: 'pending'
  };
});

const loanAllocations = (sltLoanApplied === 'yes' && sltLoanAmount > 0)
  ? allocateSltLoanByStartInstallment(sourceInstallments, sltLoanAmount, sltLoanStartInstallment)
  : sourceInstallments.map(() => 0);

const installments = sourceInstallments.map((inst, index) => {
  const loanAmount = loanAllocations[index] || 0;
  return {
    ...inst,
    slt_loan_amount: loanAmount,
    final_amount: Math.max(0, inst.discountedAmount - loanAmount)
  };
});

if (usedFallbackDueDate) {
    showWarningMessage('Some installment due dates were invalid and were auto-corrected before saving.');
}

// ===============================
// Handle registration fee discount excess
// ===============================
if (registrationFeeDiscount && installments.length > 0) {
  const regFee = parseFloat(window.currentStudentData?.registration_fee || 0);
  let discountAmount = 0;

  if (registrationFeeDiscount.discount_type === 'percentage') {
    discountAmount = regFee * (registrationFeeDiscount.discount_value / 100);
  } else if (registrationFeeDiscount.discount_type === 'amount') {
    discountAmount = registrationFeeDiscount.discount_value;
  }

  if (discountAmount > regFee) {
    const excess = discountAmount - regFee;

    // ✅ Just mark excess for displayInstallments
    installments[0].registration_fee_discount_applied = excess;
    installments[0].registration_fee_discount_note = 'Reg. Fee Excess';
  }
}




    // totals
    const totalAmount = installments.reduce((s, i) => s + (i.amount || 0), 0);
    const finalTotal  = installments.reduce((s, i) => s + (i.final_amount || 0), 0);

    const payload = {
      student_id: window.currentStudentData.student_id,
      course_id: parseInt(window.currentStudentData.course_id, 10),
      payment_plan_type: planType,
      discounts: selectedDiscounts,
      slt_loan_applied: sltLoanApplied,
      slt_loan_amount: sltLoanApplied === 'yes' ? sltLoanAmount : 0,
      slt_loan_start_installment: sltLoanApplied === 'yes' ? sltLoanStartInstallment : null,
      slt_loan_years: sltLoanApplied === 'yes' ? sltLoanYears : null,
      total_amount: totalAmount,
      final_amount: finalTotal, // top-level summary after discount + loan
      installments: installments
    };

    // Only include registration_fee_discount if it exists
    if (registrationFeeDiscount) {
      payload.registration_fee_discount = registrationFeeDiscount;
    }

    console.log('Payload being sent:', payload);

    // 2) Create plan
    return fetchWithTimeout('/payment/create-payment-plan', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
      body: JSON.stringify(payload)
    }, 15000);
  })
    .then(async r => {
        console.log('Response status:', r.status);
        const responseData = await r.json().catch(() => ({}));
        if (!r.ok) {
            throw new Error(responseData.message || `HTTP error! status: ${r.status}`);
        }
        return responseData;
  })
  .then(data => {
    console.log('Response data:', data);
    if (!data.success) {
      throw new Error(data.message || 'Failed to create payment plan.');
    }

    showSuccessMessage('Payment plan created successfully! 🎉');
    resetPaymentPlanForm();

    // Reset the call count since we successfully created a plan
    window.loadExistingPlansCallCount = 0;
  })
  .catch(err => {
    console.error('Error creating payment plan:', err);
    showErrorMessage(err.message || 'An error occurred while creating payment plan.');
  })
  .finally(() => {
    showSpinner(false);
    // Reset the flag
    window.isCreatingPaymentPlan = false;

    // Re-enable submit button
        const submitBtn = document.querySelector('.btn-create-payment-plan');
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="ti ti-check me-2"></i>Submit';
    }
  });
}

// Reset payment plan form
function resetPaymentPlanForm() {
    document.getElementById('createPaymentPlanForm').reset();
    document.getElementById('installmentTableBody').innerHTML = '';
    document.getElementById('paymentPlanFormSection').style.display = 'none';
    document.getElementById('existingPaymentPlansSection').style.display = 'none';

    // Reset discount fields to only one
    const discountsContainer = document.getElementById('discounts-container');
    const discountItems = discountsContainer.querySelectorAll('.discount-item');

    // Remove all discount items except the first one
    for (let i = 1; i < discountItems.length; i++) {
        discountItems[i].remove();
    }

    // Reset the first discount select
    const firstDiscountSelect = discountsContainer.querySelector('.discount-select');
    if (firstDiscountSelect) {
        firstDiscountSelect.value = '';
    }

    // Reset registration fee discount
    const registrationFeeDiscountSelect = document.getElementById('registration-fee-discount');
    if (registrationFeeDiscountSelect) {
        registrationFeeDiscountSelect.value = '';
    }

    // Show the warning message since student data is cleared
    const statusIndicator = document.getElementById('student-data-status');
    if (statusIndicator) {
        statusIndicator.style.display = 'block';
    }

    // Reset error states
    resetErrorStates();

    window.currentStudentData = null;
}

// Render payment plans table
function renderPaymentPlans() {
    const tbody = document.getElementById('paymentPlansTableBody');
    tbody.innerHTML = '';

    paymentPlans.forEach((plan, index) => {
        const row = `<tr>
            <td>${plan.student_id}</td>
            <td>${plan.student_name}</td>
            <td>${plan.student_nic}</td>
            <td>${plan.course_name}</td>
            <td>Rs. ${plan.course_fee.toLocaleString()}</td>
            <td>Rs. ${plan.franchise_fee.toLocaleString()}</td>
            <td>Rs. ${plan.registration_fee.toLocaleString()}</td>
            <td>Rs. ${plan.total_amount.toLocaleString()}</td>
            <td>Rs. ${plan.paid_amount.toLocaleString()}</td>
            <td>Rs. ${plan.outstanding_amount.toLocaleString()}</td>
            <td>
                <select class="form-select payment-plan-select" data-plan-index="${index}">
                    <option value="Monthly" ${plan.payment_plan === 'Monthly' ? 'selected' : ''}>Monthly</option>
                    <option value="Quarterly" ${plan.payment_plan === 'Quarterly' ? 'selected' : ''}>Quarterly</option>
                    <option value="Semester" ${plan.payment_plan === 'Semester' ? 'selected' : ''}>Semester</option>
                    <option value="Full" ${plan.payment_plan === 'Full' ? 'selected' : ''}>Full Payment</option>
                </select>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-primary btn-edit-payment-plan" data-plan-index="${index}">
                    <i class="ti ti-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-info btn-view-payment-history" data-plan-index="${index}">
                    <i class="ti ti-history"></i>
                </button>
            </td>
        </tr>`;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

// Update payment plan
window.updatePaymentPlan = function(index, value) {
    paymentPlans[index].payment_plan = value;
}

// Save payment plans
function savePaymentPlans() {
    if (paymentPlans.length === 0) {
        showWarningMessage('No payment plan to save.');
        return;
    }

    const plan = paymentPlans[0];
    showSpinner(true);

    fetch('/payment/save-plans', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            student_id: plan.student_id,
            course_id: document.getElementById('plan-course').value,
            payment_plan: plan.payment_plan
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(data.message || 'Payment plan saved successfully! ✨');
        } else {
            showErrorMessage(data.message || 'Failed to save payment plan.');
        }
    })
    .catch(() => {
        showErrorMessage('An error occurred while saving payment plan.');
    })
    .finally(() => showSpinner(false));
}

async function generatePaymentSlip() {
  const selected = document.querySelector('input[name="selectedPayment"]:checked');
  if (!selected) return showWarningMessage('Please select a payment to generate a slip.');

  const idx         = parseInt(selected.value, 10);
  const row         = (window.paymentDetailsData || [])[idx];
  const studentId   = (document.getElementById('slip-student-id').value || '').trim();
  const paymentType = document.getElementById('slip-payment-type').value;
  const courseId    = parseInt(document.getElementById('slip-course').value || '0', 10); // ✅ required by backend

  if (!row)        return showErrorMessage('Selected payment data not found.');
  if (!studentId)  return showErrorMessage('Please enter Student ID / NIC.');
  if (!paymentType)return showErrorMessage('Please select a payment type.');
  if (!courseId)   return showErrorMessage('Please select a course.');

  // Check if the selected payment is already paid
  if (row.status && row.status.toLowerCase() === 'paid') {
    return showErrorMessage('Cannot generate slip for an already paid installment.');
  }

  // Payable amount we rendered (backend will recompute/validate anyway)
  const rawAmount     = Number(row.amount || 0);
  const installmentNo = row.installment_number ?? null;
  const dueDate       = row.due_date ?? null;

    // FX inputs + SSCL & Bank Charges (only franchise)
let conversionRate = null, currencyFrom = null, ssclTaxAmount = 0, bankCharges = 0;

if (paymentType === 'franchise_fee') {
    // ✅ Ensure installment is selected first
    const selectedInstallment = document.querySelector('input[name="selectedPayment"]:checked');
    if (!selectedInstallment) {
        showErrorMessage('Please select an installment before entering SSCL or Bank Charges.');
        return;
    }

    // ✅ Conversion Rate
    conversionRate = Number(document.getElementById('currency-conversion-rate').value || 0);
    currencyFrom   = document.getElementById('currency-from').value;

    if (!conversionRate || conversionRate <= 0) {
        showErrorMessage('Please enter a valid currency conversion rate for franchise fee.');
        return;
    }

    // Keep charges synced with selected installment defaults before payload
    syncFranchiseChargeInputsToSelection();

    // ✅ SSCL and bank charges from synced inputs
    ssclTaxAmount = parseFloat(document.getElementById('sscl-tax-amount').value || 0);
    bankCharges = parseFloat(document.getElementById('bank-charges').value || 0);
}


showSpinner(true);

const payload = {
    student_id:         studentId,
    course_id:          courseId,
    payment_type:       paymentType,
    amount:             rawAmount,
    installment_number: installmentNo,
    due_date:           dueDate,
    conversion_rate:    conversionRate,
    currency_from:      currencyFrom,
    sscl_tax_amount:    ssclTaxAmount,
    bank_charges:       bankCharges,
    remarks:            '',
    payment_effective_date: (document.getElementById('payment-effective-date')?.value || null)
};


  try {
    const res  = await fetch('/payment/generate-slip', {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN' : '{{ csrf_token() }}',
        'Accept'       : 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const text = await res.text(); // guard HTML errors
    let data; try { data = JSON.parse(text); }
    catch { throw new Error('Server returned an unexpected response. Check Network tab for details.'); }

    if (!data.success) throw new Error(data.message || 'Failed to generate payment slip.');

    // Cache for print/download
    window.currentSlipData = data.slip_data;
    const s = data.slip_data;

    // ===== Delete button setup =====
    const deleteBtn = document.getElementById('delete-slip-btn');
    if (s && s.id) {
    deleteBtn.style.display = "inline-block"; // show the button
    deleteBtn.onclick = function () {
        deleteSlip(s.id);
    };
    } else {
    deleteBtn.style.display = "none"; // hide if no slip id
    }


    // ===== On-page preview =====
    setText('slip-student-id-display',   s.student_id);
    setText('slip-student-name-display', s.student_name);
    setText('slip-course-display',       s.course_name);
    setText('slip-intake-display',       s.intake);

    setText('slip-intallment-type',       s.intallment_type);

    setText('slip-payment-type-display', s.payment_type_display || s.payment_type);

    if (paymentType === 'franchise_fee') {
        document.getElementById('franchiseAmountsSection').style.display = 'block';

        setText('slip-sscl-amount', `LKR ${Number(s.sscl_tax_amount || 0).toLocaleString()}`);
        setText('slip-bank-amount', `LKR ${Number(s.bank_charges || 0).toLocaleString()}`);
        setText('slip-final-amount', `LKR ${Number(s.total_fee || 0).toLocaleString()}`);
    } else {
        document.getElementById('franchiseAmountsSection').style.display = 'none';
    }



    setText('slip-installment-display',  installmentNo ?? '-');
    setText('slip-due-date-display',     dueDate ? formatDateDmy(dueDate) : '-');
    setText('slip-date-display',         s.payment_date ? formatDateDmy(s.payment_date) : '');
    setText('slip-receipt-no-display',   s.receipt_no || '');

    // Amount text (FX + LKR for franchise; LKR for others)
    let amountDisplay;
    if (paymentType === 'franchise_fee' && s.franchise_fee_currency) {
      const fx  = Number(s.amount || 0);
      const lkr = Number(s.lkr_amount || (fx * (conversionRate || 0)));
      amountDisplay = `${s.franchise_fee_currency} ${fx.toLocaleString()} (LKR ${lkr.toLocaleString()})`;
    } else {
      amountDisplay = `LKR ${Number(s.amount || 0).toLocaleString()}`;
    }
    setText('slip-amount-display', amountDisplay);

    // Show preview
    document.getElementById('slipPreviewSection').style.display = 'block';
    document.getElementById('slipPreviewSection').scrollIntoView({ behavior: 'smooth' });

    // ===== Fill print template =====
    setText('print-student-id', s.student_id);
    setText('print-student-name', s.student_name);
    setText('print-course', s.course_name);
    setText('print-intake', s.intake);
    setText('print-location', s.location);
    setText('print-registration-date', s.registration_date ? formatDateDmy(s.registration_date) : 'N/A');
    setText('print-payment-type', s.payment_type_display || s.payment_type);
    setText('print-installment', installmentNo ?? '-');
    setText('print-due-date', dueDate ? formatDateDmy(dueDate) : '-');

    if (paymentType === 'franchise_fee' && s.franchise_fee_currency) {
      setText('print-amount', `${s.franchise_fee_currency} ${Number(s.amount||0).toLocaleString()}`);
    } else {
      setText('print-amount', `LKR ${Number(s.amount||0).toLocaleString()}`);
    }

    setText('print-receipt-no', s.receipt_no);
    setText('print-valid-until', s.valid_until ? formatDateDmy(s.valid_until) : 'N/A');

    // Breakdown rows (as returned by backend)
    setText('print-course-fee',       Number(s.course_fee || 0).toLocaleString());
    setText('print-franchise-fee',    Number(s.franchise_fee || 0).toLocaleString());
    setText('print-registration-fee', Number(s.registration_fee || 0).toLocaleString());

    // Total on slip (LKR if franchise with FX)
    const totalForPrint = (paymentType === 'franchise_fee')
      ? Number(s.lkr_amount || 0)
      : Number(s.amount || 0);
    setText('print-total-amount', totalForPrint.toLocaleString());

    setText('print-generated-date', new Date().toLocaleString());

    showSuccessMessage(data.message || 'Payment slip generated successfully! 🎉');

    // Update the payment status in the table
    const selectedIndex = parseInt(document.querySelector('input[name="selectedPayment"]:checked')?.value || '-1', 10);
    if (selectedIndex >= 0 && window.paymentDetailsData && window.paymentDetailsData[selectedIndex]) {
        // Update local data immediately for UI feedback
        window.paymentDetailsData[selectedIndex].status = 'paid';

        // Force refresh the table to show the LATEST data from backend
        // Wait longer to ensure database transaction is fully committed
        if (typeof refreshGenerateSlipsData === 'function') {
            console.log('Scheduling payment table refresh after 2.5 seconds...');
            setTimeout(() => {
                console.log('Calling refreshGenerateSlipsData()...');
                refreshGenerateSlipsData();
            }, 2500); // Increased from 1500ms to 2500ms to ensure DB commit finishes
        }
    }
  } catch (err) {
    console.error(err);
    showErrorMessage(err.message || 'An error occurred while generating payment slip.');
  } finally {
    showSpinner(false);
  }
}

function setText(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = (val == null ? '' : String(val));
}

function recalculateSSCL() {
    const selected = document.querySelector('input[name="selectedPayment"]:checked');
    if (!selected) {
        showWarningMessage('Please select an installment first.');
        document.getElementById('sscl-value').value = 0;
        document.getElementById('sscl-tax-amount').value = 0;
        return;
    }

    const type   = document.getElementById('sscl-type').value;
    const value  = parseFloat(document.getElementById('sscl-value').value || 0);

    // Base franchise fee in LKR (after conversion)
    const row = (window.paymentDetailsData || [])[selected.value];
    const rawAmount = Number(row?.amount || 0);
    const conversionRate = Number(document.getElementById('currency-conversion-rate').value || 0);
    const baseFranchise = conversionRate > 0 ? rawAmount * conversionRate : rawAmount;

    let ssclAmount = 0;
    if (type === 'percentage') {
        ssclAmount = (baseFranchise * value) / 100;
    } else {
        ssclAmount = value;
    }

    document.getElementById('sscl-tax-amount').value = ssclAmount.toFixed(2);
}



// ============ (Optional) Print remains same but uses cached slipData ============
function printPaymentSlip() {
  const s = window.currentSlipData;
  if (!s) return showErrorMessage('No slip data available for printing.');

  setText('print-generated-date', formatDateDmy(new Date()));
  setText('print-student-id', s.student_id);
  setText('print-student-name', s.student_name);
  setText('print-course', s.course_name);
  setText('print-intake', s.intake);
  setText('print-location', s.location);
  setText('print-registration-date', s.registration_date ? formatDateDmy(s.registration_date) : 'N/A');
  setText('print-payment-type', s.payment_type_display || s.payment_type);
  setText('print-installment', s.installment_number || 'N/A');
  setText('print-due-date', s.due_date ? formatDateDmy(s.due_date) : 'N/A');

  if (s.payment_type === 'franchise_fee' && s.franchise_fee_currency) {
    setText('print-amount', `${s.franchise_fee_currency} ${Number(s.amount||0).toLocaleString()}`);
  } else {
    setText('print-amount', `LKR ${Number(s.amount||0).toLocaleString()}`);
  }

  setText('print-receipt-no', s.receipt_no);
  setText('print-valid-until', s.valid_until ? formatDateDmy(s.valid_until) : 'N/A');

  setText('print-course-fee',       Number(s.course_fee || 0).toLocaleString() + '.00');
  setText('print-franchise-fee',    Number(s.franchise_fee || 0).toLocaleString() + '.00');
  setText('print-registration-fee', Number(s.registration_fee || 0).toLocaleString() + '.00');

  const totalForPrint = (s.payment_type === 'franchise_fee')
    ? Number(s.lkr_amount || 0)
    : Number(s.amount || 0);
  setText('print-total-amount', totalForPrint.toLocaleString() + '.00');

  // Show print template and print
  document.getElementById('printableSlip').style.display = 'block';
  const main = document.querySelector('.container-fluid');
  const prev = main.style.display;
  main.style.display = 'none';
  window.print();
  setTimeout(() => {
    main.style.display = prev;
    document.getElementById('printableSlip').style.display = 'none';
  }, 1000);
}

// Download payment slip
function downloadPaymentSlip() {
    if (!window.currentSlipData) {
        showErrorMessage('No slip data available for download.');
        return;
    }

    showSpinner(true);

    // Create a form to submit the receipt number
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/payment/download-slip-pdf';
    form.target = '_blank';

    // Add CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);

    // Add receipt number
    const receiptInput = document.createElement('input');
    receiptInput.type = 'hidden';
    receiptInput.name = 'receipt_no';
    receiptInput.value = window.currentSlipData.receipt_no;
    form.appendChild(receiptInput);

    // Append form to body, submit, and remove
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    showSpinner(false);
    showSuccessMessage('PDF download started!');
}

// Save payment record
function savePaymentRecord() {
    if (!window.currentSlipData) {
        showErrorMessage('No slip data available for saving.');
        return;
    }

    // Show the payment details modal instead of using prompt
    showPaymentDetailsModal();
}

// Load payment records
function loadPaymentRecords() {
    const studentNic = document.getElementById('update-student-nic').value;
    const courseId = document.getElementById('update-course').value;

    if (!studentNic || !courseId) {
        showWarningMessage('Please enter student NIC and select a course.');
        return;
    }

    showSpinner(true);

    fetch('/payment/get-records', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            student_nic: studentNic,
            course_id: courseId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.paymentRecords = data.records;
            renderPaymentRecords();
            document.getElementById('paymentRecordsSection').style.display = 'block';
            showSuccessMessage('Payment records loaded successfully!');
        } else {
            showErrorMessage(data.message || 'Failed to load payment records.');
            document.getElementById('paymentRecordsSection').style.display = 'none';
        }
    })
    .catch(() => {
        showErrorMessage('An error occurred while loading payment records.');
        document.getElementById('paymentRecordsSection').style.display = 'none';
    })
    .finally(() => showSpinner(false));
}

// Load courses for student when NIC is entered
function loadStudentCoursesForUpdate() {
    const studentNic = document.getElementById('update-student-nic').value;

    if (!studentNic) {
        document.getElementById('update-course').innerHTML = '<option value="" selected disabled>Select a Course</option>';
        return Promise.resolve(false);
    }

    showSpinner(true);

    return fetch('/payment/get-student-courses', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            student_nic: studentNic
        })
    })
    .then(response => response.json())
    .then(data => {
        const courseSelect = document.getElementById('update-course');
        courseSelect.innerHTML = '<option value="" selected disabled>Select a Course</option>';

        if (data.success) {
            data.courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.course_name;
                courseSelect.appendChild(option);
            });

            showSuccessMessage('Courses loaded successfully!');
            return true;
        }

        showErrorMessage(data.message || 'Failed to load courses.');
        return false;
    })
    .catch(() => {
        showErrorMessage('An error occurred while loading courses.');
        document.getElementById('update-course').innerHTML = '<option value="" selected disabled>Select a Course</option>';
        return false;
    })
    .finally(() => showSpinner(false));
}

function resetSltLoanAutoFields() {
    document.getElementById('sltLoanYears').value = '';
    document.getElementById('sltLoanInstallmentCount').value = '';
    document.getElementById('sltLoanStartInstallment').value = '';
    document.getElementById('sltPlanLoanAmount').value = '';
    document.getElementById('sltLoanAmount').value = '';
    document.getElementById('sltLoanSummary').style.display = 'none';
}

const SLT_MONTHS_PER_YEAR = 12;

function calculateSltLoanInstallmentCount(years) {
    const parsedYears = parseInt(years, 10) || 0;
    return parsedYears > 0 ? parsedYears * SLT_MONTHS_PER_YEAR : 0;
}

function updateSltLoanInstallmentCountField() {
    const years = document.getElementById('sltLoanYears')?.value;
    const installmentCount = calculateSltLoanInstallmentCount(years);
    const installmentField = document.getElementById('sltLoanInstallmentCount');
    if (installmentField) {
        installmentField.value = installmentCount > 0 ? installmentCount : '';
    }
}

function loadStudentCoursesForSltLoan() {
    const studentNic = document.getElementById('slt-loan-student-nic')?.value;

    if (!studentNic) {
        const courseSelect = document.getElementById('slt-loan-course');
        if (courseSelect) {
            courseSelect.innerHTML = '<option value="" selected disabled>Select a Course</option>';
            courseSelect.disabled = true;
        }
        resetSltLoanAutoFields();
        return;
    }

    showSpinner(true);

    fetch('/payment/get-student-courses', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({ student_nic: studentNic })
    })
    .then(response => response.json())
    .then(data => {
        const courseSelect = document.getElementById('slt-loan-course');
        courseSelect.innerHTML = '<option value="" selected disabled>Select a Course</option>';
        resetSltLoanAutoFields();

        if (data.success) {
            data.courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.course_name;
                courseSelect.appendChild(option);
            });
            courseSelect.disabled = data.courses.length === 0;
        } else {
            courseSelect.disabled = true;
            showErrorMessage(data.message || 'Failed to load courses.');
        }
    })
    .catch(() => {
        showErrorMessage('An error occurred while loading courses.');
        const courseSelect = document.getElementById('slt-loan-course');
        courseSelect.innerHTML = '<option value="" selected disabled>Select a Course</option>';
        courseSelect.disabled = true;
        resetSltLoanAutoFields();
    })
    .finally(() => showSpinner(false));
}

function loadSltLoanPlanDetails() {
    const studentNic = document.getElementById('slt-loan-student-nic')?.value;
    const courseId = document.getElementById('slt-loan-course')?.value;

    if (!studentNic || !courseId) {
        resetSltLoanAutoFields();
        return;
    }

    showSpinner(true);

    fetch('{{ route("payment.existingPlans") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            student_nic: studentNic,
            course_id: courseId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            resetSltLoanAutoFields();
            showErrorMessage(data.message || 'Failed to load payment plan details.');
            return;
        }

        const plans = data.plans || [];
        const plan = plans.find(p => p.status !== 'archived') || plans[0];

        if (!plan) {
            resetSltLoanAutoFields();
            showWarningMessage('No payment plan found. Create a payment plan first.');
            return;
        }

        if ((plan.slt_loan_applied || '').toLowerCase() !== 'yes') {
            showWarningMessage('This payment plan does not have an SLT loan applied.');
        }

        document.getElementById('sltLoanYears').value = plan.slt_loan_years || '';
        updateSltLoanInstallmentCountField();
        document.getElementById('sltLoanStartInstallment').value = plan.slt_loan_start_installment || '';
        document.getElementById('sltPlanLoanAmount').value = plan.slt_loan_amount > 0 ? plan.slt_loan_amount : '';

        if (plan.slt_receivable_effective_date) {
            document.getElementById('sltLoanEffectiveDate').value = plan.slt_receivable_effective_date;
        }

        updateSltReceivableSummary();
    })
    .catch(() => {
        resetSltLoanAutoFields();
        showErrorMessage('An error occurred while loading payment plan details.');
    })
    .finally(() => showSpinner(false));
}

function renderPaymentRecords() {
  const tbody = document.getElementById('paymentRecordsTableBody');
    if (!tbody) return;

    tbody.innerHTML = "";

    let modalContainer = document.getElementById('payment-record-history-modals');
    if (!modalContainer) {
        modalContainer = document.createElement('div');
        modalContainer.id = 'payment-record-history-modals';
        document.body.appendChild(modalContainer);
    }
    modalContainer.innerHTML = '';

    const records = Array.isArray(window.paymentRecords) ? window.paymentRecords : [];

    if (!records.length) {
        tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted">No payment records found.</td></tr>';
        return;
    }

    records.forEach((r) => {
    const modalId = `historyModal-${r.payment_id}`; // ✅ Use payment_id instead of id

    // ✅ Map payment_type to display name
    const paymentTypeDisplay = {
      'course_fee': 'Course Fee',
      'franchise_fee': 'Franchise Fee',
      'registration_fee': 'Registration Fee',
      'other': 'Other'
    }[r.payment_type] || r.payment_type || 'N/A';

    const isPaid = String(r.status || '').toLowerCase() === 'paid';
    const remaining = Number(r.remaining_amount ?? 0);
    const payDisabled = isPaid || remaining <= 0;

    const row = `
      <tr ${isPaid ? 'class="table-secondary"' : ''}>
        <td>${r.student_id ?? '-'}</td>
        <td>${r.student_name ?? '-'}</td>
        <td>${paymentTypeDisplay}</td>
        <td>${r.installment_number ?? '-'}</td>
        <td>${Number(r.amount ?? 0).toLocaleString()}</td>
        <td>${Number(r.late_fee ?? 0).toLocaleString()}</td>
        <td>${Number(r.approved_late_fee ?? 0).toLocaleString()}</td>
        <td>${Number(r.total_fee ?? 0).toLocaleString()}</td>
        <td>${remaining.toLocaleString()}</td>
        <td>${r.payment_method ?? '-'}</td>
        <td>${r.payment_date ?? '-'}</td>
        <td>${r.receipt_no ?? '-'}</td>
        <td>${r.status ?? '-'}</td>
        <td>
          <button class="btn btn-sm btn-success btn-open-pay-modal"
                  data-payment-id="${r.payment_id}"
                  data-remaining-amount="${remaining}"
                  ${payDisabled ? 'disabled' : ''}>
            <i class="ti ti-cash me-1"></i>${payDisabled ? 'Pay Disabled' : 'Pay'}
          </button>
          <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#${modalId}">
            <i class="ti ti-history me-1"></i>History
          </button>
        </td>
            </tr>
    `;
    tbody.insertAdjacentHTML("beforeend", row);

        const modalMarkup = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Payment History - ${r.receipt_no ?? '-'}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${renderHistoryList(r.partial_payments)}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        modalContainer.insertAdjacentHTML('beforeend', modalMarkup);
  });
}

function renderHistoryList(payments) {
  if (!payments || payments.length === 0) {
    return `<p class="text-muted">No partial payments yet.</p>`;
  }

  return `
    <ul class="list-group">
      ${payments.map(p => `
        <li class="list-group-item">
          <strong>${p.date}</strong> -
          LKR ${Number(p.amount).toLocaleString()} (${p.method})
          ${p.remarks ? `<br><small>${p.remarks}</small>` : ""}
          ${p.slip ? `<br><a href="/storage/${p.slip}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">Download Slip</a>` : ""}
        </li>
      `).join("")}
    </ul>
  `;
}


function openPayModal(paymentId, remaining) {
  if (!remaining || Number(remaining) <= 0) {
    showWarningMessage('This installment is already paid or has no remaining balance.');
    return;
  }

  document.getElementById('pay-payment-id').value = paymentId;
  const payAmountInput = document.getElementById('pay-amount');
  payAmountInput.value = Number(remaining).toFixed(2);
  payAmountInput.min = Number(remaining).toFixed(2);
  payAmountInput.max = Number(remaining).toFixed(2);
  payAmountInput.step = '0.01';
  payAmountInput.readOnly = true;
  document.getElementById('pay-date').value = window.toLocalDateString(new Date());

  const modal = new bootstrap.Modal(document.getElementById('payModal'));
  modal.show();
}


function submitPayment() {
  const paymentId = document.getElementById('pay-payment-id').value;
  const amount    = parseFloat(document.getElementById('pay-amount').value || 0);
  const method    = document.getElementById('pay-method').value;
  const date      = document.getElementById('pay-date').value;
  const remarks   = document.getElementById('pay-remarks').value;
  const slipFile  = document.getElementById('pay-slip').files[0]; // optional file

  const remaining = parseFloat(document.getElementById('pay-amount').max || 0);

  if (!amount || amount <= 0) {
    showErrorMessage("Enter a valid payment amount.");
    return;
  }

  if (!remaining || amount !== remaining) {
    showErrorMessage('Payment amount must match the remaining installment amount.');
    return;
  }

  // Use FormData to handle file
  const formData = new FormData();
  formData.append("payment_id", paymentId);
  formData.append("amount", amount);
  formData.append("payment_method", method);
  formData.append("payment_date", date);
  formData.append("remarks", remarks);
  if (slipFile) {
    formData.append("slip", slipFile);
  }

  fetch('/payment/make-payment', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
    },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      showSuccessMessage("Payment recorded successfully!");
      bootstrap.Modal.getInstance(document.getElementById('payModal')).hide();
      loadPaymentRecords(); // refresh table
      refreshGenerateSlipsData(); // refresh Generate Slips tab if data is loaded
    } else {
      showErrorMessage(data.message || "Failed to record payment.");
    }
  })
  .catch(() => showErrorMessage("An error occurred while making payment."));
}



// Update payment records
function updatePaymentRecords() {
    const updates = [];
    const editableFields = document.querySelectorAll('#paymentRecordsTableBody [data-field]');

    if (!editableFields.length) {
        showWarningMessage('No editable payment fields found to update.');
        return;
    }

    editableFields.forEach(el => {
        const idx = el.dataset.idx;
        const field = el.dataset.field;
        const value = el.value;
        const record = window.paymentRecords?.[idx] || {};
        const paymentId = record.payment_id || record.id;

        if (!paymentId) return;

        if (!updates[idx]) updates[idx] = { id: paymentId };
        updates[idx][field] = value;
    });

    const filteredUpdates = updates.filter(Boolean);
    if (!filteredUpdates.length) {
        showWarningMessage('No valid payment updates found.');
        return;
    }

    showSpinner(true);

    fetch('/payment/update-record', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ updates: filteredUpdates })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(data.message || 'Payment records updated successfully!');
            loadPaymentRecords();
            refreshGenerateSlipsData(); // refresh Generate Slips tab if data is loaded
        } else {
            showErrorMessage(data.message || 'Failed to update records.');
        }
    })
    .catch(() => showErrorMessage('An error occurred while updating records.'))
    .finally(() => showSpinner(false));
}


// Load courses for student when NIC is entered (for summary)
function loadStudentCoursesForSummary() {
    const studentNic = document.getElementById('summary-student-nic').value;

    if (!studentNic) {
        document.getElementById('summary-course').innerHTML = '<option value="" selected disabled>Select a Course</option>';
        return;
    }

    showSpinner(true);

    fetch('/payment/get-student-courses', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            student_nic: studentNic
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const courseSelect = document.getElementById('summary-course');
            courseSelect.innerHTML = '<option value="" selected disabled>Select a Course</option>';

            data.courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.course_name;
                courseSelect.appendChild(option);
            });

            showSuccessMessage('Courses loaded successfully!');
        } else {
            showErrorMessage(data.message || 'Failed to load courses.');
            document.getElementById('summary-course').innerHTML = '<option value="" selected disabled>Select a Course</option>';
        }
    })
    .catch(() => {
        showErrorMessage('An error occurred while loading courses.');
        document.getElementById('summary-course').innerHTML = '<option value="" selected disabled>Select a Course</option>';
    })
    .finally(() => showSpinner(false));
}

// Generate payment summary
function generatePaymentSummary() {
    const studentNic = document.getElementById('summary-student-nic').value;
    const courseId = document.getElementById('summary-course').value;

    if (!studentNic || !courseId) {
        showWarningMessage('Please enter student NIC and select a course.');
        return;
    }

    showSpinner(true);

    fetch('/payment/get-summary', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            student_nic: studentNic,
            course_id: courseId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPaymentSummary(data.summary);
            document.getElementById('paymentSummarySection').style.display = 'block';
            showSuccessMessage('Payment summary generated successfully!');
        } else {
            showErrorMessage(data.message || 'Failed to generate payment summary.');
            document.getElementById('paymentSummarySection').style.display = 'none';
        }
    })
    .catch(() => {
        showErrorMessage('An error occurred while generating payment summary.');
        document.getElementById('paymentSummarySection').style.display = 'none';
    })
    .finally(() => showSpinner(false));
}

// Display payment summary
function displayPaymentSummary(summary) {
    console.log('displayPaymentSummary called with summary:', summary);

    // Display student information
    document.getElementById('summary-student-id').textContent = summary.student.student_id || 'N/A';
    document.getElementById('summary-student-name').textContent = summary.student.student_name || 'N/A';
    document.getElementById('summary-course-name').textContent = summary.student.course_name || 'N/A';
    document.getElementById('summary-registration-date').textContent = summary.student.registration_date || 'N/A';
    document.getElementById('summary-total-course-fee').textContent = 'LKR ' + parseFloat(summary.student.total_amount || 0).toLocaleString();
    document.getElementById('summary-total-paid').textContent = 'LKR ' + parseFloat(summary.total_paid || 0).toLocaleString();

    // Update summary cards
    document.getElementById('total-amount').textContent = 'LKR ' + parseFloat(summary.total_amount || 0).toLocaleString();
    document.getElementById('total-paid').textContent = 'LKR ' + parseFloat(summary.total_paid || 0).toLocaleString();
    document.getElementById('total-outstanding').textContent = 'LKR ' + parseFloat(summary.total_outstanding || 0).toLocaleString();
    document.getElementById('payment-rate').textContent = (summary.payment_rate || 0) + '%';

    // Populate separate tables for each payment type
    populatePaymentTypeTable('courseFeeTableBody', summary.payment_details.find(d => d.payment_type === 'Course Fee') || {});
    populatePaymentTypeTable('franchiseFeeTableBody', summary.payment_details.find(d => d.payment_type === 'Franchise Fee') || {});
    populatePaymentTypeTable('registrationFeeTableBody', summary.payment_details.find(d => d.payment_type === 'Registration Fee') || {});
    populatePaymentTypeTable('hostelFeeTableBody', summary.payment_details.find(d => d.payment_type === 'Hostel Fee') || {});
    populatePaymentTypeTable('libraryFeeTableBody', summary.payment_details.find(d => d.payment_type === 'Library Fee') || {});
    populatePaymentTypeTable('otherFeeTableBody', summary.payment_details.find(d => d.payment_type === 'Other') || {});
}

// Populate individual payment type table
function populatePaymentTypeTable(tableId, paymentData) {
    const tbody = document.getElementById(tableId);
    tbody.innerHTML = '';

    if (!paymentData || !paymentData.payments || paymentData.payments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No data available</td></tr>';
        return;
    }

    // Sort payments by date (most recent first)
    const sortedPayments = paymentData.payments.sort((a, b) => new Date(b.payment_date) - new Date(a.payment_date));

    sortedPayments.forEach(payment => {
        const row = `<tr>
            <td>LKR ${parseFloat(payment.total_amount || 0).toLocaleString()}</td>
            <td>LKR ${parseFloat(payment.paid_amount || 0).toLocaleString()}</td>
            <td>LKR ${parseFloat(payment.outstanding || 0).toLocaleString()}</td>
            <td>${payment.payment_date || 'N/A'}</td>
            <td>${payment.due_date || 'N/A'}</td>
            <td>${payment.receipt_no || 'N/A'}</td>
            <td>
                ${payment.uploaded_receipt ?
                    `<a href="/storage/${payment.uploaded_receipt}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-download me-1"></i>View
                    </a>` :
                    '<span class="text-muted">Not uploaded</span>'
                }
            </td>
            <td>${payment.installment_number || 'N/A'}</td>
        </tr>`;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

// Export payment summary
function exportPaymentSummary(format) {
    showToast('Info', `${format.toUpperCase()} export functionality will be implemented soon.`, 'bg-info');
}

// Placeholder functions for editing and deleting
function editPaymentPlan(index) {
    showToast('Info', 'Edit payment plan functionality will be implemented soon.', 'bg-info');
}

function deletePaymentPlan(index) {
    if (confirm('Are you sure you want to delete this payment plan?')) {
        paymentPlans.splice(index, 1);
        renderPaymentPlans();
        showToast('Success', 'Payment plan deleted successfully.', 'bg-success');
    }
}

function viewPaymentHistory(index) {
    const plan = paymentPlans[index];
    showToast('Info', `Viewing payment history for ${plan.student_name} (${plan.student_nic})`, 'bg-info');
    // This would open a modal or navigate to payment history page
}

function editPaymentRecord(index) {
    showToast('Info', 'Edit payment record functionality will be implemented soon.', 'bg-info');
}

function deletePaymentRecord(index) {
    if (confirm('Are you sure you want to delete this payment record?')) {
        paymentRecords.splice(index, 1);
        renderPaymentRecords();
        showToast('Success', 'Payment record deleted successfully.', 'bg-success');
    }
}

function viewPaymentDetails(index) {
    showToast('Info', 'View payment details functionality will be implemented soon.', 'bg-info');
}

// Debounce function to prevent rapid successive calls
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Global request counter
window.activeRequests = 0;
window.maxConcurrentRequests = 5;

// Function to reset error states
function resetErrorStates() {
  window.loadExistingPlansCallCount = 0;
  window.lastLoadExistingPlansCall = null;
  window.isCreatingPaymentPlan = false;
  window.isLoadingExistingPlans = false;
  window.isBootstrappingNewPlan = false;
  window.activeRequests = 0;
  window.calculateInstallmentsTimeout = null;

  // Re-enable submit button if it was disabled
    const submitBtn = document.querySelector('.btn-create-payment-plan');
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="ti ti-check me-2"></i>Submit';
  }

  console.log('All error states have been reset');
}

// Make resetErrorStates available globally for console access
window.resetErrorStates = resetErrorStates;

// Helper function to make fetch requests with timeout
function fetchWithTimeout(url, options, timeout = 15000) {
  // Check if we have too many active requests (much higher limit)
  if (window.activeRequests >= 15) {
    return Promise.reject(new Error('Too many concurrent requests. Please wait.'));
  }

  window.activeRequests++;

  return Promise.race([
    fetch(url, options),
    new Promise((_, reject) =>
      setTimeout(() => reject(new Error('Request timeout')), timeout)
    )
  ]).finally(() => {
    window.activeRequests = Math.max(0, window.activeRequests - 1);
  });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
  // Reset error states on page load
  resetErrorStates();

    // Show warning message initially since no student data is loaded
    const statusIndicator = document.getElementById('student-data-status');
    if (statusIndicator) {
        statusIndicator.style.display = 'block';
    }

    // Add event listener for NIC field to filter courses
    const studentNicField = document.getElementById('plan-student-nic');
    if (studentNicField) {
        studentNicField.addEventListener('input', function() {
            const nicValue = this.value.trim();

            // Wait for complete NIC number (assuming NIC is 10-12 characters)
            if (nicValue.length >= 10) {
                // Add a small delay to avoid too many API calls while typing
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    loadCoursesForStudent();
                }, 1000); // 1 second delay after complete NIC
            }
        });
    }

    // Add event listeners for payment plan form fields
    const paymentPlanFields = ['payment-plan-type'];
    paymentPlanFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', calculateInstallments);
        }
    });

    // Load discounts when page loads
    loadDiscounts();

    // Note: loadDiscounts() is called when bootstrapping a new plan, no need to call it on tab click

    // Add event listener for discount type
    const discountTypeField = document.getElementById('discount-type');
    if (discountTypeField) {
        discountTypeField.addEventListener('change', calculateFinalAmount);
    }

    // Add event listener for registration fee discount
    const registrationFeeDiscountField = document.getElementById('registration-fee-discount');
    if (registrationFeeDiscountField) {
        registrationFeeDiscountField.addEventListener('change', function() {
            calculateFinalAmount();
            if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        });
    }

    // Add event listener for SLT loan applied
    const sltLoanAppliedField = document.getElementById('slt-loan-applied');
    if (sltLoanAppliedField) {
        sltLoanAppliedField.addEventListener('change', function() {
            const sltLoanAmountField = document.getElementById('slt-loan-amount');
            const sltLoanStartField = document.getElementById('slt-loan-start-installment');
            const sltLoanYearsField = document.getElementById('slt-loan-years');
            if (this.value === 'yes') {
                sltLoanAmountField.disabled = false;
                sltLoanAmountField.required = true;
                if (sltLoanStartField) {
                    sltLoanStartField.disabled = false;
                    sltLoanStartField.required = true;
                    if (!sltLoanStartField.value) sltLoanStartField.value = '1';
                }
                if (sltLoanYearsField) {
                    sltLoanYearsField.disabled = false;
                    sltLoanYearsField.required = true;
                }
            } else {
                sltLoanAmountField.disabled = true;
                sltLoanAmountField.required = false;
                sltLoanAmountField.value = '';
                if (sltLoanStartField) {
                    sltLoanStartField.disabled = true;
                    sltLoanStartField.required = false;
                    sltLoanStartField.value = '';
                }
                if (sltLoanYearsField) {
                    sltLoanYearsField.disabled = true;
                    sltLoanYearsField.required = false;
                    sltLoanYearsField.value = '';
                }
            }
            calculateFinalAmount();
            if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        });
    }

    // Add event listener for SLT loan amount
    const sltLoanAmountField = document.getElementById('slt-loan-amount');
    if (sltLoanAmountField) {
        sltLoanAmountField.addEventListener('input', function() {
            calculateFinalAmount();
            if (document.getElementById('payment-plan-type').value === 'full') {
                showInstallmentPreview();
            } else {
                if (window.currentStudentData) {
                    calculateAndDisplayInstallments();
                }
            }
        });
    }

    const sltLoanStartField = document.getElementById('slt-loan-start-installment');
    if (sltLoanStartField) {
        sltLoanStartField.addEventListener('input', function() {
            calculateFinalAmount();
            if (document.getElementById('payment-plan-type').value === 'full') {
                showInstallmentPreview();
            } else if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        });
    }

    const sltLoanYearsField = document.getElementById('slt-loan-years');
    if (sltLoanYearsField) {
        sltLoanYearsField.addEventListener('input', function() {
            calculateFinalAmount();
        });
    }

    // Add event listener for course selection
    const courseSelect = document.getElementById('plan-course');
    if (courseSelect) {
        courseSelect.addEventListener('change', function() {
            const studentNic = document.getElementById('plan-student-nic').value;
            const courseId = this.value;

            if (studentNic && courseId) {
                loadStudentForPaymentPlan();
            }
        });
    }

    // Add discount functionality
    const addDiscountBtn = document.getElementById('add-discount-btn');
    if (addDiscountBtn) {
        addDiscountBtn.addEventListener('click', function() {
            addDiscountField();
        });
    }

    // Add event listeners for form changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('discount-select')) {
            calculateFinalAmount();
            // Recalculate installments when discounts change
            if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        }

        // Recalculate installments when payment plan type changes
        if (e.target.id === 'payment-plan-type') {
            toggleFullPaymentDiscountFields();
            autoSelectFullPaymentDiscount();
            calculateFinalAmount();
            showInstallmentPreview();
        }

        // Recalculate installments when SLT loan changes
        if (e.target.id === 'slt-loan-applied' || e.target.id === 'slt-loan-amount' || e.target.id === 'slt-loan-start-installment') {
            calculateFinalAmount();
            if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        }
    });

    // Add discount field function
    function addDiscountField() {
        const container = document.getElementById('discounts-container');
        if (!container) {
            return;
        }
        const discountItem = document.createElement('div');
        discountItem.className = 'discount-item mb-2 d-flex align-items-center';

        // Get the first discount select to clone its options
        const firstSelect = document.querySelector('.discount-select');
        const options = firstSelect.innerHTML;

        discountItem.innerHTML = `
            <select class="form-select discount-select me-2" name="discounts[]">
                ${options}
            </select>
            <button type="button" class="btn btn-sm btn-outline-danger remove-discount-btn">
                <i class="ti ti-trash"></i>
            </button>
        `;

        container.appendChild(discountItem);

        // Add event listener to remove button
        discountItem.querySelector('.remove-discount-btn').addEventListener('click', function() {
            container.removeChild(discountItem);
            calculateFinalAmount();
            // Recalculate installments when discount is removed
            if (window.currentStudentData) {
                calculateAndDisplayInstallments();
            }
        });
    }

    // Add event listeners for filter changes
    const filterSelects = document.querySelectorAll('.filter-param');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Reset dependent dropdowns
            const dependentSelects = this.parentElement.parentElement.nextElementSibling?.querySelectorAll('.filter-param');
            if (dependentSelects) {
                dependentSelects.forEach(depSelect => {
                    depSelect.innerHTML = '<option selected disabled value="">Select...</option>';
                });
            }
        });
    });
});

// Load intakes for selected course
function loadIntakesForCourse() {
    const courseId = document.getElementById('slip-course').value;
    const studentId = document.getElementById('slip-student-id').value;

    const paymentTypeSelect = document.getElementById('slip-payment-type');

    if (!courseId || !studentId) {
        console.log('Missing course ID or student ID');
        paymentTypeSelect.disabled = true;
        paymentTypeSelect.value = '';
        return;
    }

    console.log('Loading intakes for course:', courseId, 'and student:', studentId);

    // Enable payment type selection when both student ID and course are selected
    paymentTypeSelect.disabled = false;
}

function checkStudentAndCourse() {
    const nic = document.getElementById('slip-student-id').value.trim();
    if (!nic) return;

    // Disable dropdown while loading
    const courseSelect = document.getElementById('slip-course');
    courseSelect.disabled = true;
    courseSelect.innerHTML = '<option>Loading courses...</option>';

    fetch('{{ route("payment.get.student.courses") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ student_nic: nic })
    })
    .then(response => response.json())
    .then(data => {
        courseSelect.innerHTML = '<option value="" selected disabled>Select Course</option>';

        if (data.success && data.courses.length > 0) {
            data.courses.forEach(course => {
                courseSelect.innerHTML += `
                    <option value="${course.course_id}">
                        ${course.course_name} (${course.approval_status})
                    </option>`;
            });

            // Auto-select if only one course
            if (data.courses.length === 1) {
                courseSelect.value = data.courses[0].course_id;
                loadIntakesForCourse(); // Automatically trigger the next step

                // If payment type already selected, refresh details immediately
                if (document.getElementById('slip-payment-type')?.value) {
                    loadPaymentDetails();
                }
            }

            courseSelect.disabled = false;
        } else {
            courseSelect.innerHTML = '<option value="">No approved courses found</option>';
            courseSelect.disabled = true;
        }
    })
    .catch(error => {
        console.error('Error fetching courses:', error);
        courseSelect.innerHTML = '<option value="">Error loading courses</option>';
        courseSelect.disabled = true;
    });
}
async function loadPaymentDetails() {
  const studentIdOrNic = document.getElementById('slip-student-id').value?.trim();
  const courseId       = parseInt(document.getElementById('slip-course').value || '0', 10);
  const paymentType    = document.getElementById('slip-payment-type').value;

  const conversionRow   = document.getElementById('currencyConversionRow');
  const currencySelect  = document.getElementById('currency-from');
  const franchiseRow    = document.getElementById('franchiseChargesRow'); // 👈 our new div

  if (!studentIdOrNic) {
    showWarningMessage('Enter Student ID / NIC first.');
    return;
  }
  if (!courseId) {
    showWarningMessage('Select a course.');
    return;
  }
  if (!paymentType) {
    showWarningMessage('Select a payment type.');
    return;
  }

  // Show/hide conversion row & extra charges only for franchise_fee
  if (paymentType === 'franchise_fee') {
      conversionRow.style.display = 'flex';
      franchiseRow.style.display  = 'block'; // 👈 show our new inputs
      currencySelect.disabled     = false;
  } else {
      conversionRow.style.display = 'none';
      franchiseRow.style.display  = 'none'; // 👈 hide if not franchise
      currencySelect.disabled     = true;
  }

  // Show payment details section
  document.getElementById('paymentDetailsSection').style.display = '';

  const payload = {
    student_id: studentIdOrNic,
    course_id: String(courseId),
    payment_type: paymentType,
    _timestamp: Date.now() // ✅ Cache-busting parameter to force fresh data from backend
  };

  try {
    const res = await fetch('/payment/get-payment-details', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Cache-Control': 'no-cache, no-store, must-revalidate', // ✅ Prevent HTTP caching
        'Pragma': 'no-cache',
        'Expires': '0'
      },
      body: JSON.stringify(payload),
      cache: 'no-store' // ✅ Prevent browser caching
    });

    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch { throw new Error('Unexpected server response while loading payment details.'); }

    if (!data.success) {
      throw new Error(data.message || 'Failed to load payment details.');
    }

    // --- Set the currency from backend for franchise_fee ---
    if (paymentType === 'franchise_fee' && data.payment_details?.length > 0) {
        // Use currency from the first installment
        const planCurrency = data.payment_details[0].currency || 'USD';

        // Add to dropdown if it doesn't exist
        if (![...currencySelect.options].some(opt => opt.value === planCurrency)) {
            const newOpt = document.createElement('option');
            newOpt.value = planCurrency;
            newOpt.textContent = planCurrency;
            currencySelect.appendChild(newOpt);
        }

        currencySelect.value = planCurrency;
    }

    // Map backend rows to table format
    const details = (data.payment_details || []).map(d => ({
      installment_number: d.installment_number ?? null,
      due_date:           d.due_date ?? null,
      final_amount:       (d.final_amount != null) ? Number(d.final_amount) : Number(d.amount || 0),
      amount:             Number(d.amount || 0),
      status:             d.status || 'pending',
      paid_date:          d.paid_date || null,
      receipt_no:         d.receipt_no || null,
      currency:           d.currency || 'LKR',
      sscl_tax:           Number(d.sscl_tax || 0),
      bank_charges:       Number(d.bank_charges || 0),
      apply_tax:          Boolean(d.apply_tax),
      conversion_rate:    d.conversion_rate ? Number(d.conversion_rate) : null,
      lkr_amount:         d.lkr_amount ? Number(d.lkr_amount) : null
    }));

    renderPaymentDetailsTable(details, paymentType);

  } catch (err) {
    console.error(err);
    const tbody = document.getElementById('paymentDetailsTableBody');
    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">${err.message}</td></tr>`;
  }
}
function toggleLateFeeColumn() {
    const paymentType = document.getElementById('slip-payment-type').value;
    const table = document.getElementById('paymentDetailsTable');
    if (!table) return;

    // Give the DOM a short time to finish inserting rows
    setTimeout(() => {
        // Find the "Late Fee" column index
        const headers = table.querySelectorAll('thead th');
        let lateFeeIndex = -1;

        headers.forEach((th, i) => {
            if (th.textContent.trim().toLowerCase().includes('late fee')) {
                lateFeeIndex = i + 1; // nth-child is 1-based
            }
        });

        // Stop if not found
        if (lateFeeIndex === -1) return;

        const lateFeeHeader = table.querySelector(`thead th:nth-child(${lateFeeIndex})`);
        const lateFeeCells = table.querySelectorAll(`tbody td:nth-child(${lateFeeIndex})`);

        if (paymentType === 'franchise_fee') {
            // Hide header & all cells
            if (lateFeeHeader) lateFeeHeader.style.display = 'none';
            lateFeeCells.forEach(td => td.style.display = 'none');
        } else {
            // Show header & cells again
            if (lateFeeHeader) lateFeeHeader.style.display = '';
            lateFeeCells.forEach(td => td.style.display = '');
        }
    }, 100); // wait 100ms for table rows to load
}



// Display payment details in the table
function displayPaymentDetails(paymentDetails) {
    const tbody = document.getElementById('paymentDetailsTableBody');
    tbody.innerHTML = '';

    const paymentType = document.getElementById('slip-payment-type').value;
    const conversionRate = paymentType === 'franchise_fee' ? parseFloat(document.getElementById('currency-conversion-rate').value || 0) : 0;

    // Show/hide conversion rate warning and info
    const warningDiv = document.getElementById('conversionRateWarning');
    const infoDiv = document.getElementById('conversionRateInfo');

    if (paymentType === 'franchise_fee') {
        if (conversionRate <= 0) {
            warningDiv.style.display = 'block';
            infoDiv.style.display = 'none';
        } else {
            warningDiv.style.display = 'none';
            infoDiv.style.display = 'block';
            // Update the info display
            document.getElementById('currentConversionRate').textContent = conversionRate;
            document.getElementById('currentCurrency').textContent = document.getElementById('currency-from').value;
        }
    } else {
        warningDiv.style.display = 'none';
        infoDiv.style.display = 'none';
    }

    paymentDetails.forEach((payment, index) => {
        // Use the currency from the payment data, default to LKR if not provided
        const currency = payment.currency || 'LKR';
        const amount = parseFloat(payment.amount);
        const isPaid = payment.status && payment.status.toLowerCase() === 'paid';

        // Calculate LKR amount for franchise fees
        let lkrAmount = '';
        const effectiveRate = payment.conversion_rate || conversionRate;
        if (paymentType === 'franchise_fee') {
            if (payment.lkr_amount !== undefined && payment.lkr_amount !== null) {
                lkrAmount = `LKR ${money(payment.lkr_amount)}`;
            } else if (effectiveRate > 0) {
                const lkrBase = amount * effectiveRate;
                const ssclAmount = (lkrBase * Number(payment.sscl_tax || 0)) / 100;
                const bankCharges = Number(payment.bank_charges || 0);
                lkrAmount = `LKR ${money(lkrBase + ssclAmount + bankCharges)}`;
            } else {
                lkrAmount = 'Enter conversion rate';
            }
        }

        const row = `
            <tr ${isPaid ? 'style="opacity: 0.6;"' : ''}>
                <td>
                    <input type="checkbox" name="selectedPayment" value="${index}" class="payment-checkbox" ${isPaid ? 'disabled title="Already Paid"' : ''}>
                </td>
                <td>${payment.installment_number || '-'}</td>
                <td>${payment.due_date ? formatDateDmy(payment.due_date) : '-'}</td>
                <td>${currency} ${amount.toLocaleString()}</td>
                ${paymentType === 'franchise_fee' ? `<td>${lkrAmount}</td>` : ''}
                <td>${payment.paid_date ? formatDateDmy(payment.paid_date) : '-'}</td>
                <td>
                    <span class="badge bg-${getPaymentStatusBadgeColor(payment.status)}">
                        ${payment.status}
                    </span>
                    ${isPaid ? '<br><small class="text-muted">Cannot generate new slip</small>' : ''}
                </td>
                <td>${payment.receipt_no || '-'}</td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });

    // Store payment details globally for validation
    window.paymentDetailsData = paymentDetails;
}

// Enable generate button when a payment is selected
function enableGenerateButton() {
    const selectedPayment = document.querySelector('input[name="selectedPayment"]:checked:not([disabled])');
    const generateBtn = document.getElementById('generateSlipBtn');

    if (selectedPayment) {
        generateBtn.disabled = false;
    } else {
        generateBtn.disabled = true;
    }
}

// Refresh Generate Slips tab data after payment update
function refreshGenerateSlipsData() {
    const studentId = document.getElementById('slip-student-id')?.value;
    const courseId = document.getElementById('slip-course')?.value;
    const paymentType = document.getElementById('slip-payment-type')?.value;

    // Only refresh if all fields are filled (meaning data is currently loaded)
    if (studentId && courseId && paymentType) {
        console.log('🔄 Refreshing Generate Slips data...', { studentId, courseId, paymentType });

        // Show loading state in the table
        const tbody = document.getElementById('paymentDetailsTableBody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Refreshing payment data...
            </td></tr>`;
        }

        // ✅ Clear ALL cached data to force absolute fresh fetch from backend
        window.paymentDetailsDataRaw = null;
        window.paymentDetailsData = null;
        window.paymentDetailsPaymentType = null;

        // Disable all payment radios temporarily to prevent new selections during refresh
        const radioButtons = document.querySelectorAll('input[name="selectedPayment"]');
        radioButtons.forEach(radio => radio.disabled = true);

        // Fetch fresh data from backend - WAIT FOR IT TO COMPLETE
        (async () => {
            try {
                console.log('⏳ Waiting for fresh data from backend...');
                await loadPaymentDetails();
                console.log('✅ Fresh data loaded and table refreshed!');
                console.log('📊 Current payment details:', window.paymentDetailsData);
            } catch (err) {
                console.error('❌ Error refreshing payment data:', err);
                showErrorMessage('Error refreshing payment data. Please try again.');
            }
        })();
    } else {
        console.log('⚠️ Cannot refresh: Missing student ID, course ID, or payment type');
    }
}

// Update conversion label when currency changes
function updateConversionLabel() {
    const currencyFrom = document.getElementById('currency-from').value;
    // Trigger recalculation when currency changes
    recalculateLKRAmounts();
}

// Recalculate LKR amounts when conversion rate changes
function recalculateLKRAmounts() {
    const paymentType = document.getElementById('slip-payment-type').value;
    const conversionRate = parseFloat(document.getElementById('currency-conversion-rate').value || 0);

    // Update the warning and info messages
    const warningDiv = document.getElementById('conversionRateWarning');
    const infoDiv = document.getElementById('conversionRateInfo');

    if (paymentType === 'franchise_fee') {
        if (conversionRate <= 0) {
            warningDiv.style.display = 'block';
            infoDiv.style.display = 'none';
        } else {
            warningDiv.style.display = 'none';
            infoDiv.style.display = 'block';
            // Update the info display
            document.getElementById('currentConversionRate').textContent = conversionRate;
            document.getElementById('currentCurrency').textContent = document.getElementById('currency-from').value;
        }
    } else {
        warningDiv.style.display = 'none';
        infoDiv.style.display = 'none';
    }

    // Only recalculate if we have payment data and it's franchise fee
    if (paymentType === 'franchise_fee' && window.paymentDetailsData) {
        syncFranchiseChargeInputsToSelection();
        // Update only the LKR amounts in the existing table rows
        updateLKRAmountsInTable(conversionRate);
    }
}

function calculateFranchiseChargesForRow(row, conversionRate) {
    const amount = Number(row?.amount || 0);
    const ssclPercent = Number(row?.sscl_tax || 0);
    const bankCharges = Number(row?.bank_charges || 0);
    const lkrBase = conversionRate > 0 ? amount * conversionRate : amount;
    const ssclAmount = (lkrBase * ssclPercent) / 100;

    return {
        ssclPercent,
        ssclAmount,
        bankCharges,
        lkrFinal: lkrBase + ssclAmount + bankCharges
    };
}

function syncFranchiseChargeInputsToSelection() {
    const paymentType = document.getElementById('slip-payment-type')?.value;
    if (paymentType !== 'franchise_fee') return;

    const selected = document.querySelector('input[name="selectedPayment"]:checked');
    if (!selected) return;

    const row = (window.paymentDetailsData || [])[Number(selected.value)];
    if (!row) return;

    const conversionRate = Number(document.getElementById('currency-conversion-rate')?.value || 0);
    const ssclTypeEl = document.getElementById('sscl-type');
    const ssclValueEl = document.getElementById('sscl-value');
    const ssclAmountEl = document.getElementById('sscl-tax-amount');
    const bankChargesEl = document.getElementById('bank-charges');

    const charges = calculateFranchiseChargesForRow(row, conversionRate);

    if (ssclTypeEl) ssclTypeEl.value = 'percentage';
    if (ssclValueEl) ssclValueEl.value = charges.ssclPercent;
    if (ssclAmountEl) ssclAmountEl.value = charges.ssclAmount.toFixed(2);
    if (bankChargesEl) bankChargesEl.value = charges.bankCharges;
}

// Update LKR amounts in the existing table without recreating the entire table
function updateLKRAmountsInTable(conversionRate) {
    const tbody = document.getElementById('paymentDetailsTableBody');
    const rows = tbody.querySelectorAll('tr');

    rows.forEach((row, index) => {
        const payment = window.paymentDetailsData[index];
        if (!payment || payment.conversion_rate || (payment.lkr_amount !== undefined && payment.lkr_amount !== null)) {
            return;
        }

        const lkrCell = row.querySelector('td:nth-child(5)'); // LKR amount column
        if (!lkrCell) {
            return;
        }

        const amount = parseFloat(payment.amount || 0);
        const ssclTax = parseFloat(payment.sscl_tax || 0);
        const bankCharges = parseFloat(payment.bank_charges || 0);
        if (conversionRate > 0) {
            const lkrBase = amount * conversionRate;
            const ssclAmount = (lkrBase * ssclTax) / 100;
            const lkrFinal = lkrBase + ssclAmount + bankCharges;

            lkrCell.style.backgroundColor = '#fff3cd';
            lkrCell.innerHTML = `
                <div>LKR ${money(lkrFinal)}</div>
                <small class="text-muted">Base: LKR ${money(lkrBase)} | SSCL: LKR ${money(ssclAmount)} | Bank: LKR ${money(bankCharges)}</small>
            `;

            setTimeout(() => {
                lkrCell.style.backgroundColor = '';
            }, 300);
        } else {
            lkrCell.textContent = 'Enter conversion rate';
            lkrCell.style.backgroundColor = '';
        }
    });
}

// Get badge color for payment status
function getPaymentStatusBadgeColor(status) {
    switch (status.toLowerCase()) {
        case 'paid':
            return 'success';
        case 'pending':
            return 'warning';
        case 'overdue':
            return 'danger';
        default:
            return 'secondary';
    }
}



// Payment Details Modal
function showPaymentDetailsModal() {
    // Create modal HTML
    const modalHTML = `
        <div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="paymentDetailsModalLabel">
                            <i class="ti ti-credit-card me-2"></i>Payment Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            <strong>Payment Information:</strong> Please provide the payment method and any additional remarks for this payment record.
                        </div>

                        <div class="mb-3">
                            <label for="modal-payment-method" class="form-label fw-bold">
                                Payment Method <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="modal-payment-method" required>
                                <option value="" selected disabled>Select Payment Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card Payment</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Online">Online Payment</option>
                                <option value="Mobile Money">Mobile Money</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="modal-remarks" class="form-label fw-bold">
                                Remarks <span class="text-muted">(Optional)</span>
                            </label>
                            <textarea class="form-control" id="modal-remarks" rows="3"
                                placeholder="Enter any additional remarks or notes about this payment..."></textarea>
                        </div>

                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong>Note:</strong> This will save the payment record to the database. Make sure all information is correct before proceeding.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success btn-confirm-save-payment-record">
                            <i class="ti ti-device-floppy me-2"></i>Save Payment Record
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    const existingModal = document.getElementById('paymentDetailsModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
    modal.show();
}

// Confirm save payment record
function confirmSavePaymentRecord() {
    const paymentMethod = document.getElementById('modal-payment-method').value;
    const remarks = document.getElementById('modal-remarks').value;

    if (!paymentMethod) {
        showErrorMessage('Please select a payment method.');
        return;
    }

    // Hide modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('paymentDetailsModal'));
    modal.hide();

    showSpinner(true);

    fetch('/payment/save-record', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            receipt_no: window.currentSlipData.receipt_no,
            payment_method: paymentMethod,
            payment_date: window.currentSlipData.payment_date,
            remarks: remarks
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Payment record saved successfully! 🎉');
            // Optionally hide the slip preview after saving
            document.getElementById('slipPreviewSection').style.display = 'none';
            // Clear the current slip data
            window.currentSlipData = null;
        } else {
            showErrorMessage(data.message || 'Failed to save payment record.');
        }
    })
    .catch((error) => {
        console.error('Error saving payment record:', error);
        showErrorMessage('An error occurred while saving payment record.');
    })
    .finally(() => showSpinner(false));
}

// Save payment record from Update Records tab
function savePaymentRecordFromUpdate() {
    const receiptNo = document.getElementById('upload-receipt-no').value;
    const paymentMethod = document.getElementById('upload-payment-method').value;
    const paymentDate = document.getElementById('upload-payment-date').value;
    const remarks = document.getElementById('upload-remarks').value;

    if (!receiptNo || !paymentMethod || !paymentDate) {
        showErrorMessage('Please fill in all required fields (Receipt Number, Payment Method, and Payment Date).');
        return;
    }

    showSpinner(true);

    fetch('/payment/save-record', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            receipt_no: receiptNo,
            payment_method: paymentMethod,
            payment_date: paymentDate,
            remarks: remarks
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Payment record saved successfully! 🎉');

            // Clear form fields
            document.getElementById('upload-receipt-no').value = '';
            document.getElementById('upload-payment-method').value = '';
            document.getElementById('upload-payment-date').value = '';
            document.getElementById('upload-remarks').value = '';
            document.getElementById('upload-paid-slip').value = '';

            // Reload payment records if they are currently displayed
            if (document.getElementById('paymentRecordsSection').style.display !== 'none') {
                loadPaymentRecords();
            }
        } else {
            showErrorMessage(data.message || 'Failed to save payment record.');
        }
    })
    .catch((error) => {
        console.error('Error saving payment record:', error);
        showErrorMessage('An error occurred while saving payment record.');
    })
    .finally(() => showSpinner(false));
}
</script>

<script nonce="{{ $cspNonce }}">
// ---------- helpers ----------
const money = (n) =>
  (Number(n || 0)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const dstr  = (d) => formatDateDmy(d);

// Re-render when user changes FX rate for franchise fee
function recalculateLKRAmounts() {
  if (!window.paymentDetailsDataRaw) return;
  // re-render with the last payment type used
  renderPaymentDetailsTable(window.paymentDetailsDataRaw, window.paymentDetailsPaymentType || 'course_fee');
}


// ---------- main renderer ----------
function renderPaymentDetailsTable(rows, paymentType) {
    console.log('Payment rows from API (FRESH DATA):', rows);
    console.log('Payment statuses:', rows.map(r => ({ inst: r.installment_number, status: r.status, receipt: r.receipt_no })));

  // keep originals so we can re-render on FX change
  window.paymentDetailsDataRaw    = Array.isArray(rows) ? rows : [];
  window.paymentDetailsPaymentType= paymentType;

  const tbody         = document.getElementById('paymentDetailsTableBody');
  const generateBtn   = document.getElementById('generateSlipBtn');
  const amountHeader  = document.getElementById('amountHeader');
  const lkrHeader     = document.getElementById('lkrAmountHeader');
  const convRow       = document.getElementById('currencyConversionRow');
  const convWarn      = document.getElementById('conversionRateWarning');
  const convInfo      = document.getElementById('conversionRateInfo');
  const currentRateEl = document.getElementById('currentConversionRate');
  const currentCurEl  = document.getElementById('currentCurrency');

  tbody.innerHTML = '';
  generateBtn.disabled = true;

  // toggle FX UI only for franchise fee
  let showLkr = paymentType === 'franchise_fee';
  convRow.style.display = showLkr ? '' : 'none';
  lkrHeader.style.display = showLkr ? '' : 'none';
    lkrHeader.textContent = showLkr ? 'Final Amount (LKR)' : 'LKR Amount';
  amountHeader.textContent = 'Amount';

  // capture FX inputs (if needed)
  let rate = null, ccy = null;
  if (showLkr) {
    rate = parseFloat(document.getElementById('currency-conversion-rate').value);
    ccy  = document.getElementById('currency-from').value;
    if (!rate || rate <= 0) {
      convWarn.style.display = '';
      convInfo.style.display = 'none';
    } else {
      convWarn.style.display = 'none';
      convInfo.style.display = '';
      currentRateEl.textContent = rate;
      currentCurEl.textContent  = ccy;
    }
  } else {
    convWarn.style.display = 'none';
    convInfo.style.display = 'none';
  }

  if (!rows || !rows.length) {
    tbody.innerHTML = `<tr><td colspan="${showLkr ? 9 : 8}" class="text-center text-muted">No records found.</td></tr>`;
    window.paymentDetailsData = [];
    return;
  }

  // ✅ Normalize rows into what the slip generator expects
  const normalized = rows.map(r => {
    const payable = (r.final_amount != null) ? Number(r.final_amount) : Number(r.amount || 0);
    return {
      installment_number: r.installment_number ?? null,
      due_date:           r.due_date ?? null,
      amount:             payable,
      base_amount:        Number(r.amount || payable),
      status:             r.status || 'pending',
      paid_date:          r.paid_date || null,
      receipt_no:         r.receipt_no || null,
      approved_late_fee:  Number(r.approved_late_fee ?? r.approvedLateFee ?? 0), // ✅ map both cases
      currency:           r.currency || (paymentType === 'franchise_fee' ? (ccy || 'USD') : 'LKR'),
      sscl_tax:           Number(r.sscl_tax || 0),
      bank_charges:       Number(r.bank_charges || 0),
      apply_tax:          Boolean(r.apply_tax),
      is_payable:         r.is_payable !== false,
      blocked_reason:     r.blocked_reason || null
    };
  });

  window.paymentDetailsData = normalized;

  // ✅ Helper to calculate late fee (same as PHP)
  function calculateLateFee(amount, daysLate) {
    const monthlyRate = 0.05;             // 5% monthly
    const monthsLate  = daysLate / 30;    // fractional months
    const lateFee     = amount * monthlyRate * monthsLate;
    const maxLateFee  = amount * 0.25;    // cap at 25%
    return Math.min(lateFee, maxLateFee);
}




  // ✅ Build table rows
  normalized.forEach((p, idx) => {
    const isPaid = p.status && p.status.toLowerCase() === 'paid';
    const hasGeneratedSlip = p.receipt_no && p.receipt_no !== null && p.receipt_no !== '';
    const disabled = (isPaid || hasGeneratedSlip) ? 'disabled' : '';
    const rowStyle = (isPaid || hasGeneratedSlip)
      ? 'style="opacity: 0.6; background-color: #f8f9fa;"'
            : '';
    const amountText = `${p.currency} ${money(p.amount)}`;

    // LKR column for franchise
    let lkrCell = '';
    if (showLkr) {
      // ✅ If slip has already been generated (receipt_no exists), always use locked data
      if (hasGeneratedSlip && p.conversion_rate && p.conversion_rate > 0 && p.lkr_amount !== null && p.lkr_amount !== undefined) {
        // Slip already generated - show LOCKED amount that NEVER changes
        lkrCell = `<td style="background-color: #f0f0f0; position: relative;">
                    <div><strong>LKR ${money(p.lkr_amount)}</strong></div>
                    <small class="text-muted">🔒 Locked | Rate: ${p.conversion_rate}</small>
                </td>`;
      } else if (!hasGeneratedSlip && rate && rate > 0) {
        // No slip yet - calculate dynamically based on current conversion rate
        const lkrBase = p.amount * rate;
        const ssclAmount = (lkrBase * p.sscl_tax) / 100;
        const lkrFinal = lkrBase + ssclAmount + p.bank_charges;
        lkrCell = `<td>
                    <div>LKR ${money(lkrFinal)}</div>
                    <small class="text-muted">Base: LKR ${money(lkrBase)} | SSCL: LKR ${money(ssclAmount)} | Bank: LKR ${money(p.bank_charges)}</small>
                </td>`;
      } else {
        lkrCell = `<td class="text-muted">—</td>`;
      }
    }

    // ---- ✅ Late Fee Calculation ----
        let lateFee = 0, lateFeeNote = '';
        // Apply late fee for course_fee payments; compare against the payment effective date if entered
        if (paymentType === 'course_fee' && p.due_date) {
            const due = new Date(p.due_date + 'T00:00:00');
            const effectiveDateInput = document.getElementById('payment-effective-date')?.value;
            const compareDate = effectiveDateInput ? new Date(effectiveDateInput + 'T00:00:00') : new Date();
            if (compareDate > due && !isPaid) {
                const diffTime = compareDate - due; // ms
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

                let rawLateFee = calculateLateFee(p.amount, diffDays);

                const approved = p.approved_late_fee;
                if (approved > 0) {
                    lateFee = Math.max(0, rawLateFee - approved);
                    lateFeeNote = `<small class="text-success">Approved reduction: LKR ${money(approved)}</small>`;
                } else {
                    lateFee = rawLateFee;
                    lateFeeNote = `<small class="text-muted">Days late: ${diffDays}</small>`;
                }
            }
        }
    // ---- ✅ Approved Late Fee Display String ----
    const approvedLateFeeStr = p.approved_late_fee && Number(p.approved_late_fee) > 0
        ? `<small class="text-success">Approved Late Fee: LKR ${money(p.approved_late_fee)}</small>`
        : `<small class="text-muted">No approved late fee</small>`;

    // Status badge with color
    let statusBadge = '';
    if (isPaid) {
      statusBadge = `<span class="badge bg-success">Paid</span>`;
      if (p.paid_date) {
        statusBadge += `<br><small class="text-muted">Paid: ${dstr(p.paid_date)}</small>`;
      }
      if (p.receipt_no) {
        statusBadge += `<br><small class="text-muted">Receipt: ${p.receipt_no}</small>`;
      }
    } else {
      statusBadge = `<span class="badge bg-warning">Pending</span>`;
    }

    const row = `
      <tr ${rowStyle}>
        <td class="text-center">
          <input type="checkbox" name="selectedPayment" value="${idx}" class="payment-checkbox" ${disabled}>
                    ${isPaid ? '<br><small class="text-danger">Cannot generate new slip</small>' : ''}
        </td>
        <td>${p.installment_number ?? '-'}</td>
        <td>${dstr(p.due_date)}</td>
        <td>${amountText}</td>
        ${showLkr ? lkrCell : ''}
        <td>
          LKR ${money(lateFee)} <br>
          ${approvedLateFeeStr}
        </td>

        <td>${statusBadge}</td>

      </tr>
    `;
    tbody.insertAdjacentHTML('beforeend', row);
  });

  // enable "Generate" only when a selection is made
  tbody.querySelectorAll('input[name="selectedPayment"]').forEach(r =>
        r.addEventListener('change', () => {
            if (r.checked) {
                tbody.querySelectorAll('input[name="selectedPayment"]').forEach(other => {
                    if (other !== r) other.checked = false;
                });
                generateBtn.disabled = false;
                syncFranchiseChargeInputsToSelection();
            } else {
                const selectedAny = tbody.querySelector('input[name="selectedPayment"]:checked');
                generateBtn.disabled = !selectedAny;
                if (selectedAny) syncFranchiseChargeInputsToSelection();
            }
        })
  );

  const firstAvailable = tbody.querySelector('input[name="selectedPayment"]:not(:disabled)');
  if (firstAvailable) {
    firstAvailable.checked = true;
    generateBtn.disabled = false;
    syncFranchiseChargeInputsToSelection();
  }
}
// If user changes FX inputs, recompute the LKR column live
document.getElementById('currency-conversion-rate')?.addEventListener('input', recalculateLKRAmounts);
document.getElementById('currency-from')?.addEventListener('change', recalculateLKRAmounts);

function updateSltLoanReceivableAmountField() {
    const planLoanAmount = parseFloat(document.getElementById('sltPlanLoanAmount')?.value) || 0;
    const years = document.getElementById('sltLoanYears')?.value;
    const installmentCount = calculateSltLoanInstallmentCount(years);
    const receivableField = document.getElementById('sltLoanAmount');

    if (receivableField) {
        receivableField.value = planLoanAmount > 0 && installmentCount > 0
            ? (planLoanAmount / installmentCount).toFixed(2)
            : '';
    }
}

function updateSltReceivableSummary() {
    const planLoanAmount = parseFloat(document.getElementById('sltPlanLoanAmount')?.value) || 0;
    const years = parseInt(document.getElementById('sltLoanYears')?.value, 10) || 0;
    const installmentCount = calculateSltLoanInstallmentCount(years);
    const startInstallment = parseInt(document.getElementById('sltLoanStartInstallment')?.value, 10) || 0;
    const summaryEl = document.getElementById('sltLoanSummary');

    updateSltLoanInstallmentCountField();
    updateSltLoanReceivableAmountField();

    if (!summaryEl) return;

    if (planLoanAmount > 0 && installmentCount > 0) {
        const receivablePerInstallment = planLoanAmount / installmentCount;
        summaryEl.innerHTML =
            `<strong>SLT Loan Amount (Payment Plan):</strong> LKR ${planLoanAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}<br>` +
            `<strong>No of loan installments:</strong> ${installmentCount}<br>` +
            `<strong>Receivable per installment:</strong> LKR ${receivablePerInstallment.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}<br>` +
            `<strong>Student collection changes from installment:</strong> ${startInstallment || '-'}`;
        summaryEl.style.display = 'block';
    } else {
        summaryEl.style.display = 'none';
    }
}

document.getElementById('slt-loan-receivable-form')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const studentNic = document.getElementById('slt-loan-student-nic')?.value;
    const courseId = document.getElementById('slt-loan-course')?.value;
    const loanYears = document.getElementById('sltLoanYears')?.value;
    const startInstallment = document.getElementById('sltLoanStartInstallment')?.value;
    const planLoanAmount = document.getElementById('sltPlanLoanAmount')?.value;
    const effectiveDate = document.getElementById('sltLoanEffectiveDate')?.value;

    if (!studentNic || !courseId) {
        showWarningMessage('Please enter student NIC and select a course.');
        return;
    }

    if (!loanYears || !startInstallment || !planLoanAmount) {
        showWarningMessage('Loan details could not be loaded from the payment plan. Select a course with an SLT loan applied.');
        return;
    }

    showSpinner(true);

    fetch('{{ route("payment.discount.save.sltloan") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({
            student_identifier: studentNic,
            course_id: courseId,
            slt_loan_amount: planLoanAmount,
            slt_loan_years: loanYears,
            slt_loan_start_installment: startInstallment,
            payment_effective_date: effectiveDate
        })
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Unable to update SLT loan receivable.');
        }
        return data;
    })
    .then(response => {
        const plan = response.payment_plan || {};
        showSuccessMessage(response.message || 'SLT loan receivable updated successfully.');
        if (plan.installment_receivable !== undefined) {
            const summaryEl = document.getElementById('sltLoanSummary');
            summaryEl.innerHTML += `<br><strong>Saved receivable per installment:</strong> LKR ${Number(plan.installment_receivable || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            summaryEl.style.display = 'block';
        }
    })
    .catch(error => {
        showErrorMessage(error.message || 'Unable to update SLT loan receivable.');
    })
    .finally(() => showSpinner(false));
});

// Tab coloring logic (like all clearance page)
$('#paymentTabs .nav-link').on('shown.bs.tab', function (e) {
    $('#paymentTabs .nav-link').removeClass('bg-primary text-white');
    $(e.target).addClass('bg-primary text-white');

    // ✅ REFRESH Generate Slips tab data when it becomes active
    if ($(e.target).attr('id') === 'generate-slips-tab') {
        console.log('✅ Generate Slips tab activated - refreshing data from backend...');
        // Small delay to ensure the tab is fully shown
        setTimeout(() => {
            refreshGenerateSlipsData();
        }, 300);
    }
});

function deleteSlip(id) {
  if (!confirm("Are you sure you want to delete this slip?")) return;

  fetch(`/payment/delete-slip/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json'
    }
  })
    .then(res => res.json())
    .then(data => {
      alert(data.message);
      if (data.success) {
        // Hide preview & reset button
        document.getElementById('slipPreviewSection').style.display = 'none';
        document.getElementById('delete-slip-btn').style.display = 'none';
        window.currentSlipData = null;
      }
    })
    .catch(err => console.error("Delete failed", err));
}


</script>

@endsection
