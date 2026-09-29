<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_download_sample_csv_template(): void
    {
        $shop = Shop::create(['name' => 'Import Store', 'code' => 'IMP-01', 'status' => 'active']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);

        $response = $this->actingAs($admin)->get('/products/template');
        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="products_sample_template.csv"');
    }

    public function test_can_bulk_import_products_via_csv_and_auto_create_brands_and_categories(): void
    {
        $shop = Shop::create(['name' => 'Bulk Import Store', 'code' => 'BIS-01', 'status' => 'active']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'shop_admin', 'status' => 'active']);

        $csvContent = implode("\n", [
            'Item Name,SKU,Brand,Category,Pack Size,Unit,Purchase Price,Selling Price,Opening Stock,Alert Quantity,Expiry Date',
            'Matte Lipstick Plum,LIP-PLUM-01,Brand Novel,Makeup,1 pc,PCS,120,250,50,5,2027-10-10',
            'Hydrating Serum 30ml,SER-HYD-30,Brand Glow,Skincare,30 ml,BOTTLE,350,600,20,4,2028-01-01',
        ]);

        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->actingAs($admin)->post('/products/import', [
            'file' => $file,
        ]);

        $response->assertRedirect('/products');
        $response->assertSessionHas('success');

        // 1. Verify products created
        $this->assertDatabaseHas('inventory_items', [
            'shop_id' => $shop->id,
            'item_name' => 'Matte Lipstick Plum',
            'sku' => 'LIP-PLUM-01',
            'quantity' => 50,
            'purchase_price' => 120,
            'selling_price' => 250,
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'shop_id' => $shop->id,
            'item_name' => 'Hydrating Serum 30ml',
            'sku' => 'SER-HYD-30',
            'quantity' => 20,
            'purchase_price' => 350,
            'selling_price' => 600,
        ]);

        // 2. Verify Brands and Categories auto-created
        $this->assertDatabaseHas('brands', ['shop_id' => $shop->id, 'name' => 'Brand Novel']);
        $this->assertDatabaseHas('brands', ['shop_id' => $shop->id, 'name' => 'Brand Glow']);
        $this->assertDatabaseHas('categories', ['shop_id' => $shop->id, 'name' => 'Makeup']);
        $this->assertDatabaseHas('categories', ['shop_id' => $shop->id, 'name' => 'Skincare']);

        // 3. Verify batches created for opening stock
        $this->assertDatabaseHas('product_batches', [
            'shop_id' => $shop->id,
            'batch_no' => 'BATCH-001',
            'quantity' => 50,
        ]);
        $this->assertDatabaseHas('product_batches', [
            'shop_id' => $shop->id,
            'batch_no' => 'BATCH-001',
            'quantity' => 20,
        ]);
    }
}
