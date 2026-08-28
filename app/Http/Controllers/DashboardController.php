<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stock = InventoryItem::query()->selectRaw('COUNT(*) total_items, COALESCE(SUM(quantity), 0) total_initial, COALESCE(SUM(sold_quantity), 0) total_sold')->first();
        $byCategory = InventoryItem::query()->join('categories', 'categories.id', '=', 'inventory_items.category_id')->selectRaw('categories.name label, SUM(inventory_items.quantity - inventory_items.sold_quantity) total')->groupBy('categories.id', 'categories.name')->orderByDesc('total')->get();
        $byBrand = InventoryItem::query()->join('brands', 'brands.id', '=', 'inventory_items.brand_id')->selectRaw('brands.name label, SUM(inventory_items.quantity - inventory_items.sold_quantity) total')->groupBy('brands.id', 'brands.name')->orderByDesc('total')->get();

        return view('dashboard.index', ['totalItems' => $stock->total_items, 'totalInitial' => $stock->total_initial, 'totalSold' => $stock->total_sold, 'totalRemaining' => $stock->total_initial - $stock->total_sold, 'totalBrands' => Brand::count(), 'totalCategories' => Category::count(), 'byCategory' => $byCategory, 'byBrand' => $byBrand]);
    }
}
