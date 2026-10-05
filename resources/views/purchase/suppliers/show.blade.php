@extends('layouts.app')
@section('title', 'Supplier Profile - ' . $supplier->name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">{{ $supplier->name }}</h4>
        <span class="text-secondary">{{ $supplier->company_name ?? 'Individual Vendor' }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('purchases.suppliers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        @if(auth()->user()->hasPermission('purchases.create'))
            <a href="{{ route('purchases.suppliers.edit', $supplier) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ route('purchases.orders.create') }}?supplier_id={{ $supplier->id }}" class="btn btn-primary">
                <i class="bi bi-cart-plus me-1"></i> Create PO
            </a>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm bg-white">
            <small class="text-secondary fw-semibold">CURRENT PAYABLE BALANCE</small>
            <h3 class="fw-bold my-1 {{ $supplier->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                Rs. {{ number_format($supplier->current_balance, 2) }}
            </h3>
            <small class="text-muted">Opening: Rs. {{ number_format($supplier->opening_balance, 2) }}</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm bg-white">
            <small class="text-secondary fw-semibold">CONTACT INFORMATION</small>
            <div class="mt-2">
                <div><i class="bi bi-telephone text-secondary me-2"></i>{{ $supplier->phone ?? 'N/A' }}</div>
                <div><i class="bi bi-envelope text-secondary me-2"></i>{{ $supplier->email ?? 'N/A' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm bg-white">
            <small class="text-secondary fw-semibold">ADDRESS & STATUS</small>
            <div class="mt-2">
                <div><i class="bi bi-geo-alt text-secondary me-2"></i>{{ $supplier->address ?? 'N/A' }}</div>
                <div class="mt-1">
                    Status: 
                    @if($supplier->status === 'active')
                        <span class="badge bg-success-subtle text-success">Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent Goods Received (GRN) -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-box-arrow-in-down me-1 text-primary"></i> Recent Stock Inwards (GRN)</h6>
                <a href="{{ route('purchases.grn.index') }}?search={{ urlencode($supplier->name) }}" class="small text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>GRN #</th>
                            <th>Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplier->goodsReceivedNotes as $grn)
                            <tr>
                                <td>
                                    <a href="{{ route('purchases.grn.show', $grn) }}" class="fw-semibold text-decoration-none">
                                        {{ $grn->grn_number }}
                                    </a>
                                </td>
                                <td>{{ $grn->received_date->format('d M Y') }}</td>
                                <td class="fw-bold">Rs. {{ number_format($grn->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No GRNs recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Purchase Orders -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-cart-check me-1 text-info"></i> Recent Purchase Orders</h6>
                <a href="{{ route('purchases.orders.index') }}?search={{ urlencode($supplier->name) }}" class="small text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>PO #</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplier->purchaseOrders as $po)
                            <tr>
                                <td>
                                    <a href="{{ route('purchases.orders.show', $po) }}" class="fw-semibold text-decoration-none">
                                        {{ $po->po_number }}
                                    </a>
                                </td>
                                <td>{{ $po->order_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($po->status) }}</span>
                                </td>
                                <td class="fw-bold">Rs. {{ number_format($po->grand_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No purchase orders created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
