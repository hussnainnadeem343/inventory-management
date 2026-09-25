<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultShopId = DB::table('shops')->where('code', 'MAIN-01')->value('id') ?? 1;

        // 1. Update users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')->constrained('shops')->nullOnDelete();
            $table->index(['shop_id', 'role']);
        });

        DB::table('users')
            ->where('role', '!=', 'super_admin')
            ->update(['shop_id' => $defaultShopId]);

        // 2. Update brands table
        Schema::table('brands', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id');
        });

        DB::table('brands')->update(['shop_id' => $defaultShopId]);

        Schema::table('brands', function (Blueprint $table) {
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->index(['shop_id', 'status']);
        });

        // 3. Update categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id');
        });

        DB::table('categories')->update(['shop_id' => $defaultShopId]);

        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->index(['shop_id', 'status']);
        });

        // 4. Update inventory_items table
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id');
            $table->date('expiry_date')->nullable()->after('category_id');
            $table->decimal('initial_quantity', 14, 2)->default(0)->after('expiry_date');
            $table->decimal('alert_quantity', 14, 2)->default(5)->after('sold_quantity');
        });

        // Backfill shop_id, consolidate quantity and compute initial_quantity
        DB::table('inventory_items')->update([
            'shop_id' => $defaultShopId,
            'quantity' => DB::raw('yk_stock + mk_stock'),
            'initial_quantity' => DB::raw('yk_stock + mk_stock + sold_quantity'),
        ]);

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->dropColumn(['yk_stock', 'mk_stock']);
            $table->dropUnique('inventory_items_sku_unique');
            $table->unique(['shop_id', 'sku'], 'inventory_items_shop_sku_unique');
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'expiry_date']);
        });

        // 5. Update inventory_transactions table
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id');
            $table->decimal('balance_before', 14, 2)->default(0)->after('quantity');
            $table->decimal('balance_after', 14, 2)->default(0)->after('balance_before');
            $table->decimal('unit_cost', 14, 2)->nullable()->after('balance_after');
            $table->decimal('unit_sale_price', 14, 2)->nullable()->after('unit_cost');
            $table->date('expiry_date')->nullable()->after('unit_sale_price');
            $table->string('notes')->nullable()->after('expiry_date');
        });

        DB::table('inventory_transactions')->update([
            'shop_id' => $defaultShopId,
        ]);

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->index(['shop_id', 'transaction_type', 'created_at']);
        });
    }

    public function down(): void
    {
        // Reversible rollback
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropIndex(['shop_id', 'transaction_type', 'created_at']);
            $table->dropColumn([
                'shop_id',
                'balance_before',
                'balance_after',
                'unit_cost',
                'unit_sale_price',
                'expiry_date',
                'notes',
            ]);
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropUnique('inventory_items_shop_sku_unique');
            $table->dropIndex(['shop_id', 'status']);
            $table->dropIndex(['shop_id', 'expiry_date']);
            $table->decimal('yk_stock', 14, 2)->default(0);
            $table->decimal('mk_stock', 14, 2)->default(0);
            $table->unique('sku', 'inventory_items_sku_unique');
            $table->dropColumn(['shop_id', 'expiry_date', 'initial_quantity', 'alert_quantity']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropIndex(['shop_id', 'status']);
            $table->dropColumn('shop_id');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropIndex(['shop_id', 'status']);
            $table->dropColumn('shop_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropIndex(['shop_id', 'role']);
            $table->dropColumn('shop_id');
        });
    }
};
