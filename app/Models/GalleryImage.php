<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $fillable = ['path', 'title', 'caption', 'is_published'];

    protected function casts(): array { return ['is_published' => 'boolean']; }
}
