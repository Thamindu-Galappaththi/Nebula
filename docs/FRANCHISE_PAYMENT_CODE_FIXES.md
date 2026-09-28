# Code Fix Implementation Guide

## Fix 1: Update generatePaymentSlip() - Handle Conversion Rate

**File**: `app/Http/Controllers/PaymentController.php`  
**Location**: Lines 1290-1360  
**Purpose**: Prevent converting non-paid franchises to LKR

### BEFORE:
```php
// 🔹 Franchise currency conversion
$foreignCurrency = null;
$foreignAmount = null;
$conversionRate = 1; // default

// ... lines 1300-1315 ...

if ($paymentType === 'franchise_fee') {
    $conversionRate = (float) ($request->conversion_rate ?? 0);
    $foreignCurrency = $request->currency_from ?? 'USD';
    $foreignAmount = $amount;

    if ($conversionRate > 0) {
        $amount = round($amount * $conversionRate, 2); // ❌ PROBLEM: Converts immediately
    }

    $franchiseFee = $amount; // Could be USD or LKR!
    
    // ... rest of code ...
}
```

### AFTER:
```php
// 🔹 Franchise currency conversion
$foreignCurrency = null;
$foreignAmount = null;
$conversionRate = null; // ✅ Changed from 1 to null
$lkrAmount = null; // ✅ New variable

if ($paymentType === 'franchise_fee') {
    $foreignCurrency = $request->currency_from ?? 'USD';
    $foreignAmount = $amount; // Store original amount
    $conversionRate = (float) ($request->conversion_rate ?? 0);
    
    // ✅ KEY CHANGE: Only convert if conversion rate is provided (means payment is happening)
    if ($conversionRate > 0) {
        $lkrAmount = round($foreignAmount * $conversionRate, 2);
        $amount = $lkrAmount; // Use LKR for calculation
    } else {
        // For pending payments without conversion rate: keep in original currency
        $amount = $foreignAmount;
        $conversionRate = null; // ✅ Don't store rate for pending
    }

    $franchiseFee = $amount;
    
    // ... rest of code ...
}
```

---

## Fix 2: Update SSCL Tax Calculation - Only Apply When Paid

**File**: `app/Http/Controllers/PaymentController.php`  
**Location**: Lines 1330-1365  
**Purpose**: Don't calculate charges for pending franchises

### BEFORE:
```php
// 🔹 Get student payment plan
$studentPlan = \App\Models\StudentPaymentPlan::where('student_id', $student->student_id)
    ->where('course_id', $request->course_id)
    ->where('status', 'active')
    ->first();

if ($studentPlan) {
    $paymentPlan = \App\Models\PaymentPlan::where('id', $studentPlan->payment_plan_id)
        ->where('course_id', $request->course_id)
        ->where('intake_id', $registration->intake_id)
        ->first();

    if ($paymentPlan) {
        // ❌ PROBLEM: Calculates tax even for pending payments
        $ssclPercent = $paymentPlan->sscl_tax ?? 0;
        $bankCharges = $paymentPlan->bank_charges ?? 0;
        $ssclTaxAmount = round($franchiseFee * ($ssclPercent / 100), 2);
    }
}

// Manual overrides always apply (problem!)
if ($request->filled('sscl_tax_amount')) {
    $ssclTaxAmount = (float) $request->sscl_tax_amount;
}

if ($request->filled('bank_charges')) {
    $bankCharges = (float) $request->bank_charges;
}
```

### AFTER:
```php
// 🔹 Only calculate SSCL tax and charges if payment is being made now
$ssclTaxAmount = null; // ✅ Initialize as null
$bankCharges = null;   // ✅ Initialize as null
$remainingAmount = 0;

if ($paymentType === 'franchise_fee' && $conversionRate > 0) {
    // ✅ Only calculate if conversion rate provided (means payment is happening)
    
    $studentPlan = \App\Models\StudentPaymentPlan::where('student_id', $student->student_id)
        ->where('course_id', $request->course_id)
        ->where('status', 'active')
        ->first();

    if ($studentPlan) {
        $paymentPlan = \App\Models\PaymentPlan::where('id', $studentPlan->payment_plan_id)
            ->where('course_id', $request->course_id)
            ->where('intake_id', $registration->intake_id)
            ->first();

        if ($paymentPlan) {
            // ✅ Now this only runs when payment is being made
            $ssclPercent = $paymentPlan->sscl_tax ?? 0;
            $bankCharges = $paymentPlan->bank_charges ?? 0;
            $ssclTaxAmount = round($franchiseFee * ($ssclPercent / 100), 2);
        }
    }

    // ✅ Only apply manual overrides if payment is being made
    if ($request->filled('sscl_tax_amount')) {
        $ssclTaxAmount = (float) $request->sscl_tax_amount;
    }

    if ($request->filled('bank_charges')) {
        $bankCharges = (float) $request->bank_charges;
    }

    // ✅ Calculate remaining only when payment is made
    $remainingAmount = round($franchiseFee + $ssclTaxAmount + $bankCharges, 2);
} else if ($paymentType === 'franchise_fee') {
    // ✅ For pending franchises, remaining is just the base amount
    $remainingAmount = $foreignAmount;
}
```

---

## Fix 3: Update PaymentDetail::create() - Store Correct Values

**File**: `app/Http/Controllers/PaymentController.php`  
**Location**: Lines 1594-1623  
**Purpose**: Store pending and paid franchises correctly

### BEFORE:
```php
// --- Create new Payment record ---
$payment = \App\Models\PaymentDetail::create([
    'student_id'             => $student->student_id,
    'course_registration_id' => $registration->id,
    'amount'                 => $amount, // ❌ Could be USD or LKR!
    'payment_method'         => 'Cash',
    'transaction_id'         => $receiptNo,
    'status'                 => 'pending',
    'remarks'                => $request->remarks,
    'due_date'               => $request->due_date,

    'installment_number'     => in_array($paymentType, ['course_fee','franchise_fee']) 
                                    ? $request->installment_number 
                                    : null,

    'late_fee'          => $lateFee,
    'approved_late_fee' => $approvedLateFee,
    'total_fee'         => $totalFee,
    'remaining_amount'  => $paymentType === 'franchise_fee' ? $remainingAmount : (float) $totalFee,
    'sscl_tax_amount'   => $paymentType === 'franchise_fee' ? $ssclTaxAmount : 0,
    'bank_charges'      => $paymentType === 'franchise_fee' ? $bankCharges : 0,
    'partial_payments'  => json_encode([]),
    'foreign_currency_code'  => $foreignCurrency,
    'foreign_currency_amount'=> $foreignAmount,
    'conversion_rate'        => $paymentType === 'franchise_fee' ? $conversionRate : null,
    'installment_type'           => $installmentType,
    'payment_effective_date'     => $request->payment_effective_date ?: null,
]);
```

### AFTER:
```php
// --- Create new Payment record ---
// ✅ Ensure proper values based on payment status
$isPaid = $conversionRate > 0 && $paymentType === 'franchise_fee';

$payment = \App\Models\PaymentDetail::create([
    'student_id'             => $student->student_id,
    'course_registration_id' => $registration->id,
    'amount'                 => $amount, // ✅ Now clearly in correct currency
    'payment_method'         => 'Cash',
    'transaction_id'         => $receiptNo,
    'status'                 => $isPaid ? 'paid' : 'pending', // ✅ Set correct status
    'remarks'                => $request->remarks,
    'due_date'               => $request->due_date,

    'installment_number'     => in_array($paymentType, ['course_fee','franchise_fee']) 
                                    ? $request->installment_number 
                                    : null,

    'late_fee'          => $paymentType === 'franchise_fee' && $isPaid ? $lateFee : 0,
    'approved_late_fee' => $paymentType === 'franchise_fee' && $isPaid ? $approvedLateFee : 0,
    'total_fee'         => $totalFee,
    'remaining_amount'  => $paymentType === 'franchise_fee' ? $remainingAmount : (float) $totalFee,
    
    // ✅ Only set charges if payment is made
    'sscl_tax_amount'   => $paymentType === 'franchise_fee' && $isPaid ? ($ssclTaxAmount ?? 0) : null,
    'bank_charges'      => $paymentType === 'franchise_fee' && $isPaid ? ($bankCharges ?? 0) : null,
    
    'partial_payments'  => json_encode([]),
    'foreign_currency_code'  => $foreignCurrency,
    'foreign_currency_amount'=> $foreignAmount,
    
    // ✅ Only store conversion rate if payment is made
    'conversion_rate'        => $paymentType === 'franchise_fee' && $isPaid ? $conversionRate : null,
    'installment_type'           => $installmentType,
    
    // ✅ Set payment_date if payment is made
    'payment_date'           => $isPaid ? ($request->payment_effective_date ?: now()) : null,
    'payment_effective_date' => $isPaid ? ($request->payment_effective_date ?: now()) : null,
]);
```

---

## Fix 4: Update Payment Slip Display

**File**: `resources/views/payments/payment_slip.blade.php`  
**Location**: After line 40  
**Purpose**: Show conversion details clearly for paid franchises

### ADD THIS SECTION (after "Core slip fields" section):

```blade.php
@php
    // ✅ NEW: Determine if this is a paid foreign currency payment
    $isFranchiseFee = $get('payment_type') === 'franchise_fee';
    $hasForeignCurrency = $isFranchiseFee && $get('foreign_currency_code') !== 'LKR';
    $hasConversionRate = $hasForeignCurrency && $get('conversion_rate') !== null;
@endphp

<!-- ✅ NEW: Show conversion breakdown for paid franchise payments -->
@if($hasConversionRate)
    @php
        $foreignAmount = (float) $get('foreign_currency_amount', 0);
        $convRate = (float) $get('conversion_rate', 0);
        $convertedAmount = (float) $get('amount', 0);
    @endphp
    
    <!-- DEBUG: Show values being used -->
    <!-- Foreign: {!! $foreignAmount !!} | Rate: {!! $convRate !!} | LKR: {!! $convertedAmount !!} -->
@endif
```

### AND UPDATE THE AMOUNT DISPLAY SECTION (around line 45-50):

```blade.php
<!-- BEFORE -->
<div class="teleshop-item">
    <span>Amount:</span>
    <span>{!! $fmt($amount) !!} LKR</span>
</div>

<!-- AFTER -->
@if($hasConversionRate)
    <!-- Show conversion breakdown -->
    <div class="teleshop-item">
        <span>Original Amount:</span>
        <span>{{ $get('foreign_currency_code') }} {!! $fmt($get('foreign_currency_amount')) !!}</span>
    </div>
    <div class="teleshop-item">
        <span>Conversion Rate:</span>
        <span>@ {!! number_format((float)$get('conversion_rate'), 2) !!}</span>
    </div>
    <div class="teleshop-item">
        <span>LKR Equivalent:</span>
        <span>LKR {!! $fmt($amount) !!}</span>
    </div>
@else
    <!-- Regular amount display -->
    <div class="teleshop-item">
        <span>Amount:</span>
        <span>
            @if($isFranchiseFee && $get('foreign_currency_code'))
                {{ $get('foreign_currency_code') }} {!! $fmt($get('foreign_currency_amount') ?? $amount) !!}
            @else
                LKR {!! $fmt($amount) !!}
            @endif
        </span>
    </div>
@endif
```

---

## Fix 5: Add Validation Method to PaymentDetail Model

**File**: `app/Models/PaymentDetail.php`  
**Location**: End of class (before closing brace)  
**Purpose**: Ensure data consistency

### ADD THIS METHOD:

```php
/**
 * Validate franchise payment consistency
 * Ensures pending and paid franchises have correct currency states
 */
public function validateFranchisePayment(): array
{
    $errors = [];

    if ($this->installment_type !== 'franchise_fee') {
        return $errors; // Only validate franchise fees
    }

    $isPaid = $this->status === 'paid';

    if (!$isPaid) {
        // PENDING FRANCHISE VALIDATIONS
        if ($this->conversion_rate !== null && $this->conversion_rate > 0) {
            $errors[] = 'Pending franchise should not have conversion_rate set';
        }
        if ($this->sscl_tax_amount !== null && $this->sscl_tax_amount > 0) {
            $errors[] = 'Pending franchise should not have sscl_tax_amount set';
        }
        if ($this->bank_charges !== null && $this->bank_charges > 0) {
            $errors[] = 'Pending franchise should not have bank_charges set';
        }
        if ($this->foreign_currency_code && $this->foreign_currency_code !== 'LKR') {
            if (!$this->foreign_currency_amount || $this->foreign_currency_amount == 0) {
                $errors[] = 'Pending foreign currency franchise must have foreign_currency_amount set';
            }
        }
    } else {
        // PAID FRANCHISE VALIDATIONS
        if (!$this->conversion_rate || $this->conversion_rate <= 0) {
            $errors[] = 'Paid franchise must have conversion_rate > 0';
        }
        if (!$this->foreign_currency_amount || $this->foreign_currency_amount <= 0) {
            $errors[] = 'Paid franchise must have foreign_currency_amount set';
        }
        
        // Verify conversion math
        if ($this->conversion_rate && $this->foreign_currency_amount) {
            $expectedLkr = round($this->foreign_currency_amount * $this->conversion_rate, 2);
            $tolerance = 0.01; // Allow 1 paisa difference due to rounding
            
            // Amount should be base LKR (before tax/charges)
            if (abs($this->amount - $expectedLkr) > $tolerance) {
                $errors[] = "Amount conversion mismatch: {$this->foreign_currency_amount} × {$this->conversion_rate} should equal {$expectedLkr}, got {$this->amount}";
            }
        }

        // Verify total fee calculation
        $calculatedTotal = round(
            $this->amount + 
            ($this->sscl_tax_amount ?? 0) + 
            ($this->bank_charges ?? 0) + 
            ($this->late_fee ?? 0) - 
            ($this->approved_late_fee ?? 0), 
            2
        );
        
        if (abs($this->total_fee - $calculatedTotal) > 0.01) {
            $errors[] = "Total fee mismatch: expected {$calculatedTotal}, got {$this->total_fee}";
        }
    }

    return $errors;
}

/**
 * Mutator: Run validation on save
 */
protected static function booted()
{
    static::saving(function ($model) {
        if (config('app.debug')) { // Only in debug mode to avoid production slowdown
            $errors = $model->validateFranchisePayment();
            if (!empty($errors)) {
                \Log::warning('Franchise payment validation warnings:', $errors);
            }
        }
    });
}
```

---

## Database Audit Query

Run this after applying fixes to find records that need manual review:

```sql
-- Find pending franchises with conversion rate (should be 0)
SELECT 
    'PENDING_WITH_RATE' as issue_type,
    id, student_id, amount, foreign_currency_code, conversion_rate, status, created_at
FROM payment_details
WHERE installment_type = 'franchise_fee'
  AND status = 'pending'
  AND conversion_rate IS NOT NULL
  AND conversion_rate > 0
  
UNION ALL

-- Find pending franchises with SSCL tax (should be 0)
SELECT 
    'PENDING_WITH_SSCL' as issue_type,
    id, student_id, amount, foreign_currency_code, sscl_tax_amount, status, created_at
FROM payment_details
WHERE installment_type = 'franchise_fee'
  AND status = 'pending'
  AND sscl_tax_amount IS NOT NULL
  AND sscl_tax_amount > 0
  
UNION ALL

-- Find paid franchises with no conversion rate (should be 0)
SELECT 
    'PAID_NO_RATE' as issue_type,
    id, student_id, amount, foreign_currency_code, conversion_rate, status, created_at
FROM payment_details
WHERE installment_type = 'franchise_fee'
  AND status = 'paid'
  AND foreign_currency_code IS NOT NULL
  AND foreign_currency_code != 'LKR'
  AND (conversion_rate IS NULL OR conversion_rate = 0)

ORDER BY issue_type, created_at DESC;
```

---

## Testing Script

Add this to `tests/Feature/FranchisePaymentTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\PaymentPlan;
use App\Models\PaymentDetail;

class FranchisePaymentTest extends TestCase
{
    /**
     * Test pending franchise stored in original currency
     */
    public function test_pending_franchise_keeps_original_currency()
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $intake = Intake::factory()->create(['course_id' => $course->course_id]);
        $registration = CourseRegistration::factory()->create([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'intake_id' => $intake->intake_id,
        ]);

        // Act
        $response = $this->post('/payment/generate-payment-slip', [
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'payment_type' => 'franchise_fee',
            'amount' => 500,
            'currency_from' => 'USD',
            // No conversion_rate provided
        ]);

        // Assert
        $this->assertEquals(200, $response->status());
        
        $payment = PaymentDetail::where('student_id', $student->student_id)
            ->where('installment_type', 'franchise_fee')
            ->latest()
            ->first();

        $this->assertEquals(500, $payment->amount);
        $this->assertEquals('USD', $payment->foreign_currency_code);
        $this->assertEquals(500, $payment->foreign_currency_amount);
        $this->assertNull($payment->conversion_rate); // ✅ Key assertion
        $this->assertNull($payment->sscl_tax_amount); // ✅ Key assertion
        $this->assertNull($payment->bank_charges); // ✅ Key assertion
        $this->assertEquals('pending', $payment->status);
    }

    /**
     * Test paid franchise locked in LKR
     */
    public function test_paid_franchise_locked_in_lkr()
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $intake = Intake::factory()->create(['course_id' => $course->course_id]);
        $registration = CourseRegistration::factory()->create([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'intake_id' => $intake->intake_id,
        ]);

        // Act
        $response = $this->post('/payment/generate-payment-slip', [
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'payment_type' => 'franchise_fee',
            'amount' => 500,
            'currency_from' => 'USD',
            'conversion_rate' => 320, // Rate provided = payment is happening
        ]);

        // Assert
        $this->assertEquals(200, $response->status());
        
        $payment = PaymentDetail::where('student_id', $student->student_id)
            ->where('installment_type', 'franchise_fee')
            ->latest()
            ->first();

        $this->assertEquals(160000, $payment->amount); // 500 × 320
        $this->assertEquals('USD', $payment->foreign_currency_code);
        $this->assertEquals(500, $payment->foreign_currency_amount);
        $this->assertEquals(320, $payment->conversion_rate); // ✅ Locked
        $this->assertNotNull($payment->sscl_tax_amount); // ✅ Calculated
        $this->assertNotNull($payment->bank_charges); // ✅ Set
        $this->assertEquals('paid', $payment->status);
    }
}
```

---

## Summary of Changes

| File | Lines | Changes | Priority |
|------|-------|---------|----------|
| PaymentController.php | 1290-1365 | Fix conversion logic | HIGH |
| PaymentController.php | 1594-1623 | Fix database storage | HIGH |
| payment_slip.blade.php | +50 | Add conversion display | MEDIUM |
| PaymentDetail.php | +60 | Add validation method | MEDIUM |
| Tests | +100 | Add test cases | LOW |

**Total Lines Changed**: ~310 lines  
**Complexity**: Medium  
**Risk**: Low (backward compatible)  
**Testing Required**: All franchise payment flows

---

## Deployment Checklist

- [ ] Create feature branch: `feature/fix-franchise-currency`
- [ ] Apply all code changes above
- [ ] Run unit tests (see Testing Script)
- [ ] Run audit query to identify problematic records
- [ ] Stage changes to development environment
- [ ] Test with various currency combinations
- [ ] Create data migration for cleanup (if needed)
- [ ] Get code review approval
- [ ] Deploy to staging
- [ ] Run UAT with sample payments
- [ ] Deploy to production
- [ ] Monitor logs for validation warnings
- [ ] Generate post-deployment report
