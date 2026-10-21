<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceHubController extends Controller
{
    /**
     * Display the Finance & Accounts Module Hub (Launchpad).
     */
    public function __invoke(Request $request): View
    {
        return view('finance.index');
    }
}
