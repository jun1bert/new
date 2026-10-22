<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientClaimRequest extends Model
{
    protected $fillable = ['claimant_id', 'name_used', 'appointment_date', 'service_name', 'booking_contact_number', 'status', 'reviewed_by', 'reviewed_at', 'review_note'];

    protected function casts(): array
    {
        return ['appointment_date' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function claimant(): BelongsTo { return $this->belongsTo(User::class, 'claimant_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
