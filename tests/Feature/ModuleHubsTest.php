<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleHubsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $shop = Shop::firstOrCreate(['code' => 'SHOP01'], ['name' => 'Flagship Store', 'status' => 'active']);
        return User::factory()->create([
            'role' => 'shop_admin',
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);
    }

    public function test_all_module_hubs_render_for_authorized_admin(): void
    {
        $admin = $this->createAdmin();

        // 1. Purchases Hub
        $resPurchase = $this->actingAs($admin)->get('/purchases');
        $resPurchase->assertOk();
        $resPurchase->assertSee('Purchases &amp; Procurement', false);
        $resPurchase->assertSee('Suppliers');
        $resPurchase->assertSee('Requisitions (PR)');
        $resPurchase->assertSee('Purchase Orders (PO)');

        // 2. Sales Hub
        $resSales = $this->actingAs($admin)->get('/sales-hub');
        $resSales->assertOk();
        $resSales->assertSee('Sales &amp; POS Hub', false);
        $resSales->assertSee('POS Billing Terminal');
        $resSales->assertSee('Sales Invoices');
        $resSales->assertDontSee('Products &amp; Catalog', false);

        // 3. Inventory Hub
        $resInv = $this->actingAs($admin)->get('/inventory-hub');
        $resInv->assertOk();
        $resInv->assertSee('Inventory &amp; Warehouses Hub', false);
        $resInv->assertSee('Product Catalog');
        $resInv->assertSee('Stock Overview');
        $resInv->assertSee('Brands');
        $resInv->assertSee('Categories');
        $resInv->assertSee('Units of Measure');
        $resInv->assertSee('Godowns / Stores');

        // 4. Finance Hub
        $resFin = $this->actingAs($admin)->get('/finance');
        $resFin->assertOk();
        $resFin->assertSee('Finance &amp; Accounts Hub', false);
        $resFin->assertSee('Chart of Accounts');
        $resFin->assertSee('Daily Expenses');

        // 5. HR Hub
        $resHr = $this->actingAs($admin)->get('/hr');
        $resHr->assertOk();
        $resHr->assertSee('HR &amp; Payroll Hub', false);
        $resHr->assertSee('Employee Directory');
        $resHr->assertSee('Daily Attendance');

        // 6. Management Hub
        $resMgmt = $this->actingAs($admin)->get('/management');
        $resMgmt->assertOk();
        $resMgmt->assertSee('Management &amp; Settings Hub', false);
        $resMgmt->assertSee('User Accounts');
        $resMgmt->assertSee('Roles &amp; Permissions', false);
        $resMgmt->assertDontSee('Product Catalog');

        // 7. Reports Hub
        $resRep = $this->actingAs($admin)->get('/reports');
        $resRep->assertOk();
        $resRep->assertSee('Business Reports &amp; Analytics Hub', false);
        $resRep->assertSee('Sales Revenue Report');
        $resRep->assertSee('Stock Movement Report');
    }

    public function test_module_cards_are_hidden_if_user_lacks_permission(): void
    {
        $shop = Shop::firstOrCreate(['code' => 'SHOP01'], ['name' => 'Flagship Store', 'status' => 'active']);
        $restrictedUser = User::factory()->create([
            'role' => 'staff',
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);

        // Staff without any permissions -> direct access to /purchases is Forbidden
        $resPurchase = $this->actingAs($restrictedUser)->get('/purchases');
        $resPurchase->assertForbidden();

        // Give permission only for PR
        $permPr = Permission::firstOrCreate(['slug' => 'purchases.requisitions'], ['name' => 'Requisitions', 'module' => 'purchases']);
        $role = Role::create(['name' => 'Requester', 'slug' => 'requester']);
        $role->permissions()->attach($permPr->id);
        $restrictedUser->role_id = $role->id;
        $restrictedUser->save();

        $resPurchaseAfter = $this->actingAs($restrictedUser)->get('/purchases');
        $resPurchaseAfter->assertOk();
        $resPurchaseAfter->assertSee('Requisitions (PR)');
        $resPurchaseAfter->assertDontSee('Purchase Orders (PO)');
        $resPurchaseAfter->assertDontSee('Purchase Invoices (PI)');
    }

    public function test_rearranged_screens_in_menu_and_role_screen(): void
    {
        $admin = $this->createAdmin();

        // Check sidebar navigation rendering in dashboard
        $resDash = $this->actingAs($admin)->get('/dashboard');
        $resDash->assertOk();

        // 1. In Role Creation Form, Inventory & Products module card groups Products, Stock, and Catalog
        $resRole = $this->actingAs($admin)->get(route('roles.create'));
        $resRole->assertOk();
        $resRole->assertSee('Inventory &amp; Products', false);
        $resRole->assertSee('View Products');
        $resRole->assertSee('View Stock');
        $resRole->assertSee('Manage Brands &amp; Categories', false);
    }
}
