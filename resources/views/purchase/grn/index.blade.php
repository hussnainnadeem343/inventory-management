@extends('layouts.app')
@section('title', 'Goods Received Notes (GRN)')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Goods Received Notes (GRN)</h4>
        <small class="text-secondary">Stock inwards from suppliers & purchase orders</small>
    </div>
    @if(auth()->user()->hasPermission('purchases.grn'))
        <a class="btn btn-primary" href="{{ route('purchases.grn.create') }}">
            <i class="bi bi-box-arrow-in-down me-1"></i> Inward Goods (New GRN)
        </a>
    @endif
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-6 col-md-8">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="GRN number, supplier name, invoice no...">
        </div>
        <div class="col-lg-6 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('purchases.grn.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>GRN Number</th>
                    <th>Supplier</th>
                    <th>PO Reference</th>
                    <th>Received Date</th>
                    <th>Supplier Invoice</th>
                    <th>Total Inward Value</th>
                    <th>Received By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grns as $grn)
                    <tr>
                        <td>
                            <a href="{{ route('purchases.grn.show', $grn) }}" class="fw-bold text-decoration-none">
                                {{ $grn->grn_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $grn->supplier->name }}</div>
                            @if($grn->supplier->company_name)
                                <small class="text-muted">{{ $grn->supplier->company_name }}</small>
                            @endif
                        </td>
                        <td>
                            @if($grn->purchaseOrder)
                                <a href="{{ route('purchases.orders.show', $grn->purchaseOrder) }}" class="badge bg-light text-dark border text-decoration-none">
                                    {{ $grn->purchaseOrder->po_number }}
                                </a>
                            @else
                                <span class="text-muted">Direct Inward</span>
                            @endif
                        </td>
                        <td>{{ $grn->received_date->format('d M Y') }}</td>
                        <td>{{ $grn->supplier_invoice_no ?? '—' }}</td>
                        <td class="fw-bold text-dark">Rs. {{ number_format($grn->total_amount, 2) }}</td>
                        <td>{{ $grn->receiver ? $grn->receiver->name : 'System' }}</td>
                        <td class="text-end">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('purchases.grn.show', $grn) }}">
                                <i class="bi bi-eye me-1"></i> View Receipt
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-secondary">
                            <i class="bi bi-box-seam fs-2 d-block mb-1"></i>
                            No goods received notes found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($grns->hasPages())
        <div class="p-3 border-top">
            {{ $grns->links() }}
        </div>
    @endif
</div>
@endsection
