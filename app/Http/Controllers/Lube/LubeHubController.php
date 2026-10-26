<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LubeHubController extends Controller
{
    /**
     * Display the Auto Lube Station Module Hub (Launchpad).
     */
    public function __invoke(Request $request): View
    {
        return view('lube.hub');
    }
}
