<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseHubController extends Controller
{
    /**
     * Display the functional Purchase Module Hub (Launchpad).
     */
    public function __invoke(Request $request): View
    {
        return view('purchase.index');
    }
}
