@extends('layouts.app')
@section('title', 'PO ' . $order->po_number)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $order->po_number }}</h4>
            @php
                $badgeClass = match($order->status) {
                    'draft' => 'bg-secondary-subtle text-secondary',
                    'approved' => 'bg-primary-subtle text-primary',
                    'partially_received' => 'bg-warning-subtle text-warning',
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

        @if($order->status === 'draft' && auth()->user()->hasPermission('purchases.create'))
            <form method="post" action="{{ route('purchases.orders.approve', $order) }}">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i> Approve PO
                </button>
            </form>
        @endif

        @if(in_array($order->status, ['approved', 'partially_received']) && auth()->user()->hasPermission('purchases.grn'))
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
                        <td class="text-end fw-bold">Rs. {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="5" class="text-end">Subtotal:</td>
                    <td class="text-end fw-bold">Rs. {{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->discount_amount > 0)
                    <tr>
                        <td colspan="5" class="text-end text-danger">Discount:</td>
                        <td class="text-end fw-bold text-danger">- Rs. {{ number_format($order->discount_amount, 2) }}</td>
                    </tr>
                @endif
                @if($order->tax_amount > 0)
                    <tr>
                        <td colspan="5" class="text-end">Tax / GST:</td>
                        <td class="text-end fw-bold">+ Rs. {{ number_format($order->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="table-active">
                    <td colspan="5" class="text-end fw-bold fs-6">Grand Total:</td>
                    <td class="text-end fw-bold text-primary fs-6">Rs. {{ number_format($order->grand_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if($order->goodsReceivedNotes->isNotEmpty())
    <div class="card shadow-sm mb-4">
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
    <div class="card shadow-sm p-3">
        <strong class="text-secondary small">NOTES / INSTRUCTIONS:</strong>
        <p class="mb-0 mt-1">{{ $order->notes }}</p>
    </div>
@endif
@endsection
