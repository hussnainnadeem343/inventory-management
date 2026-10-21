@extends('layouts.app')
@section('title', 'GRN ' . $grn->grn_number)
@section('content')
{{-- Printable Official GRN Letterhead Header (Print Only) --}}
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
            <span class="badge border border-dark px-3 py-1 fs-6 text-uppercase fw-bold mb-1 d-inline-block">GOODS RECEIVED NOTE (GRN)</span>
            <div class="font-monospace fw-bold fs-5 text-dark mt-1">{{ $grn->grn_number }}</div>
            <div class="small text-secondary">Received Date: <strong>{{ $grn->received_date->format('d M, Y') }}</strong></div>
            <div class="small text-secondary">Billing Status: <strong class="text-uppercase">{{ str_replace('_', ' ', $grn->billing_status) }}</strong></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $grn->grn_number }}</h4>
            <span class="badge bg-success-subtle text-success fs-6"><i class="bi bi-check-circle-fill me-1"></i> Stock Inward Received</span>
            <span class="badge bg-secondary-subtle text-secondary fs-6"><i class="bi bi-lock-fill me-1"></i> Posted (Locked)</span>
            @if($grn->billing_status === 'fully_billed')
                <span class="badge bg-primary-subtle text-primary">Fully Billed</span>
            @else
                <span class="badge bg-warning-subtle text-warning-emphasis">Pending Invoice</span>
            @endif
        </div>
        <small class="text-secondary">Received on {{ $grn->received_date->format('d M, Y') }}</small>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('purchases.grn.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-outline-dark btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print GRN
        </button>
        @if($grn->billing_status !== 'fully_billed')
            <a href="{{ route('purchases.invoices.create', ['goods_received_note_id' => $grn->id]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-receipt me-1"></i> Generate Purchase Invoice (Bill)
            </a>
        @endif
        <a href="{{ route('purchases.returns.create', ['goods_received_note_id' => $grn->id]) }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-arrow-return-left me-1"></i> Return Items
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
            <div><strong>Vendor Challan / Bill #:</strong> {{ $grn->supplier_invoice_no ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- GRN Meta -->
    <div class="col-md-6">
        <div class="card p-3 shadow-sm h-100">
            <small class="text-secondary fw-semibold">RECEIPT & LOGISTICS INFORMATION</small>
            <div class="row g-2 mt-1">
                <div class="col-6"><strong>GRN Number:</strong> {{ $grn->grn_number }}</div>
                <div class="col-6"><strong>Date Received:</strong> {{ $grn->received_date->format('d M, Y') }}</div>
                <div class="col-6">
                    <strong>Gate Pass (IGP):</strong> 
                    @if($grn->inwardGatePass)
                        <a href="{{ route('purchases.igp.show', $grn->inwardGatePass) }}">{{ $grn->inwardGatePass->igp_number }}</a>
                    @else
                        Direct Inward
                    @endif
                </div>
                <div class="col-6">
                    <strong>PO Reference:</strong> 
                    @if($grn->purchaseOrder)
                        <a href="{{ route('purchases.orders.show', $grn->purchaseOrder) }}">{{ $grn->purchaseOrder->po_number }}</a>
                    @else
                        Direct Inward
                    @endif
                </div>
                <div class="col-6"><strong>Received Via:</strong> {{ $grn->received_via ?? 'Road / Truck' }}</div>
                <div class="col-6"><strong>Inspector / Staff:</strong> {{ $grn->receiver ? $grn->receiver->name : 'System' }}</div>
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
                    <th class="ps-3">Item Description</th>
                    <th>Warehouse</th>
                    <th>Batch No.</th>
                    <th>Expiry Date</th>
                    <th class="text-end">Accepted Qty</th>
                    <th class="text-end">Rejected Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end pe-3">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grn->items as $item)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $item->inventoryItem->item_name }}</div>
                            <small class="text-muted">SKU: {{ $item->inventoryItem->sku }}</small>
                        </td>
                        <td>{{ $item->warehouse?->name ?? 'Default Store' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $item->batch_number ?? 'N/A' }}</span>
                        </td>
                        <td>{{ $item->expiry_date ? $item->expiry_date->format('d M, Y') : '—' }}</td>
                        <td class="text-end fw-bold text-success">{{ number_format($item->quantity, 2) }} {{ $item->inventoryItem->unit }}</td>
                        <td class="text-end text-danger">{{ number_format($item->rejected_quantity, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-end pe-3 fw-bold">Rs. {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr class="table-active">
                    <td colspan="7" class="text-end fw-bold fs-6">Total Stock Inward Value:</td>
                    <td class="text-end pe-3 fw-bold text-success fs-6">Rs. {{ number_format($grn->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Landed Expenses if recorded on GRN -->
@if($grn->expenses->isNotEmpty())
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-1"></i> Recorded GRN Landed Expenses</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Expense Head</th>
                        <th>Account</th>
                        <th>Comments</th>
                        <th class="text-end pe-3">Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grn->expenses as $exp)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $exp->expense_type }}</td>
                            <td>{{ $exp->account ? $exp->account->name : 'N/A' }}</td>
                            <td>{{ $exp->comments ?? '-' }}</td>
                            <td class="text-end pe-3 fw-bold">Rs. {{ number_format($exp->debit > 0 ? $exp->debit : ($exp->rate * $exp->quantity), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Total Landed Expenses:</td>
                        <td class="text-end pe-3 fw-bold text-primary">Rs. {{ number_format($grn->landed_expenses_total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif

@if($grn->notes)
    <div class="card shadow-sm p-3 mb-4">
        <strong class="text-secondary small">REMARKS / NOTES:</strong>
        <p class="mb-0 mt-1">{{ $grn->notes }}</p>
    </div>
@endif

{{-- Printable Official Authorization Signatures Footer (Print Only) --}}
<div class="d-none d-print-block print-signature-block">
    <div class="row text-center">
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Goods Received By</strong>
                <span class="text-secondary" style="font-size: 11px;">{{ $grn->receiver?->name ?? auth()->user()->name }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Quality Inspected By</strong>
                <span class="text-secondary" style="font-size: 11px;">QC Inspector / Stamp</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Warehouse Manager</strong>
                <span class="text-secondary" style="font-size: 11px;">Authorized Approval</span>
            </div>
        </div>
    </div>
    <div class="text-center text-secondary mt-4" style="font-size: 10px;">
        Generated on {{ now()->format('d M, Y h:i A') }} &bull; DenFin ERP Enterprise Document &bull; Stock Inward Voucher
    </div>
</div>

@endsection
