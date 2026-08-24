<?php

namespace Tests\Feature;

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
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
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
}
