<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory_items')->whereNull('deleted_at')->orderBy('id')->each(function ($product): void {
            $stockIn = (float) DB::table('inventory_transactions')->where('inventory_item_id', $product->id)->where('transaction_type', 'STOCK_IN')->sum('quantity');
            $sold = (float) DB::table('inventory_transactions')->where('inventory_item_id', $product->id)->where('transaction_type', 'SALE')->sum('quantity');
            $now = now();
            if ((float) $product->quantity > $stockIn) {
                DB::table('inventory_transactions')->insert(['inventory_item_id' => $product->id, 'transaction_type' => 'STOCK_IN', 'quantity' => (float) $product->quantity - $stockIn, 'created_by' => $product->created_by, 'created_at' => $product->created_at ?? $now, 'updated_at' => $now]);
            }
            if ((float) $product->sold_quantity > $sold) {
                DB::table('inventory_transactions')->insert(['inventory_item_id' => $product->id, 'transaction_type' => 'SALE', 'quantity' => (float) $product->sold_quantity - $sold, 'created_by' => $product->created_by, 'created_at' => $product->created_at ?? $now, 'updated_at' => $now]);
            }
        });
    }

    public function down(): void {}
};
