@extends('layouts.app')
@section('title', 'Reports Hub')
@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Reports</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold">Business Reports &amp; Analytics Hub</h4>
            <small class="text-secondary">Direct access to sales revenue, inventory flow, expiry alerts, and financial statements</small>
        </div>
    </div>
</div>

@php
    $user = auth()->user();
    $cards = [
        [
            'title' => 'Sales Revenue Report',
            'route' => route('reports.sales'),
            'icon' => 'bi-graph-up',
            'color' => '#2563eb',
            'bg' => '#eff6ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('reports.sales'),
        ],
        [
            'title' => 'Stock Movement Report',
            'route' => route('reports.stock'),
            'icon' => 'bi-boxes',
            'color' => '#0d9488',
            'bg' => '#f0fdfa',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('reports.stock_movement'),
        ],
        [
            'title' => 'Product Expiry Alert Report',
            'route' => route('reports.expiry'),
            'icon' => 'bi-exclamation-triangle',
            'color' => '#dc2626',
            'bg' => '#fef2f2',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('reports.expiry'),
        ],
        [
            'title' => 'Financial Reports (P&L)',
            'route' => route('finance.reports.profit-loss'),
            'icon' => 'bi-file-earmark-spreadsheet',
            'color' => '#059669',
            'bg' => '#ecfdf5',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('accounts.reports'),
        ],
    ];
@endphp

<div class="row g-3 g-lg-4">
    @foreach($cards as $card)
        @if($card['can'])
            <div class="col-sm-6 col-md-4 col-xl-3">
                <a href="{{ $card['route'] }}" class="card hub-card border shadow-sm rounded-3 p-4 h-100 text-decoration-none bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="hub-icon-tile rounded-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: {{ $card['bg'] }}; color: {{ $card['color'] }}; font-size: 24px;">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>
                        <i class="bi bi-arrow-right text-muted hub-arrow fs-5"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-0">{{ $card['title'] }}</h5>
                </a>
            </div>
        @endif
    @endforeach
</div>

<style>
.hub-card {
    transition: all 0.2s ease-in-out;
    border-color: #e2e8f0 !important;
}
.hub-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
    border-color: #3b82f6 !important;
}
.hub-card:hover .hub-arrow {
    color: #3b82f6 !important;
    transform: translateX(4px);
}
.hub-arrow {
    transition: all 0.2s ease-in-out;
}
</style>
@endsection
