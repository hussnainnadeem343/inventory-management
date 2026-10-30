<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Lube\LubeServicePackage;
use App\Models\Lube\LubeServiceRecord;
use App\Models\Lube\PitTechnician;
use App\Models\Lube\Vehicle;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LubePosController extends Controller
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

        $technicians = PitTechnician::forShop($shopId)->active()->orderBy('name')->get();
        
        // 1. Loose Oil Products (Active, defined price > 0, stock > 0, marked as loose)
        $looseProducts = InventoryItem::forShop($shopId)
            ->loose()
            ->where('status', 'active')
            ->whereNotNull('selling_price')
            ->where('selling_price', '>', 0)
            ->where('quantity', '>', 0)
            ->with(['brand', 'category'])
            ->orderBy('item_name')
            ->get();

        // 2. Packaged Products, Filters & Parts (Active, defined price > 0, stock > 0, EXCLUDES loose products)
        $products = InventoryItem::forShop($shopId)
            ->standard()
            ->where('status', 'active')
            ->whereNotNull('selling_price')
            ->where('selling_price', '>', 0)
            ->where('quantity', '>', 0)
            ->with(['brand', 'category'])
            ->orderBy('item_name')
            ->get();

        $packages = LubeServicePackage::forShop($shopId)
            ->active()
            ->with('items.inventoryItem')
            ->get();

        $laborServices = \App\Models\Lube\LubeLaborService::forShop($shopId)
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $viscosityGrades = [
            '0W-20', '5W-20', '5W-30', '5W-40', '10W-30', 
            '10W-40', '15W-40', '20W-50', 'ATF / Transmission', 
            'CVTF', 'Gear Oil 75W-90', 'Brake Fluid DOT4', 'Radiator Coolant'
        ];

        $vehicleTypes = [
            'sedan' => 'Car / Sedan / Hatchback',
            'suv' => 'SUV / Crossover',
            'commercial' => 'Commercial / Van / Pickup',
            'bike' => 'Motorcycle / Bike',
            'truck' => 'Heavy / Truck',
        ];

        return view('lube.pos', compact(
            'shop',
            'shopId',
            'technicians',
            'products',
            'looseProducts',
            'packages',
            'laborServices',
            'viscosityGrades',
            'vehicleTypes'
        ));
    }

    public function search(Request $request): JsonResponse
    {
        $shopId = $this->getActiveShopId();
        $term = trim($request->query('query', ''));

        if (empty($term)) {
            return response()->json(['found' => false, 'count' => 0, 'vehicles' => []]);
        }

        $termDigits = preg_replace('/[^0-9]/', '', $term);

        $vehicles = Vehicle::forShop($shopId)
            ->where(function ($q) use ($term, $termDigits) {
                $q->where('plate_number', 'like', "%{$term}%")
                  ->orWhere('customer_phone', 'like', "%{$term}%");

                if (strlen($termDigits) >= 7) {
                    $q->orWhere('customer_phone', 'like', "%{$termDigits}%");
                }
            })
            ->with(['latestService.technician'])
            ->orderByDesc('last_service_date')
            ->orderByDesc('id')
            ->get();

        if ($vehicles->isEmpty()) {
            return response()->json(['found' => false, 'count' => 0, 'vehicles' => []]);
        }

        $vehicleList = $vehicles->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'vehicle_type' => $vehicle->vehicle_type,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'customer_name' => $vehicle->customer_name,
                'customer_phone' => $vehicle->customer_phone,
                'last_odometer' => $vehicle->last_odometer,
                'last_service_date' => $vehicle->last_service_date ? $vehicle->last_service_date->format('d-M-Y') : null,
                'last_oil_grade' => $vehicle->latestService?->oil_grade,
                'notes' => $vehicle->notes,
            ];
        })->values();

        $primary = $vehicleList->first();

        return response()->json([
            'found' => true,
            'count' => $vehicleList->count(),
            'vehicle' => $primary,
            'vehicles' => $vehicleList,
        ]);
    }

    public function barcode(Request $request): JsonResponse
    {
        $shopId = $this->getActiveShopId();
        $code = trim($request->query('code', ''));

        if ($code === '') {
            return response()->json([
                'found' => false,
                'message' => 'Please provide a barcode or SKU.',
            ], 422);
        }

        // Query active inventory items for this shop matching sku or id
        $item = InventoryItem::forShop($shopId)
            ->where('status', 'active')
            ->whereNotNull('selling_price')
            ->where('selling_price', '>', 0)
            ->where(function ($q) use ($code) {
                $q->where('sku', $code)
                  ->orWhere('id', is_numeric($code) ? (int) $code : 0);
            })
            ->with(['brand', 'category'])
            ->first();

        if (!$item) {
            $item = InventoryItem::forShop($shopId)
                ->where('status', 'active')
                ->whereNotNull('selling_price')
                ->where('selling_price', '>', 0)
                ->where('sku', 'LIKE', $code)
                ->first();
        }

        if (!$item) {
            return response()->json([
                'found' => false,
                'message' => "No active product found for barcode / SKU '{$code}'.",
            ]);
        }

        return response()->json([
            'found' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->item_name,
                'sku' => $item->sku,
                'selling_price' => (float) $item->selling_price,
                'unit' => $item->unit ?? 'PCS',
                'quantity' => (float) $item->quantity,
                'is_loose' => (bool) $item->is_loose,
                'brand' => $item->brand?->name,
                'category' => $item->category?->name,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $shopId = $this->getActiveShopId();
        $shop = Shop::findOrFail($shopId);
        $user = auth()->user();

        $validated = $request->validate([
            'plate_number' => 'required|string|max:50',
            'vehicle_type' => 'required|string|max:50',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|string|max:20',
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'required|string|max:50',
            'current_odometer' => 'nullable|integer|min:0',
            'interval_km' => 'nullable|integer|min:100|max:100000',
            'oil_grade' => 'nullable|string|max:50',
            'pit_technician_id' => 'nullable|exists:pit_technicians,id',
            'inspection_checklist' => 'nullable|array',
            'items' => 'nullable|array',
            'items.*.item_id' => 'nullable|integer',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.line_total' => 'required|numeric|min:0',
            'labor_amount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,bank,credit',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($validated, $shop, $user) {
            $plateNumber = strtoupper(trim($validated['plate_number']));
            $currentOdo = !empty($validated['current_odometer']) ? (int) $validated['current_odometer'] : null;
            $intervalKm = !empty($validated['interval_km']) ? (int) $validated['interval_km'] : null;
            $nextDueOdo = ($currentOdo !== null && $intervalKm !== null) ? ($currentOdo + $intervalKm) : null;
            $nextDueDate = null;
            if ($intervalKm !== null) {
                $daysEst = max(30, (int) round($intervalKm / 50));
                $nextDueDate = now()->addDays($daysEst);
            }
            $laborAmount = (float) ($validated['labor_amount'] ?? 0);

            // 1. Upsert Vehicle Profile
            $existingVehicle = Vehicle::where('shop_id', $shop->id)->where('plate_number', $plateNumber)->first();
            $lastOdometer = $currentOdo !== null ? $currentOdo : $existingVehicle?->last_odometer;

            $vehicle = Vehicle::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'plate_number' => $plateNumber,
                ],
                [
                    'vehicle_type' => $validated['vehicle_type'],
                    'make' => trim($validated['make']),
                    'model' => trim($validated['model']),
                    'year' => $validated['year'] ?? null,
                    'customer_name' => trim($validated['customer_name']),
                    'customer_phone' => trim($validated['customer_phone']),
                    'last_odometer' => $lastOdometer,
                    'last_service_date' => now()->toDateString(),
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            // 2. Technician commission calculation
            $technicianCommission = 0;
            if (!empty($validated['pit_technician_id'])) {
                $tech = PitTechnician::forShop($shop->id)->find($validated['pit_technician_id']);
                if ($tech) {
                    $technicianCommission = $tech->calculateCommission($laborAmount);
                }
            }

            // 3. Calculate consumables totals & lines
            $grossSubtotal = 0;
            $itemsDiscountTotal = 0;
            $consumableItems = $validated['items'] ?? [];
            foreach ($consumableItems as $item) {
                $lineQty = (float) $item['quantity'];
                $linePrice = (float) $item['unit_price'];
                $lineDisc = max(0, (float) ($item['discount'] ?? 0));
                $grossSubtotal += ($lineQty * $linePrice);
                $itemsDiscountTotal += $lineDisc;
            }
            $grossSubtotal += $laborAmount;

            $billDiscount = max(0, (float) ($validated['discount'] ?? 0));
            $netTotal = max(0, $grossSubtotal - $itemsDiscountTotal - $billDiscount);

            // 4. Generate Invoice Number
            $lastSale = Sale::where('shop_id', $shop->id)->latest('id')->first();
            $nextNumber = 1001;
            if ($lastSale && preg_match('/(\d+)$/', $lastSale->invoice_no, $matches)) {
                $nextNumber = max(1001, ((int) $matches[1]) + 1);
            }
            $invoiceNo = 'INV-' . $nextNumber;
            while (Sale::where('shop_id', $shop->id)->where('invoice_no', $invoiceNo)->exists()) {
                $nextNumber++;
                $invoiceNo = 'INV-' . $nextNumber;
            }

            // 5. Create Sale
            $sale = Sale::create([
                'shop_id' => $shop->id,
                'invoice_no' => $invoiceNo,
                'customer_name' => $vehicle->customer_name,
                'customer_phone' => $vehicle->customer_phone,
                'total_items' => count($consumableItems) + ($laborAmount > 0 ? 1 : 0),
                'subtotal' => $grossSubtotal,
                'items_discount_total' => $itemsDiscountTotal,
                'discount' => $billDiscount,
                'total_amount' => $netTotal,
                'payment_method' => $validated['payment_method'],
                'paid_amount' => $netTotal,
                'change_amount' => 0,
                'notes' => "Auto Lube Service for {$vehicle->plate_number} ({$vehicle->make} {$vehicle->model})" . (!empty($validated['notes']) ? " - " . $validated['notes'] : ''),
                'created_by' => $user->id,
            ]);

            // Ensure a default generic service item exists for labor fees if needed
            $laborItem = null;
            if ($laborAmount > 0) {
                $serviceBrand = \App\Models\Brand::firstOrCreate(
                    ['shop_id' => $shop->id, 'name' => 'Service'],
                    ['status' => 'active', 'created_by' => $user->id]
                );
                $serviceCategory = \App\Models\Category::firstOrCreate(
                    ['shop_id' => $shop->id, 'name' => 'Labor & Services'],
                    ['status' => 'active', 'created_by' => $user->id]
                );

                $laborItem = InventoryItem::firstOrCreate(
                    [
                        'shop_id' => $shop->id,
                        'sku' => 'SRV-LUBE-LABOR',
                    ],
                    [
                        'item_name' => 'Pit Service & Labor Charge',
                        'brand_id' => $serviceBrand->id,
                        'category_id' => $serviceCategory->id,
                        'initial_quantity' => 0,
                        'quantity' => 0,
                        'purchase_price' => 0,
                        'selling_price' => $laborAmount,
                        'unit' => 'PCS',
                        'status' => 'active',
                        'created_by' => $user->id,
                    ]
                );
            }

            // 6. Record Line Items & Stock Reductions
            foreach ($consumableItems as $itemData) {
                $itemId = $itemData['item_id'] ?? null;
                $product = $itemId ? InventoryItem::forShop($shop->id)->find($itemId) : null;
                $qty = (float) $itemData['quantity'];
                $price = (float) $itemData['unit_price'];
                $itemDiscount = max(0, (float) ($itemData['discount'] ?? 0));
                $netLineTotal = max(0, ($qty * $price) - $itemDiscount);

                if ($product) {
                    $oldStock = (float) $product->quantity;
                    $newStock = max(0, $oldStock - $qty);
                    $product->update([
                        'quantity' => $newStock,
                        'sold_quantity' => ((float) $product->sold_quantity) + $qty,
                    ]);

                    // Inventory Transaction
                    InventoryTransaction::create([
                        'shop_id' => $shop->id,
                        'inventory_item_id' => $product->id,
                        'sale_id' => $sale->id,
                        'transaction_type' => InventoryTransaction::TYPE_SALE,
                        'quantity' => -$qty,
                        'balance_before' => $oldStock,
                        'balance_after' => $newStock,
                        'unit_cost' => $product->purchase_price ?? 0,
                        'unit_sale_price' => $price,
                        'notes' => "Lube Service POS - Vehicle {$vehicle->plate_number}",
                        'created_by' => $user->id,
                    ]);

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'shop_id' => $shop->id,
                        'inventory_item_id' => $product->id,
                        'quantity' => $qty,
                        'unit_cost' => $product->purchase_price ?? 0,
                        'unit_price' => $price,
                        'discount' => $itemDiscount,
                        'line_total' => $netLineTotal,
                    ]);
                } elseif ($laborItem) {
                    // Pit labor service item
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'shop_id' => $shop->id,
                        'inventory_item_id' => $laborItem->id,
                        'quantity' => $qty,
                        'unit_cost' => 0,
                        'unit_price' => $price,
                        'discount' => $itemDiscount,
                        'line_total' => $netLineTotal,
                    ]);
                }
            }

            // 7. Create Lube Service Record
            $record = LubeServiceRecord::create([
                'shop_id' => $shop->id,
                'sale_id' => $sale->id,
                'vehicle_id' => $vehicle->id,
                'pit_technician_id' => $validated['pit_technician_id'] ?? null,
                'current_odometer' => $currentOdo,
                'interval_km' => $intervalKm,
                'next_due_odometer' => $nextDueOdo,
                'next_due_date' => $nextDueDate,
                'oil_grade' => $validated['oil_grade'] ?? null,
                'inspection_checklist' => $validated['inspection_checklist'] ?? [],
                'labor_amount' => $laborAmount,
                'technician_commission' => $technicianCommission,
                'commission_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Service completed successfully! Invoice #{$sale->invoice_no} generated.",
                'sale_id' => $sale->id,
                'service_record_id' => $record->id,
                'invoice_no' => $sale->invoice_no,
                'plate_number' => $vehicle->plate_number,
                'next_due_odometer' => $nextDueOdo ? number_format($nextDueOdo) . ' KM' : 'N/A',
                'next_due_date' => $nextDueDate ? $nextDueDate->format('d-M-Y') : '-',
                'receipt_url' => route('lube.pos.receipt', $sale),
                'sticker_url' => route('lube.pos.sticker', $record),
            ]);
        });
    }

    public function receipt(Sale $sale): View
    {
        $sale->load(['items.inventoryItem', 'shop', 'creator']);
        $serviceRecord = LubeServiceRecord::where('sale_id', $sale->id)
            ->with(['vehicle', 'technician'])
            ->first();

        return view('lube.receipt', compact('sale', 'serviceRecord'));
    }

    public function sticker(LubeServiceRecord $serviceRecord): View
    {
        $serviceRecord->load(['vehicle', 'shop', 'technician']);

        return view('lube.sticker', compact('serviceRecord'));
    }
}
