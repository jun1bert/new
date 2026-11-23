<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = ['appointment_id', 'treatment_invoice_id', 'receipt_number', 'recorded_by', 'sales_staff_id', 'amount', 'discount_amount', 'discount_reason', 'method', 'reference', 'paid_at'];

    protected function casts(): array { return ['amount' => 'decimal:2', 'discount_amount' => 'decimal:2', 'paid_at' => 'datetime']; }

    public function invoice(): BelongsTo { return $this->belongsTo(TreatmentInvoice::class, 'treatment_invoice_id'); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function salesStaff(): BelongsTo { return $this->belongsTo(User::class, 'sales_staff_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function items(): HasMany { return $this->hasMany(PaymentItem::class); }
}


