@extends('layouts.app')

@section('title', 'Invoice #' . $sale->invoice_no)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
    <div>
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-receipt text-primary me-2"></i>Invoice Details</h2>
        <p class="text-secondary mb-0">Invoice #<strong>{{ $sale->invoice_no }}</strong> &bull; {{ $sale->created_at->format('d-M-Y h:i A') }}</p>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('sales.receipt', $sale) }}" target="_blank" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i>Thermal Receipt (80mm)
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-pdf me-1"></i>Print A4 Invoice
        </button>
        <a href="{{ route('pos') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i>New Sale (POS)
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card p-4 shadow-sm border" id="printable_invoice">
            {{-- Header --}}
            <div class="row border-bottom pb-3 mb-3">
                <div class="col-sm-7">
                    <h3 class="h4 fw-bold text-primary mb-1">{{ $sale->shop->name }}</h3>
                    <div class="text-secondary small">
                        <div>Branch Code: <strong>{{ $sale->shop->code }}</strong> &bull; {{ $sale->shop->business_type ?? 'Retail Store' }}</div>
                        @if($sale->shop->address)
                            <div><i class="bi bi-geo-alt me-1"></i>{{ $sale->shop->address }}</div>
                        @endif
                        @if($sale->shop->phone)
                            <div><i class="bi bi-telephone me-1"></i>{{ $sale->shop->phone }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-sm-5 text-sm-end mt-2 mt-sm-0">
                    <div class="badge bg-primary fs-6 mb-1">PAID INVOICE</div>
                    <div class="font-monospace fw-bold fs-5 text-dark">{{ $sale->invoice_no }}</div>
                    <div class="text-secondary small">Date: {{ $sale->created_at->format('d-M-Y h:i A') }}</div>
                    <div class="text-secondary small">Cashier: {{ $sale->creator->name ?? 'Staff' }}</div>
                </div>
            </div>

            {{-- Customer Info (if available) --}}
            @if($sale->customer_name || $sale->customer_phone)
                <div class="bg-light p-3 rounded-2 mb-3">
                    <div class="row">
                        <div class="col-sm-6">
                            <span class="text-secondary small">Customer Name:</span>
                            <div class="fw-semibold">{{ $sale->customer_name ?: 'Walk-in Customer' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-secondary small">Phone Number:</span>
                            <div class="fw-semibold">{{ $sale->customer_phone ?: 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Items Table --}}
            <div class="table-responsive mb-3">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 40%;">Item Description</th>
                            <th style="width: 12%;" class="text-center">Brand</th>
                            <th style="width: 13%;" class="text-end">Price (Rs.)</th>
                            <th style="width: 8%;" class="text-center">Qty</th>
                            <th style="width: 10%;" class="text-end">Discount</th>
                            <th style="width: 12%;" class="text-end">Total (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $idx => $item)
                            <tr>
                                <td class="text-secondary small">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->inventoryItem?->item_name ?? 'Item' }}</div>
                                    <code class="small text-secondary">{{ $item->inventoryItem?->sku ?? '' }}</code>
                                    @if($item->inventoryItem?->pack_label)
                                        <small class="text-muted">({{ $item->inventoryItem->pack_label }})</small>
                                    @endif
                                </td>
                                <td class="text-center small">{{ $item->inventoryItem?->brand?->name ?? '-' }}</td>
                                <td class="text-end">Rs. {{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-center fw-bold">{{ number_format($item->quantity, 0) }}</td>
                                <td class="text-end {{ $item->discount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    {{ $item->discount > 0 ? '- Rs. ' . number_format($item->discount, 2) : '-' }}
                                </td>
                                <td class="text-end fw-semibold">Rs. {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Financial Summary Breakdown --}}
            <div class="row justify-content-end mb-3">
                <div class="col-md-5">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-secondary">Gross Subtotal:</td>
                                    <td class="text-end fw-semibold">Rs. {{ number_format($sale->subtotal, 2) }}</td>
                                </tr>
                                @if($sale->items_discount_total > 0)
                                    <tr>
                                        <td class="text-danger">Item Discounts:</td>
                                        <td class="text-end text-danger fw-semibold">- Rs. {{ number_format($sale->items_discount_total, 2) }}</td>
                                    </tr>
                                @endif
                                @if($sale->discount > 0)
                                    <tr>
                                        <td class="text-danger">Bill Discount:</td>
                                        <td class="text-end text-danger fw-semibold">- Rs. {{ number_format($sale->discount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="border-top border-bottom">
                                    <td class="fs-6 fw-bold text-dark py-2">Net Total:</td>
                                    <td class="fs-6 fw-bold text-primary text-end py-2">Rs. {{ number_format($sale->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary small">Payment Method:</td>
                                    <td class="text-end fw-semibold text-uppercase small">{{ str($sale->payment_method)->replace('_', ' ') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary small">Paid Amount:</td>
                                    <td class="text-end fw-semibold small">Rs. {{ number_format($sale->paid_amount, 2) }}</td>
                                </tr>
                                @if($sale->change_amount > 0)
                                    <tr>
                                        <td class="text-success small">Change Returned:</td>
                                        <td class="text-end text-success fw-bold small">Rs. {{ number_format($sale->change_amount, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($sale->notes)
                <div class="alert alert-light border py-2 px-3 small text-secondary">
                    <strong>Notes:</strong> {{ $sale->notes }}
                </div>
            @endif

            {{-- Footer --}}
            <div class="border-top pt-3 text-center text-secondary small">
                <p class="mb-0">Thank you for your business! Please retain this receipt for any exchange within 7 days.</p>
                <div class="text-muted" style="font-size: 11px;">Powered by Multi-Shop Retail System</div>
            </div>
        </div>
    </div>
</div>
@endsection
