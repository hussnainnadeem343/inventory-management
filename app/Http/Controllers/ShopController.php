<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;
        $search = trim((string) $request->query('search'));

        $shops = Shop::withCount(['users', 'inventoryItems'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('business_type', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('shops.index', compact('shops', 'search', 'perPage'));
    }

    public function create(): View
    {
        return view('shops.form', ['shop' => new Shop]);
    }

    public function store(ShopRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request): void {
            $shop = Shop::create([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'business_type' => $validated['business_type'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'],
            ]);

            if ($request->filled('admin_name')) {
                User::create([
                    'shop_id' => $shop->id,
                    'name' => $validated['admin_name'],
                    'username' => $validated['admin_username'],
                    'email' => $validated['admin_email'] ?? null,
                    'password' => $validated['admin_password'],
                    'role' => 'shop_admin',
                    'status' => 'active',
                ]);
            }
        });

        return redirect()->route('shops.index')->with('success', 'Shop created successfully.');
    }

    public function edit(Shop $shop): View
    {
        return view('shops.form', compact('shop'));
    }

    public function update(ShopRequest $request, Shop $shop): RedirectResponse
    {
        $validated = $request->validated();
        $validated['code'] = strtoupper($validated['code']);

        $shop->update($validated);

        return redirect()->route('shops.index')->with('success', 'Shop updated successfully.');
    }

    public function destroy(Shop $shop): RedirectResponse
    {
        if ($shop->inventoryItems()->exists()) {
            $shop->update(['status' => 'inactive']);

            return back()->with('success', 'Shop marked as inactive because it contains existing inventory items.');
        }

        $shop->delete();

        return back()->with('success', 'Shop deleted successfully.');
    }
}
