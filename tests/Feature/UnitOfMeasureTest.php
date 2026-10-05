<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Inventory\Unit;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitOfMeasureTest extends TestCase
{
    use RefreshDatabase;

    private function shopAdmin(): User
    {
        $shop = Shop::create(['name' => 'Metro Retail', 'code' => 'MR01', 'status' => 'active']);
        return User::factory()->create([
            'role' => 'shop_admin',
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);
    }

    public function test_can_view_and_create_units_of_measure(): void
    {
        $admin = $this->shopAdmin();

        $this->actingAs($admin)->get('/units')
            ->assertOk()
            ->assertSee('Units of Measure')
            ->assertSee('PCS'); // from standard pre-seeded units

        $response = $this->actingAs($admin)->post('/units', [
            'name' => 'Carton Box',
            'code' => 'CTN',
            'description' => 'Standard packaging carton box',
            'status' => 'active',
        ]);

        $response->assertRedirect('/units');
        $this->assertDatabaseHas('units', [
            'name' => 'Carton Box',
            'code' => 'CTN',
            'status' => 'active',
        ]);
    }

    public function test_product_form_displays_units_dropdown_and_allows_creation_without_pricing(): void
    {
        $admin = $this->shopAdmin();
        $shopId = $admin->shop_id;

        $brand = Brand::create(['shop_id' => $shopId, 'name' => 'TechBrand', 'status' => 'active', 'created_by' => $admin->id]);
        $category = Category::create(['shop_id' => $shopId, 'name' => 'Peripherals', 'status' => 'active', 'created_by' => $admin->id]);

        $this->actingAs($admin)->get('/products/create')
            ->assertOk()
            ->assertSee('Measure Unit')
            ->assertSee('Piece (PCS)')
            ->assertDontSee('Purchase Price (Cost)')
            ->assertDontSee('Selling Price')
            ->assertDontSee('Opening Stock Quantity');

        // Create product with only catalog fields (no pricing or stock fields)
        $response = $this->actingAs($admin)->post('/products', [
            'item_name' => 'Ergonomic Keyboard',
            'sku' => 'KEY-ERG-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'unit' => 'PCS',
            'pack_size' => 1,
            'alert_quantity' => 3,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('inventory_items', [
            'item_name' => 'Ergonomic Keyboard',
            'sku' => 'KEY-ERG-01',
            'unit' => 'PCS',
            'quantity' => 0,
            'purchase_price' => null,
            'selling_price' => null,
        ]);
    }
}
