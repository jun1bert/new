<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Product extends Model { protected $fillable=['name','sku','type','cost_price','selling_price','stock_quantity','reorder_level','is_active']; protected function casts(): array{return ['cost_price'=>'decimal:2','selling_price'=>'decimal:2','stock_quantity'=>'decimal:2','reorder_level'=>'decimal:2','is_active'=>'boolean'];} public function movements(): HasMany{return $this->hasMany(InventoryMovement::class);} public function paymentItems(): HasMany{return $this->hasMany(PaymentItem::class);} }
