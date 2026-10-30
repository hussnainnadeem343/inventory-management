<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogDynamicFormTest extends TestCase
{
    use RefreshDatabase;

    private function createShop(string $type, string $code): Shop
    {
        return Shop::create([
            'name' => "Shop {$code}",
            'code' => $code,
            'business_type' => $type,
            'phone' => '03001234567',
            'status' => 'active',
        ]);
    }

    private function createShopAdmin(Shop $shop): User
    {
        return User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'shop_admin',
            'status' => 'active',
        ]);
    }

    public function test_retail_business_sees_generic_product_form_without_lube_fields(): void
    {
        $retailShop = $this->createShop('Retail & General Business', 'RET-01');
        $admin = $this->createShopAdmin($retailShop);

        $response = $this->actingAs($admin)->get('/products/create');

        $response->assertOk();
        $response->assertSee('Standard Retail Catalog');
        $response->assertSee('Measure Unit');
        $response->assertSee('Pack Size / Pieces per Pack');
        // Lube-specific radio options are hidden from retail business
        $response->assertSee('id="lubeSectionWrapper" style="display: none;"', false);
    }

    public function test_lube_business_shows_lube_section_and_can_create_general_item_like_air_freshener(): void
    {
        $lubeShop = $this->createShop('Lube (Oil Changing) Station', 'LUBE-01');
        $admin = $this->createShopAdmin($lubeShop);

        $brand = Brand::create(['shop_id' => $lubeShop->id, 'name' => 'Areon', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['shop_id' => $lubeShop->id, 'name' => 'Air Fresheners', 'status' => 'active', 'created_by' => $admin->id]);

        $response = $this->actingAs($admin)->get('/products/create');
        $response->assertOk();
        $response->assertSee('Auto Lube Business');
        $response->assertSee('Standard Product / Part / Accessory');
        $response->assertSee('Engine Oil / Bulk Lubricant');

        // Submit an Air Freshener as a general product in the Lube shop
        $postRes = $this->actingAs($admin)->post('/products', [
            'product_nature' => 'general',
            'item_name' => 'Areon Gel Air Freshener Vanilla',
            'sku' => 'AREON-VAN-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'unit' => 'PCS',
            'pack_size' => 1,
            'alert_quantity' => 5,
            'status' => 'active',
        ]);

        $postRes->assertRedirect('/products');

        $item = InventoryItem::where('sku', 'AREON-VAN-01')->first();
        $this->assertNotNull($item);
        $this->assertEquals('Areon Gel Air Freshener Vanilla', $item->item_name);
        $this->assertEquals('standard', $item->product_type);
        $this->assertFalse((bool) $item->is_loose);
        $this->assertNull($item->liters);
        $this->assertFalse($item->isLoose());
        $this->assertFalse($item->isDrum());
    }

    public function test_lube_business_can_create_bulk_drum_product(): void
    {
        $lubeShop = $this->createShop('Lube (Oil Changing) Station', 'LUBE-02');
        $admin = $this->createShopAdmin($lubeShop);

        $brand = Brand::create(['shop_id' => $lubeShop->id, 'name' => 'Shell', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['shop_id' => $lubeShop->id, 'name' => 'Engine Oils', 'status' => 'active', 'created_by' => $admin->id]);

        $response = $this->actingAs($admin)->post('/products', [
            'product_nature' => 'oil',
            'oil_sub_type' => 'drum',
            'item_name' => 'Shell Rimula R4 15W-40 280L Drum',
            'sku' => 'SHELL-R4-280',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'unit' => 'DRUM',
            'liters' => 280,
            'alert_quantity' => 1,
            'status' => 'active',
        ]);

        $response->assertRedirect('/products');

        $drum = InventoryItem::where('sku', 'SHELL-R4-280')->first();
        $this->assertNotNull($drum);
        $this->assertEquals('drum', $drum->product_type);
        $this->assertEquals(280.0, (float) $drum->liters);
        $this->assertFalse((bool) $drum->is_loose);
        $this->assertTrue($drum->isDrum());
        $this->assertFalse($drum->isLoose());
    }

    public function test_lube_business_can_create_loose_dispensed_oil_product(): void
    {
        $lubeShop = $this->createShop('Lube (Oil Changing) Station', 'LUBE-03');
        $admin = $this->createShopAdmin($lubeShop);

        $brand = Brand::create(['shop_id' => $lubeShop->id, 'name' => 'Caltex', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['shop_id' => $lubeShop->id, 'name' => 'Loose Oils', 'status' => 'active', 'created_by' => $admin->id]);

        $response = $this->actingAs($admin)->post('/products', [
            'product_nature' => 'oil',
            'oil_sub_type' => 'loose',
            'item_name' => 'Caltex Delo Gold 15W-40 (Loose Liters)',
            'sku' => 'DELO-LOOSE-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'unit' => 'LTR',
            'alert_quantity' => 50,
            'status' => 'active',
        ]);

        $response->assertRedirect('/products');

        $loose = InventoryItem::where('sku', 'DELO-LOOSE-01')->first();
        $this->assertNotNull($loose);
        $this->assertEquals('loose', $loose->product_type);
        $this->assertTrue((bool) $loose->is_loose);
        $this->assertEquals(1.0, (float) $loose->liters);
        $this->assertTrue($loose->isLoose());
        $this->assertFalse($loose->isDrum());
    }
}
