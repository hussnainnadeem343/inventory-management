@extends('layouts.app')
@section('title', 'GRN ' . $grn->grn_number)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $grn->grn_number }}</h4>
            <span class="badge bg-success-subtle text-success fs-6">Stock Received</span>
        </div>
        <small class="text-secondary">Received on {{ $grn->received_date->format('d M Y') }}</small>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('purchases.grn.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print GRN
        </button>
        <a href="{{ route('purchases.grn.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Inward
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Supplier Details -->
    <div class="col-md-6">
        <div class="card p-3 shadow-sm h-100">
            <small class="text-secondary fw-semibold">VENDOR / SUPPLIER</small>
            <h5 class="fw-bold my-1 text-primary">{{ $grn->supplier->name }}</h5>
            @if($grn->supplier->company_name)
                <div><strong>Company:</strong> {{ $grn->supplier->company_name }}</div>
            @endif
            <div><strong>Phone:</strong> {{ $grn->supplier->phone ?? 'N/A' }}</div>
            <div><strong>Supplier Invoice #:</strong> {{ $grn->supplier_invoice_no ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- GRN Meta -->
    <div class="col-md-6">
        <div class="card p-3 shadow-sm h-100">
            <small class="text-secondary fw-semibold">RECEIPT INFORMATION</small>
            <div class="row g-2 mt-1">
                <div class="col-6"><strong>GRN Number:</strong> {{ $grn->grn_number }}</div>
                <div class="col-6"><strong>Date Received:</strong> {{ $grn->received_date->format('d M Y') }}</div>
                <div class="col-6">
                    <strong>PO Reference:</strong> 
                    @if($grn->purchaseOrder)
                        <a href="{{ route('purchases.orders.show', $grn->purchaseOrder) }}">{{ $grn->purchaseOrder->po_number }}</a>
                    @else
                        Direct Inward
                    @endif
                </div>
                <div class="col-6"><strong>Received By:</strong> {{ $grn->receiver ? $grn->receiver->name : 'System' }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Received Items & Batches -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-boxes me-1"></i> Received Inventory Items & Batches</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Item Description</th>
                    <th>Batch No.</th>
                    <th>Expiry Date</th>
                    <th>Received Qty</th>
                    <th>Unit Cost</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grn->items as $item)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $item->inventoryItem->item_name }}</div>
                            <small class="text-muted">SKU: {{ $item->inventoryItem->sku }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $item->batch_number ?? 'N/A' }}</span>
                        </td>
                        <td>{{ $item->expiry_date ? $item->expiry_date->format('d M Y') : '—' }}</td>
                        <td class="fw-bold">{{ number_format($item->quantity, 2) }} {{ $item->inventoryItem->unit }}</td>
                        <td>Rs. {{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-end fw-bold">Rs. {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr class="table-active">
                    <td colspan="5" class="text-end fw-bold fs-6">Total Inward Value:</td>
                    <td class="text-end fw-bold text-success fs-6">Rs. {{ number_format($grn->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if($grn->notes)
    <div class="card shadow-sm p-3">
        <strong class="text-secondary small">REMARKS / NOTES:</strong>
        <p class="mb-0 mt-1">{{ $grn->notes }}</p>
    </div>
@endif
@endsection
