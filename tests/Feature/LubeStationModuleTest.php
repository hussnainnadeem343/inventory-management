<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Lube\DrumDecanting;
use App\Models\Lube\LubeServicePackage;
use App\Models\Lube\LubeServiceRecord;
use App\Models\Lube\PitTechnician;
use App\Models\Lube\Vehicle;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LubeStationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $lubeShop;
    protected Shop $retailShop;
    protected User $lubeAdmin;
    protected User $retailAdmin;
    protected \App\Models\Brand $brand;
    protected \App\Models\Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->retailShop = Shop::create([
            'name' => 'Metro Retail Superstore',
            'code' => 'MTR-01',
            'business_type' => 'General Retail',
            'status' => 'active',
        ]);

        $this->lubeShop = Shop::create([
            'name' => 'Apex Quick Lube Station',
            'code' => 'APX-01',
            'business_type' => 'Lube (Oil Changing)',
            'status' => 'active',
        ]);

        $this->retailAdmin = User::create([
            'shop_id' => $this->retailShop->id,
            'name' => 'Retail Admin',
            'username' => 'retail_admin',
            'email' => 'retail@denfin.com',
            'password' => bcrypt('password'),
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        $this->lubeAdmin = User::create([
            'shop_id' => $this->lubeShop->id,
            'name' => 'Lube Admin',
            'username' => 'lube_admin',
            'email' => 'lube@denfin.com',
            'password' => bcrypt('password'),
            'role' => 'shop_admin',
            'status' => 'active',
        ]);

        $this->brand = \App\Models\Brand::create([
            'shop_id' => $this->lubeShop->id,
            'name' => 'Mobil 1',
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        $this->category = \App\Models\Category::create([
            'shop_id' => $this->lubeShop->id,
            'name' => 'Engine Oils',
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);
    }

    public function test_business_type_gating_isolates_lube_module_from_standard_retail(): void
    {
        // 1. Retail shop is NOT lube
        $this->assertFalse($this->retailShop->isLube());
        $this->assertFalse($this->retailAdmin->isLubeShop());

        // Standard Retail user should NOT see Auto Lube Station in navigation
        $response = $this->actingAs($this->retailAdmin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertDontSee('Auto Lube Station');

        // 2. Lube shop IS lube
        $this->assertTrue($this->lubeShop->isLube());
        $this->assertTrue($this->lubeAdmin->isLubeShop());

        // Lube Station user DOES see Auto Lube Station in navigation and launchpad
        $response = $this->actingAs($this->lubeAdmin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Auto Lube Station');
        $response->assertSee('card-lube');
        $response->assertSee('Lube Counter POS');
    }

    public function test_can_search_vehicles_in_lube_counter(): void
    {
        Vehicle::create([
            'shop_id' => $this->lubeShop->id,
            'plate_number' => 'LEA-24-1234',
            'vehicle_type' => 'sedan',
            'make' => 'Honda',
            'model' => 'Civic',
            'customer_name' => 'Ali Raza',
            'customer_phone' => '03001234567',
            'last_odometer' => 45000,
        ]);

        $response = $this->actingAs($this->lubeAdmin)
            ->getJson(route('lube.pos.search', ['query' => 'LEA-24-1234']));

        $response->assertStatus(200);
        $response->assertJson([
            'found' => true,
            'vehicle' => [
                'plate_number' => 'LEA-24-1234',
                'make' => 'Honda',
                'model' => 'Civic',
                'customer_name' => 'Ali Raza',
            ]
        ]);
    }

    public function test_search_by_phone_number_returns_multiple_registered_vehicles(): void
    {
        Vehicle::create([
            'shop_id' => $this->lubeShop->id,
            'plate_number' => 'LEA-24-1111',
            'vehicle_type' => 'sedan',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'customer_name' => 'Tariq Mehmood',
            'customer_phone' => '03009876543',
            'last_odometer' => 30000,
        ]);

        Vehicle::create([
            'shop_id' => $this->lubeShop->id,
            'plate_number' => 'LEA-24-2222',
            'vehicle_type' => 'suv',
            'make' => 'Honda',
            'model' => 'BR-V',
            'customer_name' => 'Tariq Mehmood',
            'customer_phone' => '03009876543',
            'last_odometer' => 55000,
        ]);

        Vehicle::create([
            'shop_id' => $this->lubeShop->id,
            'plate_number' => 'LEA-24-3333',
            'vehicle_type' => 'bike',
            'make' => 'Suzuki',
            'model' => 'GS-150',
            'customer_name' => 'Tariq Mehmood',
            'customer_phone' => '03009876543',
            'last_odometer' => 12000,
        ]);

        $response = $this->actingAs($this->lubeAdmin)
            ->getJson(route('lube.pos.search', ['query' => '0300-9876543']));

        $response->assertStatus(200);
        $response->assertJson([
            'found' => true,
            'count' => 3,
        ]);

        $data = $response->json();
        $this->assertCount(3, $data['vehicles']);
        $plates = collect($data['vehicles'])->pluck('plate_number')->all();
        $this->assertContains('LEA-24-1111', $plates);
        $this->assertContains('LEA-24-2222', $plates);
        $this->assertContains('LEA-24-3333', $plates);
    }

    public function test_lube_counter_pos_renders_simplified_lookup_card_and_multi_vehicle_elements(): void
    {
        $response = $this->actingAs($this->lubeAdmin)->get(route('lube.pos'));
        $response->assertStatus(200);
        $response->assertSee('lookupPlateInput');
        $response->assertSee('lookupPhoneInput');
        $response->assertSee('multipleVehiclesBox');
        $response->assertSee('multiVehicleSelect');
        $response->assertSee('multiVehiclesList');
        $response->assertDontSee('linear-gradient(135deg, #f0f7ff 0%, #e0e7ff 100%)');
    }

    public function test_complete_lube_counter_pos_checkout_flow(): void
    {
        // Set up technician
        $tech = PitTechnician::create([
            'shop_id' => $this->lubeShop->id,
            'name' => 'Kamran Mechanic',
            'commission_type' => 'fixed',
            'commission_rate' => 150.00,
            'status' => 'active',
        ]);

        // Set up inventory items
        $oilItem = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Mobil 1 5W-30 Loose Liters',
            'sku' => 'MOBIL-5W30-L',
            'initial_quantity' => 100,
            'quantity' => 100,
            'sold_quantity' => 0,
            'unit' => 'LITER',
            'purchase_price' => 1800,
            'selling_price' => 2400,
            'is_loose' => true,
            'product_type' => 'loose',
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        $filterItem = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Genuine Oil Filter',
            'sku' => 'FLT-CIVIC',
            'initial_quantity' => 20,
            'quantity' => 20,
            'sold_quantity' => 0,
            'unit' => 'PCS',
            'purchase_price' => 800,
            'selling_price' => 1200,
            'is_loose' => false,
            'product_type' => 'standard',
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        $payload = [
            'plate_number' => 'BWP-22-9988',
            'vehicle_type' => 'sedan',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => '2022',
            'customer_name' => 'Zubair Khan',
            'customer_phone' => '03129876543',
            'current_odometer' => 38000,
            'interval_km' => 5000,
            'oil_grade' => '5W-30',
            'pit_technician_id' => $tech->id,
            'inspection_checklist' => [
                'oil_drained' => true,
                'oil_filter' => true,
                'air_filter' => true,
                'coolant' => true,
            ],
            'items' => [
                [
                    'item_id' => $oilItem->id,
                    'name' => $oilItem->item_name,
                    'quantity' => 4.0,
                    'unit_price' => 2400,
                    'discount' => 200,
                    'line_total' => 9400,
                ],
                [
                    'item_id' => $filterItem->id,
                    'name' => $filterItem->item_name,
                    'quantity' => 1.0,
                    'unit_price' => 1200,
                    'discount' => 0,
                    'line_total' => 1200,
                ],
            ],
            'labor_amount' => 500,
            'discount' => 300,
            'payment_method' => 'cash',
            'notes' => 'Tire pressure also inspected.',
        ];

        $response = $this->actingAs($this->lubeAdmin)->postJson(route('lube.pos.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'plate_number' => 'BWP-22-9988',
            'next_due_odometer' => '43,000 KM',
        ]);

        // Verify Vehicle was created/updated
        $this->assertDatabaseHas('vehicles', [
            'shop_id' => $this->lubeShop->id,
            'plate_number' => 'BWP-22-9988',
            'last_odometer' => 38000,
        ]);

        // Verify Lube Service Record
        $this->assertDatabaseHas('lube_service_records', [
            'shop_id' => $this->lubeShop->id,
            'current_odometer' => 38000,
            'next_due_odometer' => 43000,
            'pit_technician_id' => $tech->id,
            'technician_commission' => 150.00,
            'commission_status' => 'pending',
        ]);

        // Verify Stock Deductions
        $this->assertEquals(96.0, (float) $oilItem->fresh()->quantity);
        $this->assertEquals(19.0, (float) $filterItem->fresh()->quantity);

        // Verify Sale and Item Discounts
        $record = LubeServiceRecord::latest('id')->first();
        $this->assertNotNull($record->sale);
        $this->assertEquals(200.0, (float) $record->sale->items_discount_total);
        $this->assertEquals(300.0, (float) $record->sale->discount);

        $oilSaleItem = $record->sale->items()->where('inventory_item_id', $oilItem->id)->first();
        $this->assertNotNull($oilSaleItem);
        $this->assertEquals(200.0, (float) $oilSaleItem->discount);

        // Verify Printable Invoice & Sticker routes
        $receiptRes = $this->actingAs($this->lubeAdmin)->get(route('lube.pos.receipt', $record->sale));
        $receiptRes->assertStatus(200);
        $receiptRes->assertSee('BWP-22-9988');
        $receiptRes->assertSee('43,000 KM');
        $receiptRes->assertSee('ITEM DISCOUNTS:');

        $stickerRes = $this->actingAs($this->lubeAdmin)->get(route('lube.pos.sticker', $record));
        $stickerRes->assertStatus(200);
        $stickerRes->assertSee('43,000 KM');
    }

    public function test_can_manage_lube_labor_services(): void
    {
        // 1. Create Labor Service
        $storeResponse = $this->actingAs($this->lubeAdmin)->post(route('lube.services.store'), [
            'name' => 'Brake Pad Cleaning & Inspection',
            'code' => 'BRK-CLN-01',
            'category' => 'Brake Services',
            'price' => 750,
            'description' => 'Complete front & rear brake dust cleaning and caliper inspection',
        ]);
        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHas('success');

        $this->assertDatabaseHas('lube_labor_services', [
            'shop_id' => $this->lubeShop->id,
            'name' => 'Brake Pad Cleaning & Inspection',
            'code' => 'BRK-CLN-01',
            'category' => 'Brake Services',
            'price' => 750.00,
            'is_active' => true,
        ]);

        $service = \App\Models\Lube\LubeLaborService::where('code', 'BRK-CLN-01')->first();

        // 2. Index / Search
        $indexResponse = $this->actingAs($this->lubeAdmin)->get(route('lube.services.index', ['search' => 'Brake Pad']));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Brake Pad Cleaning & Inspection');
        $indexResponse->assertSee('BRK-CLN-01');

        // 3. Update Labor Service
        $updateResponse = $this->actingAs($this->lubeAdmin)->put(route('lube.services.update', $service), [
            'name' => 'Brake Pad Cleaning & Caliper Grease',
            'code' => 'BRK-CLN-01',
            'category' => 'Brake Services',
            'price' => 900,
            'description' => 'Updated service description',
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect();
        $this->assertEquals(900.00, (float) $service->fresh()->price);
        $this->assertEquals('Brake Pad Cleaning & Caliper Grease', $service->fresh()->name);

        // 4. Delete Labor Service
        $destroyResponse = $this->actingAs($this->lubeAdmin)->delete(route('lube.services.destroy', $service));
        $destroyResponse->assertRedirect();
        $this->assertSoftDeleted('lube_labor_services', [
            'id' => $service->id,
        ]);
    }

    public function test_drum_decanting_decrements_bulk_drum_and_increments_loose_liters(): void
    {
        $drumItem = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Shell Helix HX7 10W-40 208L Drum',
            'sku' => 'SHELL-DRUM-208',
            'initial_quantity' => 2,
            'quantity' => 2,
            'unit' => 'DRUM',
            'product_type' => 'drum',
            'is_loose' => false,
            'purchase_price' => 250000,
            'selling_price' => 300000,
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        $looseItem = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Shell Helix HX7 10W-40 Loose Liters',
            'sku' => 'SHELL-10W40-LOOSE',
            'initial_quantity' => 10,
            'quantity' => 10,
            'unit' => 'LITER',
            'product_type' => 'loose',
            'is_loose' => true,
            'purchase_price' => 1200,
            'selling_price' => 1600,
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        $packagedItem = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Packaged Sealed Air Filter',
            'sku' => 'FLT-AIR-STD',
            'initial_quantity' => 15,
            'quantity' => 15,
            'unit' => 'PCS',
            'product_type' => 'standard',
            'is_loose' => false,
            'purchase_price' => 900,
            'selling_price' => 1400,
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        // Verify decanting screen only lists loose products in destination dropdown
        $decantScreen = $this->actingAs($this->lubeAdmin)->get(route('lube.decanting.index'));
        $decantScreen->assertStatus(200);
        $decantScreen->assertSee('Shell Helix HX7 10W-40 Loose Liters');
        $decantScreen->assertDontSee('Packaged Sealed Air Filter (Current:');

        $payload = [
            'drum_item_id' => $drumItem->id,
            'loose_item_id' => $looseItem->id,
            'drums_used' => 1.0,
            'liters_produced' => 205.0,
            'shrinkage_liters' => 3.0,
            'notes' => 'Decanted first drum into bay reservoir.',
        ];

        $response = $this->actingAs($this->lubeAdmin)->post(route('lube.decanting.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Drum quantity decreased by 1
        $this->assertEquals(1.0, (float) $drumItem->fresh()->quantity);

        // Loose quantity increased by 205 (from 10 to 215)
        $this->assertEquals(215.0, (float) $looseItem->fresh()->quantity);

        // Drum Decanting log created
        $this->assertDatabaseHas('drum_decantings', [
            'shop_id' => $this->lubeShop->id,
            'drum_item_id' => $drumItem->id,
            'loose_item_id' => $looseItem->id,
            'drums_used' => 1.0,
            'liters_produced' => 205.0,
        ]);
    }

    public function test_decanting_calculates_selling_price_from_drum_and_updates_available_loose_stock_to_250_liters(): void
    {
        // 1. User has a 280L drum with quantity 1
        $drum280 = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Caltex Delo 280L Bulk Drum',
            'sku' => 'DELO-280L-DRUM',
            'initial_quantity' => 1,
            'quantity' => 1,
            'unit' => 'DRUM',
            'product_type' => 'drum',
            'is_loose' => false,
            'purchase_price' => 280000.00,
            'selling_price' => 350000.00,
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        // 2. Loose product defined as 1 Liter with 0 initial stock & unpriced
        $looseOil = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Caltex Delo Loose Liters',
            'sku' => 'DELO-LOOSE-LITER',
            'initial_quantity' => 0,
            'quantity' => 0,
            'unit' => 'LITER',
            'product_type' => 'loose',
            'is_loose' => true,
            'purchase_price' => 0,
            'selling_price' => 0,
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        // 3. Decant 1 drum into 250L loose oil (30L shrinkage/sludge)
        $payload = [
            'drum_item_id' => $drum280->id,
            'loose_item_id' => $looseOil->id,
            'drums_used' => 1.0,
            'liters_produced' => 250.0,
            'shrinkage_liters' => 30.0,
            'notes' => 'Decanted 1 drum of 280L into 250L loose oil',
        ];

        $response = $this->actingAs($this->lubeAdmin)->post(route('lube.decanting.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 4. Verification of exact business rules requested:
        // - Available quantity of selected loose product is 250
        $this->assertEquals(250.0, (float) $looseOil->fresh()->quantity);

        // - Available quantity of source drum is 0 (1 - 1 = 0)
        $this->assertEquals(0.0, (float) $drum280->fresh()->quantity);

        // - Cost per liter = 280,000 / 250 = 1,120
        $this->assertEquals(1120.00, (float) $looseOil->fresh()->purchase_price);

        // - Selling price per liter calculated from Drum selling price = 350,000 / 250 = 1,400
        $this->assertEquals(1400.00, (float) $looseOil->fresh()->selling_price);

        // - Decanting log contains both cost and selling price per liter
        $this->assertDatabaseHas('drum_decantings', [
            'shop_id' => $this->lubeShop->id,
            'drum_item_id' => $drum280->id,
            'loose_item_id' => $looseOil->id,
            'drums_used' => 1.0,
            'liters_produced' => 250.0,
            'cost_per_liter' => 1120.00,
            'selling_price_per_liter' => 1400.00,
        ]);
    }

    public function test_labor_only_service_checkout_without_odometer_and_oil_grade(): void
    {
        $tech = PitTechnician::create([
            'shop_id' => $this->lubeShop->id,
            'name' => 'Rashid Bay Master',
            'commission_type' => 'fixed',
            'commission_rate' => 100.00,
            'status' => 'active',
        ]);

        $payload = [
            'plate_number' => 'ISB-20-4321',
            'vehicle_type' => 'sedan',
            'make' => 'Suzuki',
            'model' => 'Swift',
            'customer_name' => 'Hamza Tariq',
            'customer_phone' => '03335557799',
            // Odometer and oil grade left blank / omitted for labor-only customer
            'current_odometer' => null,
            'interval_km' => 5000,
            'oil_grade' => null,
            'pit_technician_id' => $tech->id,
            'items' => [
                [
                    'item_id' => null,
                    'name' => 'Brake Pad Cleaning & Labor',
                    'quantity' => 1.0,
                    'unit_price' => 700,
                    'discount' => 50,
                    'line_total' => 650,
                ],
            ],
            'labor_amount' => 700,
            'discount' => 0,
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($this->lubeAdmin)->postJson(route('lube.pos.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'plate_number' => 'ISB-20-4321',
            'next_due_odometer' => 'N/A',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'shop_id' => $this->lubeShop->id,
            'plate_number' => 'ISB-20-4321',
            'customer_name' => 'Hamza Tariq',
            'last_odometer' => null,
        ]);

        $record = LubeServiceRecord::where('shop_id', $this->lubeShop->id)->latest('id')->first();
        $this->assertNotNull($record);
        $this->assertNull($record->current_odometer);
        $this->assertNull($record->next_due_odometer);
        $this->assertNull($record->oil_grade);
        $this->assertEquals(50.0, (float) $record->sale->items_discount_total);

        // Verify Receipt loads cleanly without odometer
        $receiptRes = $this->actingAs($this->lubeAdmin)->get(route('lube.pos.receipt', $record->sale));
        $receiptRes->assertStatus(200);
        $receiptRes->assertSee('ISB-20-4321');
        $receiptRes->assertDontSee('CURRENT ODOMETER:');
    }

    public function test_lube_pos_screen_filters_out_zero_stock_unpriced_and_loose_from_packaged_dropdown(): void
    {
        // 1. Valid loose oil in stock with selling price
        $validLoose = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Valid Mobil 10W-40 Loose',
            'sku' => 'LOOSE-VAL-01',
            'quantity' => 50,
            'unit' => 'LITER',
            'selling_price' => 2200,
            'is_loose' => true,
            'product_type' => 'loose',
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        // 2. Valid packaged filter in stock with selling price
        $validPackaged = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Valid Leppon Air Filter',
            'sku' => 'PKG-VAL-01',
            'quantity' => 15,
            'unit' => 'PCS',
            'selling_price' => 1400,
            'is_loose' => false,
            'product_type' => 'standard',
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        // 3. Newly added product with no price defined (price = 0 or null)
        $unpricedItem = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Unpriced Spark Plug Set',
            'sku' => 'UNPRICED-01',
            'quantity' => 10,
            'unit' => 'SET',
            'selling_price' => null,
            'is_loose' => false,
            'product_type' => 'standard',
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        // 4. Product with no stock (quantity = 0)
        $outOfStockItem = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Zero Stock Brake Pads',
            'sku' => 'NOSTOCK-01',
            'quantity' => 0,
            'unit' => 'PCS',
            'selling_price' => 2500,
            'is_loose' => false,
            'product_type' => 'standard',
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        // 5. Inactive item
        $inactiveItem = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Discontinued Coolant 1L',
            'sku' => 'INACT-01',
            'quantity' => 20,
            'unit' => 'PCS',
            'selling_price' => 800,
            'is_loose' => false,
            'product_type' => 'standard',
            'status' => 'inactive',
            'created_by' => $this->lubeAdmin->id,
        ]);

        $response = $this->actingAs($this->lubeAdmin)->get(route('lube.pos'));
        $response->assertStatus(200);

        // Valid loose oil is visible in loose dropdown
        $response->assertSee('Valid Mobil 10W-40 Loose');

        // Valid packaged item is visible in packaged dropdown
        $response->assertSee('Valid Leppon Air Filter');

        // Loose oil must NOT be in the packaged dropdown
        $viewDataLoose = $response->viewData('looseProducts');
        $viewDataPackaged = $response->viewData('products');

        $this->assertTrue($viewDataLoose->contains('id', $validLoose->id));
        $this->assertFalse($viewDataPackaged->contains('id', $validLoose->id), 'Loose product should NOT be in packaged products list');

        $this->assertTrue($viewDataPackaged->contains('id', $validPackaged->id));
        $this->assertFalse($viewDataLoose->contains('id', $validPackaged->id), 'Packaged product should NOT be in loose products list');

        // Unpriced, out of stock, and inactive items must NOT be in either dropdown
        $this->assertFalse($viewDataPackaged->contains('id', $unpricedItem->id), 'Unpriced item must be filtered out');
        $this->assertFalse($viewDataLoose->contains('id', $unpricedItem->id), 'Unpriced item must be filtered out');

        $this->assertFalse($viewDataPackaged->contains('id', $outOfStockItem->id), 'Out-of-stock item must be filtered out');
        $this->assertFalse($viewDataLoose->contains('id', $outOfStockItem->id), 'Out-of-stock item must be filtered out');

        $this->assertFalse($viewDataPackaged->contains('id', $inactiveItem->id), 'Inactive item must be filtered out');
        $this->assertFalse($viewDataLoose->contains('id', $inactiveItem->id), 'Inactive item must be filtered out');
    }

    public function test_all_lube_station_screens_load_successfully(): void
    {
        $screens = [
            'lube.hub',
            'lube.analytics',
            'lube.pos',
            'lube.services.index',
            'lube.packages.index',
            'lube.vehicles.index',
            'lube.reminders.index',
            'lube.technicians.index',
            'lube.commissions.index',
            'lube.decanting.index',
        ];

        foreach ($screens as $route) {
            $res = $this->actingAs($this->lubeAdmin)->get(route($route));
            $res->assertStatus(200);
        }
    }

    public function test_lube_counter_pos_barcode_lookup_returns_item_details(): void
    {
        $lubeItem = InventoryItem::create([
            'shop_id' => $this->lubeShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Castrol Magnatec 10W-40 4L',
            'sku' => 'CAS-10W40-4L',
            'initial_quantity' => 15,
            'quantity' => 15,
            'sold_quantity' => 0,
            'unit' => 'CAN',
            'purchase_price' => 3200,
            'selling_price' => 4500,
            'is_loose' => false,
            'status' => 'active',
            'created_by' => $this->lubeAdmin->id,
        ]);

        $retailItem = InventoryItem::create([
            'shop_id' => $this->retailShop->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'item_name' => 'Metro Super Retail Item',
            'sku' => 'RETAIL-ONLY-SKU',
            'initial_quantity' => 10,
            'quantity' => 10,
            'sold_quantity' => 0,
            'unit' => 'PCS',
            'purchase_price' => 100,
            'selling_price' => 150,
            'is_loose' => false,
            'status' => 'active',
            'created_by' => $this->retailAdmin->id,
        ]);

        // 1. Successful lookup by SKU
        $response = $this->actingAs($this->lubeAdmin)
            ->getJson(route('lube.pos.barcode', ['code' => 'CAS-10W40-4L']));

        $response->assertStatus(200);
        $response->assertJson([
            'found' => true,
            'item' => [
                'id' => $lubeItem->id,
                'name' => 'Castrol Magnatec 10W-40 4L',
                'sku' => 'CAS-10W40-4L',
                'selling_price' => 4500.00,
                'unit' => 'CAN',
                'quantity' => 15.00,
                'is_loose' => false,
            ],
        ]);

        // 2. Successful lookup by item ID
        $responseId = $this->actingAs($this->lubeAdmin)
            ->getJson(route('lube.pos.barcode', ['code' => (string) $lubeItem->id]));
        $responseId->assertStatus(200);
        $responseId->assertJson(['found' => true]);

        // 3. Multi-shop security: scanning another shop's SKU returns not found
        $responseRetail = $this->actingAs($this->lubeAdmin)
            ->getJson(route('lube.pos.barcode', ['code' => 'RETAIL-ONLY-SKU']));
        $responseRetail->assertStatus(200);
        $responseRetail->assertJson(['found' => false]);

        // 4. Non-existent barcode returns found: false
        $responseNonExistent = $this->actingAs($this->lubeAdmin)
            ->getJson(route('lube.pos.barcode', ['code' => 'DOES-NOT-EXIST-999']));
        $responseNonExistent->assertStatus(200);
        $responseNonExistent->assertJson(['found' => false]);

        // 5. Blank code returns 422
        $responseBlank = $this->actingAs($this->lubeAdmin)
            ->getJson(route('lube.pos.barcode', ['code' => '']));
        $responseBlank->assertStatus(422);
    }

    public function test_lube_counter_pos_renders_barcode_scanner_and_sticker_controls(): void
    {
        $response = $this->actingAs($this->lubeAdmin)->get(route('lube.pos'));
        $response->assertStatus(200);
        $response->assertSee('barcodeScannerInput');
        $response->assertSee('btnScanBarcode');
        $response->assertSee('barcodeFeedback');
        $response->assertSee('processBarcode');
        $response->assertSee('addOrIncrementItem');
        $response->assertSee('btnPrintStickerOnly');
        $response->assertSee('modalBtnSticker');
        $response->assertSee('submitService');
    }
}
