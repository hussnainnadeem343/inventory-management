<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use App\Models\Lube\LubeLaborService;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LubeLaborServiceController extends Controller
{
    protected function getActiveShopId(): ?int
    {
        $user = auth()->user();
        if ($user->shop_id) {
            return $user->shop_id;
        }

        $sessionShopId = session('dashboard_shop_id');
        if ($sessionShopId) {
            return (int) $sessionShopId;
        }

        return Shop::where('status', 'active')->first()?->id;
    }

    public function index(Request $request): View
    {
        $shopId = $this->getActiveShopId();

        $query = LubeLaborService::forShop($shopId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $services = $query->latest('id')->paginate(15)->withQueryString();

        $categories = LubeLaborService::forShop($shopId)
            ->select('category')
            ->distinct()
            ->pluck('category');

        return view('lube.services.index', compact('services', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getActiveShopId();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        LubeLaborService::create([
            'shop_id' => $shopId,
            'name' => trim($validated['name']),
            'code' => $validated['code'] ? strtoupper(trim($validated['code'])) : null,
            'category' => trim($validated['category']),
            'price' => (float) $validated['price'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Labor service created successfully!');
    }

    public function update(Request $request, LubeLaborService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $service->update([
            'name' => trim($validated['name']),
            'code' => $validated['code'] ? strtoupper(trim($validated['code'])) : null,
            'category' => trim($validated['category']),
            'price' => (float) $validated['price'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Labor service updated successfully!');
    }

    public function destroy(LubeLaborService $service): RedirectResponse
    {
        $service->delete();

        return back()->with('success', 'Labor service deleted successfully!');
    }
}
