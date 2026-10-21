<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Imports\SuppliersImport;
use App\Models\Finance\Account;
use App\Models\Purchase\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Http\Controllers\Concerns\HasPagination;

class SupplierController extends Controller
{
    use HasPagination;

    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        $query = Supplier::query()
            ->with(['creator', 'shop', 'liabilityAccount'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('ntn', 'like', "%{$search}%")
                    ->orWhere('strn', 'like', "%{$search}%")
                    ->orWhere('license_number', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $suppliers = $this->paginateWithPerPage($query->latest('id'), $request, default: 10);

        $liabilityAccounts = Account::where('account_head_id', 2)
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('purchase.suppliers.index', compact('suppliers', 'search', 'status', 'shopId', 'liabilityAccounts'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        $liabilityAccounts = Account::where('account_head_id', 2)
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('purchase.suppliers.create', compact('liabilityAccounts', 'shopId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'ntn' => ['nullable', 'string', 'max:50'],
            'strn' => ['nullable', 'string', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'liability_account_id' => ['nullable', 'exists:accounts,id'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $openingBal = (float) ($validated['opening_balance'] ?? 0);

        Supplier::create([
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'company_name' => $validated['company_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'ntn' => $validated['ntn'] ?? null,
            'strn' => $validated['strn'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'liability_account_id' => $validated['liability_account_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'opening_balance' => $openingBal,
            'current_balance' => $openingBal,
            'status' => $validated['status'],
            'created_by' => $user->id,
        ]);

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier): View
    {
        $user = auth()->user();
        $shopId = $supplier->shop_id ?? ($user->isSuperAdmin() ? null : $user->shop_id);

        $liabilityAccounts = Account::where('account_head_id', 2)
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('purchase.suppliers.edit', compact('supplier', 'liabilityAccounts'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'ntn' => ['nullable', 'string', 'max:50'],
            'strn' => ['nullable', 'string', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'liability_account_id' => ['nullable', 'exists:accounts,id'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $supplier->update($validated);

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'liabilityAccount',
            'purchaseOrders' => fn ($q) => $q->latest()->take(10),
            'goodsReceivedNotes' => fn ($q) => $q->latest()->take(10),
        ]);

        return view('purchase.suppliers.show', compact('supplier'));
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->goodsReceivedNotes()->exists() || $supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Cannot delete supplier with existing purchase records. You can deactivate them instead.');
        }

        $supplier->delete();

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier deleted successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            'liability_account_id' => ['nullable', 'exists:accounts,id'],
        ]);

        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;
        $liabilityAccountId = $request->input('liability_account_id') ? (int) $request->input('liability_account_id') : null;

        $importer = new SuppliersImport($shopId, $user->id, $liabilityAccountId);
        
        try {
            Excel::import($importer, $request->file('file'));

            $msg = "Imported {$importer->importedCount} suppliers successfully.";
            if ($importer->skippedCount > 0) {
                $msg .= " Skipped {$importer->skippedCount} rows.";
            }

            return redirect()->route('purchases.suppliers.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="suppliers_import_template.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'name',
                'company_name',
                'phone',
                'email',
                'ntn',
                'strn',
                'license_number',
                'address',
                'opening_balance',
            ]);

            fputcsv($handle, [
                'Alpha Corporation',
                'Alpha Corp Pvt Ltd',
                '0300-1234567',
                'info@alphacorp.com',
                '1234567-8',
                '9876543-2',
                'LIC-2026-9001',
                'Industrial Area, Lahore',
                '25000',
            ]);

            fputcsv($handle, [
                'Beta Traders',
                'Beta Supplies',
                '0321-9876543',
                'contact@betasupplies.com',
                '8765432-1',
                '1234567-9',
                'LIC-2026-9002',
                'Wholesale Market, Karachi',
                '0',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
