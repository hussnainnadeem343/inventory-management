<?php

namespace Tests\Feature;

use App\Exports\InventoryExport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_registration_route_does_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['password' => 'password', 'status' => 'inactive']);
        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_active_user_logs_in_with_username_not_email(): void
    {
        $user = User::factory()->create(['password' => 'password', 'status' => 'active']);

        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_admin_can_create_user_with_unique_username_and_no_email(): void
    {
        $admin = $this->admin();
        $payload = ['name' => 'No Email User', 'username' => 'noemail', 'email' => '', 'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'user', 'status' => 'active'];

        $this->actingAs($admin)->post('/users', $payload)->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['username' => 'noemail', 'email' => null]);
        $this->actingAs($admin)->post('/users', [...$payload, 'name' => 'Duplicate'])->assertSessionHasErrors('username');
    }

    public function test_normal_user_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    public function test_user_can_create_inventory_and_creator_is_server_controlled(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'Samsung', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['name' => 'Electronics', 'status' => 'active', 'created_by' => $admin->id]);
        $this->actingAs($user)->post('/inventory', ['item_name' => 'TV', 'sku' => 'TV-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 10, 'unit' => 'PCS', 'supplier' => 'Supplier', 'status' => 'active', 'created_by' => $admin->id])->assertRedirect('/inventory');
        $this->assertDatabaseHas('inventory_items', ['sku' => 'TV-1', 'created_by' => $user->id, 'purchase_price' => null, 'selling_price' => null]);
    }

    public function test_optional_pack_size_and_measure_unit_are_saved_and_displayed_together(): void
    {
        $user = $this->admin();
        $brand = Brand::create(['name' => 'Drink Brand', 'status' => 'active', 'created_by' => $user->id]);
        $category = Category::create(['name' => 'Drinks', 'status' => 'active', 'created_by' => $user->id]);
        $payload = ['item_name' => 'Juice', 'sku' => 'JUICE-400', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 10, 'pack_size' => 400, 'unit' => 'ml', 'purchase_price' => null, 'selling_price' => null, 'status' => 'active'];

        $this->actingAs($user)->post('/inventory', $payload)->assertRedirect('/inventory');
        $item = InventoryItem::where('sku', 'JUICE-400')->firstOrFail();
        $this->assertSame('400ml', $item->pack_label);
        $this->actingAs($user)->get('/inventory')->assertOk()->assertSee('400ml');
        $this->assertContains('400ml', (new InventoryExport([], true))->map($item));

        $this->actingAs($user)->put('/inventory/'.$item->id, [...$payload, 'pack_size' => null, 'unit' => null])->assertRedirect('/inventory');
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'pack_size' => null, 'unit' => null]);
    }

    public function test_normal_user_cannot_submit_prices(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $brand = Brand::create(['name' => 'Samsung', 'status' => 'active', 'created_by' => $user->id]);
        $category = Category::create(['name' => 'Electronics', 'status' => 'active', 'created_by' => $user->id]);

        $this->actingAs($user)->post('/inventory', ['item_name' => 'TV', 'sku' => 'TV-2', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 10, 'unit' => 'PCS', 'purchase_price' => 1, 'selling_price' => 2, 'status' => 'active'])->assertSessionHasErrors(['purchase_price', 'selling_price']);
    }

    public function test_sale_increments_sold_quantity_and_creates_history(): void
    {
        $user = $this->admin();
        $brand = Brand::create(['name' => 'Coca Cola', 'status' => 'active', 'created_by' => $user->id]);
        $category = Category::create(['name' => 'Beverages', 'status' => 'active', 'created_by' => $user->id]);
        $item = InventoryItem::create(['item_name' => 'Coke', 'sku' => 'COKE-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 50, 'unit' => 'PCS', 'status' => 'active', 'created_by' => $user->id]);

        $this->actingAs($user)->post("/inventory/{$item->id}/sell", ['sell_quantity' => 30])->assertSessionHas('success');
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'quantity' => 50, 'sold_quantity' => 30]);
        $this->assertDatabaseHas('inventory_transactions', ['inventory_item_id' => $item->id, 'transaction_type' => 'SALE', 'quantity' => 30, 'created_by' => $user->id]);
    }

    public function test_add_stock_increases_initial_quantity_and_creates_history(): void
    {
        $user = $this->admin();
        $brand = Brand::create(['name' => 'Stock Brand', 'status' => 'active', 'created_by' => $user->id]);
        $category = Category::create(['name' => 'Stock Category', 'status' => 'active', 'created_by' => $user->id]);
        $item = InventoryItem::create(['item_name' => 'Stock Item', 'sku' => 'STOCK-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 50, 'unit' => 'pcs', 'status' => 'active', 'created_by' => $user->id]);
        $item->forceFill(['sold_quantity' => 30])->save();

        $this->actingAs($user)->post("/inventory/{$item->id}/add-stock", ['sell_quantity' => 20])->assertSessionHas('success');
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'quantity' => 70, 'sold_quantity' => 30]);
        $this->assertDatabaseHas('inventory_transactions', ['inventory_item_id' => $item->id, 'transaction_type' => 'STOCK_IN', 'quantity' => 20, 'created_by' => $user->id]);
        $this->assertSame(40.0, $item->fresh()->remaining_quantity);
    }

    public function test_sale_cannot_exceed_remaining_stock(): void
    {
        $user = $this->admin();
        $brand = Brand::create(['name' => 'Brand', 'status' => 'active', 'created_by' => $user->id]);
        $category = Category::create(['name' => 'Category', 'status' => 'active', 'created_by' => $user->id]);
        $item = InventoryItem::create(['item_name' => 'Item', 'sku' => 'ITEM-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 10, 'unit' => 'PCS', 'status' => 'active', 'created_by' => $user->id]);
        $item->forceFill(['sold_quantity' => 8])->save();

        $this->actingAs($user)->post("/inventory/{$item->id}/sell", ['sell_quantity' => 3])->assertSessionHasErrors('sell_quantity');
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'sold_quantity' => 8]);
    }

    public function test_normal_user_can_create_and_edit_brands_and_categories(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($user)->post('/brands', ['name' => 'New Brand', 'status' => 'active'])->assertRedirect('/brands');
        $this->actingAs($user)->post('/categories', ['name' => 'New Category', 'status' => 'active'])->assertRedirect('/categories');
        $this->assertDatabaseHas('brands', ['name' => 'New Brand', 'created_by' => $user->id]);
        $this->assertDatabaseHas('categories', ['name' => 'New Category', 'created_by' => $user->id]);
    }

    public function test_excel_export_downloads_all_filtered_rows(): void
    {
        $user = $this->admin();
        $brand = Brand::create(['name' => 'Export Brand', 'status' => 'active', 'created_by' => $user->id]);
        $category = Category::create(['name' => 'Export Category', 'status' => 'active', 'created_by' => $user->id]);
        InventoryItem::create(['item_name' => 'Export Item', 'sku' => 'EXPORT-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 10, 'unit' => 'PCS', 'status' => 'active', 'created_by' => $user->id]);

        $this->actingAs($user)->get('/inventory/export?search=Export&brand_id='.$brand->id.'&category_id='.$category->id.'&page=99')->assertOk()->assertHeader('content-disposition');
    }

    public function test_inventory_date_filter_combines_with_existing_filters_and_export(): void
    {
        $user = $this->admin();
        $brand = Brand::create(['name' => 'Coca Cola', 'status' => 'active', 'created_by' => $user->id]);
        $category = Category::create(['name' => 'Beverages', 'status' => 'active', 'created_by' => $user->id]);
        $matching = InventoryItem::create(['item_name' => 'Coke Match', 'sku' => 'DATE-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 10, 'unit' => 'PCS', 'status' => 'active', 'created_by' => $user->id]);
        $otherDay = InventoryItem::create(['item_name' => 'Coke Other Day', 'sku' => 'DATE-2', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 10, 'unit' => 'PCS', 'status' => 'active', 'created_by' => $user->id]);
        $matching->forceFill(['created_at' => '2026-08-24 23:59:00'])->save();
        $otherDay->forceFill(['created_at' => '2026-08-23 23:59:00'])->save();
        $filters = ['search' => 'Coke', 'brand_id' => $brand->id, 'category_id' => $category->id, 'date' => '2026-08-24'];

        $this->actingAs($user)->get('/inventory?'.http_build_query($filters))->assertOk()->assertSee('Coke Match')->assertDontSee('Coke Other Day')->assertSee('value="2026-08-24"', false);
        $this->assertSame(1, (new InventoryExport($filters, true))->query()->count());
    }

    public function test_referenced_brand_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'Samsung', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['name' => 'Electronics', 'status' => 'active', 'created_by' => $admin->id]);
        InventoryItem::create(['item_name' => 'TV', 'sku' => 'TV-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 1, 'unit' => 'PCS', 'purchase_price' => 1, 'selling_price' => 2, 'status' => 'active', 'created_by' => $admin->id]);
        $this->actingAs($admin)->delete("/brands/$brand->id")->assertSessionHas('error');
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->delete("/users/$admin->id")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_product_creation_starts_with_zero_stock_and_form_has_no_stock_fields(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'Product Brand', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['name' => 'Product Category', 'status' => 'active', 'created_by' => $admin->id]);

        $this->actingAs($admin)->get('/products/create')->assertOk()->assertSee('Save Product')->assertDontSee('Initial Quantity')->assertDontSee('Add Stock')->assertDontSee('Sell Quantity');
        $this->actingAs($admin)->post('/products', ['item_name' => 'New Product', 'sku' => 'PRODUCT-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'pack_size' => 400, 'unit' => 'ml', 'purchase_price' => 100, 'selling_price' => 120, 'status' => 'active'])->assertRedirect('/products');
        $this->assertDatabaseHas('inventory_items', ['sku' => 'PRODUCT-1', 'quantity' => 0, 'sold_quantity' => 0, 'created_by' => $admin->id]);
    }

    public function test_stock_management_flow_and_history_are_separate_from_product(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'Flow Brand', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['name' => 'Flow Category', 'status' => 'active', 'created_by' => $admin->id]);
        $product = InventoryItem::create(['item_name' => 'Coke', 'sku' => 'FLOW-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 0, 'sold_quantity' => 0, 'unit' => 'ml', 'status' => 'active', 'created_by' => $admin->id]);

        $this->actingAs($admin)->post("/stock/{$product->id}/add", ['sell_quantity' => 50])->assertRedirect('/stock');
        $this->actingAs($admin)->post("/stock/{$product->id}/sell", ['sell_quantity' => 30])->assertRedirect('/stock');
        $this->assertDatabaseHas('inventory_items', ['id' => $product->id, 'quantity' => 50, 'sold_quantity' => 30]);
        $this->actingAs($admin)->get("/stock/{$product->id}/history")->assertOk()->assertSee('STOCK IN')->assertSee('SALE');
        $this->actingAs($admin)->post("/stock/{$product->id}/sell", ['sell_quantity' => 21])->assertSessionHasErrors('sell_quantity');
        $this->assertDatabaseHas('inventory_items', ['id' => $product->id, 'quantity' => 50, 'sold_quantity' => 30]);
    }
}
