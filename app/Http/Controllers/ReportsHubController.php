<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsHubController extends Controller
{
    /**
     * Display the Reports Hub (Launchpad).
     */
    public function __invoke(Request $request): View
    {
        return view('reports.index');
    }
}
