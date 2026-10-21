<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagementHubController extends Controller
{
    /**
     * Display the Management & Settings Hub (Launchpad).
     */
    public function __invoke(Request $request): View
    {
        return view('management.index');
    }
}
