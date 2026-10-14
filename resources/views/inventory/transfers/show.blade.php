@extends('layouts.app')
@section('title', 'Transfer ' . $transfer->transfer_number)
@section('content')
{{-- Printable Official Transfer Delivery Challan Header (Print Only) --}}
<div class="d-none d-print-block mb-4 pb-3 border-bottom">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h2 class="h4 fw-bold mb-1 text-dark">{{ auth()->user()->shop ? auth()->user()->shop->name : 'DenFin ERP' }}</h2>
            <div class="text-secondary small">
                @if(auth()->user()->shop && auth()->user()->shop->address)
                    <div><i class="bi bi-geo-alt me-1"></i>{{ auth()->user()->shop->address }}</div>
                @endif
                @if(auth()->user()->shop && auth()->user()->shop->phone)
                    <div><i class="bi bi-telephone me-1"></i>Phone: {{ auth()->user()->shop->phone }}</div>
                @endif
                <div>Branch Code: <strong>{{ auth()->user()->shop ? auth()->user()->shop->code : 'MAIN' }}</strong></div>
            </div>
        </div>
        <div class="text-end">
            <span class="badge border border-dark px-3 py-1 fs-6 text-uppercase fw-bold mb-1 d-inline-block">STOCK TRANSFER / DELIVERY CHALLAN</span>
            <div class="font-monospace fw-bold fs-5 text-dark mt-1">{{ $transfer->transfer_number }}</div>
            <div class="small text-secondary">Dispatch Date: <strong>{{ $transfer->transfer_date->format('d M, Y') }}</strong></div>
            <div class="small text-secondary">Status: <strong class="text-uppercase">{{ str_replace('_', ' ', $transfer->status) }}</strong></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
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

{{-- Printable Official Authorization Signatures Footer (Print Only) --}}
<div class="d-none d-print-block print-signature-block">
    <div class="row text-center">
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Dispatched By</strong>
                <span class="text-secondary" style="font-size: 11px;">Source Warehouse Officer</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Carrier / Driver</strong>
                <span class="text-secondary" style="font-size: 11px;">Transit Signature</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Received At Destination</strong>
                <span class="text-secondary" style="font-size: 11px;">Destination Store Incharge</span>
            </div>
        </div>
    </div>
    <div class="text-center text-secondary mt-4" style="font-size: 10px;">
        Generated on {{ now()->format('d M, Y h:i A') }} &bull; DenFin ERP Enterprise Document &bull; Stock Transfer Delivery Challan
    </div>
</div>
@endsection
