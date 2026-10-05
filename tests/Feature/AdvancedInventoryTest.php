<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\Warehouse;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function shopAdmin(): User
    {
        $shop = Shop::create(['name' => 'SuperMart', 'code' => 'SM01', 'status' => 'active']);
        return User::factory()->create([
            'role' => 'shop_admin',
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);
    }

    public function test_can_create_and_list_warehouses(): void
    {
        $admin = $this->shopAdmin();

        $response = $this->actingAs($admin)->post('/warehouses', [
            'name' => 'Secondary Godown',
            'code' => 'WH-02',
            'contact_person' => 'Hamza',
            'phone' => '0321-1234567',
            'address' => 'Site Area, Karachi',
            'status' => 'active',
        ]);

        $response->assertRedirect('/warehouses');
        $this->assertDatabaseHas('warehouses', [
            'name' => 'Secondary Godown',
            'code' => 'WH-02',
        ]);
    }

    public function test_can_transfer_stock_between_warehouses(): void
    {
        $admin = $this->shopAdmin();
        $shopId = $admin->shop_id;

        $wh1 = Warehouse::create(['shop_id' => $shopId, 'name' => 'Godown 1', 'code' => 'G1', 'status' => 'active']);
        $wh2 = Warehouse::create(['shop_id' => $shopId, 'name' => 'Godown 2', 'code' => 'G2', 'status' => 'active']);

        $brand = Brand::create(['name' => 'TestBrand', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['name' => 'TestCat', 'status' => 'active', 'created_by' => $admin->id]);
        $item = InventoryItem::create([
            'shop_id' => $shopId,
            'item_name' => 'Smart Speaker',
            'sku' => 'SPK-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 50,
            'sold_quantity' => 0,
            'unit' => 'PCS',
            'purchase_price' => 2000,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post('/transfers', [
            'from_warehouse_id' => $wh1->id,
            'to_warehouse_id' => $wh2->id,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 10,
                ],
            ],
            'notes' => 'Stock rebalance',
        ]);

        $trf = StockTransfer::first();
        $this->assertNotNull($trf);
        $response->assertRedirect(route('transfers.show', $trf));

        $this->assertDatabaseHas('stock_transfer_items', [
            'stock_transfer_id' => $trf->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10,
        ]);
    }

    public function test_can_record_stock_adjustment_and_reconcile_inventory(): void
    {
        $admin = $this->shopAdmin();
        $shopId = $admin->shop_id;

        $brand = Brand::create(['name' => 'Brand C', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['name' => 'Category C', 'status' => 'active', 'created_by' => $admin->id]);
        $item = InventoryItem::create([
            'shop_id' => $shopId,
            'item_name' => 'Glass Bottles',
            'sku' => 'GLS-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 100,
            'sold_quantity' => 0,
            'unit' => 'PCS',
            'purchase_price' => 50,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        // Deduct 5 damaged items
        $response = $this->actingAs($admin)->post('/adjustments', [
            'adjustment_date' => now()->format('Y-m-d'),
            'type' => 'subtraction',
            'reason' => 'Damaged in storage',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 5,
                ],
            ],
        ]);

        $adj = StockAdjustment::first();
        $this->assertNotNull($adj);
        $response->assertRedirect(route('adjustments.show', $adj));

        // Remaining stock should now be 95
        $this->assertSame(95.0, (float) $item->fresh()->remaining_quantity);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $item->id,
            'transaction_type' => InventoryTransaction::TYPE_DAMAGE_LOSS,
            'quantity' => 5,
            'balance_after' => 95,
        ]);
    }
}
