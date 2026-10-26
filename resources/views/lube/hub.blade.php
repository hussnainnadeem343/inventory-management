@extends('layouts.app')
@section('title', 'Auto Lube Station Hub')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Auto Lube Station</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-speedometer2 text-primary me-2"></i>Auto Lube Station Hub</h4>
            <small class="text-secondary">Direct operational launchpad for service bay POS, vehicle records, pit crew, and bulk oil decanting</small>
        </div>
    </div>
</div>

@php
    $user = auth()->user();
    $cards = [
        [
            'title' => 'Lube Counter POS',
            'desc' => 'Bay-side quick service billing terminal & windshield sticker printer',
            'route' => route('lube.pos'),
            'icon' => 'bi-cart3',
            'color' => '#059669',
            'bg' => '#ecfdf5',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('lube.pos'),
        ],
        [
            'title' => 'Station Analytics',
            'desc' => 'Operational KPI metrics, oil volume dispensed & bay leaderboard',
            'route' => route('lube.analytics'),
            'icon' => 'bi-bar-chart-line',
            'color' => '#2563eb',
            'bg' => '#eff6ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('lube.analytics'),
        ],
        [
            'title' => 'Labor & Pit Services',
            'desc' => 'Service catalog, labor charges, oil changes & tuning fees',
            'route' => route('lube.services.index'),
            'icon' => 'bi-wrench-adjustable',
            'color' => '#d97706',
            'bg' => '#fef3c7',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('lube.packages'),
        ],
        [
            'title' => 'Service Packages',
            'desc' => 'Pre-configured oil, filter, and labor bundles for 1-click POS selection',
            'route' => route('lube.packages.index'),
            'icon' => 'bi-boxes',
            'color' => '#7c3aed',
            'bg' => '#f5f3ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('lube.packages'),
        ],
        [
            'title' => 'Vehicle History',
            'desc' => 'Searchable vehicle registry and digital chronological maintenance log',
            'route' => route('lube.vehicles.index'),
            'icon' => 'bi-car-front',
            'color' => '#0284c7',
            'bg' => '#e0f2fe',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('lube.vehicles'),
        ],
        [
            'title' => 'Oil Due Reminders',
            'desc' => 'Proactive retention follow-ups with 1-click WhatsApp messaging',
            'route' => route('lube.reminders.index'),
            'icon' => 'bi-bell',
            'color' => '#dc2626',
            'bg' => '#fef2f2',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('lube.reminders'),
        ],
        [
            'title' => 'Pit Technicians',
            'desc' => 'Bay crew profiles, mechanic assignments & commission structures',
            'route' => route('lube.technicians.index'),
            'icon' => 'bi-people',
            'color' => '#4f46e5',
            'bg' => '#eef2ff',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('lube.technicians'),
        ],
        [
            'title' => 'Mechanic Commissions',
            'desc' => 'Job-by-job commission earnings and bay mechanic payout settlements',
            'route' => route('lube.commissions.index'),
            'icon' => 'bi-wallet2',
            'color' => '#16a34a',
            'bg' => '#f0fdf4',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('lube.commissions'),
        ],
        [
            'title' => 'Drum Decanting',
            'desc' => 'Decant bulk 208L drums into dispensed loose liters inventory',
            'route' => route('lube.decanting.index'),
            'icon' => 'bi-fuel-pump',
            'color' => '#ea580c',
            'bg' => '#fff7ed',
            'can' => $user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('lube.decanting'),
        ],
    ];
@endphp

<div class="row g-3 g-lg-4">
    @foreach($cards as $card)
        @if($card['can'])
            <div class="col-sm-6 col-md-4 col-xl-4">
                <a href="{{ $card['route'] }}" class="card hub-card border shadow-sm rounded-3 p-4 h-100 text-decoration-none bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="hub-icon-tile rounded-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: {{ $card['bg'] }}; color: {{ $card['color'] }}; font-size: 24px;">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>
                        <i class="bi bi-arrow-right text-muted hub-arrow fs-5"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $card['title'] }}</h5>
                    <p class="text-secondary small mb-0">{{ $card['desc'] }}</p>
                </a>
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
