<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffAttendance extends Model
{
    protected $fillable = ['user_id', 'clocked_in_at', 'clocked_out_at'];
    protected function casts(): array { return ['clocked_in_at' => 'datetime', 'clocked_out_at' => 'datetime']; }
    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function changes(): HasMany { return $this->hasMany(AttendanceChange::class)->latest('id'); }
}
