<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentForm extends Model
{
    protected $fillable = ['user_id', 'service_id', 'recorded_by', 'signed_at', 'notes',
        'appointment_id', 'signed_name', 'service_name', 'content_snapshot', 'content_hash', 'signature_strokes'];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime', 'signature_strokes' => 'array'];
    }

    public function client(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
}
