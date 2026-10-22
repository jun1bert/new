<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class StarterInventorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Cuticle Oil', 'sku' => 'TEST-RET-001', 'type' => 'retail', 'cost_price' => 95, 'selling_price' => 180, 'stock_quantity' => 12, 'reorder_level' => 4],
            ['name' => 'Nail Strengthener', 'sku' => 'TEST-RET-002', 'type' => 'retail', 'cost_price' => 150, 'selling_price' => 280, 'stock_quantity' => 8, 'reorder_level' => 3],
            ['name' => 'Hydrating Face Mask', 'sku' => 'TEST-RET-003', 'type' => 'retail', 'cost_price' => 80, 'selling_price' => 160, 'stock_quantity' => 3, 'reorder_level' => 5],
            ['name' => 'Disposable Gloves (box)', 'sku' => 'TEST-SUP-001', 'type' => 'supply', 'cost_price' => 220, 'selling_price' => 0, 'stock_quantity' => 10, 'reorder_level' => 3],
            ['name' => 'Facial Cotton Pads (pack)', 'sku' => 'TEST-SUP-002', 'type' => 'supply', 'cost_price' => 65, 'selling_price' => 0, 'stock_quantity' => 2, 'reorder_level' => 4],
            ['name' => 'Gel Polish — Nude', 'sku' => 'TEST-SUP-003', 'type' => 'supply', 'cost_price' => 180, 'selling_price' => 0, 'stock_quantity' => 6, 'reorder_level' => 2],
        ] as $product) {
            Product::firstOrCreate(['sku' => $product['sku']], $product);
        }
    }
}
