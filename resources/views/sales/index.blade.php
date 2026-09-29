@extends('layouts.app')

@section('title', 'Sales & Invoices')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-receipt text-primary me-2"></i>Sales & Invoices Ledger</h2>
        <p class="text-secondary mb-0">Overview of all customer counter sales and invoices.</p>
    </div>

    <div>
        <a href="{{ route('pos') }}" class="btn btn-success">
            <i class="bi bi-cart3 me-1"></i>+ New POS Sale
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Filtered Invoices</div>
                    <div class="kpi-value text-primary">{{ number_format($sales->total()) }}</div>
                </div>
                <span class="kpi-icon"><i class="bi bi-receipt-cutoff"></i></span>
            </div>
            <div class="small text-secondary mt-1">Total orders processed</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Total Revenue (Filtered)</div>
                    <div class="kpi-value text-success">Rs. {{ number_format($totalSalesAmount, 2) }}</div>
                </div>
                <span class="kpi-icon" style="background: #e8f7ee; color: #16a34a;"><i class="bi bi-cash-stack"></i></span>
            </div>
            <div class="small text-secondary mt-1">Sum of invoices amount</div>
        </div>
    </div>
</div>

{{-- Filters Card --}}
<div class="card p-3 mb-3 shadow-sm">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-3 col-md-6">
            <label class="form-label">Search Invoice or Customer</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Invoice #, customer name or phone">
        </div>

        @if(auth()->user()->isSuperAdmin() && $shops->count() > 1)
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Shop</label>
                <select class="form-select" name="shop_id">
                    <option value="">All Shops</option>
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" @selected($shopId == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-lg-2 col-md-3">
            <label class="form-label">Payment Method</label>
            <select class="form-select" name="payment_method">
                <option value="">All Methods</option>
                <option value="cash" @selected($paymentMethod === 'cash')>Cash</option>
                <option value="card" @selected($paymentMethod === 'card')>Card</option>
                <option value="bank_transfer" @selected($paymentMethod === 'bank_transfer')>Bank</option>
                <option value="credit" @selected($paymentMethod === 'credit')>Credit</option>
            </select>
        </div>

        <div class="col-lg-2 col-md-3">
            <label class="form-label">From Date</label>
            <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
        </div>

        <div class="col-lg-2 col-md-3">
            <label class="form-label">To Date</label>
            <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
        </div>

        <div class="col-lg-1 d-flex gap-1">
            <button class="btn btn-primary w-100" type="submit">Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('sales.index') }}" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

{{-- Invoices Table --}}
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Date & Time</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th>Shop</th>
                    @endif
                    <th>Customer</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">Discount</th>
                    <th class="text-end">Net Total</th>
                    <th class="text-center">Payment</th>
                    <th>Cashier</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td>
                            <a href="{{ route('sales.show', $sale) }}" class="fw-bold font-monospace text-decoration-none">
                                {{ $sale->invoice_no }}
                            </a>
                        </td>
                        <td class="text-secondary small">{{ $sale->created_at->format('d-M-Y h:i A') }}</td>
                        @if(auth()->user()->isSuperAdmin())
                            <td><span class="badge text-bg-light border">{{ $sale->shop->name }}</span></td>
                        @endif
                        <td>
                            @if($sale->customer_name)
                                <div class="fw-semibold">{{ $sale->customer_name }}</div>
                                @if($sale->customer_phone)
                                    <small class="text-muted">{{ $sale->customer_phone }}</small>
                                @endif
                            @else
                                <span class="text-muted small">Walk-in</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold">{{ $sale->total_items }}</td>
                        <td class="text-end text-secondary small">Rs. {{ number_format($sale->subtotal, 2) }}</td>
                        <td class="text-end text-{{ $sale->discount > 0 ? 'danger' : 'secondary' }} small">
                            {{ $sale->discount > 0 ? '- Rs. ' . number_format($sale->discount, 2) : '-' }}
                        </td>
                        <td class="text-end fw-bold text-dark fs-6">
                            Rs. {{ number_format($sale->total_amount, 2) }}
                        </td>
                        <td class="text-center">
                            @php
                                $badgeColor = match($sale->payment_method) {
                                    'cash' => 'success',
                                    'card' => 'primary',
                                    'bank_transfer' => 'info',
                                    'credit' => 'warning',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge text-bg-{{ $badgeColor }}">
                                {{ ucfirst($sale->payment_method) }}
                            </span>
                        </td>
                        <td class="text-secondary small">{{ $sale->creator->name ?? 'Staff' }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-primary py-0" style="font-size: 11px;">
                                    View
                                </a>
                                <a href="{{ route('sales.receipt', $sale) }}" target="_blank" class="btn btn-sm btn-outline-dark py-0" style="font-size: 11px;" title="Thermal Receipt">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 11 : 10 }}" class="text-center py-5">
                            <div class="empty-state py-2">
                                <i class="bi bi-receipt text-secondary"></i>
                                <p class="mb-0">No sales or invoices found.</p>
                                <div class="mt-2">
                                    <a href="{{ route('pos') }}" class="btn btn-sm btn-primary">Start POS Billing</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $sales->links() }}</div>
@endsection
