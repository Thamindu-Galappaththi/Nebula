@extends('inc.app')

@section('title', 'NEBULA | Payment Plan')

@section('content')
<div class="container-fluid">
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
            <h2 class="text-center mb-4">Intake Payment Plan</h2>
            <hr>
            <form id="paymentPlanForm" method="POST" action="{{ route('payment.plan.store') }}">
                @csrf
                <div class="mb-3 row mx-3">
                    <label for="location" class="col-sm-2 col-form-label">Location <span class="text-danger">*</span></label>
                    <div class="col-sm-10">
    <select class="form-select" id="location" name="location" required>
        <option disabled {{ !isset($selectedLocation) ? 'selected' : '' }} value="">
            Choose a location...
        </option>
        <option value="Welisara" {{ (isset($selectedLocation) && $selectedLocation === 'Welisara') ? 'selected' : '' }}>
            Nebula Institute of Technology - Welisara
        </option>
        <option value="Moratuwa" {{ (isset($selectedLocation) && $selectedLocation === 'Moratuwa') ? 'selected' : '' }}>
            Nebula Institute of Technology - Moratuwa
        </option>
        <option value="Peradeniya" {{ (isset($selectedLocation) && $selectedLocation === 'Peradeniya') ? 'selected' : '' }}>
            Nebula Institute of Technology - Peradeniya
        </option>
    </select>
</div>

                </div>
                <div class="mb-3 row mx-3">
    <label for="course" class="col-sm-2 col-form-label">
        Course <span class="text-danger">*</span>
    </label>
    <div class="col-sm-10">
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

                <div class="mb-3 row mx-3">
                    <label for="intake" class="col-sm-2 col-form-label">Intake <span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <select class="form-select" id="intake" name="intake" required disabled>
                            <option selected disabled value="">Select Intake...</option>
                        </select>
                    </div>
                </div>
                <div class="p-4">
                    <hr class="mt-4">
                    <div class="mx-3 mb-3 rounded border">
                        <h5 class="mt-0 bg-black p-3 text-white mb-4" style="border-top-left-radius: 5px; border-top-right-radius: 5px;">Course Fee</h5>
                        <div class="row align-items-center mx-3 mb-3">
                            <label for="registrationFee" class="col-sm-3 col-form-label fw-bold">Course registration Fee<span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white">&nbsp;&nbsp;LKR&nbsp;&nbsp;&nbsp;</span>
                                    <input type="number" class="form-control bg-light" id="registrationFee" name="registrationFee" placeholder="Enter registration fee" required readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center mx-3 mb-3">
                            <label for="localFee" class="col-sm-3 col-form-label fw-bold">Local course Fee<span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <span class="input-group-text bg-danger text-white">&nbsp;&nbsp;LKR&nbsp;&nbsp;&nbsp;</span>
                                    <input type="number" class="form-control bg-light" id="localFee" name="localFee" placeholder="Enter local course fee" required readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center mx-3 mb-3">
                            <label for="franchiseFee" class="col-sm-3 col-form-label fw-bold">Franchise Payment<span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <span class="input-group-text bg-danger text-white" id="currencyDisplay">  LKR  </span>
                                    <input type="number" class="form-control bg-light" id="internationalFee" name="internationalFee" placeholder="Enter international course fee" required readonly>
                                    <input type="hidden" id="currency" name="currency" value="LKR">
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center mx-3 mb-4">
                            <label for="ssclTax" class="col-sm-3 col-form-label fw-bold">SSCL Tax Percentage</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                   <input
    type="number"
    class="form-control bg-white"
    id="ssclTax"
    name="ssclTax"
    placeholder="Enter SSCL tax percentage"
    required
    min="0"
    max="100"
    step="0.01"
    oninput="validateInput(this)">

                                    <span class="input-group-text bg-black text-white">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center mx-3 mb-4">
                            <label for="bankCharges" class="col-sm-3 col-form-label fw-bold">
                                Bank Charges
                            </label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control bg-white"
                                        id="bankCharges"
                                        name="bankCharges"
                                        placeholder="Enter bank charges"
                                        min="0"
                                        step="1"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                    <span class="input-group-text bg-secondary text-white">LKR</span>
                                </div>
                                <small class="form-text text-muted">
                                    Enter bank charges only for one installment.
                                </small>
                            </div>
                        </div>

                        <div class="row mx-3 mb-3">
                            <label class="col-sm-3 col-form-label">Apply Full Payment Discount<span class="text-danger">*</span></label>
                            <div class="col-sm-3 d-flex justify-content-between">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="applyDiscountYes" name="applyDiscount" value="yes">
                                    <label class="form-check-label cursor-pointer bg-white p-1 rounded" for="applyDiscountYes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="applyDiscountNo" name="applyDiscount" value="no" checked>
                                    <label class="form-check-label cursor-pointer bg-white p-1 rounded" for="applyDiscountNo">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center mx-3 mb-3" id="discountField" style="display: none;">
                            <label for="fullPaymentDiscount" class="col-sm-3 col-form-label fw-bold">Full Payment Discount<span class="text-danger"></span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="text" class="form-control bg-white" id="fullPaymentDiscount" name="fullPaymentDiscount" placeholder="Enter discount percentage" oninput="validateInput(this)">
                                    <span class="input-group-text bg-black text-white">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 mb-3 mx-3 rounded border bg-light-warning">
                        <div class="row mx-3">
                            <label class="col-sm-3 col-form-label">Installment Plan<span class="text-danger"></span></label>
                            <div class="col-sm-3 d-flex justify-content-between">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="franchisePaymentYes" name="franchisePayment" value="yes">
                                    <label class="form-check-label cursor-pointer bg-white p-1 rounded" for="franchisePaymentYes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="franchisePaymentNo" name="franchisePayment" value="no" checked>
                                    <label class="form-check-label cursor-pointer bg-white p-1 rounded" for="franchisePaymentNo">No</label>
                                </div>
                            </div>
                        </div>
                        <div class=" mt-3 row align-items-center" id="amountField">
                            <div class="row mx-3">
                                <label for="installments" class="col-sm-3 col-form-label fw-bold">No. of Installments<span class="text-danger"></span></label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="number" class="form-control bg-white" id="installments" name="installments" placeholder="Enter number of installments">
                                        <button type="button" class="btn btn-primary" id="addRowsBtn">Add</button>
                                    </div>
                                </div>
                            </div>
                                                         <div class="table-container">
                                 <table class="table bg-white rounded border mt-4 table-bordered">
                                     <thead>
                                         <tr class="bg-warning text-black">
                                             <th scope="col">No.</th>
                                             <th scope="col">Due Date</th>
                                             <th scope="col">Local (Rs.)</th>
                                             <th scope="col" id="internationalHeader">International</th>
                                             <th scope="col">Apply Tax</th>
                                         </tr>
                                     </thead>
                                     <tbody id="installmentsTableBody">
                                         <!-- Rows will be added here dynamically -->
                                     </tbody>
                                     <tfoot>
                                         <tr class="bg-light">
                                             <td colspan="2" class="fw-bold">Total:</td>
                                             <td id="totalLocalAmount" class="fw-bold">Rs. 0.00</td>
                                             <td id="totalInternationalAmount" class="fw-bold">0.00</td>
                                             <td></td>
                                         </tr>
                                         <tr class="bg-light">
                                             <td colspan="2" class="fw-bold">Required:</td>
                                             <td id="requiredLocalAmount" class="fw-bold">Rs. 0.00</td>
                                             <td id="requiredInternationalAmount" class="fw-bold">0.00</td>
                                             <td></td>
                                         </tr>
                                         <tr id="validationRow" style="display: none;">
                                             <td colspan="5" class="text-center">
                                                 <div id="validationMessage" class="alert" style="margin-bottom: 0;"></div>
                                             </td>
                                         </tr>
                                         <tr id="autoCompleteRow" style="display: none;">
                                             <td colspan="5" class="text-center">
                                                 <button type="button" class="btn btn-sm btn-outline-primary" id="autoCompleteBtn">
                                                     <i class="fas fa-magic"></i> Auto-complete remaining amounts
                                                 </button>
                                             </td>
                                         </tr>
                                     </tfoot>
                                 </table>
                             </div>
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
// Event delegation
document.addEventListener('change', function(e) {
    if (e.target.id === 'location') {
        window.location = '{{ route('payment.plan') }}?location=' + e.target.value;
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

function toggleAmountField() {
    var amountField = document.getElementById('amountField');
    var franchisePaymentYes = document.getElementById('franchisePaymentYes');
    if (franchisePaymentYes.checked) {
      amountField.style.display = 'flex';
    } else {
      amountField.style.display = 'none';
    }
}
toggleAmountField();

function validateInput(input) {
    // Allow only numbers and one decimal point
    input.value = input.value.replace(/[^0-9.]/g, '');
    const parts = input.value.split('.');
    if (parts.length > 2) {
        input.value = parts[0] + '.' + parts.slice(1).join('');
    }
    if (input.value.startsWith('.')) {
        input.value = '0' + input.value;
    }

    // Restrict SSCL Tax and Discount to max 100%
    if (input.id === 'ssclTax' || input.id === 'fullPaymentDiscount') {
        const value = parseFloat(input.value);
        if (value > 100) {
            input.value = 100;
            showAutofillToast('Percentage value cannot exceed 100%.');
        }
        if (value < 0) {
            input.value = 0;
        }
    }
}


function addRows() {
  const tableBody = document.getElementById('installmentsTableBody');
  const numRows = document.getElementById('installments').value;
  tableBody.innerHTML = '';
  for (let i = 1; i <= numRows; i++) {
    const row = document.createElement('tr');
    row.innerHTML = `
              <td>${i}</td>
              <td><input type="date" class="form-control" id="dueDate${i}" name="dueDate${i}"></td>
              <td><input type="number" class="form-control" id="localAmount${i}" name="localAmount${i}" placeholder="0" oninput="validateInput(this); calculateTotals();"></td>
              <td><input type="number" class="form-control" id="internationalAmount${i}" name="internationalAmount${i}" placeholder="0" oninput="validateInput(this); calculateTotals();"></td>
              <td><input type="checkbox" id="applyTax${i}" name="applyTax${i}"></td>
          `;
    tableBody.appendChild(row);
  }
  calculateTotals();
}

function calculateTotals() {
  let totalLocal = 0;
  let totalInternational = 0;

  const localInputs = document.querySelectorAll('input[id^="localAmount"]');
  const internationalInputs = document.querySelectorAll('input[id^="internationalAmount"]');

  localInputs.forEach(input => {
    totalLocal += parseFloat(input.value || 0);
  });

  internationalInputs.forEach(input => {
    totalInternational += parseFloat(input.value || 0);
  });

  // Update total display
  document.getElementById('totalLocalAmount').textContent = `Rs. ${totalLocal.toFixed(2)}`;
  document.getElementById('totalInternationalAmount').textContent = totalInternational.toFixed(2);

  // Update required amounts
  const localFee = parseFloat(document.getElementById('localFee').value || 0);
  const internationalFee = parseFloat(document.getElementById('internationalFee').value || 0);

  document.getElementById('requiredLocalAmount').textContent = `Rs. ${localFee.toFixed(2)}`;
  document.getElementById('requiredInternationalAmount').textContent = internationalFee.toFixed(2);

  // Show validation message
  validateInstallmentTotals(totalLocal, totalInternational, localFee, internationalFee);
}

function validateInstallmentTotals(totalLocal, totalInternational, requiredLocal, requiredInternational) {
  const validationRow = document.getElementById('validationRow');
  const validationMessage = document.getElementById('validationMessage');
  const autoCompleteRow = document.getElementById('autoCompleteRow');

  const localMatch = Math.abs(totalLocal - requiredLocal) <= 0.01;
  const internationalMatch = Math.abs(totalInternational - requiredInternational) <= 0.01;

  if (localMatch && internationalMatch) {
    validationRow.style.display = 'none';
    autoCompleteRow.style.display = 'none';
  } else {
    validationRow.style.display = 'table-row';
    let message = '';
    let alertClass = 'alert-warning';

    if (!localMatch) {
      const localDiff = requiredLocal - totalLocal;
      message += `Local amounts mismatch: Total (Rs. ${totalLocal.toFixed(2)}) vs Required (Rs. ${requiredLocal.toFixed(2)})<br>`;
      if (localDiff > 0) {
        message += `<small class="text-info">Remaining: Rs. ${localDiff.toFixed(2)}</small><br>`;
      } else {
        message += `<small class="text-danger">Excess: Rs. ${Math.abs(localDiff).toFixed(2)}</small><br>`;
      }
    }
    if (!internationalMatch) {
      const internationalDiff = requiredInternational - totalInternational;
      message += `International amounts mismatch: Total (${totalInternational.toFixed(2)}) vs Required (${requiredInternational.toFixed(2)})<br>`;
      if (internationalDiff > 0) {
        message += `<small class="text-info">Remaining: ${internationalDiff.toFixed(2)}</small>`;
      } else {
        message += `<small class="text-danger">Excess: ${Math.abs(internationalDiff).toFixed(2)}</small>`;
      }
    }

    validationMessage.innerHTML = message;
    validationMessage.className = `alert ${alertClass}`;

    // Show auto-complete button if there are remaining amounts
    const localDiff = requiredLocal - totalLocal;
    const internationalDiff = requiredInternational - totalInternational;
    if ((localDiff > 0 && localDiff < requiredLocal * 0.1) || (internationalDiff > 0 && internationalDiff < requiredInternational * 0.1)) {
      autoCompleteRow.style.display = 'table-row';
    } else {
      autoCompleteRow.style.display = 'none';
    }
  }
}

function autoCompleteRemaining() {
  const localFee = parseFloat(document.getElementById('localFee').value || 0);
  const internationalFee = parseFloat(document.getElementById('internationalFee').value || 0);

  let totalLocal = 0;
  let totalInternational = 0;

  const localInputs = document.querySelectorAll('input[id^="localAmount"]');
  const internationalInputs = document.querySelectorAll('input[id^="internationalAmount"]');

  localInputs.forEach(input => {
    totalLocal += parseFloat(input.value || 0);
  });

  internationalInputs.forEach(input => {
    totalInternational += parseFloat(input.value || 0);
  });

  const localDiff = localFee - totalLocal;
  const internationalDiff = internationalFee - totalInternational;

  // Find the last non-empty installment and add the remaining amounts
  let lastLocalInput = null;
  let lastInternationalInput = null;

  for (let i = localInputs.length - 1; i >= 0; i--) {
    if (parseFloat(localInputs[i].value || 0) > 0) {
      lastLocalInput = localInputs[i];
      break;
    }
  }

  for (let i = internationalInputs.length - 1; i >= 0; i--) {
    if (parseFloat(internationalInputs[i].value || 0) > 0) {
      lastInternationalInput = internationalInputs[i];
      break;
    }
  }

  // If no installment has amounts, use the first one
  if (!lastLocalInput && localInputs.length > 0) {
    lastLocalInput = localInputs[0];
  }
  if (!lastInternationalInput && internationalInputs.length > 0) {
    lastInternationalInput = internationalInputs[0];
  }

  // Add remaining amounts
  if (lastLocalInput && localDiff > 0) {
    const currentValue = parseFloat(lastLocalInput.value || 0);
    lastLocalInput.value = (currentValue + localDiff).toFixed(2);
  }

  if (lastInternationalInput && internationalDiff > 0) {
    const currentValue = parseFloat(lastInternationalInput.value || 0);
    lastInternationalInput.value = (currentValue + internationalDiff).toFixed(2);
  }

  // Recalculate totals
  calculateTotals();
}

function toggleDiscountField() {
    const applyDiscountYes = document.getElementById('applyDiscountYes').checked;
    const discountField = document.getElementById('discountField');
    const discountInput = document.getElementById('fullPaymentDiscount');

    if (applyDiscountYes) {
        discountField.style.display = 'flex';
        discountInput.setAttribute('required', 'required'); // ✅ Make it required
    } else {
        discountField.style.display = 'none';
        discountInput.removeAttribute('required'); // ✅ Remove required
        discountInput.value = ''; // Optional: clear value when hidden
    }
}

document.getElementById('currency').addEventListener('change', function() {
  var selectedCurrency = this.value;
  var headerElement = document.getElementById('internationalHeader');
  headerElement.textContent = 'International (' + selectedCurrency + ')';
});
window.onload = function() {
  var selectedCurrency = document.getElementById('currency').value;
  document.getElementById('internationalHeader').textContent = 'International (' + selectedCurrency + ')';
};

function autofillFromIntake() {
  var courseId = $('#course').val();
  var location = $('#location').val();
  var intakeId = $('#intake').val();
  if (!courseId || !location || !intakeId) return;
  $.ajax({
    url: '/get-intake-fees',
    type: 'POST',
    data: {
      _token: $('input[name="_token"]').val(),
      course_id: courseId,
      location: location,
      intake_id: intakeId
    },
    success: function(response) {
      if (response.success) {
        $('#registrationFee').val(response.registration_fee);
        $('#localFee').val(response.course_fee);
        if (response.franchise_payment !== null) {
          $('#internationalFee').val(response.franchise_payment);
          // Update currency display from intakes table
          $('#currencyDisplay').text('  ' + response.franchise_payment_currency + '  ');
          // Update the hidden currency field
          $('#currency').val(response.franchise_payment_currency);
          // Update the header text
          document.getElementById('internationalHeader').textContent = 'International (' + response.franchise_payment_currency + ')';
        }
        // Populate SSCL tax and bank charges from intakes table
        $('#ssclTax').val(response.sscl_tax);
        $('#bankCharges').val(response.bank_charges);

        // Recalculate totals after updating fees
        calculateTotals();
      } else {
        showAutofillToast(response.message || 'No intake data found for autofill.');
      }
    },
    error: function(xhr) {
      let msg = 'No intake data found for autofill.';
      if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
      showAutofillToast(msg);
    }
  });
}
$('#intake').on('change', autofillFromIntake);

// Intake dropdown population
$('#course').on('change', function() {
    $('#intake').val('').prop('disabled', true);

    // Clear the fee fields when course/location changes
    $('#registrationFee, #localFee, #internationalFee, #ssclTax, #bankCharges').val('');

    if($('#course').val() && $('#location').val()) {
        // AJAX to get intakes for course/location
        $.ajax({
            url: '{{ route("intakes.byCourse") }}',
            method: 'POST',
            data: {
                course_id: $('#course').val(),
                location: $('#location').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success && response.data && response.data.length > 0) {
                    $('#intake').empty().append('<option selected disabled value="">Select Intake...</option>');
                    response.data.forEach(function(intake) {
                        // Use batch directly from response
                        $('#intake').append(`<option value="${intake.intake_id}">${intake.batch}</option>`);
                    });
                    $('#intake').prop('disabled', false);
                } else {
                    $('#intake').empty().append('<option selected disabled value="">No intakes available</option>');
                    $('#intake').prop('disabled', true);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching intakes:', error);
                console.error('Response:', xhr.responseText);
                $('#intake').empty().append('<option selected disabled value="">Error loading intakes</option>');
                $('#intake').prop('disabled', true);
                showAutofillToast('Failed to load intakes. Please try again.');
            }
        });
    }
});

function showAutofillToast(message) {
  if (!message) return;
  if ($('.toast-autofill').length) $('.toast-autofill').remove();
  const toastHtml = `<div class="toast toast-autofill align-items-center text-white bg-warning border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`;
  $('body').append(toastHtml);
  const toastEl = $('.toast-autofill').last();
  const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
  toast.show();
}

// Handle form submission to collect installment data
$('#paymentPlanForm').on('submit', function(e) {
  e.preventDefault();

  // Collect installment data from the table
  const installments = [];
  const rows = document.querySelectorAll('#installmentsTableBody tr');

  rows.forEach((row, index) => {
    const installmentNumber = index + 1;
    const dueDateInput = row.querySelector('input[type="date"]');
    const localAmountInput = row.querySelector('input[id^="localAmount"]');
    const internationalAmountInput = row.querySelector('input[id^="internationalAmount"]');
    const applyTaxInput = row.querySelector('input[type="checkbox"]');

    if (dueDateInput && localAmountInput && internationalAmountInput) {
      const dueDate = dueDateInput.value || '';
      const localAmount = parseFloat(localAmountInput.value || '0') || 0;
      const internationalAmount = parseFloat(internationalAmountInput.value || '0') || 0;
      const applyTax = applyTaxInput ? applyTaxInput.checked : false;

      if (localAmount > 0 || internationalAmount > 0) {
        installments.push({
          installment_number: installmentNumber,
          due_date: dueDate,
          local_amount: localAmount,
          international_amount: internationalAmount,
          apply_tax: applyTax
        });
      }
    }
  });

  // Validate installment amounts if installment plan is enabled
  if ($('#franchisePaymentYes').is(':checked') && installments.length > 0) {
    const localFee = parseFloat($('#localFee').val() || '0');
    const internationalFee = parseFloat($('#internationalFee').val() || '0');

    let totalLocalAmount = 0;
    let totalInternationalAmount = 0;

    installments.forEach(function(installment) {
      totalLocalAmount += parseFloat(installment.local_amount || 0);
      totalInternationalAmount += parseFloat(installment.international_amount || 0);
    });

    const errors = [];

    // Check if local amounts sum equals local course fee
    if (Math.abs(totalLocalAmount - localFee) > 0.01) {
      errors.push(`The sum of local installment amounts (Rs. ${totalLocalAmount.toFixed(2)}) must equal the local course fee (Rs. ${localFee.toFixed(2)}).`);
    }

    // Check if international amounts sum equals franchise payment amount
    if (Math.abs(totalInternationalAmount - internationalFee) > 0.01) {
      errors.push(`The sum of international installment amounts (${totalInternationalAmount.toFixed(2)}) must equal the franchise payment amount (${internationalFee.toFixed(2)}).`);
    }

    if (errors.length > 0) {
      // Show validation errors
      let errorMessage = 'Please correct the following errors:\n';
      errors.forEach(function(error) {
        errorMessage += '• ' + error + '\n';
      });
      alert(errorMessage);
      return false;
    }
  }

  // Add installments data to form
  const installmentsInput = document.createElement('input');
  installmentsInput.type = 'hidden';
  installmentsInput.name = 'installments';
  installmentsInput.value = JSON.stringify(installments);
  this.appendChild(installmentsInput);

  // Submit the form
  this.submit();
});

// Consolidated form field toggling on page load
window.addEventListener('DOMContentLoaded', function () {
    const $form = $('#paymentPlanForm');
    const $location = $('#location');
    const $course = $('#course');
    const $intake = $('#intake');
    const $submitBtn = $('#submitBtn');

    function toggleFormFields() {
        const locationSelected = $location.val() !== '' && $location.val() !== null;

        if (!locationSelected) {
            // Disable all form fields when no location is selected
            $form.find('input, select, button, textarea')
                .not('#location')
                .prop('disabled', true);
            $submitBtn.prop('disabled', true);
        } else {
            // When location is selected, enable course dropdown
            $course.prop('disabled', false);

            // Don't touch intake dropdown - let AJAX handler manage it
            // This prevents interference with the AJAX population

            // Enable other form inputs (but keep readonly fields as readonly)
            $form.find('input:not([readonly]), textarea')
                .not('#location, #course, #intake')
                .prop('disabled', false);

            // Enable buttons
            $form.find('button').prop('disabled', false);

            // Enable radio buttons
            $form.find('input[type="radio"], input[type="checkbox"]').prop('disabled', false);

            $submitBtn.prop('disabled', false);
        }

        // Always keep location enabled
        $location.prop('disabled', false);
    }

    // Run on page load
    toggleFormFields();

    // Run when location changes (but not on other field changes)
    $location.on('change', function() {
        toggleFormFields();
    });
});


</script>
@endsection
