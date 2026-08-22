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
        $this->actingAs($user)->post('/inventory', ['item_name' => 'TV', 'sku' => 'TV-1', 'brand_id' => $brand->id, 'category_id' => $category->id, 'quantity' => 10, 'unit' => 'PCS', 'purchase_price' => 100, 'selling_price' => 120, 'supplier' => 'Supplier', 'status' => 'active', 'created_by' => $admin->id])->assertRedirect('/inventory');
        $this->assertDatabaseHas('inventory_items', ['sku' => 'TV-1', 'created_by' => $user->id]);
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
