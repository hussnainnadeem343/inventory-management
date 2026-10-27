<?php

namespace Tests\Feature;

use App\Models\CashRegisterClosing;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterClosingTest extends TestCase
{
    use RefreshDatabase;

    private function createBusinessWithSales(): array
    {
        $business = Shop::create([
            'name' => 'Apex Auto HQ',
            'code' => 'APEX-HQ',
            'status' => 'active',
            'business_type' => 'Retail',
        ]);

        $owner = User::factory()->create([
            'name' => 'Owner Apex',
            'role' => 'business_owner',
            'shop_id' => $business->id,
            'status' => 'active',
        ]);

        return [$business, $owner];
    }

    public function test_cash_register_summary_api_returns_live_statistics(): void
    {
        [$business, $owner] = $this->createBusinessWithSales();

        // Create 2 cash sales today (3000 + 2000 = 5000), 1 card sale (1500)
        Sale::create([
            'shop_id' => $business->id,
            'invoice_no' => 'INV-001',
            'total_amount' => 3000,
            'payment_method' => 'cash',
            'created_at' => now(),
            'created_by' => $owner->id,
        ]);
        Sale::create([
            'shop_id' => $business->id,
            'invoice_no' => 'INV-002',
            'total_amount' => 2000,
            'payment_method' => 'cash',
            'created_at' => now(),
            'created_by' => $owner->id,
        ]);
        Sale::create([
            'shop_id' => $business->id,
            'invoice_no' => 'INV-003',
            'total_amount' => 1500,
            'payment_method' => 'card',
            'created_at' => now(),
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->getJson(route('reports.cash-register.summary', [
            'shop_id' => $business->id,
            'date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertJson([
            'shop_id' => $business->id,
            'total_invoices' => 3,
            'cash_sales' => 5000,
            'card_total' => 1500,
            'expected_cash' => 5000,
            'is_closed' => false,
        ]);
    }

    public function test_close_cash_register_calculates_difference_and_stores_record(): void
    {
        [$business, $owner] = $this->createBusinessWithSales();

        Sale::create([
            'shop_id' => $business->id,
            'invoice_no' => 'INV-CASH-1',
            'total_amount' => 10000,
            'payment_method' => 'cash',
            'created_at' => now(),
            'created_by' => $owner->id,
        ]);

        // Close drawer with Opening float 1,000 and counted cash 10,950 (shortage of 50)
        $response = $this->actingAs($owner)->post(route('reports.cash-register.close'), [
            'shop_id' => $business->id,
            'closing_date' => now()->format('Y-m-d'),
            'opening_float' => 1000,
            'actual_cash' => 10950,
            'notes' => 'Shortage of Rs. 50 due to change roundoff',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('cash_register_closings', [
            'shop_id' => $business->id,
            'opening_float' => 1000,
            'cash_sales' => 10000,
            'expected_cash' => 11000, // 1000 float + 10000 sales
            'actual_cash' => 10950,
            'difference' => -50,
            'status' => 'closed',
        ]);

        $closing = CashRegisterClosing::where('shop_id', $business->id)->first();
        $this->assertNotNull($closing);
        $this->assertTrue($closing->isShortage());
        $this->assertFalse($closing->isBalanced());

        // View and assert closing slip
        $slipResponse = $this->actingAs($owner)->get(route('reports.cash-register.slip', $closing));
        $slipResponse->assertOk();
        $slipResponse->assertSee('Cash Register Closing Slip');
        $slipResponse->assertSee('10,950.00'); // actual cash
        $slipResponse->assertSee('11,000.00'); // expected cash
        $slipResponse->assertSee('Shortage');
    }

    public function test_cash_register_table_renders_closing_status_and_actions(): void
    {
        [$business, $owner] = $this->createBusinessWithSales();

        Sale::create([
            'shop_id' => $business->id,
            'invoice_no' => 'INV-10',
            'total_amount' => 4500,
            'payment_method' => 'cash',
            'created_at' => now(),
            'created_by' => $owner->id,
        ]);

        // 1. Before close -> shows Open and Close Cash button
        $respOpen = $this->actingAs($owner)->get(route('reports.cash-register', ['preset' => 'today']));
        $respOpen->assertOk();
        $respOpen->assertSee('Open');
        $respOpen->assertSee('Close Cash Drawer');

        // 2. Perform close
        CashRegisterClosing::create([
            'shop_id' => $business->id,
            'user_id' => $owner->id,
            'closing_date' => now()->format('Y-m-d'),
            'closed_at' => now(),
            'opening_float' => 0,
            'cash_sales' => 4500,
            'expected_cash' => 4500,
            'actual_cash' => 4500,
            'difference' => 0,
            'card_total' => 0,
            'bank_total' => 0,
            'credit_total' => 0,
            'total_net_sales' => 4500,
            'total_invoices' => 1,
            'status' => 'closed',
        ]);

        // 3. After close -> shows Closed badge
        $respClosed = $this->actingAs($owner)->get(route('reports.cash-register', ['preset' => 'today']));
        $respClosed->assertOk();
        $respClosed->assertSee('Closed');
    }

    public function test_shop_admin_cannot_close_or_view_other_branches_cash_drawer(): void
    {
        $shop1 = Shop::create(['name' => 'Branch 1', 'code' => 'B01', 'status' => 'active']);
        $shop2 = Shop::create(['name' => 'Branch 2', 'code' => 'B02', 'status' => 'active']);

        $admin1 = User::factory()->create([
            'role' => 'shop_admin',
            'shop_id' => $shop1->id,
            'status' => 'active',
        ]);

        $closing2 = CashRegisterClosing::create([
            'shop_id' => $shop2->id,
            'user_id' => $admin1->id,
            'closing_date' => now()->format('Y-m-d'),
            'closed_at' => now(),
            'opening_float' => 0,
            'cash_sales' => 2000,
            'expected_cash' => 2000,
            'actual_cash' => 2000,
            'difference' => 0,
            'status' => 'closed',
        ]);

        // Admin 1 attempts to view slip of Branch 2 -> Forbidden
        $response = $this->actingAs($admin1)->get(route('reports.cash-register.slip', $closing2));
        $response->assertForbidden();
    }
}
