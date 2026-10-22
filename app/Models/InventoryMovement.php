<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InventoryMovement extends Model { protected $fillable=['product_id','recorded_by','quantity','type','notes']; protected function casts(): array{return ['quantity'=>'decimal:2'];} public function recorder(): BelongsTo{return $this->belongsTo(User::class,'recorded_by');} }
