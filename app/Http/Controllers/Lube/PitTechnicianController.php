<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use App\Models\Lube\PitTechnician;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PitTechnicianController extends Controller
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

        $query = PitTechnician::forShop($shopId)
            ->withCount('serviceRecords')
            ->withSum('serviceRecords', 'technician_commission');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('bay_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $technicians = $query->latest('id')->paginate(15)->withQueryString();

        return view('lube.technicians.index', compact('technicians'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $shopId = $this->getActiveShopId();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:50',
            'bay_number' => 'nullable|string|max:50',
            'commission_type' => 'required|string|in:fixed,percentage,none',
            'commission_rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $technician = PitTechnician::create([
            'shop_id' => $shopId,
            'name' => trim($validated['name']),
            'phone' => $validated['phone'] ?? null,
            'bay_number' => $validated['bay_number'] ?? null,
            'commission_type' => $validated['commission_type'],
            'commission_rate' => (float) ($validated['commission_rate'] ?? 0),
            'status' => $validated['status'] ?? 'active',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Technician registered successfully!',
                'technician' => $technician,
            ]);
        }

        return back()->with('success', 'Pit technician registered successfully!');
    }

    public function update(Request $request, PitTechnician $technician): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:50',
            'bay_number' => 'nullable|string|max:50',
            'commission_type' => 'required|string|in:fixed,percentage,none',
            'commission_rate' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:active,inactive',
        ]);

        $technician->update([
            'name' => trim($validated['name']),
            'phone' => $validated['phone'] ?? null,
            'bay_number' => $validated['bay_number'] ?? null,
            'commission_type' => $validated['commission_type'],
            'commission_rate' => (float) ($validated['commission_rate'] ?? 0),
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Technician profile updated successfully!');
    }

    public function destroy(PitTechnician $technician): RedirectResponse
    {
        $technician->delete();

        return back()->with('success', 'Pit technician removed successfully!');
    }
}
