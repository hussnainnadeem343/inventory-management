<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesHubController extends Controller
{
    /**
     * Display the Sales & POS Module Hub (Launchpad).
     */
    public function __invoke(Request $request): View
    {
        return view('sales.hub');
    }
}
