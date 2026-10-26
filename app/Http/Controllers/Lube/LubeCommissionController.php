<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use App\Models\Lube\LubeServiceRecord;
use App\Models\Lube\PitTechnician;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LubeCommissionController extends Controller
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

        $query = LubeServiceRecord::forShop($shopId)
            ->whereNotNull('pit_technician_id')
            ->where('technician_commission', '>', 0)
            ->with(['technician', 'vehicle', 'sale']);

        if ($request->filled('technician_id')) {
            $query->where('pit_technician_id', $request->technician_id);
        }

        if ($request->filled('status')) {
            $query->where('commission_status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Summary metrics
        $allShopRecords = LubeServiceRecord::forShop($shopId)->whereNotNull('pit_technician_id');
        $totalLabor = (float) $allShopRecords->sum('labor_amount');
        $totalCommissionDue = (float) $allShopRecords->sum('technician_commission');
        $totalCommissionPaid = (float) (clone $allShopRecords)->where('commission_status', 'paid')->sum('technician_commission');
        $pendingCommission = max(0, $totalCommissionDue - $totalCommissionPaid);

        $records = $query->latest('id')->paginate(20)->withQueryString();
        $technicians = PitTechnician::forShop($shopId)->orderBy('name')->get();

        return view('lube.commissions.index', compact(
            'records',
            'technicians',
            'totalLabor',
            'totalCommissionDue',
            'totalCommissionPaid',
            'pendingCommission'
        ));
    }

    public function markPaid(Request $request, LubeServiceRecord $serviceRecord): RedirectResponse
    {
        $serviceRecord->update([
            'commission_status' => 'paid',
            'commission_paid_at' => now(),
        ]);

        return back()->with('success', 'Commission marked as paid!');
    }

    public function bulkPay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'technician_id' => 'required|exists:pit_technicians,id',
        ]);

        $shopId = $this->getActiveShopId();

        $updated = LubeServiceRecord::forShop($shopId)
            ->where('pit_technician_id', $validated['technician_id'])
            ->where('commission_status', 'pending')
            ->update([
                'commission_status' => 'paid',
                'commission_paid_at' => now(),
            ]);

        return back()->with('success', "Settled {$updated} pending commissions for technician!");
    }
}
