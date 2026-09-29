<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiItemSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_perform_multi_item_sale_with_discount_and_cash_tendered(): void
    {
        $shop = Shop::create(['name' => 'Al-Madina Store', 'code' => 'AMD-01', 'status' => 'active']);
        $cashier = User::factory()->create(['shop_id' => $shop->id, 'role' => 'staff', 'status' => 'active']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Brand A', 'status' => 'active', 'created_by' => $cashier->id]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Category A', 'status' => 'active', 'created_by' => $cashier->id]);

        $item1 = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Matte Lipstick Red',
            'sku' => 'LIP-RED-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'initial_quantity' => 10,
            'sold_quantity' => 0,
            'purchase_price' => 200,
            'selling_price' => 350,
            'status' => 'active',
            'created_by' => $cashier->id,
        ]);

        $item2 = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Eyeliner Waterproof',
            'sku' => 'EYE-WP-02',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 6,
            'initial_quantity' => 6,
            'sold_quantity' => 0,
            'purchase_price' => 150,
            'selling_price' => 250,
            'status' => 'active',
            'created_by' => $cashier->id,
        ]);

        // Customer buys 2 Lipsticks @ 350 (700) + 1 Eyeliner @ 250 (250). Subtotal = 950. Discount = 50. Total = 900. Paid = 1000.
        $payload = [
            'items' => [
                [
                    'inventory_item_id' => $item1->id,
                    'quantity' => 2,
                    'unit_price' => 350,
                ],
                [
                    'inventory_item_id' => $item2->id,
                    'quantity' => 1,
                    'unit_price' => 250,
                ],
            ],
            'discount' => 50,
            'payment_method' => 'cash',
            'paid_amount' => 1000,
            'customer_name' => 'Ahmed Khan',
            'customer_phone' => '03129876543',
            'notes' => 'Counter sale bill',
        ];

        $response = $this->actingAs($cashier)->post('/sales', $payload);
        $response->assertRedirect();

        // 1. Verify Sale record
        $this->assertDatabaseHas('sales', [
            'shop_id' => $shop->id,
            'customer_name' => 'Ahmed Khan',
            'customer_phone' => '03129876543',
            'total_items' => 2,
            'subtotal' => 950,
            'discount' => 50,
            'total_amount' => 900,
            'paid_amount' => 1000,
            'change_amount' => 100,
            'payment_method' => 'cash',
        ]);

        $sale = Sale::first();
        $this->assertEquals('INV-1001', $sale->invoice_no);

        // 2. Verify SaleItem records
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'inventory_item_id' => $item1->id,
            'quantity' => 2,
            'unit_price' => 350,
            'line_total' => 700,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'inventory_item_id' => $item2->id,
            'quantity' => 1,
            'unit_price' => 250,
            'line_total' => 250,
        ]);

        // 3. Verify stock quantities decremented
        $item1->refresh();
        $item2->refresh();
        $this->assertEquals(8, $item1->quantity);
        $this->assertEquals(2, $item1->sold_quantity);
        $this->assertEquals(5, $item2->quantity);
        $this->assertEquals(1, $item2->sold_quantity);

        // 4. Verify audit transactions created with sale_id
        $this->assertDatabaseHas('inventory_transactions', [
            'sale_id' => $sale->id,
            'inventory_item_id' => $item1->id,
            'transaction_type' => InventoryTransaction::TYPE_SALE,
            'quantity' => 2,
            'balance_before' => 10,
            'balance_after' => 8,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'sale_id' => $sale->id,
            'inventory_item_id' => $item2->id,
            'transaction_type' => InventoryTransaction::TYPE_SALE,
            'quantity' => 1,
            'balance_before' => 6,
            'balance_after' => 5,
        ]);

        // 5. Verify invoice view and thermal receipt view render
        $this->actingAs($cashier)->get("/sales/{$sale->id}")
            ->assertOk()
            ->assertSee($sale->invoice_no)
            ->assertSee('Matte Lipstick Red')
            ->assertSee('Eyeliner Waterproof')
            ->assertSee('900.00');

        $this->actingAs($cashier)->get("/sales/{$sale->id}/receipt")
            ->assertOk()
            ->assertSee($sale->invoice_no)
            ->assertSee('NET TOTAL');
    }

    public function test_multi_item_sale_fails_atomically_if_any_product_has_insufficient_stock(): void
    {
        $shop = Shop::create(['name' => 'Stock Test Shop', 'code' => 'STK-01', 'status' => 'active']);
        $cashier = User::factory()->create(['shop_id' => $shop->id, 'role' => 'staff', 'status' => 'active']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Brand B', 'status' => 'active', 'created_by' => $cashier->id]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Category B', 'status' => 'active', 'created_by' => $cashier->id]);

        $itemA = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Item Plenty',
            'sku' => 'ITM-A',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 20,
            'initial_quantity' => 20,
            'sold_quantity' => 0,
            'purchase_price' => 50,
            'selling_price' => 100,
            'status' => 'active',
            'created_by' => $cashier->id,
        ]);

        $itemB = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Item Scarce',
            'sku' => 'ITM-B',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 1, // Only 1 in stock!
            'initial_quantity' => 1,
            'sold_quantity' => 0,
            'purchase_price' => 50,
            'selling_price' => 100,
            'status' => 'active',
            'created_by' => $cashier->id,
        ]);

        // Cashier tries to sell 2 of Item A and 5 of Item B
        $payload = [
            'items' => [
                [
                    'inventory_item_id' => $itemA->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                ],
                [
                    'inventory_item_id' => $itemB->id,
                    'quantity' => 5, // Exceeds available 1!
                    'unit_price' => 100,
                ],
            ],
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($cashier)->post('/sales', $payload);
        $response->assertSessionHasErrors('items');

        // Verify zero deductions occurred (atomic transaction rollback)
        $itemA->refresh();
        $itemB->refresh();
        $this->assertEquals(20, $itemA->quantity);
        $this->assertEquals(1, $itemB->quantity);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_sales_are_strictly_isolated_by_shop(): void
    {
        $shop1 = Shop::create(['name' => 'Branch Lahore', 'code' => 'LHR-01', 'status' => 'active']);
        $shop2 = Shop::create(['name' => 'Branch Karachi', 'code' => 'KHI-01', 'status' => 'active']);

        $staff1 = User::factory()->create(['shop_id' => $shop1->id, 'role' => 'staff', 'status' => 'active']);
        $staff2 = User::factory()->create(['shop_id' => $shop2->id, 'role' => 'staff', 'status' => 'active']);

        $sale1 = Sale::create([
            'shop_id' => $shop1->id,
            'invoice_no' => 'INV-LHR01-20260925-0001',
            'total_items' => 1,
            'subtotal' => 500,
            'total_amount' => 500,
            'payment_method' => 'cash',
            'paid_amount' => 500,
            'created_by' => $staff1->id,
        ]);

        $sale2 = Sale::create([
            'shop_id' => $shop2->id,
            'invoice_no' => 'INV-KHI01-20260925-0001',
            'total_items' => 1,
            'subtotal' => 800,
            'total_amount' => 800,
            'payment_method' => 'cash',
            'paid_amount' => 800,
            'created_by' => $staff2->id,
        ]);

        // Staff 1 can view Sale 1
        $this->actingAs($staff1)->get("/sales/{$sale1->id}")->assertOk();

        // Staff 1 CANNOT view Sale 2 (forbidden 403)
        $this->actingAs($staff1)->get("/sales/{$sale2->id}")->assertForbidden();

        // Staff 1 index only shows Sale 1
        $this->actingAs($staff1)->get('/sales')
            ->assertOk()
            ->assertSee('INV-LHR01-20260925-0001')
            ->assertDontSee('INV-KHI01-20260925-0001');

        // Staff 2 index only shows Sale 2
        $this->actingAs($staff2)->get('/sales')
            ->assertOk()
            ->assertSee('INV-KHI01-20260925-0001')
            ->assertDontSee('INV-LHR01-20260925-0001');
    }
}
