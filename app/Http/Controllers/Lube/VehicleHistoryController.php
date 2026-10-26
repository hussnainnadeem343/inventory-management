<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use App\Models\Lube\Vehicle;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleHistoryController extends Controller
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

        $query = Vehicle::forShop($shopId)
            ->withCount('serviceRecords')
            ->with('latestService.technician');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        $vehicles = $query->latest('updated_at')->paginate(20)->withQueryString();

        return view('lube.vehicles.index', compact('vehicles'));
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->loadCount('serviceRecords');
        $services = $vehicle->serviceRecords()
            ->with(['technician', 'sale.items.inventoryItem', 'creator'])
            ->latest('id')
            ->get();

        return view('lube.vehicles.show', compact('vehicle', 'services'));
    }
}
