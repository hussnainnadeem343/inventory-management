<?php

namespace App\Models\Finance;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'account_head_id',
        'code',
        'name',
        'nature',
        'opening_balance',
        'current_balance',
        'is_system',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_system' => 'boolean',
        ];
    }

    public static function ensureStandardAccountsExistForShop(?int $shopId): void
    {
        if (! $shopId) {
            return;
        }

        $count = self::where('shop_id', $shopId)->count();
        if ($count >= 10) {
            return;
        }

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

        foreach ($standardAccounts as $acc) {
            self::firstOrCreate(
                ['shop_id' => $shopId, 'code' => $acc['code']],
                [
                    'account_head_id' => $acc['head_id'],
                    'name' => $acc['name'],
                    'nature' => $acc['nature'],
                    'opening_balance' => 0.00,
                    'current_balance' => 0.00,
                    'is_system' => true,
                    'status' => 'active',
                ]
            );
        }
    }

    public function scopeForShop(Builder $query, ?int $shopId = null): Builder
    {
        $shopId = $shopId ?? auth()->user()?->shop_id;

        return $shopId ? $query->where('shop_id', $shopId) : $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(AccountHead::class, 'account_head_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }
}
