<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use App\Models\Lube\LubeServiceRecord;
use App\Models\Lube\PitTechnician;
use App\Models\Lube\Vehicle;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LubeAnalyticsController extends Controller
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
        $shop = Shop::find($shopId);

        $today = now()->startOfDay();

        // 1. Core KPIs
        $todayServicesCount = LubeServiceRecord::forShop($shopId)
            ->where('created_at', '>=', $today)
            ->count();

        $todaySalesQuery = Sale::where('shop_id', $shopId)
            ->where('created_at', '>=', $today)
            ->whereHas('items');
        $todayRevenue = (float) $todaySalesQuery->sum('total_amount');

        // Dispensed Liters Today
        $todayLitersDispensed = (float) SaleItem::where('shop_id', $shopId)
            ->where('created_at', '>=', $today)
            ->whereHas('inventoryItem', function ($q) {
                $q->where('unit', 'LITER')->orWhere('item_name', 'like', '%liter%')->orWhere('item_name', 'like', '%oil%');
            })
            ->sum('quantity');

        $activeTechsCount = PitTechnician::forShop($shopId)->active()->count();

        $overdueCount = LubeServiceRecord::forShop($shopId)
            ->where('next_due_date', '<', now()->toDateString())
            ->count();

        // 2. Oil Grade Distribution
        $gradeBreakdown = LubeServiceRecord::forShop($shopId)
            ->select('oil_grade', DB::raw('count(*) as count'))
            ->groupBy('oil_grade')
            ->orderByDesc('count')
            ->take(6)
            ->get();

        // 3. Vehicle Type Breakdown
        $vehicleTypeBreakdown = Vehicle::forShop($shopId)
            ->select('vehicle_type', DB::raw('count(*) as count'))
            ->groupBy('vehicle_type')
            ->orderByDesc('count')
            ->get();

        // 4. Technician Performance
        $technicianStats = PitTechnician::forShop($shopId)
            ->withCount(['serviceRecords' => function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            }])
            ->withSum(['serviceRecords' => function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            }], 'technician_commission')
            ->orderByDesc('service_records_count')
            ->take(5)
            ->get();

        // 5. Recent Services Log
        $recentServices = LubeServiceRecord::forShop($shopId)
            ->with(['vehicle', 'technician', 'sale'])
            ->latest('id')
            ->take(10)
            ->get();

        return view('lube.analytics', compact(
            'shop',
            'todayServicesCount',
            'todayRevenue',
            'todayLitersDispensed',
            'activeTechsCount',
            'overdueCount',
            'gradeBreakdown',
            'vehicleTypeBreakdown',
            'technicianStats',
            'recentServices'
        ));
    }
}
