<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceChange extends Model
{
    protected $fillable = ['staff_attendance_id', 'actor_id', 'reason', 'before', 'after'];
    protected function casts(): array { return ['before' => 'array', 'after' => 'array']; }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
