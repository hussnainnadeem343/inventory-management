<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualDiscountAndReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_with_item_discount_and_bill_discount_is_accurately_recorded(): void
    {
        $shop = Shop::create(['name' => 'Discount Store', 'code' => 'DISC-01', 'status' => 'active']);
        $cashier = User::factory()->create(['shop_id' => $shop->id, 'role' => 'staff', 'status' => 'active']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Brand D', 'status' => 'active', 'created_by' => $cashier->id]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Category D', 'status' => 'active', 'created_by' => $cashier->id]);

        $item1 = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Item Alpha',
            'sku' => 'ITM-ALP',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'initial_quantity' => 10,
            'sold_quantity' => 0,
            'purchase_price' => 50,
            'selling_price' => 100,
            'status' => 'active',
            'created_by' => $cashier->id,
        ]);

        $item2 = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Item Beta',
            'sku' => 'ITM-BET',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'initial_quantity' => 10,
            'sold_quantity' => 0,
            'purchase_price' => 80,
            'selling_price' => 150,
            'status' => 'active',
            'created_by' => $cashier->id,
        ]);

        // Customer buys:
        // Item 1: 2 units @ 100 = 200, item discount = 30 -> line_total = 170
        // Item 2: 1 unit @ 150 = 150, item discount = 0 -> line_total = 150
        // Gross Subtotal: 350
        // Items Discount Total: 30
        // Bill Discount: 20
        // Total Amount: 350 - 30 - 20 = 300
        $payload = [
            'items' => [
                [
                    'inventory_item_id' => $item1->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount' => 30,
                ],
                [
                    'inventory_item_id' => $item2->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                    'discount' => 0,
                ],
            ],
            'discount' => 20, // bill-level discount
            'payment_method' => 'cash',
            'paid_amount' => 500,
        ];

        $response = $this->actingAs($cashier)->post('/sales', $payload);
        $response->assertRedirect();

        $sale = Sale::first();
        $this->assertEquals(350, $sale->subtotal);
        $this->assertEquals(30, $sale->items_discount_total);
        $this->assertEquals(20, $sale->discount);
        $this->assertEquals(300, $sale->total_amount);
        $this->assertEquals(200, $sale->change_amount);

        // Verify SaleItem has item-level discount recorded
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'inventory_item_id' => $item1->id,
            'quantity' => 2,
            'discount' => 30,
            'line_total' => 170,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'inventory_item_id' => $item2->id,
            'quantity' => 1,
            'discount' => 0,
            'line_total' => 150,
        ]);
    }

    public function test_sales_report_factors_in_all_discounts_into_net_revenue_and_gross_profit(): void
    {
        $shop = Shop::create(['name' => 'Report Test Shop', 'code' => 'REP-01', 'status' => 'active']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Brand R', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Category R', 'status' => 'active', 'created_by' => $admin->id]);

        $product = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Luxury Perfume',
            'sku' => 'PRF-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 20,
            'initial_quantity' => 20,
            'sold_quantity' => 0,
            'purchase_price' => 100,
            'selling_price' => 200,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        ProductBatch::create([
            'shop_id' => $shop->id,
            'inventory_item_id' => $product->id,
            'batch_no' => 'BATCH-001',
            'purchase_price' => 100,
            'selling_price' => 200,
            'initial_quantity' => 20,
            'quantity' => 20,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        // Sale: 2 units @ 200 = 400 gross. Item discount = 40. Bill discount = 10.
        // Gross = 400, Total Discount = 50. Net Revenue = 350.
        // Cost (COGS) = 2 * 100 = 200.
        // Gross Profit = 350 - 200 = 150!
        $this->actingAs($admin)->post('/sales', [
            'items' => [
                [
                    'inventory_item_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 200,
                    'discount' => 40,
                ],
            ],
            'discount' => 10,
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($admin)->get('/reports/sales');
        $response->assertOk();

        // 1. Total Discounts card must show 50.00
        $response->assertSee('Total Discounts Given');
        $response->assertSee('50.00');

        // 2. Net Sales Revenue must be 350.00
        $response->assertSee('350.00');

        // 3. Gross Profit must be 150.00
        $response->assertSee('150.00');
    }
}
