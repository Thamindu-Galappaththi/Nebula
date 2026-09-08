<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UserTracking;
use App\Traits\CastsDateOnly;

class PaymentDetail extends Model
{
    use HasFactory, UserTracking, CastsDateOnly;

    protected $table = 'payment_details';

    protected $fillable = [
        'student_id',
        'course_registration_id',
        'misc_category',        // ✅ new
        'misc_reference',       // ✅ new
        'description',          // ✅ new (for remarks/extra info)
        'amount',
        'payment_method',
        'transaction_id',
        'remarks',
        'payment_date',
        'paid_slip_path',
        'installment_number',
        'payment_name',
        'due_date',
        'late_fee',
        'approved_late_fee',
        'total_fee',
        'remaining_amount',
        'partial_payments',
        'status',
        'created_at',
        'updated_at',
        'foreign_currency_code',
        'foreign_currency_amount',
        'installment_type',
        'sscl_tax_amount',
        'bank_charges',
        'payment_effective_date',
    ];

    protected $casts = [
        'id' => 'int',
        'student_id' => 'int',
        'course_registration_id' => 'int',
        'amount' => 'decimal:2',
        'installment_number' => 'int',
        'due_date' => 'date',
        'partial_payments' => 'array',
        'remaining_amount' => 'decimal:2',
        'conversion_rate' => 'decimal:6',
        'payment_effective_date' => 'date',
    ];

    // 🔹 Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function registration()
    {
        return $this->belongsTo(CourseRegistration::class, 'course_registration_id', 'id');
    }

    public function course()
    {
        return $this->hasOneThrough(
            Course::class,
            CourseRegistration::class,
            'id',
            'course_id',
            'course_registration_id',
            'course_id'
        );
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'method_id');
    }

    // 🔹 Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->whereHas('registration', function($q) use ($courseId) {
            $q->where('course_id', $courseId);
        });
    }

    public function scopeMiscellaneous($query)
    {
        return $query->whereNull('course_registration_id')
                     ->whereNotNull('misc_category');
    }

    public function scopeCash($query)
    {
        return $query->where('payment_method', 'cash');
    }

    public function scopeCheque($query)
    {
        return $query->where('payment_method', 'cheque');
    }

    public function scopeBankTransfer($query)
    {
        return $query->where('payment_method', 'bank_transfer');
    }

    public function scopeOnline($query)
    {
        return $query->where('payment_method', 'online');
    }

    // 🔹 Accessors
    public function getPaymentStatusTextAttribute()
    {
        return $this->status === 'paid' ? 'Successful' : ucfirst($this->status);
    }

    public function getPaymentMethodTextAttribute()
    {
        $methods = [
            'cash' => 'Cash',
            'cheque' => 'Cheque',
            'bank_transfer' => 'Bank Transfer',
            'online' => 'Online Payment',
            'card' => 'Card Payment'
        ];
        return $methods[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public function getFormattedAmountAttribute()
    {
        return 'Rs. ' . number_format($this->amount, 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at ? $this->created_at->format('d/m/Y') : 'N/A';
    }

    // 🔹 Helper Methods
    public function isSuccessful()
    {
        return $this->status === 'paid';
    }

    public function getPaymentReference()
    {
        return $this->transaction_id ?? $this->id;
    }

    public function updatePaymentStatus($status)
    {
        $this->status = $status;
        $this->save();

        // Update registration payment status if course-related
        if ($this->course_registration_id && $this->registration) {
            $this->registration->updatePaymentStatus();
        }
    }
}
