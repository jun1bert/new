<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'duration',
        'session_count',
        'price',
        'is_active',
        'requires_consent',
        'consent_content',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration' => 'integer',
            'session_count' => 'integer',
            'is_active' => 'boolean',
            'requires_consent' => 'boolean',
        ];
    }
}
