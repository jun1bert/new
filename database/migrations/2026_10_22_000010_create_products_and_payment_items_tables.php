<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->enum('type', ['retail', 'supply']);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantity', 10, 2); $table->string('type'); $table->string('notes')->nullable(); $table->timestamps();
        });
        Schema::create('payment_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('payment_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description'); $table->decimal('quantity', 10, 2)->default(1); $table->decimal('unit_price', 10, 2); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payment_items'); Schema::dropIfExists('inventory_movements'); Schema::dropIfExists('products'); }
};
