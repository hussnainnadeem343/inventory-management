<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseTransportMode;
use App\Http\Controllers\Concerns\HasPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseTransportModeController extends Controller
{
    use HasPagination;

    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));

        $query = PurchaseTransportMode::query()->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $modes = $this->paginateWithPerPage($query->orderBy('name'), $request, default: 10);

        return view('purchase.transport_modes.index', compact('modes', 'search', 'shopId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        PurchaseTransportMode::create([
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('purchases.transport_modes.index')->with('success', 'Receive Via transport mode created successfully.');
    }

    public function update(Request $request, PurchaseTransportMode $transportMode): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $transportMode->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        return redirect()->route('purchases.transport_modes.index')->with('success', 'Receive Via transport mode updated successfully.');
    }

    public function destroy(PurchaseTransportMode $transportMode): RedirectResponse
    {
        $transportMode->delete();

        return redirect()->route('purchases.transport_modes.index')->with('success', 'Transport mode deleted successfully.');
    }
}
