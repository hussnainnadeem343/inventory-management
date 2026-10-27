<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Finance\Account;
use App\Models\Finance\Expense;
use App\Models\InventoryItem;
use App\Models\Lube\LubeServiceRecord;
use App\Models\Lube\Vehicle;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSystemTest extends TestCase
{
    use RefreshDatabase;

    private function createBusinessChain(): array
    {
        $business = Shop::create([
            'name' => 'Apex Motors HQ',
            'code' => 'APX-HQ',
            'business_type' => 'Lube (Oil Changing) Station',
            'status' => 'active',
            'max_branches' => 3,
            'max_users' => 10,
        ]);

        $branch2 = Shop::create([
            'parent_id' => $business->id,
            'name' => 'Apex Motors Gulberg',
            'code' => 'APX-GLB',
            'business_type' => 'Lube (Oil Changing) Station',
            'status' => 'active',
        ]);

        $owner = User::create([
            'shop_id' => $business->id,
            'name' => 'Apex Owner',
            'username' => 'apex_owner_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'business_owner',
            'status' => 'active',
        ]);

        $admin2 = User::create([
            'shop_id' => $branch2->id,
            'name' => 'Gulberg Branch Admin',
            'username' => 'gulberg_mgr_' . uniqid(),
            'password' => 'secret1234',
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        return [$business, $branch2, $owner, $admin2];
    }

    private function createSampleSale(Shop $shop, User $creator, float $subtotal, float $discount, string $paymentMethod, ?\DateTimeInterface $date = null): Sale
    {
        $net = max(0, $subtotal - $discount);
        $date = $date ?: now();

        $sale = Sale::create([
            'shop_id' => $shop->id,
            'invoice_no' => 'INV-' . strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Test Customer',
            'customer_phone' => '03001234567',
            'total_items' => 1,
            'subtotal' => $subtotal,
            'items_discount_total' => 0,
            'discount' => $discount,
            'total_amount' => $net,
            'payment_method' => $paymentMethod,
            'paid_amount' => $net,
            'change_amount' => 0,
            'created_by' => $creator->id,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $brand = Brand::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Castrol'], ['created_by' => $creator->id]);
        $cat = Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Lubricants'], ['created_by' => $creator->id]);

        $item = InventoryItem::firstOrCreate([
            'shop_id' => $shop->id,
            'sku' => 'SKU-' . $shop->id,
        ], [
            'item_name' => 'Castrol Magnatec 5W-30',
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'purchase_price' => $subtotal * 0.7,
            'selling_price' => $subtotal,
            'quantity' => 50,
            'initial_quantity' => 50,
            'status' => 'active',
            'created_by' => $creator->id,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'shop_id' => $shop->id,
            'inventory_item_id' => $item->id,
            'quantity' => 1,
            'unit_cost' => $item->purchase_price,
            'unit_price' => $subtotal,
            'line_total' => $subtotal,
            'discount' => 0,
        ]);

        if ($date) {
            \Illuminate\Support\Facades\DB::table('sales')->where('id', $sale->id)->update([
                'created_at' => $date,
                'updated_at' => $date,
            ]);
            \Illuminate\Support\Facades\DB::table('sale_items')->where('sale_id', $sale->id)->update([
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        return $sale->fresh();
    }

    public function test_reports_directory_and_dedicated_cash_register_payment_breakdown(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        // 3 Sales today at HQ: Cash 3000, Card 2000, Credit 1500
        $this->createSampleSale($business, $owner, 3000, 0, 'cash');
        $this->createSampleSale($business, $owner, 2000, 0, 'card');
        $this->createSampleSale($business, $owner, 1500, 0, 'credit');

        // 1. Reports Index directory renders separate standalone reports
        $responseIndex = $this->actingAs($owner)->get(route('reports.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('Business Reports &amp; Analytics Hub', false);
        $responseIndex->assertSee('Sales Revenue Report');
        $responseIndex->assertSee('Product Sales &amp; Margin Report', false);
        $responseIndex->assertSee('Cash Register &amp; Payments Report', false);
        $responseIndex->assertSee('Stock Movement Report');
        $responseIndex->assertSee('Product Expiry Alert Report');

        // 2. Dedicated Standalone Cash Register & Payments Screen
        $responseCash = $this->actingAs($owner)->get(route('reports.cash-register', ['preset' => 'today']));
        $responseCash->assertOk();
        $responseCash->assertSee('Daily Cash Register &amp; Payments Report', false);
        $responseCash->assertSee('6,500.00'); // Total Net Sales
        $responseCash->assertSee('3,000.00'); // Cash
        $responseCash->assertSee('2,000.00'); // Digital / Card
        $responseCash->assertSee('1,500.00'); // Udhaar / Credit

        // 3. Dedicated Cash Register Excel Download
        $responseExport = $this->actingAs($owner)->get(route('reports.cash-register.export', ['preset' => 'today']));
        $responseExport->assertOk();
        $responseExport->assertHeader('content-disposition');
        $this->assertStringContainsString('.xlsx', $responseExport->headers->get('content-disposition'));
    }

    public function test_sales_report_preset_filtering_for_today_yesterday_and_this_month(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        // 1. Today's sale: 5000
        $this->createSampleSale($business, $owner, 5000, 0, 'cash', now());

        // 2. Yesterday's sale: 4000
        $this->createSampleSale($business, $owner, 4000, 0, 'cash', now()->subDay());

        // A. Filter by 'today' -> only 5000
        $respToday = $this->actingAs($owner)->get(route('reports.sales', ['preset' => 'today']));
        $respToday->assertOk();
        $respToday->assertSee('5,000.00');
        $respToday->assertDontSee('9,000.00');

        // B. Filter by 'yesterday' -> only 4000
        $respYesterday = $this->actingAs($owner)->get(route('reports.sales', ['preset' => 'yesterday']));
        $respYesterday->assertOk();
        $respYesterday->assertSee('4,000.00');
        $respYesterday->assertDontSee('5,000.00');

        // C. Filter by 'this_month' -> both 5000 and 4000 combined (9000 total)
        $respMonth = $this->actingAs($owner)->get(route('reports.sales', ['preset' => 'this_month']));
        $respMonth->assertOk();
        $respMonth->assertSee('9,000.00');
    }

    public function test_business_owner_can_view_consolidated_report_and_filter_by_branch(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        // HQ sells 8,000 today
        $this->createSampleSale($business, $owner, 8000, 0, 'cash');

        // Branch 2 sells 12,000 today
        $this->createSampleSale($branch2, $admin2, 12000, 0, 'card');

        // 1. Business Owner views consolidated report (default)
        $respConsolidated = $this->actingAs($owner)->get(route('reports.sales', ['preset' => 'today']));
        $respConsolidated->assertOk();
        $respConsolidated->assertSee('20,000.00'); // 8,000 + 12,000
        $respConsolidated->assertSee('Multi-Branch Performance Leaderboard');
        $respConsolidated->assertSee('Apex Motors HQ');
        $respConsolidated->assertSee('Apex Motors Gulberg');

        // 2. Business Owner filters specifically to Branch 2
        $respBranch2 = $this->actingAs($owner)->get(route('reports.sales', ['preset' => 'today', 'shop_id' => $branch2->id]));
        $respBranch2->assertOk();
        $respBranch2->assertSee('12,000.00');
        $respBranch2->assertDontSee('20,000.00');
    }

    public function test_shop_admin_is_strictly_isolated_to_own_branch_reports(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        // HQ sells 7,000
        $this->createSampleSale($business, $owner, 7000, 0, 'cash');

        // Branch 2 sells 9,000
        $this->createSampleSale($branch2, $admin2, 9000, 0, 'cash');

        // Admin 2 (Branch 2) views sales report
        $response = $this->actingAs($admin2)->get(route('reports.sales', ['preset' => 'today']));
        $response->assertOk();
        $response->assertSee('9,000.00');
        $response->assertDontSee('7,000.00');
        $response->assertDontSee('16,000.00');
        $response->assertDontSee('Multi-Branch Performance Leaderboard');

        // If Admin 2 tries to tamper by requesting ?shop_id=HQ, controller forces Admin 2's branch
        $tamperResponse = $this->actingAs($admin2)->get(route('reports.sales', ['preset' => 'today', 'shop_id' => $business->id]));
        $tamperResponse->assertOk();
        $tamperResponse->assertSee('9,000.00');
        $tamperResponse->assertDontSee('7,000.00');
    }

    public function test_sales_report_separates_auto_lube_labor_charges_from_product_sales(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        // Sale with total 5,500 (Parts: 4,000, Labor: 1,500)
        $sale = $this->createSampleSale($business, $owner, 5500, 0, 'cash');

        $vehicle = Vehicle::create([
            'shop_id' => $business->id,
            'plate_number' => 'LEA-1234',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'customer_name' => 'Ali Khan',
        ]);

        LubeServiceRecord::create([
            'shop_id' => $business->id,
            'sale_id' => $sale->id,
            'vehicle_id' => $vehicle->id,
            'labor_amount' => 1500,
            'technician_commission' => 300,
            'commission_status' => 'pending',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get(route('reports.sales', ['preset' => 'today']));
        $response->assertOk();
        $response->assertSee('Auto Lube Service Breakdown');
        $response->assertSee('1,500.00'); // Labor charges
        $response->assertSee('4,000.00'); // Parts sales
        $response->assertSee('300.00');   // Commission
    }

    public function test_sales_report_excel_export_downloads_valid_xlsx_file(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        $this->createSampleSale($business, $owner, 6000, 0, 'cash');

        $response = $this->actingAs($owner)->get(route('reports.sales.export', ['preset' => 'today']));
        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Sales-Financial-Report-', $response->headers->get('content-disposition'));
    }

    public function test_product_sales_margin_report_screen_and_excel_export(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        $this->createSampleSale($business, $owner, 5000, 0, 'cash');

        // 1. View Standalone Screen
        $response = $this->actingAs($owner)->get(route('reports.product-sales', ['preset' => 'today']));
        $response->assertOk();
        $response->assertSee('Product Sales &amp; Profit Margin Report', false);
        $response->assertSee('Castrol Magnatec 5W-30');
        $response->assertSee('5,000.00');

        // 2. Download Excel Export
        $export = $this->actingAs($owner)->get(route('reports.product-sales.export', ['preset' => 'today']));
        $export->assertOk();
        $this->assertStringContainsString('.xlsx', $export->headers->get('content-disposition'));
        $this->assertStringContainsString('Product-Sales-Margin-Report-', $export->headers->get('content-disposition'));
    }

    public function test_stock_movement_report_screen_and_excel_export(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        $this->createSampleSale($business, $owner, 3000, 0, 'cash');

        // 1. View Standalone Screen
        $response = $this->actingAs($owner)->get(route('reports.stock', ['preset' => 'today']));
        $response->assertOk();
        $response->assertSee('Stock Movement Audit Report');

        // 2. Download Excel Export
        $export = $this->actingAs($owner)->get(route('reports.stock.export', ['preset' => 'today']));
        $export->assertOk();
        $this->assertStringContainsString('.xlsx', $export->headers->get('content-disposition'));
        $this->assertStringContainsString('Stock-Movement-Audit-Report-', $export->headers->get('content-disposition'));
    }

    public function test_product_expiry_report_screen_and_excel_export(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        // 1. View Standalone Screen
        $response = $this->actingAs($owner)->get(route('reports.expiry', ['range' => '30']));
        $response->assertOk();
        $response->assertSee('Product Expiry Alert Report');

        // 2. Download Excel Export
        $export = $this->actingAs($owner)->get(route('reports.expiry.export', ['range' => '30']));
        $export->assertOk();
        $this->assertStringContainsString('.xlsx', $export->headers->get('content-disposition'));
        $this->assertStringContainsString('Product-Expiry-Report-', $export->headers->get('content-disposition'));
    }

    public function test_expense_report_screen_and_excel_export(): void
    {
        [$business, $branch2, $owner, $admin2] = $this->createBusinessChain();

        Account::ensureStandardAccountsExistForShop($business->id);
        $cashAcc = Account::where('shop_id', $business->id)->where('code', '1001')->first();
        $expAcc = Account::where('shop_id', $business->id)->where('account_head_id', 5)->first();

        Expense::create([
            'shop_id' => $business->id,
            'expense_account_id' => $expAcc->id,
            'payment_account_id' => $cashAcc->id,
            'voucher_no' => 'EXP-2026-0001',
            'date' => now()->format('Y-m-d'),
            'amount' => 1450.00,
            'category' => 'Utilities',
            'description' => 'Office tea and electric bill advance',
            'receipt_ref' => 'REC-9921',
            'created_by' => $owner->id,
        ]);

        // 1. View Standalone Screen
        $response = $this->actingAs($owner)->get(route('reports.expenses', ['preset' => 'today']));
        $response->assertOk();
        $response->assertSee('Daily Expense Report &amp; Sheet', false);
        $response->assertSee('EXP-2026-0001');
        $response->assertSee('1,450.00');
        $response->assertSee('Utilities');

        // 2. Download Excel Export
        $export = $this->actingAs($owner)->get(route('reports.expenses.export', ['preset' => 'today']));
        $export->assertOk();
        $this->assertStringContainsString('.xlsx', $export->headers->get('content-disposition'));
        $this->assertStringContainsString('Daily-Expense-Sheet-', $export->headers->get('content-disposition'));
    }
}
