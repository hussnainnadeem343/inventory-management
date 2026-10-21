<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrHubController extends Controller
{
    /**
     * Display the HR & Payroll Module Hub (Launchpad).
     */
    public function __invoke(Request $request): View
    {
        return view('hr.index');
    }
}
