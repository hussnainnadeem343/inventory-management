<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        $query = Supplier::query()
            ->with(['creator', 'shop'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $suppliers = $query->latest('id')->paginate(15)->withQueryString();

        return view('purchase.suppliers.index', compact('suppliers', 'search', 'status', 'shopId'));
    }

    public function create(Request $request): View
    {
        return view('purchase.suppliers.create');
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
        return view('purchase.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $supplier->update($validated);

        return redirect()->route('purchases.suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['purchaseOrders' => fn($q) => $q->latest()->take(10), 'goodsReceivedNotes' => fn($q) => $q->latest()->take(10)]);

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
}
