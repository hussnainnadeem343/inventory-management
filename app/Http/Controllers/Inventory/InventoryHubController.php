<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryHubController extends Controller
{
    /**
     * Display the Inventory & Warehouses Module Hub (Launchpad).
     */
    public function __invoke(Request $request): View
    {
        return view('inventory.hub');
    }
}
