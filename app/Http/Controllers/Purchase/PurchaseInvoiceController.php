<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\InventoryItem;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceCommission;
use App\Models\Purchase\PurchaseInvoiceExpense;
use App\Models\Purchase\PurchaseInvoiceItem;
use App\Models\Purchase\Supplier;
use App\Services\Finance\JournalEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $paymentStatus = $request->query('payment_status');

        $query = PurchaseInvoice::query()
            ->with(['supplier', 'goodsReceivedNote', 'purchaseOrder', 'creator'])
            ->forShop($shopId);

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('manual_invoice_number', 'like', "%{$search}%")
                    ->orWhere('supplier_invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->latest('id')->paginate(15)->withQueryString();

        return view('purchase.invoices.index', compact('invoices', 'paymentStatus', 'search', 'shopId'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;
        $grnId = $request->query('goods_received_note_id');

        $selectedGrn = null;
        if ($grnId) {
            $selectedGrn = GoodsReceivedNote::with(['items.inventoryItem', 'supplier', 'purchaseOrder', 'expenses.account'])
                ->forShop($shopId)
                ->find($grnId);
        }

        $suppliers = Supplier::forShop($shopId)->active()->orderBy('name')->get();
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'unit', 'purchase_price']);

        $pendingGrns = GoodsReceivedNote::forShop($shopId)
            ->where('billing_status', '!=', GoodsReceivedNote::BILLING_BILLED)
            ->latest('id')
            ->get(['id', 'grn_number', 'supplier_id', 'total_amount', 'received_date']);

        $expenseAccounts = Account::forShop($shopId)->orderBy('code')->get(['id', 'code', 'name']);

        return view('purchase.invoices.create', compact(
            'suppliers',
            'products',
            'pendingGrns',
            'selectedGrn',
            'expenseAccounts',
            'shopId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'goods_received_note_id' => ['nullable', 'exists:goods_received_notes,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'invoice_type' => ['required', 'in:credit,cash'],
            'currency' => ['required', 'string', 'max:10'],
            'station' => ['nullable', 'string', 'max:150'],
            'manual_invoice_number' => ['nullable', 'string', 'max:50'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'supplier_invoice_date' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.goods_received_item_id' => ['nullable', 'exists:goods_received_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0'],
            'expenses' => ['nullable', 'array'],
            'expenses.*.account_id' => ['nullable', 'exists:accounts,id'],
            'expenses.*.category' => ['nullable', 'in:inventory,other'],
            'expenses.*.expense_type' => ['nullable', 'string', 'max:100'],
            'expenses.*.comments' => ['nullable', 'string', 'max:255'],
            'expenses.*.quantity' => ['nullable', 'numeric'],
            'expenses.*.rate' => ['nullable', 'numeric'],
            'expenses.*.debit' => ['nullable', 'numeric'],
            'expenses.*.credit' => ['nullable', 'numeric'],
            'commissions' => ['nullable', 'array'],
            'commissions.*.agent_name' => ['nullable', 'string', 'max:150'],
            'commissions.*.commission_type' => ['nullable', 'string', 'max:100'],
            'commissions.*.calculation_type' => ['nullable', 'in:percentage,fixed'],
            'commissions.*.deduction_type' => ['nullable', 'in:deductible,payable'],
            'commissions.*.rate' => ['nullable', 'numeric'],
            'commissions.*.amount' => ['nullable', 'numeric'],
            'commissions.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $invoice = DB::transaction(function () use ($user, $shopId, $validated) {
            $prefix = 'PI-' . now()->format('Ym') . '-';
            $count = PurchaseInvoice::where('invoice_number', 'like', "{$prefix}%")->count() + 1;
            $invoiceNumber = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $totalDiscount = 0;
            $totalTax = 0;

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $rate = (float) $item['unit_cost'];
                $lineSub = $qty * $rate;
                $lineDisc = (float) ($item['discount_amount'] ?? 0);
                $taxPct = (float) ($item['tax_percent'] ?? 0);
                $lineTax = ($lineSub - $lineDisc) * ($taxPct / 100);

                $subtotal += $lineSub;
                $totalDiscount += $lineDisc;
                $totalTax += $lineTax;
            }

            $inventoryExpensesTotal = 0;
            $otherExpensesTotal = 0;

            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $exp) {
                    $cat = $exp['category'] ?? 'inventory';
                    $debit = (float) ($exp['debit'] ?? 0);
                    $rate = (float) ($exp['rate'] ?? 0);
                    $qty = (float) ($exp['quantity'] ?? 1);
                    $amount = $debit > 0 ? $debit : ($rate * $qty);

                    if ($cat === 'inventory') {
                        $inventoryExpensesTotal += $amount;
                    } else {
                        $otherExpensesTotal += $amount;
                    }
                }
            }

            $commissionTotal = 0;
            if (!empty($validated['commissions'])) {
                foreach ($validated['commissions'] as $comm) {
                    $commissionTotal += (float) ($comm['amount'] ?? 0);
                }
            }

            $grandTotal = ($subtotal - $totalDiscount + $totalTax) + $inventoryExpensesTotal + $otherExpensesTotal;

            $invoice = PurchaseInvoice::create([
                'shop_id' => $shopId,
                'supplier_id' => $validated['supplier_id'],
                'goods_received_note_id' => $validated['goods_received_note_id'] ?? null,
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'manual_invoice_number' => $validated['manual_invoice_number'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'invoice_type' => $validated['invoice_type'],
                'currency' => $validated['currency'],
                'station' => $validated['station'] ?? null,
                'supplier_invoice_no' => $validated['supplier_invoice_no'] ?? null,
                'supplier_invoice_date' => $validated['supplier_invoice_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount' => $totalTax,
                'inventory_expenses_total' => $inventoryExpensesTotal,
                'other_expenses_total' => $otherExpensesTotal,
                'commission_total' => $commissionTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => 0.00,
                'payment_status' => PurchaseInvoice::STATUS_UNPAID,
                'terms' => $validated['terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Save Items
            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $rate = (float) $item['unit_cost'];
                $lineSub = $qty * $rate;
                $lineDisc = (float) ($item['discount_amount'] ?? 0);
                $taxPct = (float) ($item['tax_percent'] ?? 0);
                $lineTax = ($lineSub - $lineDisc) * ($taxPct / 100);

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'goods_received_item_id' => $item['goods_received_item_id'] ?? null,
                    'quantity' => $qty,
                    'unit_cost' => $rate,
                    'discount_amount' => $lineDisc,
                    'tax_percent' => $taxPct,
                    'tax_amount' => $lineTax,
                    'subtotal' => ($lineSub - $lineDisc) + $lineTax,
                ]);
            }

            // Save Expenses
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $exp) {
                    if (empty($exp['expense_type']) && empty($exp['debit']) && empty($exp['rate'])) {
                        continue;
                    }
                    PurchaseInvoiceExpense::create([
                        'purchase_invoice_id' => $invoice->id,
                        'account_id' => $exp['account_id'] ?? null,
                        'category' => $exp['category'] ?? 'inventory',
                        'expense_type' => $exp['expense_type'] ?? 'Freight',
                        'comments' => $exp['comments'] ?? null,
                        'quantity' => (float) ($exp['quantity'] ?? 1),
                        'rate' => (float) ($exp['rate'] ?? 0),
                        'debit' => (float) ($exp['debit'] ?? 0),
                        'credit' => (float) ($exp['credit'] ?? 0),
                    ]);
                }
            }

            // Save Commissions
            if (!empty($validated['commissions'])) {
                foreach ($validated['commissions'] as $comm) {
                    if (empty($comm['agent_name'])) {
                        continue;
                    }
                    PurchaseInvoiceCommission::create([
                        'purchase_invoice_id' => $invoice->id,
                        'agent_name' => $comm['agent_name'],
                        'commission_type' => $comm['commission_type'] ?? 'Broker',
                        'calculation_type' => $comm['calculation_type'] ?? 'percentage',
                        'deduction_type' => $comm['deduction_type'] ?? 'deductible',
                        'invoice_net_total' => $grandTotal,
                        'rate' => (float) ($comm['rate'] ?? 0),
                        'amount' => (float) ($comm['amount'] ?? 0),
                        'remarks' => $comm['remarks'] ?? null,
                    ]);
                }
            }

            // If linked to GRN, mark GRN as fully billed
            if (!empty($validated['goods_received_note_id'])) {
                GoodsReceivedNote::where('id', $validated['goods_received_note_id'])->update([
                    'billing_status' => GoodsReceivedNote::BILLING_BILLED,
                ]);
            }

            // Update Supplier Balance if this is a standalone invoice without GRN
            if (empty($validated['goods_received_note_id'])) {
                $supplier = Supplier::find($validated['supplier_id']);
                if ($supplier) {
                    $supplier->increment('current_balance', $grandTotal);
                }
            }

            // Record GL double-entry voucher
            try {
                app(JournalEntryService::class)->recordPurchaseInvoiceEntry($invoice);
            } catch (\Throwable) {
                // Gracefully continue
            }

            return $invoice;
        });

        return redirect()->route('purchases.invoices.show', $invoice)->with('success', "Purchase Invoice {$invoice->invoice_number} created successfully.");
    }

    public function show(PurchaseInvoice $invoice): View
    {
        $invoice->load([
            'supplier',
            'goodsReceivedNote',
            'purchaseOrder',
            'creator',
            'items.inventoryItem',
            'expenses.account',
            'commissions',
            'returns',
        ]);

        return view('purchase.invoices.show', compact('invoice'));
    }
}
