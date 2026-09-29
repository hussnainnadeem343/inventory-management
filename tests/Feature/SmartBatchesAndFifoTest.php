<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\ProductBatch;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartBatchesAndFifoTest extends TestCase
{
    use RefreshDatabase;

    public function test_restocking_product_creates_new_independent_batch(): void
    {
        $shop = Shop::create(['name' => 'Batch Test Store', 'code' => 'BTS-01', 'status' => 'active']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Brand A', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Category A', 'status' => 'active', 'created_by' => $admin->id]);

        // 1. Initial product with 10 units @ 100
        $product = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Velvet Lipstick',
            'sku' => 'LIP-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'initial_quantity' => 10,
            'sold_quantity' => 0,
            'purchase_price' => 100,
            'selling_price' => 200,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $batch1 = ProductBatch::create([
            'shop_id' => $shop->id,
            'inventory_item_id' => $product->id,
            'batch_no' => 'BATCH-001',
            'purchase_price' => 100,
            'selling_price' => 200,
            'initial_quantity' => 10,
            'quantity' => 10,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        // 2. Restock 20 units @ 150 via StockController@add
        $response = $this->actingAs($admin)->post("/stock/{$product->id}/add", [
            'quantity' => 20,
            'purchase_price' => 150,
            'notes' => 'New shipment batch',
        ]);
        $response->assertRedirect();

        // 3. Verify ProductBatch table has 2 distinct batches
        $this->assertDatabaseCount('product_batches', 2);

        $this->assertDatabaseHas('product_batches', [
            'inventory_item_id' => $product->id,
            'batch_no' => 'BATCH-002',
            'purchase_price' => 150,
            'quantity' => 20,
            'status' => 'active',
        ]);

        $product->refresh();
        $this->assertEquals(30, $product->quantity);
    }

    public function test_pos_sale_allocates_stock_using_auto_fifo_across_multiple_batches(): void
    {
        $shop = Shop::create(['name' => 'FIFO Test Store', 'code' => 'FIFO-01', 'status' => 'active']);
        $cashier = User::factory()->create(['shop_id' => $shop->id, 'role' => 'staff', 'status' => 'active']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Brand B', 'status' => 'active', 'created_by' => $cashier->id]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Category B', 'status' => 'active', 'created_by' => $cashier->id]);

        $product = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Nail Polish Red',
            'sku' => 'NP-RED-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 25,
            'initial_quantity' => 25,
            'sold_quantity' => 0,
            'purchase_price' => 100,
            'selling_price' => 200,
            'status' => 'active',
            'created_by' => $cashier->id,
        ]);

        // Old Batch 1: 10 units @ cost 100 (created 2 days ago)
        $batch1 = ProductBatch::create([
            'shop_id' => $shop->id,
            'inventory_item_id' => $product->id,
            'batch_no' => 'BATCH-001',
            'purchase_price' => 100,
            'selling_price' => 200,
            'initial_quantity' => 10,
            'quantity' => 10,
            'status' => 'active',
            'created_by' => $cashier->id,
            'created_at' => now()->subDays(2),
        ]);

        // Newer Batch 2: 15 units @ cost 120 (created today)
        $batch2 = ProductBatch::create([
            'shop_id' => $shop->id,
            'inventory_item_id' => $product->id,
            'batch_no' => 'BATCH-002',
            'purchase_price' => 120,
            'selling_price' => 200,
            'initial_quantity' => 15,
            'quantity' => 15,
            'status' => 'active',
            'created_by' => $cashier->id,
            'created_at' => now(),
        ]);

        // Customer buys 15 units.
        // FIFO must consume all 10 units from Batch 1, and 5 units from Batch 2.
        $payload = [
            'items' => [
                [
                    'inventory_item_id' => $product->id,
                    'quantity' => 15,
                    'unit_price' => 200,
                ],
            ],
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($cashier)->post('/sales', $payload);
        $response->assertRedirect();

        // 1. Verify Batch 1 is depleted
        $batch1->refresh();
        $this->assertEquals(0, $batch1->quantity);
        $this->assertEquals('depleted', $batch1->status);

        // 2. Verify Batch 2 has 10 units remaining (15 - 5)
        $batch2->refresh();
        $this->assertEquals(10, $batch2->quantity);
        $this->assertEquals('active', $batch2->status);

        // 3. Verify total product stock is 10 (25 - 15)
        $product->refresh();
        $this->assertEquals(10, $product->quantity);
        $this->assertEquals(15, $product->sold_quantity);

        // 4. Verify COGS: (10 * 100) + (5 * 120) = 1000 + 600 = 1600. Effective cost per unit = 1600 / 15 = 106.67
        $this->assertDatabaseHas('sale_items', [
            'inventory_item_id' => $product->id,
            'quantity' => 15,
            'unit_cost' => 106.67,
        ]);
    }
}
