@extends('layouts.app')
@section('title', 'PO ' . $order->po_number)
@section('content')
{{-- Printable Official Document Letterhead Header (Print Only) --}}
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
            <span class="badge border border-dark px-3 py-1 fs-6 text-uppercase fw-bold mb-1 d-inline-block">PURCHASE ORDER</span>
            <div class="font-monospace fw-bold fs-5 text-dark mt-1">{{ $order->po_number }}</div>
            <div class="small text-secondary">Order Date: <strong>{{ $order->order_date->format('d M, Y') }}</strong></div>
            <div class="small text-secondary">Status: <strong class="text-uppercase">{{ str_replace('_', ' ', $order->status) }}</strong></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $order->po_number }}</h4>
            @php
                $badgeClass = match($order->status) {
                    'pending_approval' => 'bg-warning-subtle text-warning',
                    'draft' => 'bg-secondary-subtle text-secondary',
                    'approved' => 'bg-primary-subtle text-primary',
                    'partially_received' => 'bg-info-subtle text-info',
                    'received' => 'bg-success-subtle text-success',
                    'cancelled' => 'bg-danger-subtle text-danger',
                    default => 'bg-light text-dark',
                };
            @endphp
            <span class="badge {{ $badgeClass }} fs-6">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
        </div>
        <small class="text-secondary">Ordered on {{ $order->order_date->format('d M Y') }}</small>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('purchases.orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print PO
        </button>

        {{-- Edit and Delete buttons (enabled if no IGP/GRN) --}}
        @if($order->canBeEditedOrDeleted())
            <a href="{{ route('purchases.orders.edit', $order) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <form method="post" action="{{ route('purchases.orders.destroy', $order) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete PO {{ $order->po_number }}?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            </form>
        @endif

        {{-- Approval --}}
        @if(($order->isPending() || $order->status === 'draft') && (auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin() || auth()->user()->hasPermission('purchases.orders.approve')))
            <form method="post" action="{{ route('purchases.orders.approve', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i> Approve PO
                </button>
            </form>
        @endif

        {{-- IGP & GRN buttons (enabled if approved) --}}
        @if($order->isApproved())
            <a href="{{ route('purchases.igp.create') }}?purchase_order_id={{ $order->id }}" class="btn btn-info text-white">
                <i class="bi bi-door-open me-1"></i> Create IGP
            </a>
            <a href="{{ route('purchases.grn.create') }}?purchase_order_id={{ $order->id }}" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-down me-1"></i> Inward Goods (GRN)
            </a>
        @endif

        @if(!in_array($order->status, ['received', 'cancelled']) && auth()->user()->hasPermission('purchases.create'))
            <form method="post" action="{{ route('purchases.orders.cancel', $order) }}" data-confirm="Are you sure you want to cancel this PO?">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    Cancel
                </button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Supplier Info -->
    <div class="col-md-6">
        <div class="card p-3 shadow-sm h-100">
            <small class="text-secondary fw-semibold">VENDOR / SUPPLIER</small>
            <h5 class="fw-bold my-1 text-primary">{{ $order->supplier->name }}</h5>
            @if($order->supplier->company_name)
                <div><strong>Company:</strong> {{ $order->supplier->company_name }}</div>
            @endif
            <div><strong>Phone:</strong> {{ $order->supplier->phone ?? 'N/A' }}</div>
            <div><strong>Address:</strong> {{ $order->supplier->address ?? 'N/A' }}</div>
            @if($order->supplier->ntn)
                <div><strong>NTN:</strong> {{ $order->supplier->ntn }}</div>
            @endif
            @if($order->supplier->strn)
                <div><strong>STRN:</strong> {{ $order->supplier->strn }}</div>
            @endif
        </div>
    </div>

    <!-- PO Meta -->
    <div class="col-md-6">
        <div class="card p-3 shadow-sm h-100">
            <small class="text-secondary fw-semibold">ORDER DETAILS</small>
            <div class="row g-2 mt-1">
                <div class="col-6"><strong>PO Number:</strong> {{ $order->po_number }}</div>
                <div class="col-6"><strong>Order Date:</strong> {{ $order->order_date->format('d M Y') }}</div>
                <div class="col-6"><strong>Expected Delivery:</strong> {{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d M Y') : 'Immediate' }}</div>
                <div class="col-6"><strong>Created By:</strong> {{ $order->creator ? $order->creator->name : 'System' }}</div>
                <div class="col-6"><strong>Payment Mode:</strong> <span class="badge bg-light text-dark border">{{ ucfirst($order->payment_mode ?? 'bank') }}</span></div>
                <div class="col-6"><strong>Payment Terms:</strong> {{ $order->payment_terms === 'Other' && $order->payment_term_custom ? $order->payment_term_custom : ($order->payment_terms ?? 'Net 30') }}</div>
                @if($order->delivery_station)
                    <div class="col-6"><strong>Delivery Station:</strong> {{ $order->delivery_station }}</div>
                @endif
                @if($order->purchaseRequisition)
                    <div class="col-6"><strong>Requisition Ref:</strong> <span class="badge bg-info-subtle text-info">{{ $order->purchaseRequisition->pr_number }}</span></div>
                @endif
                @if($order->approved_by)
                    <div class="col-6"><strong>Approved By:</strong> <span class="text-success fw-semibold">{{ $order->approver?->name }}</span> ({{ $order->approved_at?->format('d M Y') }})</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Ordered Items -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Ordered Products & Receiving Status</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Item Description</th>
                    <th>Ordered Qty</th>
                    <th>Received Qty</th>
                    <th>Remaining Qty</th>
                    <th>Unit Cost</th>
                    <th>Disc. (Rs.)</th>
                    <th>VAT (%)</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    @php
                        $remaining = max(0, $item->quantity - $item->received_quantity);
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $item->inventoryItem->item_name }}</div>
                            <small class="text-muted">SKU: {{ $item->inventoryItem->sku }}</small>
                        </td>
                        <td>{{ number_format($item->quantity, 2) }} {{ $item->inventoryItem->unit }}</td>
                        <td>
                            <span class="badge {{ $item->received_quantity >= $item->quantity ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                {{ number_format($item->received_quantity, 2) }}
                            </span>
                        </td>
                        <td>
                            <span class="{{ $remaining > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ number_format($remaining, 2) }}
                            </span>
                        </td>
                        <td>Rs. {{ number_format($item->unit_cost, 2) }}</td>
                        <td>{{ (float)$item->discount_amount > 0 ? 'Rs. ' . number_format($item->discount_amount, 2) : '-' }}</td>
                        <td>{{ (float)$item->tax_percent > 0 ? number_format($item->tax_percent, 1) . '%' : '-' }}</td>
                        <td class="text-end fw-bold">Rs. {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="7" class="text-end">Subtotal:</td>
                    <td class="text-end fw-bold">Rs. {{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->discount_amount > 0)
                    <tr>
                        <td colspan="7" class="text-end text-danger">Discount:</td>
                        <td class="text-end fw-bold text-danger">- Rs. {{ number_format($order->discount_amount, 2) }}</td>
                    </tr>
                @endif
                @if($order->tax_amount > 0)
                    <tr>
                        <td colspan="7" class="text-end">Tax / VAT / GST:</td>
                        <td class="text-end fw-bold">+ Rs. {{ number_format($order->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="table-active">
                    <td colspan="7" class="text-end fw-bold fs-6">Grand Total:</td>
                    <td class="text-end fw-bold text-primary fs-6">Rs. {{ number_format($order->grand_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if($order->inwardGatePasses->isNotEmpty())
    <div class="card shadow-sm mb-4 d-print-none">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-door-open me-1 text-info"></i> Related Inward Gate Passes (IGP)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>IGP #</th>
                        <th>Date & Time</th>
                        <th>Challan / Bilty #</th>
                        <th>Vehicle #</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->inwardGatePasses as $igp)
                        <tr>
                            <td><span class="fw-bold">{{ $igp->igp_number }}</span></td>
                            <td>{{ $igp->inward_date->format('d M Y, h:i A') }}</td>
                            <td>{{ $igp->challan_no ?? '—' }}</td>
                            <td>{{ $igp->vehicle_no ?? '—' }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($igp->status) }}</span></td>
                            <td>
                                <a href="{{ route('purchases.igp.show', $igp) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye me-1"></i> View IGP
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($order->goodsReceivedNotes->isNotEmpty())
    <div class="card shadow-sm mb-4 d-print-none">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold"><i class="bi bi-box-arrow-in-down me-1 text-success"></i> Related Goods Received Notes (GRN)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>GRN #</th>
                        <th>Received Date</th>
                        <th>Invoice No</th>
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->goodsReceivedNotes as $grn)
                        <tr>
                            <td><span class="fw-bold">{{ $grn->grn_number }}</span></td>
                            <td>{{ $grn->received_date->format('d M Y') }}</td>
                            <td>{{ $grn->supplier_invoice_no ?? '—' }}</td>
                            <td class="fw-bold">Rs. {{ number_format($grn->total_amount, 2) }}</td>
                            <td>
                                <a href="{{ route('purchases.grn.show', $grn) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye me-1"></i> View GRN
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($order->notes)
    <div class="card shadow-sm p-3 mb-4">
        <strong class="text-secondary small">NOTES / INSTRUCTIONS:</strong>
        <p class="mb-0 mt-1">{{ $order->notes }}</p>
    </div>
@endif

{{-- Printable Official Authorization Signatures Footer (Print Only) --}}
<div class="d-none d-print-block print-signature-block">
    <div class="row text-center">
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Prepared By</strong>
                <span class="text-secondary" style="font-size: 11px;">{{ $order->creator?->name ?? auth()->user()->name }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Checked / Store In-charge</strong>
                <span class="text-secondary" style="font-size: 11px;">Signature & Stamp</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Authorized Approval</strong>
                <span class="text-secondary" style="font-size: 11px;">Manager / Director</span>
            </div>
        </div>
    </div>
    <div class="text-center text-secondary mt-4" style="font-size: 10px;">
        Generated on {{ now()->format('d M, Y h:i A') }} &bull; DenFin ERP Enterprise Document &bull; Purchase Order Voucher
    </div>
</div>

@endsection
