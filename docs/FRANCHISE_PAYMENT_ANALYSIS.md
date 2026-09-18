# Franchise Payment Details - Current State & Issues Analysis

**Date**: May 19, 2026  
**Status**: Investigation Complete - Issues Identified

---

## Overview

This document outlines how franchise payment details are currently being saved in the payment dashboard and database, identifies currency conversion issues, and provides recommendations for proper storage.

---

## 1. Current Architecture

### 1.1 Database Schema (PaymentDetail Table)

The `payment_details` table stores the following relevant fields for franchise payments:

```sql
- id (primary key)
- student_id
- course_registration_id
- amount                      ← LKR amount (after conversion)
- foreign_currency_code       ← Original currency (USD, GBP, EUR, etc.)
- foreign_currency_amount     ← Original amount (before conversion)
- conversion_rate             ← Rate used to convert to LKR
- sscl_tax_amount            ← Tax applied (on LKR amount)
- bank_charges               ← Fixed charges in LKR
- total_fee                  ← amount + sscl_tax + bank_charges + late_fee
- remaining_amount           ← Outstanding amount
- installment_type           ← 'franchise_fee', 'course_fee', 'registration_fee'
- status                     ← 'pending', 'paid', 'failed'
- payment_date              ← When payment was received
- payment_effective_date    ← When payment was credited (may differ for retroactive entries)
```

---

## 2. Payment Generation Flow

### 2.1 Frontend Submission → PaymentController::generatePaymentSlip()

**Location**: `app/Http/Controllers/PaymentController.php` (Line 1200-1450)

```php
public function generatePaymentSlip(Request $request)
{
    // Input validation
    $amount             = (float) $request->amount;              // User enters amount
    $currency_from      = $request->currency_from ?? 'USD';    // Original currency
    $conversion_rate    = (float) ($request->conversion_rate ?? 0); // Conversion rate
    $payment_type       = $request->payment_type;               // Always 'franchise_fee' here
    
    // CRITICAL ISSUE: Conversion happens here
    if ($paymentType === 'franchise_fee') {
        $foreignCurrency = $request->currency_from ?? 'USD';
        $foreignAmount = $amount;  // ✅ Store original amount
        
        if ($conversionRate > 0) {
            $amount = round($amount * $conversionRate, 2); // ❌ CONVERTS IMMEDIATELY
        }
    }
    
    // Then later, store to database...
}
```

**Issues**:
1. ✅ **Correct**: Original amount stored in `foreign_currency_amount`
2. ✅ **Correct**: Conversion rate stored in `conversion_rate`
3. ⚠️ **Issue**: If `$conversion_rate` is not provided → `$conversion_rate = 0` → Amount is NOT converted
4. ⚠️ **Issue**: Database stores amount in MIXED currencies (some in USD, some in LKR)

---

## 3. Current Issues

### 3.1 **Issue #1: Non-Paid Franchises Stored in Mixed Currencies**

**Scenario**: User creates a franchise payment slip WITHOUT providing conversion rate

```
Frontend Input:
- Amount: 500 USD
- Currency: USD
- Conversion Rate: (empty) → Defaults to 0

Database Result:
- amount = 500 (USD, but column doesn't know!)
- foreign_currency_code = 'USD'
- foreign_currency_amount = 500
- conversion_rate = NULL or 0
- total_fee = 500 (USD not LKR!)
```

**Problem**: The `amount` field is ambiguous - it's unclear if it's USD or LKR until you check `conversion_rate` and `foreign_currency_code`.

---

### 3.2 **Issue #2: Inconsistent Total Fee Calculation**

**For Paid Franchises** (Line 1327-1350):

```php
$franchiseFee = $amount;  // LKR after conversion

// Get SSCL tax percentage from payment plan
$ssclPercent = $paymentPlan->sscl_tax ?? 0;  // e.g., 10%
$ssclTaxAmount = round($franchiseFee * ($ssclPercent / 100), 2);

// Add bank charges
$bankCharges = $paymentPlan->bank_charges ?? 0;

// Total remaining
$remainingAmount = round($franchiseFee + $ssclTaxAmount + $bankCharges, 2);

// Stored as:
$totalFee = $franchiseFee + $ssclTaxAmount + $bankCharges + $lateFee;
```

**Problem**: If conversion rate changes between when payment was promised and when it's paid:
- **Promised**: USD 500 @ 320 = LKR 160,000
- **Paid Later**: USD 500 @ 330 = LKR 165,000
- System stores the new LKR amount, losing the original promise

---

### 3.3 **Issue #3: SSCL Tax & Bank Charges Applied Before Payment**

**Current Logic**:
- SSCL tax is calculated on the LKR-converted amount
- Bank charges are added to pending payment
- Both are included in `remaining_amount`

**Problem**: For non-paid franchises, these charges are locked in at CREATION time:
- If SSCL tax rate changes, old pending payments are unaffected
- If bank charges change, pending payments don't update
- Creates audit trail confusion

---

## 4. Data Integrity Checks

### 4.1 Current Dashboard Display

In `PaymentController::getPaymentDetails()` (Line 175-250):

```php
// For franchise_fee:
$rows[] = [
    'amount'          => $paymentDetail && $paymentDetail->foreign_currency_amount !== null
                        ? (float) $paymentDetail->foreign_currency_amount
                        : (float) $ins->international_amount,
    'currency'        => $paymentDetail->foreign_currency_code ?? 'USD',
    'conversion_rate' => $paymentDetail ? (float) $paymentDetail->conversion_rate : null,
    'lkr_amount'      => $paymentDetail ? (float) $paymentDetail->amount : null,
];
```

**What's displayed**:
- ✅ Original foreign amount
- ✅ Currency code
- ✅ Conversion rate (if paid)
- ✅ LKR equivalent (if paid)

**What's missing**:
- ❌ Total fee breakdown (amount + tax + charges)
- ❌ Whether charges are "provisional" or "locked"

---

## 5. Tally Verification Points

### 5.1 For **Non-Paid** Franchise Payments

**Expected**:
```
Database.amount = Foreign Currency Amount (USD/GBP/EUR)
Database.foreign_currency_code = Currency Code
Database.foreign_currency_amount = Same as amount
Database.conversion_rate = NULL (or 0)
Database.sscl_tax_amount = NULL or 0 (not yet applied)
Database.bank_charges = NULL or 0 (not yet applied)
Database.total_fee = amount (no additions yet)
Database.remaining_amount = amount
```

**Actual** (Current Issues):
```
Database.amount = ??? (Could be foreign amount or LKR if rate provided)
Database.foreign_currency_code = Correct
Database.foreign_currency_amount = Correct
Database.conversion_rate = NULL (no conversion done)
Database.sscl_tax_amount = Calculated & locked in
Database.bank_charges = Calculated & locked in
Database.total_fee = amount + tax + charges (INCONSISTENT!)
```

---

### 5.2 For **Paid** Franchise Payments

**Expected**:
```
Database.amount = LKR (locked at payment date)
Database.foreign_currency_code = Original currency
Database.foreign_currency_amount = Original amount quoted
Database.conversion_rate = Locked rate at payment time
Database.sscl_tax_amount = Calculated on LKR amount
Database.bank_charges = Fixed charges in LKR
Database.total_fee = amount + tax + charges + late_fee - approved_late_fee
Database.remaining_amount = 0 (or partial if partial payment)
Database.payment_date = Payment received date
Database.status = 'paid'
```

**Actual** (Mostly Correct):
```
✅ Database.amount = Correct
✅ Database.foreign_currency_code = Correct
✅ Database.foreign_currency_amount = Correct
✅ Database.conversion_rate = Correct
✅ Database.sscl_tax_amount = Correct
✅ Database.bank_charges = Correct
✅ Database.total_fee = Correct
✅ Database.remaining_amount = Correct
✅ Database.payment_date = Correct
✅ Database.status = Correct
```

---

## 6. Recommendations

### 6.1 For **Non-Paid** Franchises (DO NOT CONVERT)

**Rule**: Keep amounts in original currency until payment is received.

**Implementation**:

```php
if ($paymentType === 'franchise_fee' && !$isPaid) {
    // 1. Store original amount
    $amount = (float) $request->amount;  // USD 500
    $foreignCurrency = $request->currency_from;  // 'USD'
    
    // 2. DO NOT convert or apply conversion rate
    $conversionRate = null;  // EXPLICITLY NULL
    $lkrAmount = null;       // EXPLICITLY NULL
    
    // 3. DO NOT calculate SSCL/charges yet
    $ssclTaxAmount = null;
    $bankCharges = null;
    $remainingAmount = $amount;  // Just the original amount
    
    // 4. Save to database
    PaymentDetail::create([
        'amount' => $amount,  // Original currency amount
        'foreign_currency_code' => $foreignCurrency,
        'foreign_currency_amount' => $amount,
        'conversion_rate' => null,  // ✅ KEY CHANGE
        'sscl_tax_amount' => null,   // ✅ KEY CHANGE
        'bank_charges' => null,      // ✅ KEY CHANGE
        'total_fee' => $amount,      // Just the base amount
        'remaining_amount' => $amount,
        'status' => 'pending',
    ]);
}
```

**Benefits**:
- ✅ No ambiguity - `amount` is always in `foreign_currency_code`
- ✅ Conversion rates don't affect pending amounts
- ✅ Can update SSCL/charges without modifying original promise
- ✅ When payment is made, lock the rate and calculate charges

---

### 6.2 For **Paid** Franchises (LOCK CONVERSION)

**Rule**: When payment is received, lock the conversion rate and calculate charges based on that rate.

**Implementation**:

```php
if ($paymentType === 'franchise_fee' && $isPaid) {
    // 1. Get current conversion rate at payment time
    $conversionRate = (float) $request->conversion_rate;  // 320
    
    // 2. Convert to LKR with locked rate
    $lkrAmount = round($foreignAmount * $conversionRate, 2);  // 500 * 320 = 160,000
    
    // 3. NOW calculate SSCL/charges on locked LKR amount
    $ssclPercent = $paymentPlan->sscl_tax ?? 0;  // 10%
    $ssclTaxAmount = round($lkrAmount * ($ssclPercent / 100), 2);
    $bankCharges = $paymentPlan->bank_charges ?? 0;  // Fixed 500
    
    // 4. Total is locked
    $remainingAmount = round($lkrAmount + $ssclTaxAmount + $bankCharges, 2);
    
    // 5. Save with all locked values
    PaymentDetail::create([
        'amount' => $lkrAmount,  // ✅ Locked LKR
        'foreign_currency_code' => $foreignCurrency,
        'foreign_currency_amount' => $foreignAmount,
        'conversion_rate' => $conversionRate,  // ✅ Locked rate
        'sscl_tax_amount' => $ssclTaxAmount,   // ✅ Locked charges
        'bank_charges' => $bankCharges,        // ✅ Locked charges
        'total_fee' => round($lkrAmount + $ssclTaxAmount + $bankCharges + $lateFee - $approvedLateFee, 2),
        'remaining_amount' => $remainingAmount,
        'payment_date' => $paymentDate,
        'status' => 'paid',
    ]);
}
```

**Benefits**:
- ✅ Conversion rate frozen at payment date
- ✅ SSCL/charges calculated on actual LKR received
- ✅ Future rate changes don't affect past payments
- ✅ Clear audit trail

---

### 6.3 Payment Slip Generation

**Current**: Shows `lkr_amount` from database

**Recommended**: Show payment breakdown clearly

```
FRANCHISE PAYMENT SLIP
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Franchise Amount (Original)    USD 500.00
Conversion Rate                @ 320.00
Franchise Amount (LKR)         LKR 160,000.00

SSCL Tax (10%)                 LKR 16,000.00
Bank Charges                   LKR 500.00
─────────────────────────────────────
TOTAL DUE                      LKR 176,500.00
```

---

## 7. Implementation Checklist

### Phase 1: Validation & Reporting
- [ ] Audit all existing `pending` franchise payments with non-null `conversion_rate`
- [ ] Identify payments with mixed currency amounts
- [ ] Generate report of inconsistent totals

### Phase 2: Fix Non-Paid Franchises
- [ ] Update `generatePaymentSlip()` to set `conversion_rate = null` for pending franchises
- [ ] Update `generatePaymentSlip()` to set `sscl_tax_amount = null` for pending franchises
- [ ] Update `generatePaymentSlip()` to set `bank_charges = null` for pending franchises
- [ ] Test with USD, GBP, EUR payments

### Phase 3: Fix Payment Recording
- [ ] Update payment recording to lock conversion rate
- [ ] Ensure SSCL/charges calculated on final LKR amount
- [ ] Add payment_effective_date logic for retroactive entries

### Phase 4: Dashboard Updates
- [ ] Update `getPaymentDetails()` to show breakdown clearly
- [ ] Update payment slip template to show conversion snapshot
- [ ] Add validation to ensure foreign_currency_amount × conversion_rate ≈ amount

### Phase 5: Reporting & Tally
- [ ] Create franchise payment tally report
- [ ] Verify all pending amount = original currency amount
- [ ] Verify all paid amount = locked LKR amount
- [ ] Generate reconciliation report

---

## 8. Testing Scenarios

### Scenario 1: Create Pending Franchise Payment
```
Input:
- Amount: USD 500
- Currency: USD
- Conversion Rate: (empty)

Expected Database State:
✅ amount = 500
✅ foreign_currency_code = 'USD'
✅ conversion_rate = null
✅ sscl_tax_amount = null
✅ total_fee = 500
```

### Scenario 2: Conversion Rate Changes
```
Scenario:
- Created with USD 500
- Rate was 320 on creation date
- Rate is now 340 on payment date

Expected:
✅ New payment created with rate 340
✅ LKR amount = 500 × 340 = 170,000
✅ Original promise (160,000) remains unchanged
✅ Difference in exchange gain/loss tracked separately
```

### Scenario 3: Partial Payment
```
Scenario:
- Total Due: LKR 176,500
- Partial Payment: LKR 100,000
- payment_details.remaining_amount = 76,500

Expected:
✅ New installment created for LKR 76,500
✅ Conversion rate remains locked
✅ SSCL/charges already included in total
```

---

## 9. SQL Queries for Audit

### Find Non-Paid Franchises with Conversion Rate

```sql
SELECT 
    id,
    student_id,
    amount,
    foreign_currency_code,
    foreign_currency_amount,
    conversion_rate,
    status
FROM payment_details
WHERE installment_type = 'franchise_fee'
AND status = 'pending'
AND conversion_rate IS NOT NULL
AND conversion_rate > 0;
```

### Find Inconsistent Totals

```sql
SELECT 
    id,
    amount,
    sscl_tax_amount,
    bank_charges,
    total_fee,
    (amount + sscl_tax_amount + bank_charges) as calculated_total,
    CASE 
        WHEN (amount + sscl_tax_amount + bank_charges) != total_fee THEN 'MISMATCH'
        ELSE 'OK'
    END as status
FROM payment_details
WHERE installment_type = 'franchise_fee'
AND status = 'paid';
```

---

## 10. Conclusion

**Current State**: Franchise payment amounts are inconsistently stored - some in original currency, some partially converted.

**Required Action**: 
1. **Keep pending franchises in original currency** (no conversion until paid)
2. **Lock conversion rate at payment time** (prevents rate fluctuation issues)
3. **Calculate charges only on paid amounts** (reduces confusion)
4. **Update dashboard to show full breakdown** (transparency)

**Timeline**: 1-2 weeks for complete implementation and testing
