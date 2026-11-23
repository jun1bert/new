<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TreatmentInvoice extends Model
{
    protected $fillable = ['treatment_course_id', 'total', 'opening_credit', 'reviewed_by'];
    protected function casts(): array { return ['total' => 'decimal:2']; }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function firstAppointment(): HasOne { return $this->hasOne(Appointment::class, 'treatment_course_id', 'treatment_course_id')->oldest('session_number'); }
    public function getPaidCentsAttribute(): int { return (int) round(((float) $this->opening_credit + (float) $this->payments()->sum('amount')) * 100); }
    public function getBalanceCentsAttribute(): int { return max(0, (int) round((float) $this->total * 100) - $this->paid_cents); }
}
