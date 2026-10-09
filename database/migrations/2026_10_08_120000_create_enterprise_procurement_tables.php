<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Purchase Requisitions (Internal Demand)
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->string('pr_number', 50)->unique();
            $table->date('requisition_date');
            $table->date('required_by_date')->nullable();
            $table->string('department', 100)->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
            $table->string('status', 30)->default('pending_approval'); // draft, pending_approval, approved, rejected, converted_to_po
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'requisition_date']);
        });

        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('estimated_unit_cost', 12, 2)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // Add PR reference to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('purchase_requisition_id')->nullable()->after('supplier_id')->constrained('purchase_requisitions')->nullOnDelete();
            $table->string('payment_terms', 100)->nullable()->after('expected_delivery_date');
            $table->string('delivery_station', 150)->nullable()->after('payment_terms');
        });

        // 2. Inward Gate Passes (Gate Security Entry)
        Schema::create('inward_gate_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('igp_number', 50)->unique();
            $table->date('igp_date');
            $table->time('gate_entry_time')->nullable();
            $table->string('received_via', 100)->default('Road / Truck'); // Road / Truck, Courier, Vehicle, Hand
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 50)->nullable();
            $table->string('bilty_number', 100)->nullable(); // Consignment tracking / Bilty
            $table->string('challan_number', 100)->nullable(); // Vendor Delivery Challan
            $table->string('status', 30)->default('pending_inspection'); // pending_inspection, grn_completed, cancelled
            $table->text('remarks')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'igp_date']);
        });

        Schema::create('inward_gate_pass_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inward_gate_pass_id')->constrained('inward_gate_passes')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('packages_count', 10, 2)->default(0); // Cartons / Bags / Bundles
            $table->decimal('declared_quantity', 10, 2); // As stated on challan
            $table->string('remarks', 255)->nullable();
            $table->timestamps();
        });

        // Add IGP & Landed fields to goods_received_notes
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->foreignId('inward_gate_pass_id')->nullable()->after('purchase_order_id')->constrained('inward_gate_passes')->nullOnDelete();
            $table->string('manual_grn_number', 50)->nullable()->after('grn_number');
            $table->string('received_via', 100)->nullable()->after('manual_grn_number');
            $table->foreignId('warehouse_id')->nullable()->after('received_via')->constrained('warehouses')->nullOnDelete();
            $table->decimal('landed_expenses_total', 12, 2)->default(0.00)->after('total_amount');
            $table->string('billing_status', 30)->default('unbilled')->after('status'); // unbilled, partially_billed, fully_billed
        });

        // Add rejected quantity to goods_received_items
        Schema::table('goods_received_items', function (Blueprint $table) {
            $table->decimal('rejected_quantity', 10, 2)->default(0.00)->after('quantity');
            $table->foreignId('warehouse_id')->nullable()->after('product_batch_id')->constrained('warehouses')->nullOnDelete();
        });

        // Landed Expenses on GRN (Screenshot 5: Freight, Unloading)
        Schema::create('goods_received_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_received_note_id')->constrained('goods_received_notes')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('expense_type', 100); // Freight, Carriage, Loading, Unloading, Other
            $table->string('comments', 255)->nullable();
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('rate', 12, 2)->default(0.00);
            $table->decimal('debit', 12, 2)->default(0.00);
            $table->decimal('credit', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // 3. Purchase Invoices (Screenshots 1, 2, 3: Vendor Commercial Bill & AP Booking)
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('goods_received_note_id')->nullable()->constrained('goods_received_notes')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->string('manual_invoice_number', 50)->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('invoice_type', 20)->default('credit'); // credit, cash
            $table->string('currency', 10)->default('PKR');
            $table->string('station', 150)->nullable();
            $table->string('supplier_invoice_no', 100)->nullable();
            $table->date('supplier_invoice_date')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('inventory_expenses_total', 14, 2)->default(0.00);
            $table->decimal('other_expenses_total', 14, 2)->default(0.00);
            $table->decimal('commission_total', 14, 2)->default(0.00);
            $table->decimal('grand_total', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->string('payment_status', 30)->default('unpaid'); // unpaid, partially_paid, paid
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'invoice_number']);
            $table->index(['shop_id', 'invoice_date']);
            $table->index(['shop_id', 'payment_status']);
        });

        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('goods_received_item_id')->nullable()->constrained('goods_received_items')->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('tax_percent', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        Schema::create('purchase_invoice_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('category', 50)->default('inventory'); // inventory, other
            $table->string('expense_type', 100);
            $table->string('comments', 255)->nullable();
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('rate', 14, 2)->default(0.00);
            $table->decimal('debit', 14, 2)->default(0.00);
            $table->decimal('credit', 14, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('purchase_invoice_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->string('agent_name', 150);
            $table->string('commission_type', 100)->nullable(); // Buying Agent, Broker, Local Agent
            $table->string('calculation_type', 20)->default('percentage'); // percentage, fixed
            $table->string('deduction_type', 20)->default('deductible'); // deductible, payable
            $table->decimal('invoice_net_total', 14, 2)->default(0.00);
            $table->decimal('rate', 8, 2)->default(0.00);
            $table->decimal('amount', 14, 2)->default(0.00);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();
        });

        // 4. Purchase Returns (Debit Notes)
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('goods_received_note_id')->nullable()->constrained('goods_received_notes')->nullOnDelete();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->string('return_number', 50)->unique();
            $table->date('return_date');
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->string('status', 30)->default('approved'); // draft, approved, completed
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'return_number']);
            $table->index(['shop_id', 'return_date']);
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->string('reason', 255)->nullable(); // expired, damaged, wrong_spec, excess
            $table->timestamps();
        });

        // Seed new permissions
        $now = now();
        $newPermissions = [
            ['name' => 'Purchase Requisitions', 'slug' => 'purchases.requisitions', 'module' => 'purchases', 'description' => 'Create and approve purchase requisitions', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Inward Gate Pass', 'slug' => 'purchases.igp', 'module' => 'purchases', 'description' => 'Log gate entry and inward passes for shipments', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Purchase Invoices', 'slug' => 'purchases.invoices', 'module' => 'purchases', 'description' => 'Audit and record commercial vendor purchase invoices', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($newPermissions as $perm) {
            DB::table('permissions')->insertOrIgnore($perm);
        }

        // Grant new permissions to super_admin and shop_admin roles
        $roles = DB::table('roles')->whereIn('slug', ['super_admin', 'shop_admin'])->get();
        $permIds = DB::table('permissions')->whereIn('slug', ['purchases.requisitions', 'purchases.igp', 'purchases.invoices'])->pluck('id');

        foreach ($roles as $role) {
            foreach ($permIds as $pid) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $role->id,
                    'permission_id' => $pid,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_invoice_commissions');
        Schema::dropIfExists('purchase_invoice_expenses');
        Schema::dropIfExists('purchase_invoice_items');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('goods_received_expenses');

        Schema::table('goods_received_items', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['rejected_quantity', 'warehouse_id']);
        });

        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropForeign(['inward_gate_pass_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['inward_gate_pass_id', 'manual_grn_number', 'received_via', 'warehouse_id', 'landed_expenses_total', 'billing_status']);
        });

        Schema::dropIfExists('inward_gate_pass_items');
        Schema::dropIfExists('inward_gate_passes');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['purchase_requisition_id']);
            $table->dropColumn(['purchase_requisition_id', 'payment_terms', 'delivery_station']);
        });

        Schema::dropIfExists('purchase_requisition_items');
        Schema::dropIfExists('purchase_requisitions');

        DB::table('permissions')->whereIn('slug', ['purchases.requisitions', 'purchases.igp', 'purchases.invoices'])->delete();
    }
};
