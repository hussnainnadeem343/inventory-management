@extends('layouts.app')
@section('title', 'Purchase Orders')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Purchase Orders (PO)</h4>
        <small class="text-secondary">Procurement orders sent to suppliers and vendors</small>
    </div>
    @if(auth()->user()->hasPermission('purchases.create'))
        <a class="btn btn-primary" href="{{ route('purchases.orders.create') }}">
            <i class="bi bi-cart-plus me-1"></i> Create Purchase Order
        </a>
    @endif
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-5 col-md-6">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="PO number or supplier name...">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All Statuses</option>
                <option value="draft" @selected($status === 'draft')>Draft</option>
                <option value="approved" @selected($status === 'approved')>Approved</option>
                <option value="partially_received" @selected($status === 'partially_received')>Partially Received</option>
                <option value="received" @selected($status === 'received')>Fully Received</option>
                <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
            </select>
        </div>
        <div class="col-lg-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('purchases.orders.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th>Items</th>
                    <th>Grand Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $po)
                    <tr>
                        <td>
                            <a href="{{ route('purchases.orders.show', $po) }}" class="fw-bold text-decoration-none">
                                {{ $po->po_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $po->supplier->name }}</div>
                            @if($po->supplier->company_name)
                                <small class="text-muted">{{ $po->supplier->company_name }}</small>
                            @endif
                        </td>
                        <td>{{ $po->order_date->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $po->items->count() }} items</span>
                        </td>
                        <td class="fw-bold text-dark">Rs. {{ number_format($po->grand_total, 2) }}</td>
                        <td>
                            @php
                                $badgeClass = match($po->status) {
                                    'draft' => 'bg-secondary-subtle text-secondary',
                                    'approved' => 'bg-primary-subtle text-primary',
                                    'partially_received' => 'bg-warning-subtle text-warning',
                                    'received' => 'bg-success-subtle text-success',
                                    'cancelled' => 'bg-danger-subtle text-danger',
                                    default => 'bg-light text-dark',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $po->status)) }}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="{{ route('purchases.orders.show', $po) }}" title="View Order">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(in_array($po->status, ['approved', 'partially_received']) && auth()->user()->hasPermission('purchases.grn'))
                                    <a class="btn btn-outline-success" href="{{ route('purchases.grn.create') }}?purchase_order_id={{ $po->id }}" title="Receive Goods (GRN)">
                                        <i class="bi bi-box-arrow-in-down me-1"></i> Receive
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-secondary">
                            <i class="bi bi-cart-x fs-2 d-block mb-1"></i>
                            No purchase orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
        <div class="p-3 border-top">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
