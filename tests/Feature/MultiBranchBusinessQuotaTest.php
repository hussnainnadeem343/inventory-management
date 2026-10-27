<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Inventory\Unit;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiBranchBusinessQuotaTest extends TestCase
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

    private function createBusinessWithOwner(string $name = 'Mian Oil Change', int $maxBranches = 3, int $maxUsers = 5, string $code = null): array
    {
        $code = $code ?: 'BIZ-' . strtoupper(substr(uniqid(), -6));

        $business = Shop::create([
            'parent_id' => null,
            'name' => $name,
            'code' => $code,
            'business_type' => 'Lube (Oil Changing)',
            'phone' => '03001234567',
            'address' => 'Gulberg, Lahore',
            'status' => 'active',
            'max_branches' => $maxBranches,
            'max_users' => $maxUsers,
        ]);

        $owner = User::create([
            'shop_id' => $business->id,
            'name' => 'Mian Sahib',
            'username' => 'mian_owner_' . uniqid(),
            'email' => 'mian_' . uniqid() . '@oil.test',
            'password' => 'secret1234',
            'role' => 'business_owner',
            'status' => 'active',
        ]);

        return [$business, $owner];
    }

    public function test_super_admin_can_create_business_with_quotas_and_business_owner(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $payload = [
            'name' => 'Al-Madina Traders',
            'code' => 'AMT-01',
            'business_type' => 'General Retail',
            'phone' => '03123456789',
            'address' => 'Circular Road, Lahore',
            'status' => 'active',
            'max_branches' => 4,
            'max_users' => 10,
            'admin_role' => 'business_owner',
            'admin_name' => 'Haji Madina',
            'admin_username' => 'haji_madina',
            'admin_email' => 'haji@madina.test',
            'admin_password' => 'secret1234',
        ];

        $response = $this->actingAs($superAdmin)->post(route('shops.store'), $payload);
        $response->assertRedirect(route('shops.index'));

        $this->assertDatabaseHas('shops', [
            'code' => 'AMT-01',
            'max_branches' => 4,
            'max_users' => 10,
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'haji_madina',
            'role' => 'business_owner',
        ]);
    }

    public function test_business_owner_can_view_branches_list_and_quota_progress(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner('Mian Oil Change', 3, 5, 'MIAN-HQ');

        $response = $this->actingAs($owner)->get(route('branches.index'));
        $response->assertOk();
        $response->assertSee('Branch Network');
        $response->assertSee('Mian Oil Change');
        $response->assertSee('MIAN-HQ');
        $response->assertSee('Branch Quota');
    }

    public function test_business_owner_can_create_sub_branches_up_to_quota(): void
    {
        // Limit is 3 (1 HQ + 2 sub-branches)
        [$business, $owner] = $this->createBusinessWithOwner('Mian Oil Change', 3, 5);

        // Branch 1 (Child branch #1)
        $branch1Payload = [
            'name' => 'Mian Oil - Model Town',
            'code' => 'MIAN-MT',
            'phone' => '03009988771',
            'address' => 'Link Road Model Town',
            'status' => 'active',
        ];
        $res1 = $this->actingAs($owner)->post(route('branches.store'), $branch1Payload);
        $res1->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('shops', [
            'parent_id' => $business->id,
            'name' => 'Mian Oil - Model Town',
            'code' => 'MIAN-MT',
            'business_type' => 'Lube (Oil Changing)',
        ]);

        // Branch 2 (Child branch #2) - reaches quota (1 HQ + 2 branches = 3 total)
        $branch2Payload = [
            'name' => 'Mian Oil - DHA Phase 5',
            'code' => 'MIAN-DHA',
            'phone' => '03009988772',
            'address' => 'DHA Phase 5 Commercial',
            'status' => 'active',
        ];
        $res2 = $this->actingAs($owner)->post(route('branches.store'), $branch2Payload);
        $res2->assertRedirect(route('branches.index'));

        $this->assertEquals(3, $business->fresh()->totalBranchesCount());
        $this->assertFalse($business->fresh()->canAddBranch());
    }

    public function test_business_owner_is_blocked_when_exceeding_max_branches(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner('Mian Express', 2, 5);

        // Create 1 child branch (reaches max_branches = 2 including HQ)
        Shop::create([
            'parent_id' => $business->id,
            'name' => 'Mian Express - Branch 2',
            'code' => 'MEX-02',
            'business_type' => 'Lube (Oil Changing)',
            'status' => 'active',
        ]);

        $this->assertFalse($business->fresh()->canAddBranch());

        // Attempting to create an excess branch should fail
        $excessBranchPayload = [
            'name' => 'Mian Express - Illegal Branch 3',
            'code' => 'MEX-03',
            'status' => 'active',
        ];

        $response = $this->actingAs($owner)->post(route('branches.store'), $excessBranchPayload);
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('shops', ['code' => 'MEX-03']);

        // Visiting create page should redirect to index
        $createPageResponse = $this->actingAs($owner)->get(route('branches.create'));
        $createPageResponse->assertRedirect(route('branches.index'));
    }

    public function test_business_owner_can_create_and_assign_users_across_branches_up_to_quota(): void
    {
        // max_users = 3 (1 Owner + 2 staff)
        [$business, $owner] = $this->createBusinessWithOwner('Super Autos', 3, 3);

        $branch = Shop::create([
            'parent_id' => $business->id,
            'name' => 'Super Autos Branch B',
            'code' => 'SAB-02',
            'status' => 'active',
        ]);

        // Create User 2 assigned to Branch B
        $userPayload = [
            'name' => 'Branch Manager B',
            'username' => 'manager_b',
            'email' => 'managerb@test.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'shop_id' => $branch->id,
            'assigned_role' => 'system:shop_admin',
            'status' => 'active',
        ];

        $res1 = $this->actingAs($owner)->post(route('users.store'), $userPayload);
        $res1->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'manager_b',
            'shop_id' => $branch->id,
            'role' => 'shop_admin',
        ]);

        // Create User 3 assigned to HQ
        $user3Payload = [
            'name' => 'Cashier HQ',
            'username' => 'cashier_hq',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'shop_id' => $business->id,
            'assigned_role' => 'system:staff',
            'status' => 'active',
        ];

        $res2 = $this->actingAs($owner)->post(route('users.store'), $user3Payload);
        $res2->assertRedirect(route('users.index'));

        $this->assertEquals(3, $business->fresh()->totalUsersCount());
        $this->assertFalse($business->fresh()->canAddUser());
    }

    public function test_business_owner_is_blocked_from_exceeding_max_users(): void
    {
        // Limit is 2 users (Owner is user 1)
        [$business, $owner] = $this->createBusinessWithOwner('Quick Lube', 2, 2);

        // Add 2nd user
        User::create([
            'shop_id' => $business->id,
            'name' => 'Staff 1',
            'username' => 'staff_1',
            'password' => 'secret1234',
            'role' => 'staff',
            'status' => 'active',
        ]);

        $this->assertFalse($business->fresh()->canAddUser());

        // Attempting to add 3rd user should fail
        $payload = [
            'name' => 'Staff 2 Excess',
            'username' => 'staff_2_excess',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'shop_id' => $business->id,
            'assigned_role' => 'system:staff',
            'status' => 'active',
        ];

        $response = $this->actingAs($owner)->post(route('users.store'), $payload);
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['username' => 'staff_2_excess']);

        // Create page should redirect
        $createResponse = $this->actingAs($owner)->get(route('users.create'));
        $createResponse->assertRedirect(route('users.index'));
    }

    public function test_business_owner_cannot_modify_branches_of_other_businesses(): void
    {
        [$businessA, $ownerA] = $this->createBusinessWithOwner('Business A');
        [$businessB, $ownerB] = $this->createBusinessWithOwner('Business B');

        $branchB = Shop::create([
            'parent_id' => $businessB->id,
            'name' => 'Business B Sub-Branch',
            'code' => 'BBB-01',
            'status' => 'active',
        ]);

        // Owner A tries to edit Branch B -> 403 Forbidden
        $editResponse = $this->actingAs($ownerA)->get(route('branches.edit', $branchB));
        $editResponse->assertForbidden();

        // Owner A tries to update Branch B -> 403 Forbidden
        $updateResponse = $this->actingAs($ownerA)->put(route('branches.update', $branchB), [
            'name' => 'Hacked Branch',
            'code' => 'HACK-01',
            'status' => 'active',
        ]);
        $updateResponse->assertForbidden();

        // Owner A tries to delete Branch B -> 403 Forbidden
        $deleteResponse = $this->actingAs($ownerA)->delete(route('branches.destroy', $branchB));
        $deleteResponse->assertForbidden();
    }

    public function test_business_owner_dashboard_branch_switcher_filters_properly(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner('Apex Group', 3, 5);

        $branch = Shop::create([
            'parent_id' => $business->id,
            'name' => 'Apex Branch 2',
            'code' => 'APEX-02',
            'status' => 'active',
        ]);

        // 1. Visit dashboard without filter (Consolidated view)
        $responseConsolidated = $this->actingAs($owner)->get(route('dashboard'));
        $responseConsolidated->assertOk();
        $responseConsolidated->assertSee('All Branches Consolidated');

        // 2. Filter to Branch 2
        $responseBranch = $this->actingAs($owner)->get(route('dashboard', ['shop_id' => $branch->id]));
        $responseBranch->assertOk();
        $responseBranch->assertSee('Apex Branch 2');
    }

    public function test_product_and_brand_created_in_one_branch_are_visible_across_all_branches_of_business(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner('Mian Oil Network', 3, 5);

        $branch2 = Shop::create([
            'parent_id' => $business->id,
            'name' => 'Mian Oil Branch 2',
            'code' => 'MOB-02',
            'business_type' => $business->business_type,
            'status' => 'active',
        ]);

        $manager1 = User::create([
            'shop_id' => $business->id,
            'name' => 'Manager Branch 1',
            'username' => 'mgr_b1_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        $manager2 = User::create([
            'shop_id' => $branch2->id,
            'name' => 'Manager Branch 2',
            'username' => 'mgr_b2_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        // Branch 1 creates a Brand, Category, and Unit
        $brand = Brand::create(['shop_id' => $business->id, 'name' => 'Total Lubricants', 'status' => 'active', 'created_by' => $manager1->id]);
        $category = Category::create(['shop_id' => $business->id, 'name' => 'Engine Oils', 'status' => 'active', 'created_by' => $manager1->id]);
        $unit = Unit::create(['shop_id' => $business->id, 'name' => 'Litre', 'code' => 'LTR', 'created_by' => $manager1->id]);

        // Branch 1 creates Product
        $productPayload = [
            'item_name' => 'Total Quartz 9000',
            'sku' => 'TOT-9000',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'unit' => 'Litre',
            'purchase_price' => 5000,
            'selling_price' => 6500,
            'initial_quantity' => 10,
            'status' => 'active',
        ];

        $storeResponse = $this->actingAs($manager1)->post(route('products.store'), $productPayload);
        $storeResponse->assertRedirect(route('products.index'));

        // Branch 2 logs in and views catalog
        $branch2Catalog = $this->actingAs($manager2)->get(route('products.index'));
        $branch2Catalog->assertOk();
        $branch2Catalog->assertSee('Total Quartz 9000');
        $branch2Catalog->assertSee('TOT-9000');
        $branch2Catalog->assertSee('Total Lubricants');
        $branch2Catalog->assertSee('Engine Oils');

        // Branch 2 can view Brands and Categories created by the business
        $branch2Brands = $this->actingAs($manager2)->get(route('brands.index'));
        $branch2Brands->assertOk()->assertSee('Total Lubricants');

        $branch2Categories = $this->actingAs($manager2)->get(route('categories.index'));
        $branch2Categories->assertOk()->assertSee('Engine Oils');
    }

    public function test_cross_branch_stocking_maintains_independent_branch_quantities_and_universal_selling_price(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner('Universal Lube Chain', 3, 5);

        $branch2 = Shop::create([
            'parent_id' => $business->id,
            'name' => 'Universal Lube Gulberg',
            'code' => 'ULG-02',
            'business_type' => $business->business_type,
            'status' => 'active',
        ]);

        $manager1 = User::create([
            'shop_id' => $business->id,
            'name' => 'HQ Admin',
            'username' => 'hq_admin_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        $manager2 = User::create([
            'shop_id' => $branch2->id,
            'name' => 'Gulberg Branch Admin',
            'username' => 'gulberg_admin_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        $brand = Brand::create(['shop_id' => $business->id, 'name' => 'Mobil 1', 'status' => 'active', 'created_by' => $manager1->id]);
        $cat = Category::create(['shop_id' => $business->id, 'name' => 'Synthetic Oils', 'status' => 'active', 'created_by' => $manager1->id]);

        // HQ creates master product with 10 units at HQ
        $this->actingAs($manager1)->post(route('products.store'), [
            'item_name' => 'Mobil 1 ESP 5W-30',
            'sku' => 'MOB-ESP-5W30',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'purchase_price' => 7000,
            'selling_price' => 8500,
            'initial_quantity' => 10,
            'status' => 'active',
        ]);

        $masterItem = InventoryItem::where('sku', 'MOB-ESP-5W30')->first();
        $this->assertNotNull($masterItem);
        $this->assertEquals(10, $masterItem->quantity);

        // Branch 2 stocks in 4 units with an updated universal selling price of 9000
        $stockResponse = $this->actingAs($manager2)->post(route('stock.add', $masterItem), [
            'quantity' => 4,
            'purchase_price' => 7200,
            'selling_price' => 9000,
        ]);
        $stockResponse->assertRedirect();

        // Branch 2 now has its local inventory record with 4 units
        $branch2Item = InventoryItem::where('shop_id', $branch2->id)->where('sku', 'MOB-ESP-5W30')->first();
        $this->assertNotNull($branch2Item);
        $this->assertEquals(4, $branch2Item->quantity);
        $this->assertEquals(9000, $branch2Item->selling_price);

        // HQ still has 10 units of physical stock, and its selling price synchronized to 9000
        $masterItem->refresh();
        $this->assertEquals(10, $masterItem->quantity);
        $this->assertEquals(9000, $masterItem->selling_price);
    }

    public function test_business_catalog_isolation_between_different_client_businesses(): void
    {
        [$businessA, $ownerA] = $this->createBusinessWithOwner('Client Business Alpha');
        [$businessB, $ownerB] = $this->createBusinessWithOwner('Client Business Beta');

        $brandA = Brand::create(['shop_id' => $businessA->id, 'name' => 'Alpha Oil Brand', 'status' => 'active', 'created_by' => $ownerA->id]);
        $catA = Category::create(['shop_id' => $businessA->id, 'name' => 'Alpha Category', 'status' => 'active', 'created_by' => $ownerA->id]);

        $this->actingAs($ownerA)->post(route('products.store'), [
            'item_name' => 'Alpha Secret Formula',
            'sku' => 'ALPHA-999',
            'brand_id' => $brandA->id,
            'category_id' => $catA->id,
            'purchase_price' => 1000,
            'selling_price' => 1500,
            'initial_quantity' => 5,
            'status' => 'active',
        ]);

        // Owner B visits Product Catalog -> Must NOT see Alpha's product, brand, or SKU
        $responseB = $this->actingAs($ownerB)->get(route('products.index'));
        $responseB->assertOk();
        $responseB->assertDontSee('Alpha Secret Formula');
        $responseB->assertDontSee('ALPHA-999');
        $responseB->assertDontSee('Alpha Oil Brand');

        // Owner B visits Brands -> Must NOT see Alpha's brand
        $brandsResponseB = $this->actingAs($ownerB)->get(route('brands.index'));
        $brandsResponseB->assertOk();
        $brandsResponseB->assertDontSee('Alpha Oil Brand');
    }

    public function test_super_admin_dashboard_shows_platform_kpis_and_no_operational_retail_cards(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createBusinessWithOwner('Demo Client Business 1');
        $this->createBusinessWithOwner('Demo Client Business 2');

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));
        $response->assertOk();

        // Platform Administration metrics and titles must be visible
        $response->assertSee('Platform Administration');
        $response->assertSee('Client Businesses');
        $response->assertSee('Sub-Branch Network');
        $response->assertSee('Platform Staff &amp; Users', false);

        // Operational store retail cards must NOT be on Super Admin platform dashboard
        $response->assertDontSee('Sales Today');
        $response->assertDontSee('Inward to Bill');
        $response->assertDontSee('Recent Stock Activity');
        $response->assertDontSee('Auto Lube Station');
    }

    public function test_super_admin_sidebar_has_no_operational_retail_screens(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));
        $response->assertOk();

        // Operational nav menus must be hidden
        $response->assertDontSee('POS Terminal');
        $response->assertDontSee('Stock In / Out');
        $response->assertDontSee('Vendor Invoices');
        $response->assertDontSee('Vehicle Profiles');

        // Super Admin Management items must be visible
        $response->assertSee('Client Businesses');
        $response->assertSee('All Branches');
    }

    public function test_single_store_shop_admin_does_not_see_my_branches_and_cannot_access_branch_management(): void
    {
        $singleShop = Shop::create([
            'parent_id' => null,
            'name' => 'Single Standalone Store',
            'code' => 'SSS-01',
            'status' => 'active',
            'max_branches' => 1,
            'max_users' => 5,
        ]);

        $shopAdmin = User::create([
            'shop_id' => $singleShop->id,
            'name' => 'Standalone Admin',
            'username' => 'standalone_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        // 1. Dashboard does not show 'My Branches'
        $response = $this->actingAs($shopAdmin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('My Branches');
        $response->assertDontSee('All Branches');

        // 2. Direct access to /branches is forbidden
        $branchAccess = $this->actingAs($shopAdmin)->get(route('branches.index'));
        $branchAccess->assertForbidden();
    }

    public function test_shop_admin_cannot_see_edit_or_delete_business_owner(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner('Mian Corp HQ', 3, 10);

        $shopAdmin = User::create([
            'shop_id' => $business->id,
            'name' => 'Store Admin 1',
            'username' => 'store_admin_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        // 1. Shop Admin views staff list -> Business Owner is completely invisible
        $response = $this->actingAs($shopAdmin)->get(route('users.index'));
        $response->assertOk();
        $response->assertSee('Store Admin 1');
        $response->assertDontSee($owner->name);
        $response->assertDontSee($owner->username);

        // 2. Shop Admin attempts to edit Business Owner -> 403 Forbidden
        $editResponse = $this->actingAs($shopAdmin)->get(route('users.edit', $owner));
        $editResponse->assertForbidden();

        // 3. Shop Admin attempts to update Business Owner -> 403 Forbidden
        $updateResponse = $this->actingAs($shopAdmin)->put(route('users.update', $owner), [
            'name' => 'Hacked Owner',
            'username' => $owner->username,
            'email' => $owner->email,
            'assigned_role' => 'system:shop_admin',
            'status' => 'active',
        ]);
        $updateResponse->assertForbidden();

        // 4. Shop Admin attempts to delete Business Owner -> 403 Forbidden
        $deleteResponse = $this->actingAs($shopAdmin)->delete(route('users.destroy', $owner));
        $deleteResponse->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'role' => 'business_owner']);
    }

    public function test_shop_admin_can_only_manage_users_of_own_branch_and_not_other_branches(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner('Multi Branch Retail Group', 3, 10);

        $branch2 = Shop::create([
            'parent_id' => $business->id,
            'name' => 'Retail Branch 2',
            'code' => 'RBR-02',
            'business_type' => $business->business_type,
            'status' => 'active',
        ]);

        $admin1 = User::create([
            'shop_id' => $business->id,
            'name' => 'Admin Branch 1',
            'username' => 'admin_b1_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        $staff1 = User::create([
            'shop_id' => $business->id,
            'name' => 'Staff Member Branch 1',
            'username' => 'staff_b1_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'staff',
            'status' => 'active',
        ]);

        $admin2 = User::create([
            'shop_id' => $branch2->id,
            'name' => 'Admin Branch 2',
            'username' => 'admin_b2_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        $staff2 = User::create([
            'shop_id' => $branch2->id,
            'name' => 'Staff Member Branch 2',
            'username' => 'staff_b2_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'staff',
            'status' => 'active',
        ]);

        // 1. Admin 1 views users -> sees Staff 1, but NOT Admin 2 or Staff 2
        $listResponse = $this->actingAs($admin1)->get(route('users.index'));
        $listResponse->assertOk();
        $listResponse->assertSee('Staff Member Branch 1');
        $listResponse->assertDontSee('Staff Member Branch 2');
        $listResponse->assertDontSee('Admin Branch 2');

        // 2. Admin 1 attempts to edit Staff 2 (from Branch 2) -> 403 Forbidden
        $editResponse = $this->actingAs($admin1)->get(route('users.edit', $staff2));
        $editResponse->assertForbidden();

        // 3. Admin 1 attempts to delete Staff 2 (from Branch 2) -> 403 Forbidden
        $deleteResponse = $this->actingAs($admin1)->delete(route('users.destroy', $staff2));
        $deleteResponse->assertForbidden();

        // 4. Admin 1 creates a new staff member -> strictly forced into Admin 1's branch
        $createResponse = $this->actingAs($admin1)->post(route('users.store'), [
            'name' => 'New Cashier',
            'username' => 'new_cashier_' . uniqid(),
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'shop_id' => $branch2->id, // Attempting to inject Branch 2
            'assigned_role' => 'system:staff',
            'status' => 'active',
        ]);
        $createResponse->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'New Cashier',
            'shop_id' => $business->id, // Must be forced to Admin 1's branch!
        ]);

        // 5. Admin 1 can edit and delete their own branch staff
        $updateSelfStaff = $this->actingAs($admin1)->put(route('users.update', $staff1), [
            'name' => 'Staff 1 Renamed',
            'username' => $staff1->username,
            'assigned_role' => 'system:staff',
            'status' => 'active',
        ]);
        $updateSelfStaff->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $staff1->id, 'name' => 'Staff 1 Renamed']);
    }

    public function test_business_owner_can_manage_users_across_all_branches(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner('Omni Network', 3, 10);

        $branch2 = Shop::create([
            'parent_id' => $business->id,
            'name' => 'Omni South Branch',
            'code' => 'OSB-02',
            'business_type' => $business->business_type,
            'status' => 'active',
        ]);

        // 1. Business Owner creates a user specifically assigned to Branch 2
        $storeResponse = $this->actingAs($owner)->post(route('users.store'), [
            'name' => 'South Branch Manager',
            'username' => 'south_mgr_' . uniqid(),
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'shop_id' => $branch2->id,
            'assigned_role' => 'system:shop_admin',
            'status' => 'active',
        ]);
        $storeResponse->assertRedirect(route('users.index'));

        $branch2User = User::where('name', 'South Branch Manager')->first();
        $this->assertNotNull($branch2User);
        $this->assertEquals($branch2->id, $branch2User->shop_id);

        // 2. Business Owner can view, edit, and update Branch 2 user
        $editResponse = $this->actingAs($owner)->get(route('users.edit', $branch2User));
        $editResponse->assertOk();
        $editResponse->assertSee('South Branch Manager');

        $updateResponse = $this->actingAs($owner)->put(route('users.update', $branch2User), [
            'name' => 'South Branch Lead',
            'username' => $branch2User->username,
            'shop_id' => $branch2->id,
            'assigned_role' => 'system:shop_admin',
            'status' => 'active',
        ]);
        $updateResponse->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $branch2User->id, 'name' => 'South Branch Lead']);

        // 3. Business Owner can delete Branch 2 user
        $deleteResponse = $this->actingAs($owner)->delete(route('users.destroy', $branch2User));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $branch2User->id]);
    }

    public function test_super_admin_can_manage_business_owner_and_all_users(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [$business, $owner] = $this->createBusinessWithOwner('Super Managed Biz', 3, 10);

        // 1. Super Admin sees Business Owner in users directory
        $response = $this->actingAs($superAdmin)->get(route('users.index'));
        $response->assertOk();
        $response->assertSee($owner->name);
        $response->assertSee('Business Owner');

        // 2. Super Admin can edit Business Owner
        $editResponse = $this->actingAs($superAdmin)->get(route('users.edit', $owner));
        $editResponse->assertOk();

        // 3. Super Admin can update Business Owner
        $updateResponse = $this->actingAs($superAdmin)->put(route('users.update', $owner), [
            'name' => 'Mian Updated Owner',
            'username' => $owner->username,
            'email' => 'mian_updated@test.com',
            'shop_id' => $business->id,
            'assigned_role' => 'system:business_owner',
            'status' => 'active',
        ]);
        $updateResponse->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'name' => 'Mian Updated Owner']);
    }
}
