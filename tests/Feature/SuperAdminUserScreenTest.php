<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminUserScreenTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'shop_id' => null,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_users_screen_strictly_displays_business_owners_and_shop_admins(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $biz1 = Shop::create([
            'name' => 'Metro Motors HQ',
            'code' => 'MTR-01',
            'status' => 'active',
            'parent_id' => null,
            'max_branches' => 5,
            'max_users' => 10,
        ]);

        $biz2 = Shop::create([
            'name' => 'City Auto HQ',
            'code' => 'CTY-01',
            'status' => 'active',
            'parent_id' => null,
            'max_branches' => 3,
            'max_users' => 6,
        ]);

        $owner1 = User::factory()->create([
            'name' => 'Tariq Business Owner',
            'username' => 'tariq_owner',
            'role' => 'business_owner',
            'shop_id' => $biz1->id,
            'status' => 'active',
        ]);

        $admin1 = User::factory()->create([
            'name' => 'Kamran Shop Admin',
            'username' => 'kamran_admin',
            'role' => 'shop_admin',
            'shop_id' => $biz1->id,
            'status' => 'active',
        ]);

        $staff1 = User::factory()->create([
            'name' => 'Bilal Ordinary Cashier',
            'username' => 'bilal_staff',
            'role' => 'staff',
            'shop_id' => $biz1->id,
            'status' => 'active',
        ]);

        $owner2 = User::factory()->create([
            'name' => 'Zubair City Owner',
            'username' => 'zubair_owner',
            'role' => 'business_owner',
            'shop_id' => $biz2->id,
            'status' => 'active',
        ]);

        // 1. Super Admin visits users screen -> sees Owners and Shop Admins, NOT ordinary staff
        $response = $this->actingAs($superAdmin)->get(route('users.index'));
        $response->assertOk();
        $response->assertSee('Business Owners &amp; Shop Admins', false);
        $response->assertSee('Tariq Business Owner');
        $response->assertSee('Kamran Shop Admin');
        $response->assertSee('Zubair City Owner');
        $response->assertDontSee('Bilal Ordinary Cashier');

        // 2. Sidebar contains link to Owners & Shop Admins
        $response->assertSee('Owners &amp; Shop Admins', false);

        // 3. Filter by role=business_owner -> sees only business owners
        $respOwner = $this->actingAs($superAdmin)->get(route('users.index', ['role' => 'business_owner']));
        $respOwner->assertOk();
        $respOwner->assertSee('Tariq Business Owner');
        $respOwner->assertSee('Zubair City Owner');
        $respOwner->assertDontSee('Kamran Shop Admin');
        $respOwner->assertDontSee('Bilal Ordinary Cashier');

        // 4. Filter by role=shop_admin -> sees only shop admins
        $respAdmin = $this->actingAs($superAdmin)->get(route('users.index', ['role' => 'shop_admin']));
        $respAdmin->assertOk();
        $respAdmin->assertSee('Kamran Shop Admin');
        $respAdmin->assertDontSee('Tariq Business Owner');
        $respAdmin->assertDontSee('Zubair City Owner');

        // 5. Filter by shop_id=biz1 -> sees only biz1's owner and shop admin
        $respBiz1 = $this->actingAs($superAdmin)->get(route('users.index', ['shop_id' => $biz1->id]));
        $respBiz1->assertOk();
        $respBiz1->assertSee('Tariq Business Owner');
        $respBiz1->assertSee('Kamran Shop Admin');
        $respBiz1->assertDontSee('Zubair City Owner');
    }

    public function test_super_admin_can_create_and_edit_owner_and_shop_admin(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $biz = Shop::create([
            'name' => 'Prime Lubricants',
            'code' => 'PRM-01',
            'status' => 'active',
            'max_branches' => 5,
            'max_users' => 10,
        ]);

        // 1. Super Admin creates a new Shop Admin
        $createResp = $this->actingAs($superAdmin)->post(route('users.store'), [
            'name' => 'New Branch Manager',
            'username' => 'new_manager',
            'email' => 'manager@prime.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'shop_id' => $biz->id,
            'assigned_role' => 'system:shop_admin',
            'status' => 'active',
        ]);
        $createResp->assertRedirect(route('users.index'));

        $newAdmin = User::where('username', 'new_manager')->firstOrFail();
        $this->assertEquals('shop_admin', $newAdmin->role);
        $this->assertEquals($biz->id, $newAdmin->shop_id);

        // 2. Super Admin edits the Shop Admin
        $editResp = $this->actingAs($superAdmin)->get(route('users.edit', $newAdmin));
        $editResp->assertOk();

        // 3. Super Admin promotes Shop Admin to Business Owner
        $updateResp = $this->actingAs($superAdmin)->put(route('users.update', $newAdmin), [
            'name' => 'Promoted Business Owner',
            'username' => 'new_manager',
            'email' => 'promoted@prime.com',
            'shop_id' => $biz->id,
            'assigned_role' => 'system:business_owner',
            'status' => 'active',
        ]);
        $updateResp->assertRedirect(route('users.index'));

        $newAdmin->refresh();
        $this->assertEquals('business_owner', $newAdmin->role);
        $this->assertEquals('Promoted Business Owner', $newAdmin->name);
    }
}
