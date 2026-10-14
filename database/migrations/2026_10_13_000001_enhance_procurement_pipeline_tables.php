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
        // 1. Add rejected_quantity to inward_gate_pass_items
        if (Schema::hasTable('inward_gate_pass_items')) {
            Schema::table('inward_gate_pass_items', function (Blueprint $table) {
                if (!Schema::hasColumn('inward_gate_pass_items', 'rejected_quantity')) {
                    $table->decimal('rejected_quantity', 10, 2)->default(0.00)->after('declared_quantity');
                }
            });
        }

        // 2. Add tax_percent to purchase_orders
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_orders', 'tax_percent')) {
                    $table->decimal('tax_percent', 5, 2)->default(0.00)->after('tax_amount');
                }
            });
        }

        // 3. Add item-level discount and tax to purchase_order_items
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_order_items', 'discount_amount')) {
                    $table->decimal('discount_amount', 12, 2)->default(0.00)->after('unit_cost');
                }
                if (!Schema::hasColumn('purchase_order_items', 'tax_percent')) {
                    $table->decimal('tax_percent', 5, 2)->default(0.00)->after('discount_amount');
                }
                if (!Schema::hasColumn('purchase_order_items', 'tax_amount')) {
                    $table->decimal('tax_amount', 12, 2)->default(0.00)->after('tax_percent');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inward_gate_pass_items')) {
            Schema::table('inward_gate_pass_items', function (Blueprint $table) {
                if (Schema::hasColumn('inward_gate_pass_items', 'rejected_quantity')) {
                    $table->dropColumn('rejected_quantity');
                }
            });
        }

        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_orders', 'tax_percent')) {
                    $table->dropColumn('tax_percent');
                }
            });
        }

        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('purchase_order_items', 'discount_amount')) $cols[] = 'discount_amount';
                if (Schema::hasColumn('purchase_order_items', 'tax_percent')) $cols[] = 'tax_percent';
                if (Schema::hasColumn('purchase_order_items', 'tax_amount')) $cols[] = 'tax_amount';
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
