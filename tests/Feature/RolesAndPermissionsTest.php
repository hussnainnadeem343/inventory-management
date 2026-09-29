<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_admin_can_create_custom_role_and_assign_permissions(): void
    {
        $shop = Shop::create(['name' => 'RBAC Store', 'code' => 'RBAC-01', 'status' => 'active']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);

        $perm1 = Permission::where('slug', 'pos.access')->first();
        $perm2 = Permission::where('slug', 'products.view')->first();

        $response = $this->actingAs($admin)->post('/roles', [
            'name' => 'Junior Counter Cashier',
            'description' => 'Only allowed to access POS and view products',
            'permissions' => [$perm1->id, $perm2->id],
        ]);

        $response->assertRedirect('/roles');
        $response->assertSessionHas('success');

        $role = Role::where('slug', 'junior-counter-cashier')->first();
        $this->assertNotNull($role);
        $this->assertEquals($shop->id, $role->shop_id);
        $this->assertTrue($role->hasPermission('pos.access'));
        $this->assertTrue($role->hasPermission('products.view'));
        $this->assertFalse($role->hasPermission('reports.sales'));
    }

    public function test_user_with_custom_role_inherits_its_configured_permissions(): void
    {
        $shop = Shop::create(['name' => 'User Role Store', 'code' => 'URS-01', 'status' => 'active']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);

        $permStock = Permission::where('slug', 'stock.view')->first();
        $permStockAdd = Permission::where('slug', 'stock.add')->first();

        $role = Role::create([
            'shop_id' => $shop->id,
            'name' => 'Warehouse Assistant',
            'slug' => 'warehouse-assistant',
            'created_by' => $admin->id,
        ]);
        $role->permissions()->sync([$permStock->id, $permStockAdd->id]);

        $staff = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'staff',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->assertTrue($staff->hasPermission('stock.view'));
        $this->assertTrue($staff->hasPermission('stock.add'));
        $this->assertFalse($staff->hasPermission('reports.sales'));
        $this->assertFalse($staff->hasPermission('roles.manage'));
    }

    public function test_roles_are_isolated_by_shop(): void
    {
        $shopA = Shop::create(['name' => 'Store Alpha', 'code' => 'ALP-01', 'status' => 'active']);
        $shopB = Shop::create(['name' => 'Store Beta', 'code' => 'BET-01', 'status' => 'active']);

        $adminA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'shop_admin', 'status' => 'active']);
        $adminB = User::factory()->create(['shop_id' => $shopB->id, 'role' => 'shop_admin', 'status' => 'active']);

        $roleA = Role::create([
            'shop_id' => $shopA->id,
            'name' => 'Alpha Specific Role',
            'slug' => 'alpha-role',
        ]);

        // Admin B cannot edit Role A
        $this->actingAs($adminB)->get("/roles/{$roleA->id}/edit")->assertForbidden();
    }
}
