@extends('layouts.app')
@section('title', 'Product Expiry Alert Report')
@section('content')
<div class="card p-3 mb-4">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Expiry Horizon</label>
            <select class="form-select" name="range">
                <option value="15" @selected($range === '15')>Expiring within 15 Days</option>
                <option value="30" @selected($range === '30')>Expiring within 30 Days (Default)</option>
                <option value="60" @selected($range === '60')>Expiring within 60 Days</option>
                <option value="expired" @selected($range === 'expired')>Already Expired Products</option>
            </select>
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-md-4">
                <label class="form-label">Shop Filter</label>
                <select class="form-select" name="shop_id">
                    <option value="">All Shops</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected($shopId == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-{{ auth()->user()->isSuperAdmin() ? '4' : '8' }} d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i>Filter Expiry</button>
            <a class="btn btn-outline-secondary" href="{{ route('reports.expiry') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0">Products at Risk of Expiry</h3>
        <span class="badge text-bg-warning">Total: {{ $items->total() }} items</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product / SKU</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th>Shop</th>
                    @endif
                    <th>Brand & Category</th>
                    <th>Remaining Stock</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $daysLeft = now()->diffInDays($item->expiry_date, false);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $item->item_name }}</strong><br>
                            <small class="text-secondary font-monospace">{{ $item->sku ?: '-' }}</small>
                        </td>
                        @if(auth()->user()->isSuperAdmin())
                            <td><span class="badge text-bg-light border">{{ $item->shop->name ?? 'Default' }}</span></td>
                        @endif
                        <td>
                            <span>{{ $item->brand->name ?? '-' }}</span><br>
                            <small class="text-muted">{{ $item->category->name ?? '-' }}</small>
                        </td>
                        <td class="fw-bold">{{ number_format($item->quantity, 2) }}</td>
                        <td>
                            <strong class="{{ $daysLeft < 0 ? 'text-danger' : 'text-warning' }}">
                                {{ $item->expiry_date->format('d-M-Y') }}
                            </strong>
                        </td>
                        <td>
                            @if($daysLeft < 0)
                                <span class="badge text-bg-danger">Expired {{ abs($daysLeft) }} days ago</span>
                            @elseif($daysLeft == 0)
                                <span class="badge text-bg-danger">Expires Today!</span>
                            @else
                                <span class="badge text-bg-warning">{{ $daysLeft }} days remaining</span>
                            @endif
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('stock.history', $item) }}">
                                <i class="bi bi-clock-history me-1"></i>Logs
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}" class="text-center py-4 text-muted">
                            No products matching the selected expiry horizon.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $items->onEachSide(1)->links() }}</div>
@endsection
