@extends('layouts.app')
@section('title', 'Inventory & Stock Hub')
@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Inventory &amp; Stock</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold">Inventory &amp; Warehouses Hub</h4>
            <small class="text-secondary">Direct operational access to stock balances, warehouses, transfers, and catalog</small>
        </div>
    </div>
</div>

@php
    $user = auth()->user();
    $cards = [
        [
            'title' => 'Stock Overview',
            'route' => route('stock.index'),
            'icon' => 'bi-stack',
            'color' => '#0284c7',
            'bg' => '#f0f9ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('stock.view'),
        ],
        [
            'title' => 'Godowns / Stores',
            'route' => route('warehouses.index'),
            'icon' => 'bi-buildings',
            'color' => '#4f46e5',
            'bg' => '#eef2ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('stock.view'),
        ],
        [
            'title' => 'Stock Transfers',
            'route' => route('transfers.index'),
            'icon' => 'bi-arrow-left-right',
            'color' => '#0d9488',
            'bg' => '#f0fdfa',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('stock.view'),
        ],
        [
            'title' => 'Stock Adjustments',
            'route' => route('adjustments.index'),
            'icon' => 'bi-clipboard2-check',
            'color' => '#d97706',
            'bg' => '#fffbeb',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('stock.view'),
        ],
        [
            'title' => 'Product Items',
            'route' => route('products.index'),
            'icon' => 'bi-boxes',
            'color' => '#2563eb',
            'bg' => '#eff6ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('products.view'),
        ],
        [
            'title' => 'Brands',
            'route' => route('brands.index'),
            'icon' => 'bi-tag',
            'color' => '#db2777',
            'bg' => '#fdf2f8',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('catalog.manage'),
        ],
        [
            'title' => 'Categories',
            'route' => route('categories.index'),
            'icon' => 'bi-grid',
            'color' => '#7c3aed',
            'bg' => '#f5f3ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('catalog.manage'),
        ],
        [
            'title' => 'Units of Measure',
            'route' => route('units.index'),
            'icon' => 'bi-rulers',
            'color' => '#475569',
            'bg' => '#f1f5f9',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('catalog.manage'),
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
