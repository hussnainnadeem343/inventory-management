@extends('layouts.app')
@section('title', 'Stock Movement Ledger')
@section('content')
<div class="card p-3 mb-4">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input class="form-control" type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input class="form-control" type="date" name="end_date" value="{{ $endDate }}">
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-md-3">
                <label class="form-label">Shop Filter</label>
                <select class="form-select" name="shop_id">
                    <option value="">All Shops Combined</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected($shopId == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-{{ auth()->user()->isSuperAdmin() ? '3' : '6' }} d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i>Filter Movement</button>
            <a class="btn btn-outline-secondary" href="{{ route('reports.stock') }}">Reset</a>
        </div>
    </form>
</div>

<!-- Movement Summary Badges -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border-start border-success border-4 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold">Total Stock In</div>
            <div class="h3 my-1 fw-bold text-success">+{{ number_format($summary['STOCK_IN'] ?? 0, 2) }}</div>
            <div class="text-secondary small">Purchases & Restocks</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border-start border-primary border-4 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold">Total Sold Out</div>
            <div class="h3 my-1 fw-bold text-primary">-{{ number_format($summary['SALE'] ?? 0, 2) }}</div>
            <div class="text-secondary small">Customer Sales</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border-start border-info border-4 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold">Customer Returns</div>
            <div class="h3 my-1 fw-bold text-info">+{{ number_format($summary['CUSTOMER_RETURN'] ?? 0, 2) }}</div>
            <div class="text-secondary small">Returned into stock</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border-start border-danger border-4 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold">Damage & Wastage</div>
            <div class="h3 my-1 fw-bold text-danger">-{{ number_format($summary['DAMAGE_LOSS'] ?? 0, 2) }}</div>
            <div class="text-secondary small">Broken, leaked & expired</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <h3 class="h6 mb-0">Detailed Inventory Movement Log</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date & Time</th>
                    <th>Product</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th>Shop</th>
                    @endif
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Stock Balance</th>
                    <th>User</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                    @php
                        $badgeClass = match($txn->transaction_type) {
                            'STOCK_IN' => 'success',
                            'SALE' => 'primary',
                            'CUSTOMER_RETURN' => 'info text-white',
                            'EXCHANGE_IN' => 'warning text-dark',
                            'EXCHANGE_OUT' => 'secondary',
                            'DAMAGE_LOSS' => 'danger',
                            default => 'dark'
                        };
                        $isInc = in_array($txn->transaction_type, ['STOCK_IN', 'CUSTOMER_RETURN', 'EXCHANGE_IN']);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $txn->created_at->format('d-M-Y') }}</strong><br>
                            <small class="text-muted">{{ $txn->created_at->format('h:i A') }}</small>
                        </td>
                        <td>
                            <strong>{{ $txn->inventoryItem->item_name ?? 'Deleted Item' }}</strong><br>
                            <small class="text-secondary font-monospace">{{ $txn->inventoryItem->sku ?? '-' }}</small>
                        </td>
                        @if(auth()->user()->isSuperAdmin())
                            <td><span class="badge text-bg-light border">{{ $txn->shop->name ?? 'Default' }}</span></td>
                        @endif
                        <td>
                            <span class="badge text-bg-{{ $badgeClass }}">
                                {{ str($txn->transaction_type)->replace('_', ' ') }}
                            </span>
                        </td>
                        <td class="fw-bold text-{{ $isInc ? 'success' : 'danger' }}">
                            {{ $isInc ? '+' : '-' }}{{ number_format($txn->quantity, 2) }}
                        </td>
                        <td>
                            <span class="text-muted">{{ number_format($txn->balance_before, 2) }}</span>
                            <i class="bi bi-arrow-right mx-1 text-secondary small"></i>
                            <strong>{{ number_format($txn->balance_after, 2) }}</strong>
                        </td>
                        <td>{{ $txn->creator->name ?? 'System' }}</td>
                        <td class="small">{{ $txn->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="text-center py-4 text-muted">
                            No movement logs found for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $transactions->onEachSide(1)->links() }}</div>
@endsection
