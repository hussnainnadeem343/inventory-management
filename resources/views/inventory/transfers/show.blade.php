@extends('layouts.app')
@section('title', 'Transfer ' . $transfer->transfer_number)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $transfer->transfer_number }}</h4>
            <span class="badge bg-success-subtle text-success fs-6">Transfer Completed</span>
        </div>
        <small class="text-secondary">Dispatched on {{ $transfer->transfer_date->format('d M Y') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Delivery Challan
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card p-3 shadow-sm h-100 border-start border-4 border-danger">
            <small class="text-secondary fw-semibold">SOURCE LOCATION</small>
            <h5 class="fw-bold my-1 text-danger">{{ $transfer->fromWarehouse->name }}</h5>
            <div><strong>Code:</strong> {{ $transfer->fromWarehouse->code }}</div>
            <div><strong>Contact:</strong> {{ $transfer->fromWarehouse->contact_person ?? '—' }} ({{ $transfer->fromWarehouse->phone ?? '—' }})</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 shadow-sm h-100 border-start border-4 border-success">
            <small class="text-secondary fw-semibold">DESTINATION LOCATION</small>
            <h5 class="fw-bold my-1 text-success">{{ $transfer->toWarehouse->name }}</h5>
            <div><strong>Code:</strong> {{ $transfer->toWarehouse->code }}</div>
            <div><strong>Contact:</strong> {{ $transfer->toWarehouse->contact_person ?? '—' }} ({{ $transfer->toWarehouse->phone ?? '—' }})</div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Transferred Products</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Item Description</th>
                    <th>SKU</th>
                    <th>Transferred Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfer->items as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item->inventoryItem->item_name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $item->inventoryItem->sku }}</span></td>
                        <td class="fw-bold fs-6">{{ number_format($item->quantity, 2) }} {{ $item->inventoryItem->unit }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($transfer->notes)
    <div class="card shadow-sm p-3">
        <strong class="text-secondary small">GATE PASS / DISPATCH NOTES:</strong>
        <p class="mb-0 mt-1">{{ $transfer->notes }}</p>
    </div>
@endif
@endsection
