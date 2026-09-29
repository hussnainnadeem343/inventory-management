<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('batch_no', 64);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('initial_quantity', 12, 2)->default(0);
            $table->decimal('quantity', 12, 2)->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->default('active'); // active, depleted
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'inventory_item_id', 'status']);
            $table->index(['inventory_item_id', 'created_at']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_batch_id')->nullable()->after('inventory_item_id')->constrained('product_batches')->nullOnDelete();
            $table->decimal('discount', 12, 2)->default(0)->after('unit_price');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('items_discount_total', 12, 2)->default(0)->after('subtotal');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('product_batch_id')->nullable()->after('inventory_item_id')->constrained('product_batches')->nullOnDelete();
        });

        // Backfill existing inventory items into initial BATCH-001
        $now = now();
        $items = DB::table('inventory_items')->whereNull('deleted_at')->get();
        foreach ($items as $item) {
            if ($item->quantity > 0 || $item->initial_quantity > 0) {
                DB::table('product_batches')->insert([
                    'shop_id' => $item->shop_id,
                    'inventory_item_id' => $item->id,
                    'batch_no' => 'BATCH-001',
                    'purchase_price' => $item->purchase_price ?? 0,
                    'selling_price' => $item->selling_price ?? 0,
                    'initial_quantity' => $item->initial_quantity ?: $item->quantity,
                    'quantity' => $item->quantity,
                    'expiry_date' => $item->expiry_date,
                    'status' => $item->quantity > 0 ? 'active' : 'depleted',
                    'created_by' => $item->created_by,
                    'created_at' => $item->created_at ?: $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['product_batch_id']);
            $table->dropColumn('product_batch_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('items_discount_total');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['product_batch_id']);
            $table->dropColumn(['product_batch_id', 'discount']);
        });

        Schema::dropIfExists('product_batches');
    }
};
