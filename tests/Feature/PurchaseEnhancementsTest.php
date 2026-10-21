<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Finance\Account;
use App\Models\Finance\AccountHead;
use App\Models\InventoryItem;
use App\Models\Purchase\GoodsReceivedExpense;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Purchase\InwardGatePass;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Purchase\PurchaseTransportMode;
use App\Models\Purchase\Supplier;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Models\Permission;
use Tests\TestCase;

class PurchaseEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private function shopAdmin(): User
    {
        $shop = Shop::firstOrCreate(['code' => 'SHOP01'], ['name' => 'Flagship Store', 'status' => 'active']);
        return User::factory()->create([
            'role' => 'shop_admin',
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);
    }

    private function createItem(User $admin, string $name, string $sku, float $price = 1000, float $qty = 0): InventoryItem
    {
        $brand = Brand::firstOrCreate(['name' => 'Default Brand'], ['status' => 'active', 'created_by' => $admin->id]);
        $category = Category::firstOrCreate(['name' => 'Default Category'], ['status' => 'active', 'created_by' => $admin->id]);

        return InventoryItem::create([
            'shop_id' => $admin->shop_id,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'item_name' => $name,
            'sku' => $sku,
            'quantity' => $qty,
            'unit' => 'PCS',
            'purchase_price' => $price,
            'selling_price' => $price * 1.2,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
    }

    private function createLiabilityAccount(User $admin, string $name = 'Accounts Payable'): Account
    {
        $head = AccountHead::firstOrCreate(['id' => 2], ['name' => 'Liabilities', 'code' => '2000']);
        return Account::create([
            'shop_id' => $admin->shop_id,
            'account_head_id' => $head->id,
            'name' => $name,
            'code' => '2101',
            'nature' => 'credit',
            'status' => 'active',
        ]);
    }

    private function createExpenseAccount(User $admin, string $name = 'Freight & Carriage Inward'): Account
    {
        $head = AccountHead::firstOrCreate(['id' => 5], ['name' => 'Expenses', 'code' => '5000']);
        return Account::create([
            'shop_id' => $admin->shop_id,
            'account_head_id' => $head->id,
            'name' => $name,
            'code' => '5101',
            'nature' => 'debit',
            'status' => 'active',
        ]);
    }

    public function test_supplier_enhancements_ntn_strn_licence_and_liability_account(): void
    {
        $admin = $this->shopAdmin();
        $liabilityAccount = $this->createLiabilityAccount($admin);

        // Test create supplier with tax and liability account fields
        $response = $this->actingAs($admin)->post('/purchases/suppliers', [
            'name' => 'Global Logistics & Supplies',
            'company_name' => 'Global Tech Corp',
            'email' => 'vendor@global.com',
            'phone' => '03001234567',
            'ntn' => 'NTN-8839201',
            'strn' => 'STRN-3920192',
            'license_number' => 'LIC-77482',
            'liability_account_id' => $liabilityAccount->id,
            'opening_balance' => 0,
            'status' => 'active',
        ]);

        $response->assertRedirect('/purchases/suppliers');
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Global Logistics & Supplies',
            'ntn' => 'NTN-8839201',
            'strn' => 'STRN-3920192',
            'license_number' => 'LIC-77482',
            'liability_account_id' => $liabilityAccount->id,
        ]);

        // Test edit supplier
        $supplier = Supplier::where('name', 'Global Logistics & Supplies')->first();
        $this->actingAs($admin)->get("/purchases/suppliers/{$supplier->id}/edit")->assertOk()->assertSee('NTN-8839201');

        $this->actingAs($admin)->put("/purchases/suppliers/{$supplier->id}", [
            'name' => 'Global Logistics & Supplies Updated',
            'ntn' => 'NTN-9999999',
            'strn' => 'STRN-9999999',
            'license_number' => 'LIC-99999',
            'liability_account_id' => $liabilityAccount->id,
            'status' => 'active',
        ])->assertRedirect('/purchases/suppliers');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Global Logistics & Supplies Updated',
            'ntn' => 'NTN-9999999',
        ]);
    }

    public function test_pr_edit_and_delete_permissions_based_on_po_existence(): void
    {
        $admin = $this->shopAdmin();
        $item = $this->createItem($admin, 'Power Supply 650W', 'PSU-650');

        $pr = PurchaseRequisition::create([
            'shop_id' => $admin->shop_id,
            'pr_number' => 'PR-202610-0010',
            'requisition_date' => now()->format('Y-m-d'),
            'department' => 'Hardware',
            'priority' => 'medium',
            'status' => PurchaseRequisition::STATUS_PENDING,
        ]);
        $pr->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 5,
        ]);

        // Can edit and view edit page when no PO exists
        $this->assertTrue($pr->canBeEditedOrDeleted());
        $this->actingAs($admin)->get("/purchases/requisitions/{$pr->id}/edit")->assertOk();

        // Update PR
        $this->actingAs($admin)->put("/purchases/requisitions/{$pr->id}", [
            'requisition_date' => now()->format('Y-m-d'),
            'department' => 'Network Dept',
            'priority' => 'medium',
            'items' => [
                ['inventory_item_id' => $item->id, 'quantity' => 8],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('purchase_requisitions', [
            'id' => $pr->id,
            'department' => 'Network Dept',
        ]);

        // Now link a PO to this PR
        $supplier = Supplier::create([
            'shop_id' => $admin->shop_id,
            'name' => 'Supplier Alpha',
            'status' => 'active',
        ]);
        $po = PurchaseOrder::create([
            'shop_id' => $admin->shop_id,
            'purchase_requisition_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-202610-0010',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrder::STATUS_PENDING,
        ]);

        // Now editing or deleting should be blocked
        $this->assertFalse($pr->fresh()->canBeEditedOrDeleted());

        $this->actingAs($admin)->put("/purchases/requisitions/{$pr->id}", [
            'requisition_date' => now()->format('Y-m-d'),
            'department' => 'Should Not Change',
            'priority' => 'medium',
            'items' => [['inventory_item_id' => $item->id, 'quantity' => 10]],
        ])->assertSessionHas('error');

        $this->actingAs($admin)->delete("/purchases/requisitions/{$pr->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('purchase_requisitions', ['id' => $pr->id]);
    }

    public function test_pr_edit_and_delete_disabled_after_rejection(): void
    {
        $admin = $this->shopAdmin();
        $item = $this->createItem($admin, 'Hard Disk 2TB', 'HDD-2TB');

        $pr = PurchaseRequisition::create([
            'shop_id' => $admin->shop_id,
            'pr_number' => 'PR-202610-0099',
            'requisition_date' => now()->format('Y-m-d'),
            'department' => 'IT Dept',
            'priority' => 'high',
            'status' => PurchaseRequisition::STATUS_PENDING,
        ]);
        $pr->items()->create([
            'inventory_item_id' => $item->id,
            'quantity' => 3,
        ]);

        $this->assertTrue($pr->canBeEditedOrDeleted());

        // Reject PR
        $this->actingAs($admin)->post("/purchases/requisitions/{$pr->id}/reject", [
            'rejection_reason' => 'Budget exceeded for Q4',
        ])->assertRedirect();

        $pr->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_REJECTED, $pr->status);
        $this->assertTrue($pr->isRejected());
        $this->assertFalse($pr->canBeEditedOrDeleted());

        // Index and Show views should show disabled edit and delete
        $this->actingAs($admin)->get('/purchases/requisitions')
            ->assertOk()
            ->assertSee('Cannot edit: Requisition has been rejected');

        $this->actingAs($admin)->get("/purchases/requisitions/{$pr->id}")
            ->assertOk()
            ->assertSee('Cannot edit: Requisition has been rejected');

        // Edit page GET should be blocked with error redirect
        $this->actingAs($admin)->get("/purchases/requisitions/{$pr->id}/edit")
            ->assertSessionHas('error');

        // PUT request to update should be blocked
        $this->actingAs($admin)->put("/purchases/requisitions/{$pr->id}", [
            'requisition_date' => now()->format('Y-m-d'),
            'department' => 'IT Dept Modified',
            'priority' => 'high',
            'items' => [['inventory_item_id' => $item->id, 'quantity' => 5]],
        ])->assertSessionHas('error');

        // DELETE request should be blocked
        $this->actingAs($admin)->delete("/purchases/requisitions/{$pr->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('purchase_requisitions', ['id' => $pr->id]);
    }

    public function test_po_enhancements_approval_payment_mode_and_downstream_lock(): void
    {
        $admin = $this->shopAdmin();
        $supplier = Supplier::create([
            'shop_id' => $admin->shop_id,
            'name' => 'Supplier Beta',
            'status' => 'active',
        ]);
        $item = $this->createItem($admin, 'Motherboard B550', 'MB-B550', 15000);

        // 1. Create PO: default status is pending_approval, supports payment_mode and other terms
        $response = $this->actingAs($admin)->post('/purchases/orders', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->format('Y-m-d'),
            'payment_mode' => 'bank',
            'payment_terms' => 'Other',
            'payment_term_custom' => '60 Days Credit with LC',
            'tax_percent' => 5, // 5% overall tax ratio
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 2,
                    'unit_cost' => 15000,
                    'discount_amount' => 0,
                    'tax_percent' => 5, // 5% VAT
                ],
            ],
        ]);

        $response->assertRedirect();
        $po = PurchaseOrder::where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals(PurchaseOrder::STATUS_PENDING, $po->status);
        $this->assertEquals('bank', $po->payment_mode);
        $this->assertEquals('60 Days Credit with LC', $po->payment_term_custom);

        // Line subtotal = 30,000 + 1,500 VAT = 31,500. Overall 5% on 30,000 = 1,500. Total tax = 3,000. Grand total = 33,000.
        $this->assertEquals(33000, (float) $po->grand_total);

        // 2. Test items-json endpoint
        $jsonRes = $this->actingAs($admin)->getJson("/purchases/orders/{$po->id}/items-json");
        $jsonRes->assertOk()->assertJsonStructure(['id', 'po_number', 'supplier_id', 'items']);

        // 3. Approval action requires permission / shop_admin
        Permission::firstOrCreate(['slug' => 'purchases.orders.approve'], ['name' => 'Approve PO', 'module' => 'purchases']);
        $this->actingAs($admin)->post("/purchases/orders/{$po->id}/approve")->assertRedirect();
        $this->assertEquals(PurchaseOrder::STATUS_APPROVED, $po->fresh()->status);
        $this->assertEquals($admin->id, $po->fresh()->approved_by);

        // 4. Edit allowed before downstream documents
        $this->assertTrue($po->fresh()->canBeEditedOrDeleted());
        $this->actingAs($admin)->get("/purchases/orders/{$po->id}/edit")->assertOk();

        // 5. Downstream document created: Inward Gate Pass
        $igp = InwardGatePass::create([
            'shop_id' => $admin->shop_id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'igp_number' => 'IGP-202610-0010',
            'igp_date' => now()->format('Y-m-d'),
            'vehicle_number' => 'LES-1234',
            'status' => InwardGatePass::STATUS_PENDING,
        ]);

        // Now PO can no longer be edited or deleted
        $this->assertFalse($po->fresh()->canBeEditedOrDeleted());
        $this->actingAs($admin)->delete("/purchases/orders/{$po->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id]);
    }

    public function test_igp_enhancements_vendor_quantity_and_edit_delete(): void
    {
        $admin = $this->shopAdmin();
        $supplier = Supplier::create(['shop_id' => $admin->shop_id, 'name' => 'Vendor Gamma', 'status' => 'active']);
        $item = $this->createItem($admin, 'Monitor 24 inch', 'MON-24', 20000);

        // Create IGP with vendor_quantity and received quantity
        $response = $this->actingAs($admin)->post('/purchases/igp', [
            'supplier_id' => $supplier->id,
            'igp_date' => now()->format('Y-m-d'),
            'received_via' => 'Road / Truck',
            'vehicle_number' => 'LXZ-9988',
            'challan_number' => 'CH-8821',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'vendor_quantity' => 10,
                    'declared_quantity' => 9,
                ],
            ],
        ]);

        $response->assertRedirect();
        $igp = InwardGatePass::where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($igp);
        $this->assertDatabaseHas('inward_gate_pass_items', [
            'inward_gate_pass_id' => $igp->id,
            'vendor_quantity' => 10,
            'declared_quantity' => 9,
        ]);

        // Edit IGP
        $this->assertTrue($igp->canBeEditedOrDeleted());
        $this->actingAs($admin)->get("/purchases/igp/{$igp->id}/edit")->assertOk()->assertSee('LXZ-9988');

        $this->actingAs($admin)->put("/purchases/igp/{$igp->id}", [
            'supplier_id' => $supplier->id,
            'igp_date' => now()->format('Y-m-d'),
            'received_via' => 'Road / Truck',
            'vehicle_number' => 'LXZ-9988-EDITED',
            'challan_number' => 'CH-8821',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'vendor_quantity' => 10,
                    'declared_quantity' => 10,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('inward_gate_passes', [
            'id' => $igp->id,
            'vehicle_number' => 'LXZ-9988-EDITED',
        ]);
    }

    public function test_purchase_transport_modes_screen_and_crud(): void
    {
        $admin = $this->shopAdmin();

        // 1. Transport modes index
        $this->actingAs($admin)->get('/purchases/transport-modes')->assertOk();

        // 2. Create transport mode
        $this->actingAs($admin)->post('/purchases/transport-modes', [
            'name' => 'Air Freight Express',
            'description' => 'Direct courier via air',
        ])->assertRedirect('/purchases/transport-modes');

        $mode = PurchaseTransportMode::where('name', 'Air Freight Express')->first();
        $this->assertNotNull($mode);

        // 3. Update transport mode
        $this->actingAs($admin)->put("/purchases/transport-modes/{$mode->id}", [
            'name' => 'Air Cargo Express',
            'description' => 'Fastest delivery mode',
            'is_active' => true,
        ])->assertRedirect('/purchases/transport-modes');

        $this->assertDatabaseHas('purchase_transport_modes', [
            'id' => $mode->id,
            'name' => 'Air Cargo Express',
        ]);

        // 4. Delete transport mode
        $this->actingAs($admin)->delete("/purchases/transport-modes/{$mode->id}")->assertRedirect('/purchases/transport-modes');
        $this->assertDatabaseMissing('purchase_transport_modes', ['id' => $mode->id]);
    }

    public function test_grn_expenses_and_landed_costs_loaded_into_purchase_invoice(): void
    {
        $admin = $this->shopAdmin();
        $supplier = Supplier::create(['shop_id' => $admin->shop_id, 'name' => 'Vendor Delta', 'status' => 'active']);
        $item = $this->createItem($admin, 'Cisco Switch 24P', 'SW-24P', 25000);
        $expenseAccount = $this->createExpenseAccount($admin, 'Freight Inward');

        // 1. Create GRN with landed expense (single amount column)
        $response = $this->actingAs($admin)->post('/purchases/grn', [
            'supplier_id' => $supplier->id,
            'received_date' => now()->format('Y-m-d'),
            'received_via' => 'Road / Truck',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'total_delivered' => 4,
                    'rejected_quantity' => 0,
                    'quantity' => 4,
                    'unit_cost' => 25000,
                ],
            ],
            'expenses' => [
                [
                    'account_id' => $expenseAccount->id,
                    'expense_type' => 'Freight',
                    'comments' => 'TCS cargo charges',
                    'amount' => 3500,
                ],
            ],
        ]);

        $response->assertRedirect();
        $grn = GoodsReceivedNote::where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($grn);
        $this->assertEquals(3500, (float) $grn->landed_expenses_total);
        $this->assertDatabaseHas('goods_received_expenses', [
            'goods_received_note_id' => $grn->id,
            'account_id' => $expenseAccount->id,
            'expense_type' => 'Freight',
            'debit' => 3500,
        ]);

        // 2. Invoice creation view with GRN pre-selected must render the landed expense
        $invoiceCreateView = $this->actingAs($admin)->get("/purchases/invoices/create?goods_received_note_id={$grn->id}");
        $invoiceCreateView->assertOk()
            ->assertSee('TCS cargo charges')
            ->assertSee('3500');

        // 3. Save Purchase Invoice with loaded GRN expenses
        $piResponse = $this->actingAs($admin)->post('/purchases/invoices', [
            'supplier_id' => $supplier->id,
            'goods_received_note_id' => $grn->id,
            'invoice_date' => now()->format('Y-m-d'),
            'invoice_type' => 'credit',
            'currency' => 'PKR',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 4,
                    'unit_cost' => 25000,
                    'discount_amount' => 0,
                    'tax_percent' => 0,
                ],
            ],
            'expenses' => [
                [
                    'account_id' => $expenseAccount->id,
                    'category' => 'inventory',
                    'expense_type' => 'Freight',
                    'comments' => 'TCS cargo charges',
                    'amount' => 3500,
                ],
            ],
        ]);

        $piResponse->assertRedirect();
        $invoice = PurchaseInvoice::where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($invoice);
        // Products = 100,000 + Landed = 3,500 => Grand Total = 103,500
        $this->assertEquals(103500, (float) $invoice->grand_total);
        $this->assertEquals(3500, (float) $invoice->inventory_expenses_total);

        $this->assertDatabaseHas('purchase_invoice_expenses', [
            'purchase_invoice_id' => $invoice->id,
            'account_id' => $expenseAccount->id,
            'category' => 'inventory',
            'expense_type' => 'Freight',
            'debit' => 3500,
        ]);
    }

    public function test_purchase_module_listings_default_to_10_records_per_page(): void
    {
        $admin = $this->shopAdmin();

        // Create 15 suppliers
        for ($i = 1; $i <= 15; $i++) {
            Supplier::create([
                'shop_id' => $admin->shop_id,
                'name' => "Supplier {$i}",
                'status' => 'active',
            ]);
        }

        // Create 15 requisitions
        for ($i = 1; $i <= 15; $i++) {
            PurchaseRequisition::create([
                'shop_id' => $admin->shop_id,
                'pr_number' => sprintf('PR-TEST-%04d', $i),
                'requisition_date' => now()->format('Y-m-d'),
                'priority' => 'medium',
                'status' => PurchaseRequisition::STATUS_PENDING,
            ]);
        }

        // Test Suppliers listing defaults to 10
        $responseSuppliers = $this->actingAs($admin)->get('/purchases/suppliers');
        $responseSuppliers->assertOk();
        $responseSuppliers->assertViewHas('suppliers', function ($suppliers) {
            return $suppliers->count() === 10 && $suppliers->total() === 15 && $suppliers->perPage() === 10;
        });
        $responseSuppliers->assertSee('10 per page');
        $responseSuppliers->assertSee('20 per page');
        $responseSuppliers->assertSee('50 per page');
        $responseSuppliers->assertSee('100 per page');
        $responseSuppliers->assertSee('500 per page');

        // Test Requisitions listing defaults to 10
        $responsePr = $this->actingAs($admin)->get('/purchases/requisitions');
        $responsePr->assertOk();
        $responsePr->assertViewHas('requisitions', function ($requisitions) {
            return $requisitions->count() === 10 && $requisitions->total() === 15 && $requisitions->perPage() === 10;
        });

        // Test Orders listing defaults to 10
        $responsePo = $this->actingAs($admin)->get('/purchases/orders');
        $responsePo->assertOk();
        $responsePo->assertViewHas('orders', function ($orders) {
            return $orders->perPage() === 10;
        });
    }

    public function test_purchase_module_listings_support_custom_per_page_sizes(): void
    {
        $admin = $this->shopAdmin();

        for ($i = 1; $i <= 25; $i++) {
            Supplier::create([
                'shop_id' => $admin->shop_id,
                'name' => "Vendor {$i}",
                'status' => 'active',
            ]);
        }

        // Request 20 per page
        $res20 = $this->actingAs($admin)->get('/purchases/suppliers?per_page=20');
        $res20->assertOk();
        $res20->assertViewHas('suppliers', function ($suppliers) {
            return $suppliers->count() === 20 && $suppliers->perPage() === 20;
        });

        // Request 50 per page
        $res50 = $this->actingAs($admin)->get('/purchases/suppliers?per_page=50');
        $res50->assertOk();
        $res50->assertViewHas('suppliers', function ($suppliers) {
            return $suppliers->count() === 25 && $suppliers->perPage() === 50;
        });

        // Invalid per_page falls back to default 10
        $resInvalid = $this->actingAs($admin)->get('/purchases/suppliers?per_page=9999');
        $resInvalid->assertOk();
        $resInvalid->assertViewHas('suppliers', function ($suppliers) {
            return $suppliers->count() === 10 && $suppliers->perPage() === 10;
        });

        // Test Invoices listing defaults to 10 and accepts custom per_page
        $resInv = $this->actingAs($admin)->get('/purchases/invoices?per_page=50');
        $resInv->assertOk();
        $resInv->assertViewHas('invoices', function ($invoices) {
            return $invoices->perPage() === 50;
        });

        // Test Returns listing defaults to 10
        $resRet = $this->actingAs($admin)->get('/purchases/returns');
        $resRet->assertOk();
        $resRet->assertViewHas('returns', function ($returns) {
            return $returns->perPage() === 10;
        });

        // Test Transport Modes listing defaults to 10 and accepts custom per_page
        $resModes = $this->actingAs($admin)->get('/purchases/transport-modes?per_page=20');
        $resModes->assertOk();
        $resModes->assertViewHas('modes', function ($modes) {
            return $modes->perPage() === 20;
        });
    }

    public function test_purchases_hub_launchpad_renders_successfully(): void
    {
        $admin = $this->shopAdmin();

        $response = $this->actingAs($admin)->get('/purchases');
        $response->assertOk();
        $response->assertSee('Purchases &amp; Procurement', false);
        $response->assertSee('Suppliers');
        $response->assertSee('Requisitions (PR)');
        $response->assertSee('Purchase Orders (PO)');
        $response->assertSee('Gate Pass (IGP)');
        $response->assertSee('Stock Inward (GRN)');
        $response->assertSee('Purchase Invoices (PI)');
        $response->assertSee('Purchase Returns');
        $response->assertSee('Receive Via Modes');
    }
}
