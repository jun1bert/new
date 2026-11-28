<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthProfile extends Model
{
    protected $fillable = ['details'];
    protected $hidden = ['details'];

    protected function casts(): array
    {
        return ['details' => 'encrypted:array'];
    }
}
