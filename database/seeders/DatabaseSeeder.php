<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['username' => env('ADMIN_USERNAME', 'admin')], ['name' => env('ADMIN_NAME', 'Super Admin'), 'email' => env('ADMIN_EMAIL', 'admin@example.com'), 'password' => env('ADMIN_PASSWORD', 'password'), 'role' => 'super_admin', 'status' => 'active']);
        $brands = collect(['Samsung' => 'Electronics', 'Apple' => 'Technology', 'Nike' => 'Sports', 'Coca Cola' => 'Beverages'])->mapWithKeys(fn ($description, $name) => [$name => Brand::firstOrCreate(['name' => $name], ['description' => $description, 'status' => 'active', 'created_by' => $admin->id])]);
        $categories = collect(['Electronics', 'Clothing', 'Beverages', 'General'])->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['name' => $name], ['description' => "$name items", 'status' => 'active', 'created_by' => $admin->id])]);
        $items = [['Samsung LED TV', 'SAM-TV-001', 'Samsung', 'Electronics', 10, 'PCS', 50000, 60000, 'ABC Traders'], ['iPhone Case', 'APL-CASE-001', 'Apple', 'General', 50, 'PCS', 500, 850, 'Tech Supply'], ['Running Shoes', 'NIKE-SHOE-001', 'Nike', 'Clothing', 25, 'BOX', 3500, 5000, 'Sports Hub'], ['Coke 500ml', 'COKE-500-001', 'Coca Cola', 'Beverages', 100, 'PACK', 1200, 1500, 'Beverage Distributor']];
        foreach ($items as [$name,$sku,$brand,$category,$qty,$unit,$purchase,$selling,$supplier]) {
            InventoryItem::firstOrCreate(['sku' => $sku], ['item_name' => $name, 'brand_id' => $brands[$brand]->id, 'category_id' => $categories[$category]->id, 'quantity' => $qty, 'unit' => $unit, 'purchase_price' => $purchase, 'selling_price' => $selling, 'supplier' => $supplier, 'status' => 'active', 'created_by' => $admin->id]);
        }
    }
}
