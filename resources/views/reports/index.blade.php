@extends('layouts.app')
@section('title', 'Reports & Analytics')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Reports</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart text-primary me-2"></i>Business Reports &amp; Analytics Hub</h4>
            <small class="text-secondary">Direct operational reporting launchpad with standalone screens and instant Excel (.xlsx) downloads</small>
        </div>
        @if(isset($scope['shops']) && $scope['shops']->count() > 1)
            <form method="get" action="{{ route('reports.index') }}" class="d-flex align-items-center gap-2 bg-white px-3 py-1.5 rounded-3 border shadow-sm">
                <label for="reports_branch_selector" class="form-label mb-0 fw-semibold text-secondary small text-nowrap">
                    <i class="bi bi-shop text-primary me-1"></i>Branch Scope:
                </label>
                <select id="reports_branch_selector" name="shop_id" class="form-select form-select-sm" style="min-width: 220px;" onchange="this.form.submit()">
                    <option value="all" {{ empty($scope['selectedShopId']) ? 'selected' : '' }}>
                        All Branches (Consolidated)
                    </option>
                    @foreach($scope['shops'] as $s)
                        <option value="{{ $s->id }}" {{ $scope['selectedShopId'] == $s->id ? 'selected' : '' }}>
                            {{ $s->name }} ({{ $s->code }}){{ $s->isBusiness() ? ' - HQ' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>
</div>

@php
    $user = auth()->user();
    $shop = $user->shop;
    $hasLube = $shop ? $shop->isModuleAvailable('lube') : true;

    $cards = [
        [
            'title' => 'Sales Revenue Report',
            'route' => route('reports.sales'),
            'export_route' => route('reports.sales.export'),
            'icon' => 'bi-receipt',
            'color' => '#2563eb',
            'bg' => '#eff6ff',
            'can' => $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('reports.sales'),
        ],
        [
            'title' => 'Product Sales & Margin Report',
            'route' => route('reports.product-sales'),
            'export_route' => route('reports.product-sales.export'),
            'icon' => 'bi-boxes',
            'color' => '#0891b2',
            'bg' => '#ecfeff',
            'can' => $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('reports.sales'),
        ],
        [
            'title' => 'Cash Register & Payments Report',
            'route' => route('reports.cash-register'),
            'export_route' => route('reports.cash-register.export'),
            'icon' => 'bi-wallet2',
            'color' => '#16a34a',
            'bg' => '#f0fdf4',
            'can' => $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('reports.sales'),
        ],
        [
            'title' => 'Stock Movement Report',
            'route' => route('reports.stock'),
            'export_route' => route('reports.stock.export'),
            'icon' => 'bi-arrow-left-right',
            'color' => '#d97706',
            'bg' => '#fffbeb',
            'can' => $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('reports.stock_movement'),
        ],
        [
            'title' => 'Product Expiry Alert Report',
            'route' => route('reports.expiry'),
            'export_route' => route('reports.expiry.export'),
            'icon' => 'bi-clock-history',
            'color' => '#dc2626',
            'bg' => '#fef2f2',
            'can' => $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('reports.expiry'),
        ],
        [
            'title' => 'Daily Expense Report & Sheet',
            'route' => route('reports.expenses'),
            'export_route' => route('reports.expenses.export'),
            'icon' => 'bi-cash-stack',
            'color' => '#ef4444',
            'bg' => '#fef2f2',
            'can' => $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('reports.sales') || $user->hasPermission('accounts.view'),
        ],
    ];

    if ($hasLube) {
        $cards[] = [
            'title' => 'Auto Lube Station Services',
            'route' => route('reports.lube-services'),
            'export_route' => route('reports.lube-services.export'),
            'icon' => 'bi-droplet-half',
            'color' => '#7c3aed',
            'bg' => '#f5f3ff',
            'can' => $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('reports.sales'),
        ];
    }

    $cards[] = [
        'title' => 'Profit & Loss Statement',
        'route' => route('finance.reports.profit-loss'),
        'export_route' => null,
        'icon' => 'bi-file-earmark-bar-graph',
        'color' => '#059669',
        'bg' => '#ecfdf5',
        'can' => $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('accounts.reports'),
    ];
@endphp

<div class="row g-3 g-lg-4">
    @foreach($cards as $card)
        @if($card['can'])
            <div class="col-sm-6 col-md-4 col-xl-4">
                <div class="card hub-card border shadow-sm rounded-3 p-4 h-100 position-relative bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="hub-icon-tile rounded-3 d-flex align-items-center justify-content-center" 
                             style="width: 52px; height: 52px; background: {{ $card['bg'] }}; color: {{ $card['color'] }}; font-size: 24px;">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-right text-muted hub-arrow fs-5"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $card['title'] }}</h5>
                    <a href="{{ $card['route'] }}" class="stretched-link" aria-label="{{ $card['title'] }}"></a>
                </div>
            </div>
        @endif
    @endforeach
</div>

<style>
.hub-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.hub-card:hover {
    transform: translateY(-3px);
    border-color: #cbd5e1 !important;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06) !important;
}
.hub-card:hover .hub-arrow {
    color: var(--primary) !important;
    transform: translateX(3px);
}
.hub-arrow {
    transition: transform 0.2s ease, color 0.2s ease;
}
</style>
@endsection
