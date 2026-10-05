<?php

namespace Tests\Feature;

use App\Models\Finance\Account;
use App\Models\Finance\AccountHead;
use App\Models\Finance\Expense;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Payment;
use App\Models\Purchase\Supplier;
use App\Models\Shop;
use App\Models\User;
use App\Services\Finance\JournalEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    private function shopAdmin(): User
    {
        $shop = Shop::create(['name' => 'Flagship Store', 'code' => 'SHOP01', 'status' => 'active']);
        $admin = User::factory()->create([
            'role' => 'shop_admin',
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);

        return $admin;
    }

    public function test_can_view_chart_of_accounts_and_create_custom_account(): void
    {
        $admin = $this->shopAdmin();

        $this->actingAs($admin)->get('/finance/accounts')
            ->assertOk()
            ->assertSee('Chart of Accounts');

        $assetHead = AccountHead::where('code', '1')->first();
        $response = $this->actingAs($admin)->post('/finance/accounts', [
            'account_head_id' => $assetHead->id,
            'code' => '1005',
            'name' => 'Petty Cash Safe',
            'opening_balance' => 25000,
        ]);

        $response->assertRedirect('/finance/accounts');
        $this->assertDatabaseHas('accounts', [
            'code' => '1005',
            'name' => 'Petty Cash Safe',
            'current_balance' => 25000,
        ]);
    }

    public function test_recording_expense_posts_balanced_journal_and_adjusts_balances(): void
    {
        $admin = $this->shopAdmin();
        $shopId = $admin->shop_id;

        $cashAcc = Account::firstOrCreate(
            ['shop_id' => $shopId, 'code' => '1001'],
            ['account_head_id' => 1, 'name' => 'Cash in Hand', 'nature' => 'debit', 'opening_balance' => 50000, 'current_balance' => 50000, 'is_system' => true, 'status' => 'active']
        );

        $rentAcc = Account::firstOrCreate(
            ['shop_id' => $shopId, 'code' => '5020'],
            ['account_head_id' => 5, 'name' => 'Shop Rent Expense', 'nature' => 'debit', 'opening_balance' => 0, 'current_balance' => 0, 'is_system' => true, 'status' => 'active']
        );

        $response = $this->actingAs($admin)->post('/finance/expenses', [
            'expense_account_id' => $rentAcc->id,
            'payment_account_id' => $cashAcc->id,
            'date' => now()->format('Y-m-d'),
            'amount' => 15000,
            'category' => 'Rent',
            'description' => 'Shop rent for the current month',
        ]);

        $response->assertRedirect('/finance/expenses');

        $this->assertDatabaseHas('expenses', [
            'shop_id' => $shopId,
            'amount' => 15000,
            'description' => 'Shop rent for the current month',
        ]);

        // Cash in hand (debit normal) reduced from 50,000 to 35,000
        $this->assertSame(35000.0, (float) $cashAcc->fresh()->current_balance);
        // Rent expense (debit normal) increased to 15,000
        $this->assertSame(15000.0, (float) $rentAcc->fresh()->current_balance);

        // Verify Journal entry was created
        $this->assertDatabaseHas('journal_entries', [
            'shop_id' => $shopId,
            'reference_type' => 'expense',
            'total_debit' => 15000,
            'total_credit' => 15000,
        ]);
    }

    public function test_supplier_payment_deducts_supplier_balance_and_cash(): void
    {
        $admin = $this->shopAdmin();
        $shopId = $admin->shop_id;

        $cashAcc = Account::firstOrCreate(
            ['shop_id' => $shopId, 'code' => '1001'],
            ['account_head_id' => 1, 'name' => 'Cash in Hand', 'nature' => 'debit', 'current_balance' => 100000, 'is_system' => true, 'status' => 'active']
        );

        $apAcc = Account::firstOrCreate(
            ['shop_id' => $shopId, 'code' => '2001'],
            ['account_head_id' => 2, 'name' => 'Accounts Payable', 'nature' => 'credit', 'current_balance' => 40000, 'is_system' => true, 'status' => 'active']
        );

        $supplier = Supplier::create([
            'shop_id' => $shopId,
            'name' => 'Apex Electronics',
            'current_balance' => 40000,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post('/finance/payments', [
            'type' => 'supplier_payment',
            'supplier_id' => $supplier->id,
            'payment_account_id' => $cashAcc->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 25000,
            'payment_method' => 'cash',
            'notes' => 'Partial payment',
        ]);

        $response->assertRedirect('/finance/payments');

        // Supplier balance reduced by 25,000 (from 40,000 to 15,000)
        $this->assertSame(15000.0, (float) $supplier->fresh()->current_balance);

        // Cash reduced by 25,000
        $this->assertSame(75000.0, (float) $cashAcc->fresh()->current_balance);
    }

    public function test_financial_reports_render_correctly(): void
    {
        $admin = $this->shopAdmin();

        $this->actingAs($admin)->get('/finance/reports/profit-loss')->assertOk()->assertSee('Profit & Loss');
        $this->actingAs($admin)->get('/finance/reports/balance-sheet')->assertOk()->assertSee('Balance Sheet');
        $this->actingAs($admin)->get('/finance/reports/trial-balance')->assertOk()->assertSee('Trial Balance');
    }
}
