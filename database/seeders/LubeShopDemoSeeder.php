<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Lube\DrumDecanting;
use App\Models\Lube\LubeServicePackage;
use App\Models\Lube\LubeServicePackageItem;
use App\Models\Lube\LubeServiceRecord;
use App\Models\Lube\PitTechnician;
use App\Models\Lube\Vehicle;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LubeShopDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create or Find Lube Shop
            $shop = Shop::updateOrCreate(
                ['code' => 'LUBE-01'],
                [
                    'name' => 'Apex Auto Lube & Pit Stop',
                    'business_type' => 'Lube (Oil Changing)',
                    'phone' => '+92 300 9876543',
                    'address' => 'Plot 12-A, Main Commercial Boulevard, Phase 5, DHA',
                    'status' => 'active',
                ]
            );

            // 2. Create Shop Admin User
            $admin = User::updateOrCreate(
                ['username' => 'lube_admin'],
                [
                    'shop_id' => $shop->id,
                    'name' => 'Tariq Lube Manager',
                    'email' => 'manager@apexpitstop.com',
                    'password' => Hash::make('password'),
                    'role' => 'shop_admin',
                    'status' => 'active',
                ]
            );

            // Also make sure super_admin has access or session helper can resolve
            $superAdmin = User::where('role', 'super_admin')->first();

            // 3. Brands & Categories
            $brands = [
                'Mobil 1' => Brand::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Mobil 1'], ['status' => 'active', 'created_by' => $admin->id]),
                'Shell Helix' => Brand::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Shell Helix'], ['status' => 'active', 'created_by' => $admin->id]),
                'Castrol' => Brand::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Castrol'], ['status' => 'active', 'created_by' => $admin->id]),
                'ZIC' => Brand::firstOrCreate(['shop_id' => $shop->id, 'name' => 'ZIC'], ['status' => 'active', 'created_by' => $admin->id]),
                'Guard Filters' => Brand::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Guard Filters'], ['status' => 'active', 'created_by' => $admin->id]),
                'Leppon Filters' => Brand::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Leppon Filters'], ['status' => 'active', 'created_by' => $admin->id]),
                'Service Brand' => Brand::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Pit Service'], ['status' => 'active', 'created_by' => $admin->id]),
            ];

            $categories = [
                'Engine Oils' => Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Engine Oils'], ['status' => 'active', 'created_by' => $admin->id]),
                'Oil Filters' => Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Oil Filters'], ['status' => 'active', 'created_by' => $admin->id]),
                'Air & Cabin Filters' => Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Air & Cabin Filters'], ['status' => 'active', 'created_by' => $admin->id]),
                'Transmission & Fluids' => Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Transmission & Fluids'], ['status' => 'active', 'created_by' => $admin->id]),
                'Bulk Drums' => Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Bulk Drums'], ['status' => 'active', 'created_by' => $admin->id]),
                'Labor' => Category::firstOrCreate(['shop_id' => $shop->id, 'name' => 'Labor Charges'], ['status' => 'active', 'created_by' => $admin->id]),
            ];

            // 4. Products Inventory
            $productsData = [
                ['Mobil 1 Advanced 5W-30 (Loose Liters)', 'MOBIL-5W30-LOOSE', 'Mobil 1', 'Engine Oils', 185.00, 'LITER', 1800, 2500],
                ['Shell Helix Ultra 0W-20 (Loose Liters)', 'SHELL-0W20-LOOSE', 'Shell Helix', 'Engine Oils', 120.00, 'LITER', 2100, 2850],
                ['Castrol Magnatec 10W-40 (Loose Liters)', 'CASTROL-10W40-LOOSE', 'Castrol', 'Engine Oils', 240.00, 'LITER', 1400, 1950],
                ['ZIC X7 20W-50 (4L Can)', 'ZIC-20W50-4L', 'ZIC', 'Engine Oils', 35.00, 'PCS', 3200, 4200],
                ['Total Quartz 9000 5W-40 (Loose Liters)', 'TOTAL-5W40-LOOSE', 'Castrol', 'Engine Oils', 90.00, 'LITER', 1900, 2600],
                ['ATF Dexron VI Synthetic Fluid (Liters)', 'ATF-DEX6-L', 'Mobil 1', 'Transmission & Fluids', 60.00, 'LITER', 2200, 3100],
                ['Guard Genuine Oil Filter (Corolla / Yaris)', 'FLT-GUARD-TYT', 'Guard Filters', 'Oil Filters', 45.00, 'PCS', 650, 1050],
                ['Leppon Genuine Oil Filter (Civic / City)', 'FLT-LEPPON-HND', 'Leppon Filters', 'Oil Filters', 38.00, 'PCS', 750, 1150],
                ['Suzuki Genuine Oil Filter (Alto / Swift)', 'FLT-SGP-ALTO', 'Guard Filters', 'Oil Filters', 50.00, 'PCS', 500, 850],
                ['Guard Engine Air Filter (Universal)', 'AIR-GUARD-UNI', 'Guard Filters', 'Air & Cabin Filters', 30.00, 'PCS', 900, 1400],
                ['Guard AC Cabin Filter (Civic / Corolla)', 'CAB-GUARD-GEN', 'Guard Filters', 'Air & Cabin Filters', 25.00, 'PCS', 800, 1300],
                ['Radiator Coolant Premix 50/50 (1L)', 'CLNT-PREMIX-1L', 'Mobil 1', 'Transmission & Fluids', 40.00, 'PCS', 600, 950],
                ['Mobil 1 5W-30 Bulk Steel Drum (208L)', 'DRUM-MOBIL-208', 'Mobil 1', 'Bulk Drums', 2.00, 'PCS', 260000, 350000],
                ['Shell Helix HX7 10W-40 Drum (208L)', 'DRUM-SHELL-208', 'Shell Helix', 'Bulk Drums', 1.00, 'PCS', 210000, 290000],
            ];

            $products = [];
            foreach ($productsData as [$name, $sku, $brandName, $catName, $qty, $unit, $buy, $sell]) {
                $isLoose = ($unit === 'LITER') || str_contains(strtolower($name), 'loose');
                $isDrum = str_contains(strtolower($name), 'drum') || ($catName === 'Bulk Drums');
                $productType = $isLoose ? 'loose' : ($isDrum ? 'drum' : 'standard');
                $liters = null;
                if ($isDrum) {
                    $liters = 208.00;
                } elseif ($isLoose) {
                    $liters = 1.00;
                }

                $products[$sku] = InventoryItem::updateOrCreate(
                    ['shop_id' => $shop->id, 'sku' => $sku],
                    [
                        'item_name' => $name,
                        'brand_id' => $brands[$brandName]->id,
                        'category_id' => $categories[$catName]->id,
                        'initial_quantity' => $qty,
                        'quantity' => $qty,
                        'sold_quantity' => 0,
                        'unit' => $isDrum ? 'DRUM' : $unit,
                        'product_type' => $productType,
                        'is_loose' => $isLoose,
                        'liters' => $liters,
                        'pack_size' => $liters,
                        'purchase_price' => $buy,
                        'selling_price' => $sell,
                        'supplier' => 'Direct Distributor',
                        'status' => 'active',
                        'created_by' => $admin->id,
                    ]
                );
            }

            // Generic Labor Item
            $laborItem = InventoryItem::updateOrCreate(
                ['shop_id' => $shop->id, 'sku' => 'SRV-LUBE-LABOR'],
                [
                    'item_name' => 'Pit Service & Labor Charge',
                    'brand_id' => $brands['Service Brand']->id,
                    'category_id' => $categories['Labor']->id,
                    'initial_quantity' => 0,
                    'quantity' => 0,
                    'purchase_price' => 0,
                    'selling_price' => 500,
                    'unit' => 'PCS',
                    'status' => 'active',
                    'created_by' => $admin->id,
                ]
            );

            // 5. Pit Technicians
            $techRashid = PitTechnician::updateOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Muhammad Rashid'],
                ['phone' => '0300-1112233', 'bay_number' => '1', 'commission_type' => 'fixed', 'commission_rate' => 150.00, 'status' => 'active']
            );

            $techTariq = PitTechnician::updateOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Tariq Mehmood'],
                ['phone' => '0301-2223344', 'bay_number' => '2', 'commission_type' => 'percentage', 'commission_rate' => 40.00, 'status' => 'active']
            );

            $techShahid = PitTechnician::updateOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Shahid Pit Master'],
                ['phone' => '0302-3334455', 'bay_number' => '3', 'commission_type' => 'fixed', 'commission_rate' => 200.00, 'status' => 'active']
            );

            $techImran = PitTechnician::updateOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Imran Bay Junior'],
                ['phone' => '0303-4445566', 'bay_number' => '4', 'commission_type' => 'fixed', 'commission_rate' => 100.00, 'status' => 'active']
            );

            // 5b. Labor & Pit Services Catalog
            $laborServicesList = [
                ['name' => 'Oil Change Labor Fee', 'code' => 'SRV-OIL', 'category' => 'Oil & Filter Service', 'price' => 500.00, 'description' => 'Drain old oil, replace filter, pour new oil'],
                ['name' => 'Oil Filter Replacement Labor', 'code' => 'SRV-FLT', 'category' => 'Oil & Filter Service', 'price' => 200.00, 'description' => 'Specialized filter wrench removal and gasket seal'],
                ['name' => 'Engine Tuning & Throttle Clean', 'code' => 'SRV-TUNE', 'category' => 'Engine Tuning & Throttle', 'price' => 800.00, 'description' => 'Throttle body wash and spark gap check'],
                ['name' => 'Coolant System Flush & Refill', 'code' => 'SRV-CLNT', 'category' => 'Cooling System & AC', 'price' => 400.00, 'description' => 'Radiator drain, chemical flush and new premix'],
                ['name' => 'Brake Pad Inspection & Labor', 'code' => 'SRV-BRK', 'category' => 'Brakes & Tires', 'price' => 600.00, 'description' => 'Brake caliper inspection, pad replacement labor'],
                ['name' => 'Transmission Fluid Drain & Refill', 'code' => 'SRV-ATF', 'category' => 'Transmission & Gearbox', 'price' => 700.00, 'description' => 'Automatic transmission fluid replacement'],
                ['name' => 'AC Cabin Filter Replacement Labor', 'code' => 'SRV-AC', 'category' => 'Cooling System & AC', 'price' => 250.00, 'description' => 'Glove compartment filter change'],
                ['name' => 'Comprehensive 15-Point Inspection', 'code' => 'SRV-INSP', 'category' => 'Pit Inspection', 'price' => 1000.00, 'description' => 'Full chassis, fluid, battery and suspension check'],
            ];

            foreach ($laborServicesList as $srvData) {
                \App\Models\Lube\LubeLaborService::updateOrCreate(
                    ['shop_id' => $shop->id, 'name' => $srvData['name']],
                    $srvData + ['is_active' => true]
                );
            }

            // 6. Service Packages
            $pkg1 = LubeServicePackage::updateOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Standard 5,000KM Semi-Synthetic Package'],
                [
                    'vehicle_type' => 'sedan',
                    'package_price' => 7200.00,
                    'labor_amount' => 500.00,
                    'description' => '4L Castrol 10W-40 + Guard Genuine Filter + 7-Point Pit Inspection',
                    'is_active' => true,
                ]
            );
            $pkg1->items()->delete();
            LubeServicePackageItem::create(['package_id' => $pkg1->id, 'inventory_item_id' => $products['CASTROL-10W40-LOOSE']->id, 'quantity' => 4.0]);
            LubeServicePackageItem::create(['package_id' => $pkg1->id, 'inventory_item_id' => $products['FLT-GUARD-TYT']->id, 'quantity' => 1.0]);

            $pkg2 = LubeServicePackage::updateOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Premium 10,000KM Full Synthetic Package'],
                [
                    'vehicle_type' => 'sedan',
                    'package_price' => 11500.00,
                    'labor_amount' => 600.00,
                    'description' => '4L Mobil 1 5W-30 Advanced Synthetic + Leppon Filter + Pit Inspection',
                    'is_active' => true,
                ]
            );
            $pkg2->items()->delete();
            LubeServicePackageItem::create(['package_id' => $pkg2->id, 'inventory_item_id' => $products['MOBIL-5W30-LOOSE']->id, 'quantity' => 4.0]);
            LubeServicePackageItem::create(['package_id' => $pkg2->id, 'inventory_item_id' => $products['FLT-LEPPON-HND']->id, 'quantity' => 1.0]);

            $pkg3 = LubeServicePackage::updateOrCreate(
                ['shop_id' => $shop->id, 'name' => 'SUV / Crossover Heavy Duty Package'],
                [
                    'vehicle_type' => 'suv',
                    'package_price' => 16800.00,
                    'labor_amount' => 800.00,
                    'description' => '6L Total 5W-40 + Genuine Guard Filter + Coolant Top-up',
                    'is_active' => true,
                ]
            );
            $pkg3->items()->delete();
            LubeServicePackageItem::create(['package_id' => $pkg3->id, 'inventory_item_id' => $products['TOTAL-5W40-LOOSE']->id, 'quantity' => 6.0]);
            LubeServicePackageItem::create(['package_id' => $pkg3->id, 'inventory_item_id' => $products['FLT-GUARD-TYT']->id, 'quantity' => 1.0]);

            // 7. Vehicles & Services
            // Vehicle 1: LEA-24-1234 (Civic - Ahmad Khan)
            $veh1 = Vehicle::updateOrCreate(
                ['shop_id' => $shop->id, 'plate_number' => 'LEA-24-1234'],
                [
                    'vehicle_type' => 'sedan',
                    'make' => 'Honda',
                    'model' => 'Civic RS 1.5 Turbo',
                    'year' => '2022',
                    'customer_name' => 'Ahmad Khan',
                    'customer_phone' => '0300-1234567',
                    'last_odometer' => 45000,
                    'last_service_date' => now()->subDays(5)->toDateString(),
                    'notes' => 'Prefers 5W-30 synthetic oil only',
                ]
            );

            // Vehicle 2: ICT-23-5678 (Corolla - Usman Tariq) - Serviced TODAY!
            $veh2 = Vehicle::updateOrCreate(
                ['shop_id' => $shop->id, 'plate_number' => 'ICT-23-5678'],
                [
                    'vehicle_type' => 'sedan',
                    'make' => 'Toyota',
                    'model' => 'Corolla Altis Grande 1.8',
                    'year' => '2023',
                    'customer_name' => 'Usman Tariq',
                    'customer_phone' => '0321-7654321',
                    'last_odometer' => 22000,
                    'last_service_date' => now()->toDateString(),
                    'notes' => 'New car under warranty maintenance',
                ]
            );

            // Vehicle 3: KHI-21-9988 (Alto - Bilal Sheikh) - OVERDUE
            $veh3 = Vehicle::updateOrCreate(
                ['shop_id' => $shop->id, 'plate_number' => 'KHI-21-9988'],
                [
                    'vehicle_type' => 'sedan',
                    'make' => 'Suzuki',
                    'model' => 'Alto VXL 660cc',
                    'year' => '2021',
                    'customer_name' => 'Bilal Sheikh',
                    'customer_phone' => '0333-5551234',
                    'last_odometer' => 30000,
                    'last_service_date' => now()->subDays(110)->toDateString(),
                    'notes' => 'Daily city commute car',
                ]
            );

            // Vehicle 4: BWP-22-4411 (Sportage - Dr. Farhan) - Serviced TODAY!
            $veh4 = Vehicle::updateOrCreate(
                ['shop_id' => $shop->id, 'plate_number' => 'BWP-22-4411'],
                [
                    'vehicle_type' => 'suv',
                    'make' => 'Kia',
                    'model' => 'Sportage AWD',
                    'year' => '2022',
                    'customer_name' => 'Dr. Farhan Malik',
                    'customer_phone' => '0302-8889900',
                    'last_odometer' => 38500,
                    'last_service_date' => now()->toDateString(),
                    'notes' => 'Full inspection requested on every visit',
                ]
            );

            // Vehicle 5: LHR-24-7700 (Tucson - Malik Kamran) - DUE SOON
            $veh5 = Vehicle::updateOrCreate(
                ['shop_id' => $shop->id, 'plate_number' => 'LHR-24-7700'],
                [
                    'vehicle_type' => 'suv',
                    'make' => 'Hyundai',
                    'model' => 'Tucson GLS Sport',
                    'year' => '2024',
                    'customer_name' => 'Malik Kamran',
                    'customer_phone' => '0301-4445566',
                    'last_odometer' => 12000,
                    'last_service_date' => now()->subDays(85)->toDateString(),
                    'notes' => 'Due soon for 17,000 KM maintenance',
                ]
            );

            // Vehicle 6: ISB-20-3322 (City - Hamza Ali) - OVERDUE & CONTACTED
            $veh6 = Vehicle::updateOrCreate(
                ['shop_id' => $shop->id, 'plate_number' => 'ISB-20-3322'],
                [
                    'vehicle_type' => 'sedan',
                    'make' => 'Honda',
                    'model' => 'City 1.5 Aspire',
                    'year' => '2020',
                    'customer_name' => 'Hamza Ali',
                    'customer_phone' => '0315-9988776',
                    'last_odometer' => 48000,
                    'last_service_date' => now()->subDays(125)->toDateString(),
                    'notes' => 'Follow up sent on WhatsApp',
                ]
            );

            // Helper lambda to create a complete billed service ticket
            $createTicket = function (
                Vehicle $veh,
                PitTechnician $tech,
                int $odo,
                int $interval,
                string $grade,
                int $daysAgo,
                array $itemsData,
                float $laborFee,
                string $commStatus = 'pending',
                ?string $customNotes = null
            ) use ($shop, $admin, $laborItem) {
                $serviceDate = now()->subDays($daysAgo);
                $nextDueOdo = $odo + $interval;
                $nextDueDate = (clone $serviceDate)->addDays(max(30, (int) round($interval / 50)));

                // Calculate gross total
                $subtotal = $laborFee;
                foreach ($itemsData as $it) {
                    $subtotal += ($it['qty'] * $it['price']);
                }

                // Create Sale
                $invNumber = 'INV-' . rand(10100, 99999);
                $sale = Sale::create([
                    'shop_id' => $shop->id,
                    'invoice_no' => $invNumber,
                    'customer_name' => $veh->customer_name,
                    'customer_phone' => $veh->customer_phone,
                    'total_items' => count($itemsData) + ($laborFee > 0 ? 1 : 0),
                    'subtotal' => $subtotal,
                    'items_discount_total' => 0,
                    'discount' => 0,
                    'total_amount' => $subtotal,
                    'payment_method' => 'cash',
                    'paid_amount' => $subtotal,
                    'change_amount' => 0,
                    'notes' => "Auto Lube Service for {$veh->plate_number} ({$veh->make} {$veh->model})",
                    'created_by' => $admin->id,
                    'created_at' => $serviceDate,
                    'updated_at' => $serviceDate,
                ]);

                // Create Sale Items & Inventory Transactions
                foreach ($itemsData as $it) {
                    $prod = $it['item'];
                    $qty = $it['qty'];
                    $price = $it['price'];
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'shop_id' => $shop->id,
                        'inventory_item_id' => $prod->id,
                        'quantity' => $qty,
                        'unit_cost' => $prod->purchase_price ?? 0,
                        'unit_price' => $price,
                        'discount' => 0,
                        'line_total' => $qty * $price,
                        'created_at' => $serviceDate,
                        'updated_at' => $serviceDate,
                    ]);

                    InventoryTransaction::create([
                        'shop_id' => $shop->id,
                        'inventory_item_id' => $prod->id,
                        'sale_id' => $sale->id,
                        'transaction_type' => InventoryTransaction::TYPE_SALE,
                        'quantity' => -$qty,
                        'balance_before' => $prod->quantity + $qty,
                        'balance_after' => $prod->quantity,
                        'unit_cost' => $prod->purchase_price ?? 0,
                        'unit_sale_price' => $price,
                        'notes' => "Lube POS - Vehicle {$veh->plate_number}",
                        'created_by' => $admin->id,
                        'created_at' => $serviceDate,
                        'updated_at' => $serviceDate,
                    ]);
                }

                if ($laborFee > 0) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'shop_id' => $shop->id,
                        'inventory_item_id' => $laborItem->id,
                        'quantity' => 1,
                        'unit_cost' => 0,
                        'unit_price' => $laborFee,
                        'discount' => 0,
                        'line_total' => $laborFee,
                        'created_at' => $serviceDate,
                        'updated_at' => $serviceDate,
                    ]);
                }

                $commAmount = $tech->calculateCommission($laborFee);

                LubeServiceRecord::create([
                    'shop_id' => $shop->id,
                    'sale_id' => $sale->id,
                    'vehicle_id' => $veh->id,
                    'pit_technician_id' => $tech->id,
                    'current_odometer' => $odo,
                    'interval_km' => $interval,
                    'next_due_odometer' => $nextDueOdo,
                    'next_due_date' => $nextDueDate,
                    'oil_grade' => $grade,
                    'inspection_checklist' => [
                        'oil_drained' => true,
                        'oil_filter' => true,
                        'air_filter' => true,
                        'coolant' => true,
                        'brake_fluid' => true,
                        'tire_pressure' => true,
                        'washer_fluid' => false,
                    ],
                    'labor_amount' => $laborFee,
                    'technician_commission' => $commAmount,
                    'commission_status' => $commStatus,
                    'commission_paid_at' => $commStatus === 'paid' ? $serviceDate : null,
                    'notes' => $customNotes,
                    'created_by' => $admin->id,
                    'created_at' => $serviceDate,
                    'updated_at' => $serviceDate,
                ]);
            };

            // Seed Past History for Vehicle 1 (Civic)
            $createTicket($veh1, $techRashid, 35000, 5000, '5W-30', 180, [
                ['item' => $products['MOBIL-5W30-LOOSE'], 'qty' => 4.0, 'price' => 2500],
                ['item' => $products['FLT-LEPPON-HND'], 'qty' => 1.0, 'price' => 1150],
            ], 500, 'paid');

            $createTicket($veh1, $techTariq, 40000, 5000, '5W-30', 95, [
                ['item' => $products['MOBIL-5W30-LOOSE'], 'qty' => 4.0, 'price' => 2500],
                ['item' => $products['FLT-LEPPON-HND'], 'qty' => 1.0, 'price' => 1150],
            ], 600, 'paid');

            $createTicket($veh1, $techRashid, 45000, 5000, '5W-30', 5, [
                ['item' => $products['MOBIL-5W30-LOOSE'], 'qty' => 4.0, 'price' => 2500],
                ['item' => $products['FLT-LEPPON-HND'], 'qty' => 1.0, 'price' => 1150],
            ], 500, 'pending', 'Due soon follow-up scheduled');

            // Seed TODAY Ticket 1: Vehicle 2 (Corolla) -> Tariq / Rashid
            $createTicket($veh2, $techShahid, 22000, 5000, '0W-20', 0, [
                ['item' => $products['SHELL-0W20-LOOSE'], 'qty' => 4.2, 'price' => 2850],
                ['item' => $products['FLT-GUARD-TYT'], 'qty' => 1.0, 'price' => 1050],
                ['item' => $products['CLNT-PREMIX-1L'], 'qty' => 1.0, 'price' => 950],
            ], 800, 'pending', 'New car first service at our bay');

            // Seed TODAY Ticket 2: Vehicle 4 (Sportage) -> Tariq
            $createTicket($veh4, $techTariq, 38500, 5000, '5W-40', 0, [
                ['item' => $products['TOTAL-5W40-LOOSE'], 'qty' => 5.5, 'price' => 2600],
                ['item' => $products['FLT-GUARD-TYT'], 'qty' => 1.0, 'price' => 1050],
                ['item' => $products['AIR-GUARD-UNI'], 'qty' => 1.0, 'price' => 1400],
            ], 800, 'pending', 'Full pit inspection and brake check done');

            // Seed OVERDUE Ticket: Vehicle 3 (Alto) -> Imran
            $createTicket($veh3, $techImran, 30000, 5000, '10W-40', 115, [
                ['item' => $products['CASTROL-10W40-LOOSE'], 'qty' => 2.8, 'price' => 1950],
                ['item' => $products['FLT-SGP-ALTO'], 'qty' => 1.0, 'price' => 850],
            ], 400, 'paid', 'Overdue for next service');

            // Seed DUE SOON Ticket: Vehicle 5 (Tucson) -> Shahid
            $createTicket($veh5, $techShahid, 12000, 5000, '5W-30', 88, [
                ['item' => $products['MOBIL-5W30-LOOSE'], 'qty' => 5.0, 'price' => 2500],
                ['item' => $products['FLT-GUARD-TYT'], 'qty' => 1.0, 'price' => 1050],
            ], 600, 'paid', 'Customer requested SMS reminder');

            // Seed OVERDUE & CONTACTED Ticket: Vehicle 6 (City) -> Imran
            $createTicket($veh6, $techImran, 48000, 5000, '10W-40', 130, [
                ['item' => $products['CASTROL-10W40-LOOSE'], 'qty' => 3.7, 'price' => 1950],
                ['item' => $products['FLT-LEPPON-HND'], 'qty' => 1.0, 'price' => 1150],
            ], 500, 'paid', '[Contacted on 20-Oct-2026 by Tariq Manager - Customer booking for Saturday]');

            // 8. Drum Decanting Batches
            DrumDecanting::create([
                'shop_id' => $shop->id,
                'drum_item_id' => $products['DRUM-MOBIL-208']->id,
                'loose_item_id' => $products['MOBIL-5W30-LOOSE']->id,
                'drums_used' => 1.00,
                'liters_produced' => 205.00,
                'shrinkage_liters' => 3.00,
                'cost_per_liter' => round(260000 / 205, 2),
                'notes' => 'Decanted first Mobil 1 bulk drum into Bay 1 & 2 overhead dispensing tanks.',
                'created_by' => $admin->id,
                'created_at' => now()->subDays(14),
            ]);

            DrumDecanting::create([
                'shop_id' => $shop->id,
                'drum_item_id' => $products['DRUM-SHELL-208']->id,
                'loose_item_id' => $products['CASTROL-10W40-LOOSE']->id,
                'drums_used' => 1.00,
                'liters_produced' => 204.00,
                'shrinkage_liters' => 4.00,
                'cost_per_liter' => round(210000 / 204, 2),
                'notes' => 'Decanted Shell semi-synthetic barrel into Bay 3 drum trolley.',
                'created_by' => $admin->id,
                'created_at' => now()->subDay(),
            ]);
        });
    }
}
