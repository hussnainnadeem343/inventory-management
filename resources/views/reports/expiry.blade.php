@extends('layouts.app')
@section('title', 'Product Expiry Alert Report')
@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">Reports</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Expiry Alerts</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-clock-history text-danger me-2"></i>Product Expiry Alert Report</h4>
            <small class="text-secondary">Batch monitoring for perishable items expiring soon or already expired</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reports.expiry.export', request()->all()) }}" class="btn btn-success d-inline-flex align-items-center shadow-sm">
                <i class="bi bi-file-earmark-excel me-1.5 fs-6"></i>
                <span class="fw-semibold">Download Excel (.xlsx)</span>
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>All Reports
            </a>
        </div>
    </div>
</div>

{{-- Standalone Filter Bar (No Dashboard Widgets) --}}
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="get" action="{{ route('reports.expiry') }}" class="row g-2 align-items-end">
            <div class="col-sm-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Expiry Horizon</label>
                <select class="form-select form-select-sm" name="range">
                    <option value="15" @selected(($range ?? '30') === '15')>Expiring within 15 Days</option>
                    <option value="30" @selected(($range ?? '30') === '30')>Expiring within 30 Days (Default)</option>
                    <option value="60" @selected(($range ?? '30') === '60')>Expiring within 60 Days</option>
                    <option value="expired" @selected(($range ?? '30') === 'expired')>Already Expired Products</option>
                </select>
            </div>
            @if(isset($shops) && $shops->count() > 1)
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Branch Scope</label>
                    <select class="form-select form-select-sm" name="shop_id">
                        <option value="all" {{ empty($shopId) ? 'selected' : '' }}>All Branches (Consolidated)</option>
                        @foreach($shops as $s)
                            <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>
                                {{ $s->name }} ({{ $s->code }}){{ $s->isBusiness() ? ' - HQ' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-sm-6 col-md-{{ isset($shops) && $shops->count() > 1 ? '4' : '6' }}">
                <label class="form-label small fw-semibold text-secondary mb-1">Search Product / SKU</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" name="search" placeholder="Item name or SKU" value="{{ $search ?? '' }}">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
                    @if(request()->hasAny(['range', 'shop_id', 'search']))
                        <a class="btn btn-outline-secondary" href="{{ route('reports.expiry') }}" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Standalone Expiry Table --}}
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-exclamation-triangle text-danger me-1"></i>Products at Risk of Expiry</h6>
            <small class="text-secondary">Monitored inventory matching horizon: {{ $range === 'expired' ? 'Already Expired' : "Within $range days" }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-light border px-2.5 py-1.5 fw-semibold text-secondary">
                Showing Top {{ min(10, $items->count()) }} of {{ number_format($totalItemsCount ?? $items->count()) }} Expiring Products
            </span>
            <a href="{{ route('reports.expiry.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">Product Item</th>
                    @if(isset($shops) && $shops->count() > 1)
                        <th>Branch</th>
                    @endif
                    <th>Brand &amp; Category</th>
                    <th class="text-end">Current Stock</th>
                    <th class="text-center">Expiry Date</th>
                    <th class="text-center">Status / Horizon</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $daysLeft = now()->diffInDays($item->expiry_date, false);
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold text-dark">{{ $item->item_name }}</div>
                            <code class="small text-secondary">{{ $item->sku ?: '-' }}</code>
                        </td>
                        @if(isset($shops) && $shops->count() > 1)
                            <td>
                                <span class="badge text-bg-light border">{{ $item->shop->name ?? 'Default' }}</span>
                            </td>
                        @endif
                        <td>
                            <span class="small text-dark">{{ $item->brand->name ?? '-' }}</span><br>
                            <small class="text-muted">{{ $item->category->name ?? '-' }}</small>
                        </td>
                        <td class="text-end fw-bold text-dark">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-center">
                            <strong class="{{ $daysLeft < 0 ? 'text-danger' : 'text-warning' }}">
                                {{ $item->expiry_date?->format('d M Y') ?? 'N/A' }}
                            </strong>
                        </td>
                        <td class="text-center">
                            @if($daysLeft < 0)
                                <span class="badge text-bg-danger">Expired {{ abs($daysLeft) }} days ago</span>
                            @elseif($daysLeft == 0)
                                <span class="badge text-bg-danger">Expires Today!</span>
                            @else
                                <span class="badge text-bg-warning">{{ $daysLeft }} days remaining</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a class="btn btn-sm btn-outline-secondary py-0.5 px-2" href="{{ route('stock.history', $item) }}">
                                <i class="bi bi-clock-history me-1"></i>Logs
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ (isset($shops) && $shops->count() > 1) ? 7 : 6 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                            No products matching the selected expiry horizon.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(($totalItemsCount ?? $items->count()) > 10)
        <div class="card-footer bg-light py-2.5 px-3 border-top text-secondary small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span>
                <i class="bi bi-info-circle text-primary me-1"></i>
                Showing top <strong>{{ $items->count() }}</strong> near-expiry items in UI preview. For complete stock shelf-life audit, export to Excel.
            </span>
            <a href="{{ route('reports.expiry.export', request()->all()) }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Download Full Excel (.xlsx)
            </a>
        </div>
    @else
        <div class="card-footer bg-white py-2 px-3 border-top text-secondary small d-flex justify-content-between align-items-center">
            <span>Showing all {{ $items->count() }} expiring item{{ $items->count() === 1 ? '' : 's' }}.</span>
            <a href="{{ route('reports.expiry.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    @endif
</div>
@endsection
