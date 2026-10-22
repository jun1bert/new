<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PaymentItem extends Model { protected $fillable=['payment_id','product_id','description','quantity','unit_price']; protected function casts(): array{return ['quantity'=>'decimal:2','unit_price'=>'decimal:2'];} public function product(): BelongsTo{return $this->belongsTo(Product::class);} public function payment(): BelongsTo{return $this->belongsTo(Payment::class);} }
