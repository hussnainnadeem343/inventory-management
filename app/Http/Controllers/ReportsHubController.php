<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsHubController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * Display the Reports Hub (Launchpad) with live Today's Financial Overview.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // 1. Resolve shop scope
        $scope = $this->reportService->resolveShopScope($user, $request);

        // 2. Compute Live Today's Financial Summary
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $todaySummary = $this->reportService->getFinancialSummary(
            $todayStart,
            $todayEnd,
            $scope['targetShopIds']
        );

        return view('reports.index', [
            'todaySummary' => $todaySummary,
            'todayDate' => now()->format('d M Y'),
            'scope' => $scope,
        ]);
    }
}
