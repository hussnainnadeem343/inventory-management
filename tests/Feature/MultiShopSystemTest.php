<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiShopSystemTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
            'shop_id' => null,
        ]);
    }

    public function test_super_admin_can_create_shop_and_initial_shop_admin(): void
    {
        $admin = $this->superAdmin();

        $payload = [
            'name' => 'Glamour Cosmetics',
            'code' => 'GLAM-01',
            'business_type' => 'Cosmetics',
            'phone' => '03001234567',
            'address' => 'Mall Road, Lahore',
            'status' => 'active',
            'admin_name' => 'Sara Khan',
            'admin_username' => 'sara_glam',
            'admin_email' => 'sara@glamour.test',
            'admin_password' => 'secret1234',
        ];

        $response = $this->actingAs($admin)->post('/shops', $payload);
        $response->assertRedirect('/shops');

        $this->assertDatabaseHas('shops', ['code' => 'GLAM-01', 'name' => 'Glamour Cosmetics']);
        $this->assertDatabaseHas('users', [
            'username' => 'sara_glam',
            'role' => 'shop_admin',
        ]);
    }

    public function test_shop_admin_can_create_staff_for_their_own_shop(): void
    {
        $shop = Shop::create(['name' => 'Apex Shoes', 'code' => 'APEX-01', 'status' => 'active']);
        $shopAdmin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);

        $response = $this->actingAs($shopAdmin)->post('/users', [
            'name' => 'Cashier Bilal',
            'username' => 'bilal_cashier',
            'email' => 'bilal@apex.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'role' => 'staff',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'bilal_cashier',
            'shop_id' => $shop->id,
            'role' => 'staff',
        ]);
    }

    public function test_shop_admin_can_create_another_shop_admin_for_their_own_shop(): void
    {
        $shop = Shop::create(['name' => 'Metro Superstore', 'code' => 'MTR-01', 'status' => 'active']);
        $shopAdmin1 = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);

        $response = $this->actingAs($shopAdmin1)->post('/users', [
            'name' => 'Manager Tariq',
            'username' => 'tariq_admin2',
            'email' => 'tariq@metro.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'role' => 'shop_admin',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'tariq_admin2',
            'shop_id' => $shop->id,
            'role' => 'shop_admin',
        ]);
    }

    public function test_shop_admin_cannot_create_super_admin(): void
    {
        $shop = Shop::create(['name' => 'Metro Superstore', 'code' => 'MTR-02', 'status' => 'active']);
        $shopAdmin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);

        $response = $this->actingAs($shopAdmin)->post('/users', [
            'name' => 'Fake Super Admin',
            'username' => 'fake_super',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'role' => 'super_admin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['username' => 'fake_super']);
    }

    public function test_same_sku_allowed_in_different_shops_without_collision(): void
    {
        $shopA = Shop::create(['name' => 'Shop A', 'code' => 'SHP-A', 'status' => 'active']);
        $shopB = Shop::create(['name' => 'Shop B', 'code' => 'SHP-B', 'status' => 'active']);

        $adminA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'shop_admin', 'status' => 'active']);
        $adminB = User::factory()->create(['shop_id' => $shopB->id, 'role' => 'shop_admin', 'status' => 'active']);

        $brandA = Brand::create(['shop_id' => $shopA->id, 'name' => 'Brand A', 'status' => 'active', 'created_by' => $adminA->id]);
        $catA = Category::create(['shop_id' => $shopA->id, 'name' => 'Cat A', 'status' => 'active', 'created_by' => $adminA->id]);

        $brandB = Brand::create(['shop_id' => $shopB->id, 'name' => 'Brand B', 'status' => 'active', 'created_by' => $adminB->id]);
        $catB = Category::create(['shop_id' => $shopB->id, 'name' => 'Cat B', 'status' => 'active', 'created_by' => $adminB->id]);

        // Shop A adds SKU-001
        $this->actingAs($adminA)->post('/products', [
            'item_name' => 'Product 1',
            'sku' => 'SKU-001',
            'brand_id' => $brandA->id,
            'category_id' => $catA->id,
            'selling_price' => 1000,
            'purchase_price' => 700,
            'status' => 'active',
        ])->assertRedirect('/products');

        // Shop B adds SAME SKU-001 with different price
        $this->actingAs($adminB)->post('/products', [
            'item_name' => 'Different Product',
            'sku' => 'SKU-001',
            'brand_id' => $brandB->id,
            'category_id' => $catB->id,
            'selling_price' => 1200,
            'purchase_price' => 850,
            'status' => 'active',
        ])->assertRedirect('/products');

        $this->assertDatabaseHas('inventory_items', ['shop_id' => $shopA->id, 'sku' => 'SKU-001', 'selling_price' => 1000]);
        $this->assertDatabaseHas('inventory_items', ['shop_id' => $shopB->id, 'sku' => 'SKU-001', 'selling_price' => 1200]);
    }

    public function test_stock_actions_with_wac_returns_damage_and_history(): void
    {
        $shop = Shop::create(['name' => 'Shop Test', 'code' => 'TEST-01', 'status' => 'active']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Brand', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Category', 'status' => 'active', 'created_by' => $admin->id]);

        // 1. Initial product with 10 units @ cost Rs. 100
        $product = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Lipstick Matte',
            'sku' => 'LIP-10',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'initial_quantity' => 10,
            'sold_quantity' => 0,
            'purchase_price' => 100,
            'selling_price' => 150,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        // 2. Sell 4 units
        $this->actingAs($admin)->post("/stock/{$product->id}/sell", [
            'sell_quantity' => 4,
            'selling_price' => 150,
        ])->assertRedirect();

        $product->refresh();
        $this->assertEquals(6, $product->quantity);
        $this->assertEquals(4, $product->sold_quantity);

        // 3. Restock 10 units with higher purchase price @ Rs. 120 -> WAC should be ((6*100) + (10*120)) / 16 = 112.50
        $this->actingAs($admin)->post("/stock/{$product->id}/add", [
            'quantity' => 10,
            'purchase_price' => 120,
        ])->assertRedirect();

        $product->refresh();
        $this->assertEquals(16, $product->quantity);
        $this->assertEquals(112.50, (float) $product->purchase_price);

        // 4. Customer return: 1 unit returned with Rs. 150 refund
        $this->actingAs($admin)->post("/stock/{$product->id}/return", [
            'return_quantity' => 1,
            'refund_amount' => 150,
            'reason' => 'Wrong shade',
        ])->assertRedirect();

        $product->refresh();
        $this->assertEquals(17, $product->quantity);
        $this->assertEquals(3, $product->sold_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $product->id,
            'transaction_type' => 'CUSTOMER_RETURN',
            'quantity' => 1,
        ]);

        // 5. Damage write-off: 2 units broken
        $this->actingAs($admin)->post("/stock/{$product->id}/damage", [
            'damage_quantity' => 2,
            'damage_type' => 'damaged_broken',
            'notes' => 'Dropped on floor',
        ])->assertRedirect();

        $product->refresh();
        $this->assertEquals(15, $product->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $product->id,
            'transaction_type' => 'DAMAGE_LOSS',
            'quantity' => 2,
        ]);

        // 6. View history ledger
        $this->actingAs($admin)->get("/stock/{$product->id}/history")
            ->assertOk()
            ->assertSee('SALE')
            ->assertSee('STOCK IN')
            ->assertSee('CUSTOMER RETURN')
            ->assertSee('DAMAGE LOSS');
    }

    public function test_sales_and_profit_reporting(): void
    {
        $shop = Shop::create(['name' => 'Report Shop', 'code' => 'REP-01', 'status' => 'active']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);
        $brand = Brand::create(['shop_id' => $shop->id, 'name' => 'Brand', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Category', 'status' => 'active', 'created_by' => $admin->id]);

        $item = InventoryItem::create([
            'shop_id' => $shop->id,
            'item_name' => 'Watch Model A',
            'sku' => 'W-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 5,
            'initial_quantity' => 5,
            'sold_quantity' => 0,
            'purchase_price' => 2000,
            'selling_price' => 3000,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        // Sell 2 units (Revenue: 6000, Cost: 4000, Profit: 2000)
        $this->actingAs($admin)->post("/stock/{$item->id}/sell", [
            'sell_quantity' => 2,
            'selling_price' => 3000,
        ]);

        $response = $this->actingAs($admin)->get('/reports/sales');
        $response->assertOk()
            ->assertSee('6,000.00')
            ->assertSee('4,000.00')
            ->assertSee('2,000.00');
    }

    public function test_dashboard_is_strictly_scoped_by_shop_and_allows_super_admin_switching(): void
    {
        $shopA = Shop::create(['name' => 'Shop Alpha', 'code' => 'ALP-01', 'status' => 'active']);
        $shopB = Shop::create(['name' => 'Shop Beta', 'code' => 'BET-02', 'status' => 'active']);

        $adminA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'shop_admin', 'status' => 'active']);
        $adminB = User::factory()->create(['shop_id' => $shopB->id, 'role' => 'shop_admin', 'status' => 'active']);
        $superAdmin = $this->superAdmin();

        $brandA = Brand::create(['shop_id' => $shopA->id, 'name' => 'Brand Alpha', 'status' => 'active', 'created_by' => $adminA->id]);
        $catA = Category::create(['shop_id' => $shopA->id, 'name' => 'Cat Alpha', 'status' => 'active', 'created_by' => $adminA->id]);

        InventoryItem::create([
            'shop_id' => $shopA->id,
            'item_name' => 'Alpha Specific Gadget',
            'sku' => 'ALP-SKU-99',
            'brand_id' => $brandA->id,
            'category_id' => $catA->id,
            'quantity' => 2,
            'initial_quantity' => 2,
            'sold_quantity' => 0,
            'alert_quantity' => 5,
            'purchase_price' => 500,
            'selling_price' => 900,
            'status' => 'active',
            'created_by' => $adminA->id,
        ]);

        // 1. Shop A Admin visits dashboard -> Sees Shop Alpha, item count 1, and 'Alpha Specific Gadget'
        $respA = $this->actingAs($adminA)->get('/dashboard');
        $respA->assertOk()
            ->assertSee('Shop Alpha')
            ->assertSee('ALP-01')
            ->assertSee('Alpha Specific Gadget')
            ->assertDontSee('Shop Beta');

        // 2. Shop B Admin visits dashboard -> Sees Shop Beta, 0 items, and NOT 'Alpha Specific Gadget'
        $respB = $this->actingAs($adminB)->get('/dashboard');
        $respB->assertOk()
            ->assertSee('Shop Beta')
            ->assertSee('BET-02')
            ->assertDontSee('Alpha Specific Gadget');

        // 3. Super Admin switches to Shop B -> Sees Shop Beta and NOT 'Alpha Specific Gadget'
        $respSuperB = $this->actingAs($superAdmin)->get("/dashboard?shop_id={$shopB->id}");
        $respSuperB->assertOk()
            ->assertSee('Shop Beta')
            ->assertDontSee('Alpha Specific Gadget');

        // 4. Super Admin switches to Shop A -> Sees Shop Alpha and 'Alpha Specific Gadget'
        $respSuperA = $this->actingAs($superAdmin)->get("/dashboard?shop_id={$shopA->id}");
        $respSuperA->assertOk()
            ->assertSee('Shop Alpha')
            ->assertSee('Alpha Specific Gadget');
    }
}
