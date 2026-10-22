<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = [
        'treatment_course_id', 'user_id', 'service_id', 'staff_id',
        'session_number', 'scheduled_at', 'contact_number', 'notes', 'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function client(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'staff_id'); }
    public function payment(): HasOne { return $this->hasOne(Payment::class); }
}
