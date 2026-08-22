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
        return view('dashboard.index', ['totalItems' => InventoryItem::count(), 'totalQuantity' => InventoryItem::sum('quantity'), 'totalBrands' => Brand::count(), 'totalCategories' => Category::count()]);
    }
}
