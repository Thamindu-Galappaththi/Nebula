# Franchise Payment - Quick Reference & Action Items

## Current Issue Summary

| Status | Issue | Impact |
|--------|-------|--------|
| ⚠️ CRITICAL | Non-paid franchises stored with mixed currencies (some USD, some LKR) | Dashboard shows inconsistent amounts |
| ⚠️ HIGH | Conversion rate applied immediately at creation (not at payment) | Lost flexibility if rates change |
| ⚠️ MEDIUM | SSCL/bank charges locked at creation time | Can't update charges for pending payments |
| ✅ LOW | Paid franchises mostly correct | Acceptable for historical payments |

---

## Database State Check

### Run These Queries to Verify Issues:

**Query 1: Count non-paid franchises with conversion rates (SHOULD BE ZERO)**
```sql
SELECT COUNT(*) as problem_count
FROM payment_details
WHERE installment_type = 'franchise_fee'
  AND status = 'pending'
  AND conversion_rate IS NOT NULL
  AND conversion_rate > 0;
```

**Query 2: Show detail of problematic records**
```sql
SELECT 
    id,
    student_id,
    amount,
    foreign_currency_code,
    foreign_currency_amount,
    conversion_rate,
    total_fee,
    status,
    created_at
FROM payment_details
WHERE installment_type = 'franchise_fee'
  AND status = 'pending'
  AND conversion_rate IS NOT NULL
ORDER BY created_at DESC
LIMIT 20;
```

**Query 3: Check paid franchises (should ALL have conversion_rate)**
```sql
SELECT 
    COUNT(*) as total_paid,
    COUNT(CASE WHEN conversion_rate IS NOT NULL THEN 1 END) as with_rate,
    COUNT(CASE WHEN conversion_rate IS NULL THEN 1 END) as missing_rate
FROM payment_details
WHERE installment_type = 'franchise_fee'
  AND status = 'paid';
```

**Query 4: Find total fee mismatches**
```sql
SELECT 
    id,
    amount,
    sscl_tax_amount,
    bank_charges,
    total_fee,
    (amount + COALESCE(sscl_tax_amount,0) + COALESCE(bank_charges,0)) as should_be
FROM payment_details
WHERE installment_type = 'franchise_fee'
  AND status = 'paid'
  AND (amount + COALESCE(sscl_tax_amount,0) + COALESCE(bank_charges,0)) != total_fee;
```

---

## Expected Values by Status

### ✅ NON-PAID FRANCHISE (Correct State)
```
amount                 = Original currency amount (e.g., 500)
foreign_currency_code  = 'USD' / 'GBP' / 'EUR'
foreign_currency_amount= Same as amount
conversion_rate        = NULL ← KEY!
sscl_tax_amount        = NULL ← KEY!
bank_charges           = NULL ← KEY!
total_fee              = amount (just the base)
remaining_amount       = amount
status                 = 'pending'
```

### ✅ PAID FRANCHISE (Correct State)
```
amount                 = LKR value (locked)
foreign_currency_code  = 'USD' / 'GBP' / 'EUR'
foreign_currency_amount= Original amount (e.g., 500)
conversion_rate        = Locked rate (e.g., 320)
sscl_tax_amount        = Calculated tax in LKR
bank_charges           = Fixed charges in LKR
total_fee              = amount + tax + charges + late_fee - approved_late_fee
remaining_amount       = 0 (if full payment) or remaining balance
status                 = 'paid'
payment_date           = Date payment received
```

---

## Code Changes Required

### 1. PaymentController::generatePaymentSlip() 
**File**: `app/Http/Controllers/PaymentController.php` (Line ~1294-1340)

**Change**:
```php
// BEFORE (Line 1319-1325):
if ($conversionRate > 0) {
    $amount = round($amount * $conversionRate, 2); // ❌ Converts immediately
}

// AFTER:
if ($conversionRate > 0) {
    // Only convert if payment is being made NOW
    if ($isPaid) {  // ← Add this check
        $amount = round($amount * $conversionRate, 2);
    } else {
        // For pending: keep original amount, don't convert
        $conversionRate = null;  // ✅ Don't store rate
    }
}
```

**Change**:
```php
// BEFORE (Line 1337-1350):
$ssclTaxAmount = round($franchiseFee * ($ssclPercent / 100), 2);

// AFTER:
// Only calculate if payment is made NOW
if ($isPaid && $paymentType === 'franchise_fee') {
    $ssclTaxAmount = round($franchiseFee * ($ssclPercent / 100), 2);
} else if ($paymentType === 'franchise_fee') {
    $ssclTaxAmount = null;  // ✅ Don't lock charges yet
    $bankCharges = null;    // ✅ Don't lock charges yet
}
```

### 2. PaymentDetail::create() 
**File**: `app/Http/Controllers/PaymentController.php` (Line ~1594-1623)

**Ensure these fields are set correctly**:
```php
$payment = PaymentDetail::create([
    // ... other fields ...
    'amount'                 => $amount,
    'conversion_rate'        => $paymentType === 'franchise_fee' && $isPaid ? $conversionRate : null,
    'sscl_tax_amount'        => $paymentType === 'franchise_fee' && $isPaid ? $ssclTaxAmount : null,
    'bank_charges'           => $paymentType === 'franchise_fee' && $isPaid ? $bankCharges : null,
    'foreign_currency_code'  => $foreignCurrency,
    'foreign_currency_amount'=> $foreignAmount,
    // ... rest of fields ...
]);
```

---

## Dashboard Display Updates

### Payment Slip Template
**File**: `resources/views/payments/payment_slip.blade.php`

**Add breakdown section**:
```blade.php
@if ($paymentType === 'franchise_fee' && $get('conversion_rate'))
    <div class="section">
        <div class="section-header">Currency Conversion</div>
        <div class="section-content">
            <p>Original Amount: {{ $get('foreign_currency_code') }} {{ number_format($get('foreign_currency_amount'), 2) }}</p>
            <p>Conversion Rate: @ {{ number_format($get('conversion_rate'), 2) }}</p>
            <p>LKR Equivalent: LKR {{ number_format($amount, 2) }}</p>
        </div>
    </div>
@endif
```

---

## Payment Workflow Diagram

```
NON-PAID FRANCHISE PAYMENT:
┌─────────────────────────────────────────┐
│ User enters: USD 500, No conversion rate│
└──────────────┬──────────────────────────┘
               │
               ▼
    ┌──────────────────────┐
    │  generatePaymentSlip │
    │  - amount = 500 USD  │
    │  - conversion_rate   │
    │    = null ✅         │
    │  - sscl_tax = null ✅│
    │  - bank_charges =    │
    │    null ✅           │
    └──────────────┬───────┘
                   │
                   ▼
        ┌────────────────────┐
        │  Save to Database  │
        │ (amounts stay USD) │
        └────────────────────┘


PAYMENT TIME (Exchange Rate Known):
┌──────────────────────────────────┐
│ User selects: Pay Now, Rate: 320 │
└──────────────┬──────────────────┘
               │
               ▼
    ┌──────────────────────────┐
    │  convertPendingPayment   │
    │  (new method to create)  │
    │  - amount = 500 × 320 =  │
    │    160,000 LKR ✅        │
    │  - conversion_rate = 320 │
    │    (locked) ✅           │
    │  - sscl_tax = 16,000 ✅  │
    │  - bank_charges = 500 ✅ │
    └──────────────┬───────────┘
                   │
                   ▼
        ┌────────────────────────┐
        │  Update to PAID Status │
        │  (all amounts in LKR)  │
        └────────────────────────┘
```

---

## Tally Verification

### For Each Franchise Payment, Check:

**Pending Payments**:
```
✅ amount = original currency (USD/GBP/EUR)
✅ conversion_rate IS NULL
✅ sscl_tax_amount IS NULL
✅ bank_charges IS NULL
✅ total_fee = amount
✅ remaining_amount = amount
```

**Paid Payments**:
```
✅ amount = LKR (integer, no decimals expected)
✅ conversion_rate > 0 (not null)
✅ sscl_tax_amount > 0 (not null)
✅ bank_charges >= 0 (not null)
✅ total_fee = amount + sscl_tax + bank_charges + late_fee - approved_late_fee
✅ remaining_amount = 0 or (balance for partial)
✅ payment_date IS NOT NULL
✅ status = 'paid'
✅ Verify: foreign_currency_amount × conversion_rate ≈ amount
```

---

## Testing Checklist

- [ ] Test creating USD franchise (no conversion rate) → Should store in USD
- [ ] Test creating GBP franchise (with conversion rate) → Should store in GBP only
- [ ] Test paying pending USD franchise → Should lock conversion rate
- [ ] Test updating pending payment → Should NOT change conversion_rate
- [ ] Test changing currency on pending → Should reset conversion_rate to null
- [ ] Test viewing payment dashboard → Show breakdown clearly
- [ ] Test generating payment slip → Show conversion snapshot
- [ ] Run tally report → All amounts should reconcile

---

## Files to Monitor

| File | Change Required | Priority |
|------|-----------------|----------|
| `app/Http/Controllers/PaymentController.php` | Line 1294-1350, 1594-1623 | HIGH |
| `resources/views/payments/payment_slip.blade.php` | Add conversion breakdown | MEDIUM |
| `resources/views/payments/payment.blade.php` | Update display fields | MEDIUM |
| `app/Models/PaymentDetail.php` | Add validation methods | LOW |
| Database migrations | None (schema already correct) | N/A |

---

## Rollout Plan

### Week 1
- [ ] Run audit queries on production
- [ ] Generate report of all problematic records
- [ ] Document affected franchises

### Week 2
- [ ] Deploy code fixes
- [ ] Update payment dashboard
- [ ] Test with staging data

### Week 3
- [ ] Production rollout
- [ ] Monitor payment creation
- [ ] User acceptance testing

### Week 4
- [ ] Run final tally report
- [ ] Document lessons learned
- [ ] Create runbooks

---

## Questions for Review

1. **Should we backfill existing non-paid franchises?**
   - Option A: Nullify conversion_rate, sscl_tax, bank_charges
   - Option B: Leave as-is, only apply fix going forward
   - **Recommendation**: Option A (for data consistency)

2. **Should we track exchange gain/loss separately?**
   - When rate changes between quote and payment
   - Creates audit trail and accounting entries
   - **Recommendation**: Yes, create separate line item

3. **Who approves conversion rates for franchise payments?**
   - Currently: Whoever processes the payment
   - Better: Finance team should input rate
   - **Recommendation**: Add approval workflow

4. **What happens to partial payments on franchises?**
   - Current: Stored in `partial_payments` JSON array
   - Should we re-lock each partial? 
   - **Recommendation**: Lock rate on first partial, keep for subsequent

---

## Contact & Support

For questions about this analysis:
1. Review `FRANCHISE_PAYMENT_ANALYSIS.md` for detailed explanation
2. Check database queries in "Database State Check" section
3. Review code changes required in "Code Changes Required" section
