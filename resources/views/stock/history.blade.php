@extends('layouts.app')
@section('title', 'Item Audit History - ' . $product->item_name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-1">{{ $product->item_name }}</h2>
        <span class="text-secondary font-monospace">{{ $product->sku ?: 'No SKU' }}</span>
        <span class="mx-2">•</span>
        <span class="badge text-bg-{{ $product->quantity > 0 ? 'success' : 'danger' }}">
            Current Stock: {{ number_format($product->quantity, 2) }}
        </span>
        <span class="mx-2">•</span>
        <span class="fw-semibold text-primary">Selling: Rs. {{ number_format($product->selling_price, 2) }}</span>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin())
            <span class="mx-2">•</span>
            <span class="text-muted">Avg Cost: Rs. {{ number_format($product->purchase_price, 2) }}</span>
        @endif
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('stock.index') }}">
        <i class="bi bi-arrow-left me-1"></i>Back to Stock
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Qty Change</th>
                    <th>Stock Balance</th>
                    <th>Price Snapshot</th>
                    <th>Performed By</th>
                    <th>Notes / Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    @php
                        $badgeClass = match($transaction->transaction_type) {
                            'STOCK_IN' => 'success',
                            'SALE' => 'primary',
                            'CUSTOMER_RETURN' => 'info text-white',
                            'EXCHANGE_IN' => 'warning text-dark',
                            'EXCHANGE_OUT' => 'secondary',
                            'DAMAGE_LOSS' => 'danger',
                            default => 'dark'
                        };

                        $isIncrement = in_array($transaction->transaction_type, ['STOCK_IN', 'CUSTOMER_RETURN', 'EXCHANGE_IN']);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $transaction->created_at->format('d-M-Y') }}</strong><br>
                            <small class="text-muted">{{ $transaction->created_at->format('h:i:s A') }}</small>
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $badgeClass }}">
                                {{ str($transaction->transaction_type)->replace('_', ' ') }}
                            </span>
                        </td>
                        <td class="fw-bold text-{{ $isIncrement ? 'success' : 'danger' }}">
                            {{ $isIncrement ? '+' : '-' }}{{ number_format($transaction->quantity, 2) }}
                        </td>
                        <td>
                            <span class="text-muted">{{ number_format($transaction->balance_before, 2) }}</span>
                            <i class="bi bi-arrow-right mx-1 text-secondary small"></i>
                            <strong>{{ number_format($transaction->balance_after, 2) }}</strong>
                        </td>
                        <td class="small">
                            @if($transaction->unit_sale_price)
                                <span>Sell/Ref: Rs. {{ number_format($transaction->unit_sale_price, 2) }}</span><br>
                            @endif
                            @if((auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin()) && $transaction->unit_cost)
                                <span class="text-muted">Cost: Rs. {{ number_format($transaction->unit_cost, 2) }}</span>
                            @endif
                        </td>
                        <td>{{ $transaction->creator->name ?? 'System' }}</td>
                        <td>
                            <span class="small">{{ $transaction->notes ?: '-' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No transaction history found for this product.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $transactions->onEachSide(1)->links() }}</div>
@endsection
