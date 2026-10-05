<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseModuleTest extends TestCase
{
    use RefreshDatabase;

    private function shopAdmin(): User
    {
        return User::factory()->create([
            'role' => 'shop_admin',
            'status' => 'active',
        ]);
    }

    public function test_can_create_and_list_suppliers(): void
    {
        $admin = $this->shopAdmin();

        $response = $this->actingAs($admin)->post('/purchases/suppliers', [
            'name' => 'Metro Distributors',
            'company_name' => 'Metro Group',
            'phone' => '0300-1122334',
            'email' => 'metro@example.com',
            'address' => 'Industrial Area, Karachi',
            'opening_balance' => 5000,
            'status' => 'active',
        ]);

        $response->assertRedirect('/purchases/suppliers');
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Metro Distributors',
            'opening_balance' => 5000,
            'current_balance' => 5000,
        ]);

        $this->actingAs($admin)->get('/purchases/suppliers')
            ->assertOk()
            ->assertSee('Metro Distributors');
    }

    public function test_create_screens_render_successfully(): void
    {
        $admin = $this->shopAdmin();

        $this->actingAs($admin)->get('/purchases/suppliers/create')
            ->assertOk()
            ->assertSee('Add New Supplier');

        $this->actingAs($admin)->get('/purchases/orders/create')
            ->assertOk()
            ->assertSee('Create Purchase Order');

        $this->actingAs($admin)->get('/purchases/grn/create')
            ->assertOk()
            ->assertSee('Goods Received Note');
    }

    public function test_can_create_purchase_order(): void
    {
        $admin = $this->shopAdmin();
        $brand = Brand::create(['name' => 'Brand A', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['name' => 'Category A', 'status' => 'active', 'created_by' => $admin->id]);
        $supplier = Supplier::create(['name' => 'Supplier X', 'status' => 'active', 'created_by' => $admin->id]);

        $item1 = InventoryItem::create([
            'item_name' => 'Test Laptop',
            'sku' => 'LAP-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 0,
            'unit' => 'PCS',
            'purchase_price' => 50000,
            'selling_price' => 60000,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post('/purchases/orders', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->addDays(5)->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $item1->id,
                    'quantity' => 10,
                    'unit_cost' => 50000,
                ],
            ],
            'discount_amount' => 1000,
            'tax_amount' => 500,
            'notes' => 'Urgent procurement',
        ]);

        $po = PurchaseOrder::first();
        $this->assertNotNull($po);
        $response->assertRedirect(route('purchases.orders.show', $po));

        $this->assertSame(500000.0, (float) $po->subtotal);
        $this->assertSame(499500.0, (float) $po->grand_total);
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $item1->id,
            'quantity' => 10,
        ]);
    }

    public function test_goods_received_note_increments_stock_and_updates_supplier_balance(): void
    {
        $admin = $this->shopAdmin();
        $brand = Brand::create(['name' => 'Brand B', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['name' => 'Category B', 'status' => 'active', 'created_by' => $admin->id]);
        $supplier = Supplier::create([
            'name' => 'Wholesale Depot',
            'current_balance' => 0,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $product = InventoryItem::create([
            'item_name' => 'Wireless Mouse',
            'sku' => 'MOU-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 5,
            'sold_quantity' => 0,
            'unit' => 'PCS',
            'purchase_price' => 1000,
            'selling_price' => 1500,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        // Create PO
        $po = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-TEST-001',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrder::STATUS_APPROVED,
            'subtotal' => 20000,
            'grand_total' => 20000,
            'created_by' => $admin->id,
        ]);

        $po->items()->create([
            'inventory_item_id' => $product->id,
            'quantity' => 20,
            'received_quantity' => 0,
            'unit_cost' => 1000,
            'subtotal' => 20000,
        ]);

        // Receive 20 units via GRN
        $response = $this->actingAs($admin)->post('/purchases/grn', [
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'received_date' => now()->format('Y-m-d'),
            'supplier_invoice_no' => 'INV-SUP-8899',
            'items' => [
                [
                    'inventory_item_id' => $product->id,
                    'batch_number' => 'BATCH-WM-01',
                    'expiry_date' => now()->addYear()->format('Y-m-d'),
                    'quantity' => 20,
                    'unit_cost' => 1000,
                ],
            ],
        ]);

        $grn = GoodsReceivedNote::first();
        $this->assertNotNull($grn);
        $response->assertRedirect(route('purchases.grn.show', $grn));

        // 1. Check stock increment (5 existing + 20 received = 25)
        $this->assertSame(25.0, (float) $product->fresh()->remaining_quantity);

        // 2. Check batch creation
        $batch = ProductBatch::where('batch_no', 'BATCH-WM-01')->first();
        $this->assertNotNull($batch);
        $this->assertSame(20.0, (float) $batch->quantity);

        // 3. Check inventory transaction record
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $product->id,
            'product_batch_id' => $batch->id,
            'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
            'quantity' => 20,
            'balance_after' => 25,
        ]);

        // 4. Check supplier payable balance incremented by 20,000
        $this->assertSame(20000.0, (float) $supplier->fresh()->current_balance);

        // 5. Check PO status updated to received
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $po->fresh()->status);
    }
}
