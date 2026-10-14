<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Finance\Account;
use App\Models\Finance\JournalEntry;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Purchase\InwardGatePass;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\Supplier;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterprisePurchasePipelineTest extends TestCase
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

    public function test_all_six_procurement_stage_views_render_successfully(): void
    {
        $admin = $this->shopAdmin();

        // Stage 1: Requisitions
        $this->actingAs($admin)->get('/purchases/requisitions')->assertOk();
        $this->actingAs($admin)->get('/purchases/requisitions/create')->assertOk();
        $pr = PurchaseRequisition::create([
            'shop_id' => $admin->shop_id,
            'pr_number' => 'PR-202610-0001',
            'requisition_date' => now()->format('Y-m-d'),
            'department' => 'Hardware',
            'status' => PurchaseRequisition::STATUS_APPROVED,
        ]);

        // Stage 2: Purchase Orders
        $this->actingAs($admin)->get('/purchases/orders')->assertOk();
        $this->actingAs($admin)->get('/purchases/orders/create')->assertOk()->assertSee($pr->pr_number);

        // Stage 3: Inward Gate Pass
        $this->actingAs($admin)->get('/purchases/igp')->assertOk();
        $this->actingAs($admin)->get('/purchases/igp/create')->assertOk();

        // Stage 4: GRN
        $this->actingAs($admin)->get('/purchases/grn')->assertOk();
        $this->actingAs($admin)->get('/purchases/grn/create')->assertOk();

        // Stage 5: Invoices
        $this->actingAs($admin)->get('/purchases/invoices')->assertOk();
        $this->actingAs($admin)->get('/purchases/invoices/create')->assertOk();

        // Stage 6: Returns
        $this->actingAs($admin)->get('/purchases/returns')->assertOk();
        $this->actingAs($admin)->get('/purchases/returns/create')->assertOk();
    }

    public function test_stage_1_purchase_requisition_flow(): void
    {
        $admin = $this->shopAdmin();
        $item = $this->createItem($admin, 'RAM DDR4 8GB', 'RAM-8GB', 4500, 2);

        // 1. Create PR
        $response = $this->actingAs($admin)->post('/purchases/requisitions', [
            'requisition_date' => now()->format('Y-m-d'),
            'required_by_date' => now()->addDays(3)->format('Y-m-d'),
            'priority' => 'high',
            'department' => 'IT Hardware',
            'notes' => 'Urgent replacement stock',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 20,
                    'estimated_unit_cost' => 4500,
                    'description' => 'Kingston or Corsair preferred',
                ],
            ],
        ]);

        $requisition = PurchaseRequisition::latest('id')->first();
        $this->assertNotNull($requisition);
        $response->assertRedirect('/purchases/requisitions/' . $requisition->id);
        $this->assertEquals(PurchaseRequisition::STATUS_PENDING, $requisition->status);
        $this->assertEquals(90000.00, (float) $requisition->estimated_total);

        // 2. Approve PR
        $this->actingAs($admin)->post("/purchases/requisitions/{$requisition->id}/approve")
            ->assertRedirect();
        $requisition->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_APPROVED, $requisition->status);

        // 3. Convert PR to PO (First ensure supplier exists)
        $supplier = Supplier::create([
            'shop_id' => $admin->shop_id,
            'name' => 'Mega IT Suppliers',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $convertResponse = $this->actingAs($admin)->post("/purchases/requisitions/{$requisition->id}/convert-po", [
            'supplier_id' => $supplier->id,
        ]);
        $po = PurchaseOrder::latest('id')->first();
        $this->assertNotNull($po);
        $convertResponse->assertRedirect('/purchases/orders/' . $po->id);
        $requisition->refresh();
        $this->assertEquals(PurchaseRequisition::STATUS_CONVERTED, $requisition->status);
        $this->assertEquals(90000.00, (float) $po->grand_total);
    }

    public function test_stage_3_inward_gate_pass_creation(): void
    {
        $admin = $this->shopAdmin();
        $supplier = Supplier::create([
            'shop_id' => $admin->shop_id,
            'name' => 'Al-Makkah Traders',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $item = $this->createItem($admin, 'Steel Rods', 'STL-01', 250, 10);

        $response = $this->actingAs($admin)->post('/purchases/igp', [
            'supplier_id' => $supplier->id,
            'igp_date' => now()->format('Y-m-d'),
            'received_via' => 'Road / Truck',
            'vehicle_number' => 'LES-2024',
            'driver_name' => 'Muhammad Aslam',
            'driver_phone' => '0321-9988776',
            'challan_number' => 'CH-8812',
            'bilty_number' => 'BL-991',
            'transporter_name' => 'New Khan Cargo',
            'remarks' => 'Checked at Main Gate 1',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'declared_quantity' => 500,
                    'packages_count' => 10,
                    'remarks' => 'Packed in 10 bundles',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $igp = InwardGatePass::latest('id')->first();
        $this->assertNotNull($igp);
        $response->assertRedirect('/purchases/igp/' . $igp->id);
        $this->assertDatabaseHas('inward_gate_passes', [
            'vehicle_number' => 'LES-2024',
            'driver_name' => 'Muhammad Aslam',
            'challan_number' => 'CH-8812',
        ]);
        $this->assertDatabaseHas('inward_gate_pass_items', [
            'inward_gate_pass_id' => $igp->id,
            'declared_quantity' => 500,
        ]);
    }

    public function test_stage_4_grn_with_igp_and_landed_expenses(): void
    {
        $admin = $this->shopAdmin();
        Account::ensureStandardAccountsExistForShop($admin->shop_id);

        $supplier = Supplier::create([
            'shop_id' => $admin->shop_id,
            'name' => 'National Electric Co',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $item = $this->createItem($admin, 'Copper Cable 2.5mm', 'CAB-25', 3000, 0);

        $igp = InwardGatePass::create([
            'shop_id' => $admin->shop_id,
            'supplier_id' => $supplier->id,
            'igp_number' => 'IGP-TEST-001',
            'igp_date' => now(),
            'received_via' => 'Road / Truck',
            'vehicle_number' => 'LEA-4433',
            'created_by' => $admin->id,
        ]);

        $expAccount = Account::where('shop_id', $admin->shop_id)->where('code', '5010')->first();

        $response = $this->actingAs($admin)->post('/purchases/grn', [
            'supplier_id' => $supplier->id,
            'inward_gate_pass_id' => $igp->id,
            'received_date' => now()->format('Y-m-d'),
            'manual_grn_number' => 'MGRN-5544',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'batch_number' => 'BATCH-CB-2026',
                    'manufacturing_date' => now()->subMonth()->format('Y-m-d'),
                    'expiry_date' => now()->addYears(2)->format('Y-m-d'),
                    'quantity' => 48,
                    'rejected_quantity' => 2,
                    'unit_cost' => 3000,
                ],
            ],
            'expenses' => [
                [
                    'expense_type' => 'Freight Delivery Charge',
                    'debit' => 2500,
                    'account_id' => $expAccount?->id,
                    'comments' => 'Allocated to stock',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $grn = GoodsReceivedNote::latest('id')->first();
        $this->assertNotNull($grn);
        $response->assertRedirect('/purchases/grn/' . $grn->id);

        // Check stock updated (48 accepted)
        $item->refresh();
        $this->assertEquals(48, $item->quantity);

        // Check batch created with 48 accepted quantity
        $this->assertDatabaseHas('product_batches', [
            'batch_no' => 'BATCH-CB-2026',
            'quantity' => 48,
        ]);

        // Check landed expenses saved
        $this->assertDatabaseHas('goods_received_expenses', [
            'goods_received_note_id' => $grn->id,
            'expense_type' => 'Freight Delivery Charge',
            'debit' => 2500,
        ]);
    }

    public function test_stage_5_purchase_invoice_records_ap_debt_and_journal_voucher(): void
    {
        $admin = $this->shopAdmin();
        Account::ensureStandardAccountsExistForShop($admin->shop_id);

        $supplier = Supplier::create([
            'shop_id' => $admin->shop_id,
            'name' => 'Habib Oil Mills',
            'status' => 'active',
            'opening_balance' => 0,
            'current_balance' => 0,
            'created_by' => $admin->id,
        ]);

        $item = $this->createItem($admin, 'Cooking Oil 16L Tin', 'OIL-16L', 8000, 100);
        $expAccount = Account::where('shop_id', $admin->shop_id)->where('code', '5010')->first();

        $response = $this->actingAs($admin)->post('/purchases/invoices', [
            'supplier_id' => $supplier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'invoice_type' => 'credit',
            'currency' => 'PKR',
            'supplier_invoice_no' => 'INV-HOM-902',
            'payment_terms' => 'Net 30 Days',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 10,
                    'unit_cost' => 8000,
                    'discount_amount' => 1000,
                    'tax_percent' => 0,
                ],
            ],
            'expenses' => [
                [
                    'category' => 'inventory',
                    'expense_type' => 'Unloading Labor',
                    'debit' => 500,
                    'credit' => 0,
                    'account_id' => $expAccount?->id,
                ],
            ],
            'commissions' => [
                [
                    'agent_name' => 'Farhan Broker',
                    'commission_type' => 'Brokerage',
                    'calculation_type' => 'fixed',
                    'rate' => 200,
                    'amount' => 200,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect('/purchases/invoices/' . $invoice->id);

        // Subtotal = 80000, Discount = 1000, Inventory Exp = 500, Net = 79500
        $this->assertEquals(80000.00, (float) $invoice->subtotal);
        $this->assertEquals(79500.00, (float) $invoice->grand_total);

        // Check supplier debt updated
        $supplier->refresh();
        $this->assertEquals(79500.00, (float) $supplier->current_balance);

        // Check journal entry was posted
        $journal = JournalEntry::where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertEquals(79500.00, (float) $journal->total_debit);
    }

    public function test_stage_6_purchase_return_debits_vendor_and_deducts_inventory(): void
    {
        $admin = $this->shopAdmin();
        Account::ensureStandardAccountsExistForShop($admin->shop_id);

        $supplier = Supplier::create([
            'shop_id' => $admin->shop_id,
            'name' => 'Metro Packaging',
            'status' => 'active',
            'current_balance' => 50000,
            'created_by' => $admin->id,
        ]);

        $item = $this->createItem($admin, 'Carton Boxes 5-Ply', 'BOX-5P', 120, 100);

        $response = $this->actingAs($admin)->post('/purchases/returns', [
            'supplier_id' => $supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Damaged / Defective Goods',
            'notes' => 'Wet boxes rejected during inspection',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 20,
                    'unit_cost' => 120,
                    'reason' => 'Water damage',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $return = PurchaseReturn::latest('id')->first();
        $this->assertNotNull($return);
        $response->assertRedirect('/purchases/returns/' . $return->id);

        // Total returned = 20 * 120 = 2400
        $this->assertEquals(2400.00, (float) $return->total_amount);

        // Check inventory decremented: 100 - 20 = 80
        $item->refresh();
        $this->assertEquals(80, $item->quantity);

        // Check stock out transaction logged
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $item->id,
            'transaction_type' => InventoryTransaction::TYPE_STOCK_OUT,
            'quantity' => 20,
        ]);

        // Check supplier payable balance reduced: 50000 - 2400 = 47600
        $supplier->refresh();
        $this->assertEquals(47600.00, (float) $supplier->current_balance);

        // Check journal entry recorded for debit note
        $journal = JournalEntry::where('reference_type', 'purchase_return')
            ->where('reference_id', $return->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertEquals(2400.00, (float) $journal->total_debit);
    }

    public function test_enhanced_procurement_pipeline_features_and_fixes(): void
    {
        $admin = $this->shopAdmin();
        $supplier = Supplier::create([
            'shop_id' => $admin->shop_id,
            'name' => 'Rehan Wood Supplies',
            'status' => 'active',
            'current_balance' => 0,
            'created_by' => $admin->id,
        ]);
        $item = $this->createItem($admin, 'Teak Wood Plank', 'TWP-01', 500, 10);

        // Point 1: Create PO linked to approved PR, or Direct (Point 2)
        // Testing Direct PO with tax percent and line discount & tax (Point 3)
        $poResponse = $this->actingAs($admin)->post('/purchases/orders', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->format('Y-m-d'),
            'delivery_date' => now()->addDays(5)->format('Y-m-d'),
            'station' => 'Factory Godown 1',
            'tax_percent' => 10,
            'payment_terms' => 'Net 30',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 10,
                    'unit_cost' => 500,
                    'discount_amount' => 100, // Point 12: discount defined on PO
                    'tax_percent' => 10,      // Point 13: tax % defined on PO
                ],
            ],
        ]);
        $poResponse->assertRedirect();
        $po = PurchaseOrder::where('supplier_id', $supplier->id)->latest('id')->first();
        $this->assertNotNull($po);
        $this->assertEquals(10, (float) $po->tax_percent);
        $poItem = $po->items->first();
        $this->assertEquals(100.00, (float) $poItem->discount_amount);
        $this->assertEquals(10.00, (float) $poItem->tax_percent);

        // Approve PO
        $po->update(['status' => PurchaseOrder::STATUS_APPROVED]);

        // Point 7: IGP with rejected quantity
        $igpResponse = $this->actingAs($admin)->post('/purchases/igp', [
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'igp_date' => now()->format('Y-m-d'),
            'received_via' => 'By Road / Truck',
            'vehicle_number' => 'LES-999',
            'transporter_name' => 'Fast Cargo',
            'driver_name' => 'Ali',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'declared_quantity' => 10,
                    'rejected_quantity' => 2, // Point 7: rejected quantity
                ],
            ],
        ]);
        $igpResponse->assertRedirect();
        $igp = InwardGatePass::latest('id')->first();
        $this->assertNotNull($igp);
        $igpItem = $igp->items->first();
        $this->assertEquals(2.00, (float) $igpItem->rejected_quantity);
        $this->assertEquals(8.00, (float) $igpItem->net_accepted_quantity);

        // Point 10: GRN without requiring unit_cost (it auto-fills from PO)
        $grnResponse = $this->actingAs($admin)->post('/purchases/grn', [
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po->id,
            'inward_gate_pass_id' => $igp->id,
            'received_date' => now()->format('Y-m-d'),
            'station' => 'Factory Godown 1',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'purchase_order_item_id' => $poItem->id,
                    'quantity' => 8,
                    'rejected_quantity' => 0,
                    // Note: unit_cost omitted here to verify Point 10!
                ],
            ],
        ]);
        $grnResponse->assertRedirect();
        $grn = GoodsReceivedNote::latest('id')->first();
        $this->assertNotNull($grn);
        $grnItem = $grn->items->first();
        $this->assertEquals(500.00, (float) $grnItem->unit_cost); // auto-fetched from PO!

        // Point 14 & 15: Create Purchase Invoice directly from PO (or GRN) with blank optional expenses
        $invResponse = $this->actingAs($admin)->post('/purchases/invoices', [
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po->id,
            'goods_received_note_id' => $grn->id,
            'invoice_date' => now()->format('Y-m-d'),
            'invoice_type' => 'credit',
            'currency' => 'PKR',
            'station' => 'Factory Godown 1',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'goods_received_item_id' => $grnItem->id,
                    'quantity' => 8,
                    'unit_cost' => 500,
                    'discount_amount' => 100, // auto fetched from PO
                    'tax_percent' => 10,     // auto fetched from PO
                ],
            ],
            // Sending empty default expense and commission rows to test Point 14 fix
            'expenses' => [
                ['account_id' => '', 'category' => 'inventory', 'quantity' => 1, 'rate' => 0, 'debit' => 0, 'credit' => 0],
            ],
            'commissions' => [
                ['agent_name' => '', 'rate' => 0, 'amount' => 0],
            ],
        ]);
        $invResponse->assertRedirect();
        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($supplier->id, $invoice->supplier_id);

        // Point 15: Invoice appears in invoices index
        $indexResponse = $this->actingAs($admin)->get('/purchases/invoices');
        $indexResponse->assertOk();
        $indexResponse->assertSee($invoice->invoice_number);

        // Point 17: Purchase return create view loads with invoice without crashing
        $returnCreateResponse = $this->actingAs($admin)->get('/purchases/returns/create?purchase_invoice_id=' . $invoice->id);
        $returnCreateResponse->assertOk();
        $returnCreateResponse->assertSee($invoice->invoice_number);
    }
}

