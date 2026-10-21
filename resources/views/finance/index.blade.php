@extends('layouts.app')
@section('title', 'Finance & Accounts Hub')
@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Finance &amp; Accounts</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold">Finance &amp; Accounts Hub</h4>
            <small class="text-secondary">Direct operational access to Chart of Accounts, vouchers, ledgers, and financial statements</small>
        </div>
    </div>
</div>

@php
    $user = auth()->user();
    $cards = [
        [
            'title' => 'Chart of Accounts',
            'route' => route('finance.accounts.index'),
            'icon' => 'bi-diagram-3',
            'color' => '#2563eb',
            'bg' => '#eff6ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('accounts.view'),
        ],
        [
            'title' => 'Daily Expenses',
            'route' => route('finance.expenses.index'),
            'icon' => 'bi-cash-coin',
            'color' => '#dc2626',
            'bg' => '#fef2f2',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('accounts.view'),
        ],
        [
            'title' => 'Payments & Receipts',
            'route' => route('finance.payments.index'),
            'icon' => 'bi-credit-card',
            'color' => '#059669',
            'bg' => '#ecfdf5',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('accounts.view'),
        ],
        [
            'title' => 'Journal Vouchers',
            'route' => route('finance.journal.index'),
            'icon' => 'bi-journal-bookmark',
            'color' => '#d97706',
            'bg' => '#fffbeb',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('accounts.view'),
        ],
        [
            'title' => 'Profit & Loss Statement',
            'route' => route('finance.reports.profit-loss'),
            'icon' => 'bi-graph-up-arrow',
            'color' => '#0d9488',
            'bg' => '#f0fdfa',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('accounts.reports'),
        ],
        [
            'title' => 'Balance Sheet',
            'route' => route('finance.reports.balance-sheet'),
            'icon' => 'bi-bank',
            'color' => '#7c3aed',
            'bg' => '#f5f3ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('accounts.reports'),
        ],
        [
            'title' => 'Trial Balance',
            'route' => route('finance.reports.trial-balance'),
            'icon' => 'bi-scale',
            'color' => '#475569',
            'bg' => '#f1f5f9',
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
