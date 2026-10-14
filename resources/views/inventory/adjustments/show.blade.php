@extends('layouts.app')
@section('title', 'Adjustment ' . $adjustment->adjustment_number)
@section('content')
{{-- Printable Official Stock Adjustment Header (Print Only) --}}
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
            <span class="badge border border-dark px-3 py-1 fs-6 text-uppercase fw-bold mb-1 d-inline-block">STOCK ADJUSTMENT VOUCHER</span>
            <div class="font-monospace fw-bold fs-5 text-dark mt-1">{{ $adjustment->adjustment_number }}</div>
            <div class="small text-secondary">Adjustment Date: <strong>{{ $adjustment->adjustment_date->format('d M, Y') }}</strong></div>
            <div class="small text-secondary">Type: <strong class="text-uppercase">{{ $adjustment->type === 'addition' ? 'Stock Addition' : 'Stock Deduction' }}</strong></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $adjustment->adjustment_number }}</h4>
            @if($adjustment->type === 'addition')
                <span class="badge bg-success-subtle text-success fs-6">+ Stock Addition</span>
            @else
                <span class="badge bg-danger-subtle text-danger fs-6">- Stock Deduction</span>
            @endif
        </div>
        <small class="text-secondary">Recorded on {{ $adjustment->adjustment_date->format('d M Y') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('adjustments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Audit Note
        </button>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <strong>Warehouse:</strong> {{ $adjustment->warehouse ? $adjustment->warehouse->name : 'General Stock' }}
            </div>
            <div class="col-md-4">
                <strong>Reason:</strong> {{ $adjustment->reason }}
            </div>
            <div class="col-md-4">
                <strong>Auditor / Recorded By:</strong> {{ $adjustment->creator ? $adjustment->creator->name : 'System' }}
            </div>
            <div class="col-12 border-top pt-2">
                <strong>Total Adjustment Value:</strong> 
                <span class="fw-bold {{ $adjustment->type === 'addition' ? 'text-success' : 'text-danger' }}">
                    {{ $adjustment->type === 'addition' ? '+' : '-' }} Rs. {{ number_format($adjustment->total_amount, 2) }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Adjusted Items</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Item Description</th>
                    <th>SKU</th>
                    <th>Adjusted Qty</th>
                    <th>Unit Cost</th>
                    <th class="text-end">Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustment->items as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item->inventoryItem->item_name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $item->inventoryItem->sku }}</span></td>
                        <td class="fw-bold fs-6">
                            {{ $adjustment->type === 'addition' ? '+' : '-' }} {{ number_format($item->quantity, 2) }} {{ $item->inventoryItem->unit }}
                        </td>
                        <td>Rs. {{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-end fw-bold">Rs. {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($adjustment->notes)
    <div class="card shadow-sm p-3">
        <strong class="text-secondary small">REMARKS / AUDIT NOTES:</strong>
        <p class="mb-0 mt-1">{{ $adjustment->notes }}</p>
    </div>
@endif

{{-- Printable Official Authorization Signatures Footer (Print Only) --}}
<div class="d-none d-print-block print-signature-block">
    <div class="row text-center">
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Recorded By</strong>
                <span class="text-secondary" style="font-size: 11px;">{{ $adjustment->creator?->name ?? auth()->user()->name }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Internal Stock Auditor</strong>
                <span class="text-secondary" style="font-size: 11px;">Verification & Stamp</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Operations / Manager</strong>
                <span class="text-secondary" style="font-size: 11px;">Approval Signature</span>
            </div>
        </div>
    </div>
    <div class="text-center text-secondary mt-4" style="font-size: 10px;">
        Generated on {{ now()->format('d M, Y h:i A') }} &bull; DenFin ERP Enterprise Document &bull; Stock Adjustment Voucher
    </div>
</div>
@endsection
