<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add product_type and is_loose to inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'product_type')) {
                $table->string('product_type', 50)->default('standard')->index()->after('unit');
            }
            if (!Schema::hasColumn('inventory_items', 'is_loose')) {
                $table->boolean('is_loose')->default(false)->index()->after('product_type');
            }
        });

        // 2. Make odometer and oil_grade nullable in lube_service_records
        if (Schema::hasTable('lube_service_records')) {
            Schema::table('lube_service_records', function (Blueprint $table) {
                $table->unsignedInteger('current_odometer')->nullable()->change();
                $table->unsignedInteger('interval_km')->nullable()->change();
                $table->unsignedInteger('next_due_odometer')->nullable()->change();
                $table->string('oil_grade')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'is_loose')) {
                $table->dropColumn('is_loose');
            }
            if (Schema::hasColumn('inventory_items', 'product_type')) {
                $table->dropColumn('product_type');
            }
        });

        if (Schema::hasTable('lube_service_records')) {
            Schema::table('lube_service_records', function (Blueprint $table) {
                $table->unsignedInteger('current_odometer')->nullable(false)->change();
                $table->unsignedInteger('interval_km')->nullable(false)->default(5000)->change();
                $table->unsignedInteger('next_due_odometer')->nullable(false)->change();
                $table->string('oil_grade')->nullable(false)->default('5W-30')->change();
            });
        }
    }
};
