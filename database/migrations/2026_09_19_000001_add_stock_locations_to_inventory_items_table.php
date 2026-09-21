<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('yk_stock', 14, 2)->default(0)->after('quantity');
            $table->decimal('mk_stock', 14, 2)->default(0)->after('yk_stock');
        });

        DB::table('inventory_items')->update([
            'yk_stock' => DB::raw('CASE WHEN quantity > sold_quantity THEN quantity - sold_quantity ELSE 0 END'),
            'mk_stock' => 0,
            'quantity' => DB::raw('CASE WHEN quantity > sold_quantity THEN quantity - sold_quantity ELSE 0 END'),
        ]);
    }

    public function down(): void
    {
        DB::table('inventory_items')->update([
            'quantity' => DB::raw('yk_stock + mk_stock + sold_quantity'),
        ]);

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['yk_stock', 'mk_stock']);
        });
    }
};
