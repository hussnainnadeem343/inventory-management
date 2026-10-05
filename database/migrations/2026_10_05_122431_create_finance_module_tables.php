<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Account Heads (Top-level COA Categories)
        Schema::create('account_heads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 10)->unique();
            $table->enum('nature', ['debit', 'credit']);
            $table->timestamps();
        });

        // Seed 5 standard accounting heads
        $now = now();
        DB::table('account_heads')->insert([
            ['id' => 1, 'name' => 'Assets', 'code' => '1', 'nature' => 'debit', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Liabilities', 'code' => '2', 'nature' => 'credit', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Equity', 'code' => '3', 'nature' => 'credit', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'Revenue', 'code' => '4', 'nature' => 'credit', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'Expenses', 'code' => '5', 'nature' => 'debit', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 2. Chart of Accounts (COA)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('account_head_id')->constrained('account_heads')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->enum('nature', ['debit', 'credit']);
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->boolean('is_system')->default(false);
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'code']);
            $table->index(['shop_id', 'account_head_id']);
        });

        // 3. Journal Entries (Double-entry General Ledger Master)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->string('entry_number', 50);
            $table->date('entry_date');
            $table->string('reference_type', 50)->default('manual'); // sale, purchase_grn, payment, receipt, expense, payroll, manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('total_debit', 14, 2)->default(0.00);
            $table->decimal('total_credit', 14, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'entry_number']);
            $table->index(['shop_id', 'entry_date']);
            $table->index(['reference_type', 'reference_id']);
        });

        // 4. Journal Entry Items (Debit / Credit lines)
        Schema::create('journal_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('debit', 14, 2)->default(0.00);
            $table->decimal('credit', 14, 2)->default(0.00);
            $table->string('narration', 255)->nullable();
            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_id');
        });

        // 5. Daily Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('expense_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('payment_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('voucher_no', 50);
            $table->date('date');
            $table->decimal('amount', 12, 2);
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_ref', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'voucher_no']);
            $table->index(['shop_id', 'date']);
        });

        // 6. Payments (Supplier Payments & Customer Receipts)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->enum('type', ['supplier_payment', 'customer_receipt']);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('payment_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('voucher_no', 50);
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 50)->default('cash');
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'voucher_no']);
            $table->index(['shop_id', 'payment_date']);
        });

        // Pre-seed standard system accounts for each shop (or global template)
        $standardAccounts = [
            ['code' => '1001', 'name' => 'Cash in Hand', 'head_id' => 1, 'nature' => 'debit'],
            ['code' => '1002', 'name' => 'Main Bank Account', 'head_id' => 1, 'nature' => 'debit'],
            ['code' => '1050', 'name' => 'Inventory Asset', 'head_id' => 1, 'nature' => 'debit'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'head_id' => 1, 'nature' => 'debit'],
            ['code' => '2001', 'name' => 'Accounts Payable (Vendors)', 'head_id' => 2, 'nature' => 'credit'],
            ['code' => '3001', 'name' => "Owner's Capital", 'head_id' => 3, 'nature' => 'credit'],
            ['code' => '4001', 'name' => 'Sales Revenue', 'head_id' => 4, 'nature' => 'credit'],
            ['code' => '5001', 'name' => 'Cost of Goods Sold (COGS)', 'head_id' => 5, 'nature' => 'debit'],
            ['code' => '5010', 'name' => 'Salaries & Wages Expense', 'head_id' => 5, 'nature' => 'debit'],
            ['code' => '5020', 'name' => 'Shop Rent Expense', 'head_id' => 5, 'nature' => 'debit'],
            ['code' => '5030', 'name' => 'Utilities & Electricity', 'head_id' => 5, 'nature' => 'debit'],
            ['code' => '5090', 'name' => 'General & Office Expense', 'head_id' => 5, 'nature' => 'debit'],
        ];

        $shops = DB::table('shops')->get();
        $targetShopIds = $shops->isNotEmpty() ? $shops->pluck('id')->all() : [null];

        foreach ($targetShopIds as $sId) {
            foreach ($standardAccounts as $acc) {
                DB::table('accounts')->insert([
                    'shop_id' => $sId,
                    'account_head_id' => $acc['head_id'],
                    'code' => $acc['code'],
                    'name' => $acc['name'],
                    'nature' => $acc['nature'],
                    'opening_balance' => 0.00,
                    'current_balance' => 0.00,
                    'is_system' => true,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('journal_entry_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('account_heads');
    }
};
