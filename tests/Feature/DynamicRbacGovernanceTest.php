<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use App\Services\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicRbacGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
            'shop_id' => null,
        ]);
    }

    private function createShopWithAdmin(string $businessType = 'Retail & General Business', string $name = 'Alpha Retail'): array
    {
        $shop = Shop::create([
            'name' => $name,
            'code' => 'SHP-' . strtoupper(substr(uniqid(), -5)),
            'business_type' => $businessType,
            'status' => 'active',
            'max_branches' => 3,
            'max_users' => 10,
        ]);

        $admin = User::create([
            'shop_id' => $shop->id,
            'name' => 'Shop Admin',
            'username' => 'admin_' . uniqid(),
            'email' => 'admin_' . uniqid() . '@shop.test',
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        return [$shop, $admin];
    }

    /**
     * 1. Super Admin Platform Separation & Shop Creation
     */
    public function test_super_admin_can_create_business_with_business_type_and_initial_shop_admin(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $payload = [
            'name' => 'Beta Mart',
            'code' => 'BM-01',
            'business_type' => 'Retail & General Business',
            'phone' => '03001122334',
            'status' => 'active',
            'max_branches' => 2,
            'max_users' => 5,
            'admin_role' => 'shop_admin',
            'admin_name' => 'Usman Malik',
            'admin_username' => 'usman_beta',
            'admin_email' => 'usman@beta.test',
            'admin_password' => 'secret1234',
        ];

        $response = $this->actingAs($superAdmin)->post(route('shops.store'), $payload);
        $response->assertRedirect(route('shops.index'));

        $this->assertDatabaseHas('shops', [
            'code' => 'BM-01',
            'business_type' => 'Retail & General Business',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'usman_beta',
            'role' => 'shop_admin',
        ]);
    }

    /**
     * 2. Shop Admin Full Access to Enabled Modules
     */
    public function test_shop_admin_has_full_access_to_all_enabled_modules_of_their_business(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin('Retail & General Business');

        $this->assertTrue($admin->hasPermission('pos.access'));
        $this->assertTrue($admin->hasPermission('products.create'));
        $this->assertTrue($admin->hasPermission('stock.view'));
        $this->assertTrue($admin->hasPermission('purchases.create'));
        $this->assertTrue($admin->hasPermission('accounts.view'));
        $this->assertTrue($admin->hasPermission('hr.employees'));
        $this->assertTrue($admin->hasPermission('reports.sales'));
        $this->assertTrue($admin->hasPermission('roles.manage'));

        // But Lube module is NOT available for Retail & General Business
        $this->assertFalse($admin->hasPermission('lube.pos'));
        $this->assertFalse($admin->hasPermission('lube.analytics'));
    }

    /**
     * 3. Shop Admin Can Create Dynamic Custom Roles
     */
    public function test_shop_admin_can_create_custom_role_with_granular_permissions(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $permPos = Permission::where('slug', 'pos.access')->first();
        $permInvoices = Permission::where('slug', 'sales.view_invoices')->first();

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Front Desk Cashier',
            'description' => 'Handles POS and reprints invoices',
            'permissions' => [$permPos->id, $permInvoices->id],
        ]);

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success');

        $role = Role::where('shop_id', $shop->id)->where('name', 'Front Desk Cashier')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermission('pos.access'));
        $this->assertTrue($role->hasPermission('sales.view_invoices'));
        $this->assertFalse($role->hasPermission('stock.view'));
        $this->assertFalse($role->hasPermission('purchases.create'));
    }

    /**
     * 4. POS-Only Operator: Authorized on POS, Blocked Everywhere Else
     */
    public function test_pos_only_operator_can_access_pos_and_is_blocked_from_unauthorized_modules(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $permPos = Permission::where('slug', 'pos.access')->first();

        $role = Role::create([
            'shop_id' => $shop->id,
            'name' => 'POS Operator',
            'slug' => 'pos-operator',
            'created_by' => $admin->id,
        ]);
        $role->permissions()->sync([$permPos->id]);

        $posUser = User::create([
            'shop_id' => $shop->id,
            'role_id' => $role->id,
            'name' => 'Ali Cashier',
            'username' => 'ali_cashier_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'staff',
            'status' => 'active',
        ]);

        // 1. Can access POS
        $this->actingAs($posUser)->get(route('pos'))->assertOk();

        // 2. Blocked from Inventory
        $this->actingAs($posUser)->get(route('inventory.index'))->assertForbidden();

        // 3. Blocked from Purchases
        $this->actingAs($posUser)->get(route('purchases.index'))->assertForbidden();

        // 4. Blocked from Finance
        $this->actingAs($posUser)->get(route('finance.index'))->assertForbidden();

        // 5. Blocked from HR
        $this->actingAs($posUser)->get(route('hr.index'))->assertForbidden();

        // 6. Blocked from Roles & Staff Management
        $this->actingAs($posUser)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($posUser)->get(route('users.index'))->assertForbidden();

        // 7. Smart Redirect: Visiting /dashboard redirects POS-only operator straight to /pos
        $this->actingAs($posUser)->get(route('dashboard'))->assertRedirect(route('pos'));
    }

    /**
     * 5. Direct URL Protection for Non-Permitted Actions
     */
    public function test_user_cannot_access_unauthorized_screens_through_direct_urls(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $permStockView = Permission::where('slug', 'stock.view')->first();

        $role = Role::create([
            'shop_id' => $shop->id,
            'name' => 'Stock Viewer Only',
            'slug' => 'stock-viewer',
            'created_by' => $admin->id,
        ]);
        $role->permissions()->sync([$permStockView->id]);

        $viewer = User::create([
            'shop_id' => $shop->id,
            'role_id' => $role->id,
            'name' => 'Viewer',
            'username' => 'viewer_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'staff',
            'status' => 'active',
        ]);

        // Can view stock
        $this->actingAs($viewer)->get(route('stock.index'))->assertOk();

        // Direct URL to create purchase order -> Forbidden (403)
        $this->actingAs($viewer)->get(route('purchases.orders.create'))->assertForbidden();

        // Direct URL to create payroll -> Forbidden (403)
        $this->actingAs($viewer)->get(route('hr.payroll.create'))->assertForbidden();
    }

    /**
     * 6. Business-Type Module Gating: Retail Store Cannot Access Lube Module
     */
    public function test_retail_store_cannot_access_lube_module_routes_server_side(): void
    {
        [$retailShop, $retailAdmin] = $this->createShopWithAdmin('Retail & General Business', 'Retail Shop');

        // Even the Shop Admin of a Retail store cannot access Lube screens
        $this->actingAs($retailAdmin)->get(route('lube.hub'))->assertForbidden();
        $this->actingAs($retailAdmin)->get(route('lube.pos'))->assertForbidden();
        $this->actingAs($retailAdmin)->get(route('lube.analytics'))->assertForbidden();
    }

    /**
     * 7. Lube Station Can Access Both Retail and Lube Modules
     */
    public function test_lube_station_shop_admin_can_access_lube_module(): void
    {
        [$lubeShop, $lubeAdmin] = $this->createShopWithAdmin('Lube (Oil Changing) Station', 'City Lube Station');

        // Lube Admin has access to Lube module
        $this->actingAs($lubeAdmin)->get(route('lube.hub'))->assertOk();
        $this->actingAs($lubeAdmin)->get(route('lube.pos'))->assertOk();
        $this->actingAs($lubeAdmin)->get(route('lube.analytics'))->assertOk();

        // And standard modules too
        $this->actingAs($lubeAdmin)->get(route('pos'))->assertOk();
        $this->actingAs($lubeAdmin)->get(route('stock.index'))->assertOk();
    }

    /**
     * 8. Retail Store Role Cannot Receive Lube Permissions
     */
    public function test_retail_store_role_creation_filters_out_lube_permissions(): void
    {
        [$retailShop, $retailAdmin] = $this->createShopWithAdmin('Retail & General Business');

        $permPos = Permission::where('slug', 'pos.access')->first();
        $permLubePos = Permission::where('slug', 'lube.pos')->first();

        // Attacking / tampering submission with a lube permission on a retail store
        $this->actingAs($retailAdmin)->post(route('roles.store'), [
            'name' => 'Sneaky Cashier',
            'permissions' => [$permPos->id, $permLubePos->id],
        ]);

        $role = Role::where('shop_id', $retailShop->id)->where('name', 'Sneaky Cashier')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermission('pos.access'));
        // lube.pos was automatically stripped because retail store does not support lube!
        $this->assertFalse($role->hasPermission('lube.pos'));
    }

    /**
     * 9. Cross-Tenant Isolation: Business A Cannot Access or Tamper Business B's Roles
     */
    public function test_roles_and_access_are_strictly_isolated_between_businesses(): void
    {
        [$shopA, $adminA] = $this->createShopWithAdmin('Retail & General Business', 'Shop A');
        [$shopB, $adminB] = $this->createShopWithAdmin('Retail & General Business', 'Shop B');

        $roleA = Role::create([
            'shop_id' => $shopA->id,
            'name' => 'Alpha Exclusive Role',
            'slug' => 'alpha-role',
            'created_by' => $adminA->id,
        ]);

        // Admin B cannot edit Role A
        $this->actingAs($adminB)->get(route('roles.edit', $roleA))->assertForbidden();

        // Admin B cannot update Role A
        $this->actingAs($adminB)->put(route('roles.update', $roleA), [
            'name' => 'Hacked Role',
        ])->assertForbidden();

        // Admin B cannot delete Role A
        $this->actingAs($adminB)->delete(route('roles.destroy', $roleA))->assertForbidden();
    }

    /**
     * 10. Role Named 'Super Admin' Inside a Business Never Grants Platform Privileges
     */
    public function test_business_role_named_super_admin_does_not_grant_platform_privileges(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $role = Role::create([
            'shop_id' => $shop->id,
            'name' => 'Super Admin',
            'slug' => 'super-admin-custom',
            'created_by' => $admin->id,
        ]);

        $user = User::create([
            'shop_id' => $shop->id,
            'role_id' => $role->id,
            'name' => 'Fake Super Admin',
            'username' => 'fake_super_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'staff',
            'status' => 'active',
        ]);

        $this->assertFalse($user->isSuperAdmin());

        // Visiting platform shop management screen is strictly forbidden
        $this->actingAs($user)->get(route('shops.index'))->assertForbidden();
        $this->actingAs($user)->get(route('shops.create'))->assertForbidden();
    }

    /**
     * 11. Inactive User is Immediately Blocked and Logged Out
     */
    public function test_inactive_user_is_logged_out_immediately(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $permPos = Permission::where('slug', 'pos.access')->first();
        $role = Role::create(['shop_id' => $shop->id, 'name' => 'Cashier', 'slug' => 'cashier']);
        $role->permissions()->sync([$permPos->id]);

        $inactiveUser = User::create([
            'shop_id' => $shop->id,
            'role_id' => $role->id,
            'name' => 'Fired Staff',
            'username' => 'fired_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'staff',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($inactiveUser)->get(route('pos'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * 12. Deactivated Business Blocks All Its Users
     */
    public function test_deactivated_business_blocks_all_its_users_from_system(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $shop->update(['status' => 'inactive']);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * 13. Instant Permission Changes Take Effect Immediately
     */
    public function test_revoking_permission_from_role_takes_effect_instantly(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $permPos = Permission::where('slug', 'pos.access')->first();
        $permStock = Permission::where('slug', 'stock.view')->first();

        $role = Role::create([
            'shop_id' => $shop->id,
            'name' => 'Cashier Lead',
            'slug' => 'cashier-lead',
        ]);
        $role->permissions()->sync([$permPos->id, $permStock->id]);

        $staff = User::create([
            'shop_id' => $shop->id,
            'role_id' => $role->id,
            'name' => 'Lead',
            'username' => 'lead_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'staff',
            'status' => 'active',
        ]);

        // Initially has stock view
        $this->actingAs($staff)->get(route('stock.index'))->assertOk();

        // Admin revokes stock.view from the role
        $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => 'Cashier Lead',
            'permissions' => [$permPos->id], // Only POS remains
        ]);

        // Staff immediately gets 403 on stock without stale session
        $this->actingAs($staff)->get(route('stock.index'))->assertForbidden();
    }

    /**
     * 14. Cannot Delete Role if Assigned to Active Staff
     */
    public function test_cannot_delete_role_assigned_to_active_users(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $role = Role::create([
            'shop_id' => $shop->id,
            'name' => 'Auditor',
            'slug' => 'auditor',
        ]);

        User::create([
            'shop_id' => $shop->id,
            'role_id' => $role->id,
            'name' => 'Auditor User',
            'username' => 'auditor_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'staff',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $role));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }
}
