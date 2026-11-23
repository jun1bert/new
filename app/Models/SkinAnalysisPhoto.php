<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkinAnalysisPhoto extends Model
{
    protected $fillable = ['appointment_id', 'service_id', 'type', 'path'];
}
