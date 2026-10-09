@extends('layouts.app')
@section('title', 'Debit Note ' . $return->return_number)
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold">{{ $return->return_number }}</h5>
                    <span class="badge bg-danger-subtle text-danger fs-6">
                        <i class="bi bi-arrow-return-left me-1"></i> Debit Note / Purchase Return
                    </span>
                    <span class="badge bg-success-subtle text-success">
                        {{ ucfirst($return->status) }}
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('purchases.returns.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <button class="btn btn-outline-dark btn-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Debit Note
                    </button>
                    @if($return->goodsReceivedNote)
                        <a href="{{ route('purchases.grn.show', $return->goodsReceivedNote) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box-arrow-in-down me-1"></i> View GRN
                        </a>
                    @endif
                    @if($return->purchaseInvoice)
                        <a href="{{ route('purchases.invoices.show', $return->purchaseInvoice) }}" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-receipt me-1"></i> View Invoice
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body p-4">
                {{-- Metadata Grid --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Supplier / Vendor</small>
                        <h6 class="fw-bold mb-0 text-primary">{{ $return->supplier->name }}</h6>
                        @if($return->supplier->phone)
                            <small class="text-muted d-block">{{ $return->supplier->phone }}</small>
                        @endif
                        <small class="text-muted">Current Balance: Rs. {{ number_format($return->supplier->current_balance, 2) }}</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Return Date</small>
                        <span class="fw-semibold">{{ $return->return_date->format('d M, Y') }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Primary Reason</small>
                        <span class="fw-bold text-danger">{{ $return->reason }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Issued By</small>
                        <span>{{ $return->creator->name ?? 'System' }}</span>
                    </div>
                    @if($return->goodsReceivedNote)
                        <div class="col-md-3">
                            <small class="text-secondary d-block">Referenced GRN</small>
                            <a href="{{ route('purchases.grn.show', $return->goodsReceivedNote) }}" class="fw-semibold text-decoration-none">
                                {{ $return->goodsReceivedNote->grn_number }}
                            </a>
                        </div>
                    @endif
                    @if($return->purchaseInvoice)
                        <div class="col-md-3">
                            <small class="text-secondary d-block">Referenced Invoice</small>
                            <a href="{{ route('purchases.invoices.show', $return->purchaseInvoice) }}" class="fw-semibold text-decoration-none">
                                {{ $return->purchaseInvoice->invoice_number }}
                            </a>
                        </div>
                    @endif
                    @if($return->notes)
                        <div class="col-12">
                            <div class="p-2 bg-light rounded small">
                                <strong>Remarks / Claim Note:</strong> {{ $return->notes }}
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Returned Products Table --}}
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="bg-light small text-secondary">
                            <tr>
                                <th class="ps-3" style="width: 5%;">#</th>
                                <th style="width: 35%;">Product Item</th>
                                <th style="width: 15%;">Batch #</th>
                                <th class="text-end" style="width: 12%;">Qty Returned</th>
                                <th class="text-end" style="width: 15%;">Unit Cost (Rs.)</th>
                                <th class="text-end pe-3" style="width: 18%;">Subtotal (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($return->items as $idx => $line)
                                <tr>
                                    <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $line->inventoryItem->item_name }}</div>
                                        <div class="small text-muted">SKU: {{ $line->inventoryItem->sku ?? 'N/A' }} | Category: {{ $line->inventoryItem->category->name ?? 'N/A' }}</div>
                                        @if($line->reason && $line->reason !== $return->reason)
                                            <div class="small text-danger fst-italic">Note: {{ $line->reason }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($line->batch)
                                            <span class="badge bg-light text-dark border">{{ $line->batch->batch_number }}</span>
                                        @else
                                            <span class="text-muted">Standard</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ number_format($line->quantity, 2) }} {{ $line->inventoryItem->unit ?? 'pcs' }}
                                    </td>
                                    <td class="text-end">
                                        Rs. {{ number_format($line->unit_cost, 2) }}
                                    </td>
                                    <td class="text-end pe-3 fw-bold text-danger">
                                        - Rs. {{ number_format($line->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="3" class="ps-3 text-end">Total Return Units:</th>
                                <th class="text-end">{{ number_format($return->items->sum('quantity'), 2) }}</th>
                                <th class="text-end">Total Credit Claim:</th>
                                <th class="text-end pe-3 text-danger fs-5 fw-bold">- Rs. {{ number_format($return->total_amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Accounting Impact Box --}}
                <div class="row g-3">
                    <div class="col-md-7">
                        <div class="alert alert-secondary small mb-0">
                            <div class="fw-bold mb-1"><i class="bi bi-journal-check me-1"></i> Automated General Ledger Reversal:</div>
                            <div class="d-flex justify-content-between border-bottom py-1">
                                <span>Debit: Accounts Payable (Vendor Account)</span>
                                <strong>Rs. {{ number_format($return->total_amount, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span>Credit: Merchandise Inventory (Asset Decrease)</span>
                                <strong>Rs. {{ number_format($return->total_amount, 2) }}</strong>
                            </div>
                            <div class="text-muted mt-1 fst-italic">
                                Physical stock and supplier debt balance were automatically decremented.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card border bg-light">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-secondary">Subtotal:</span>
                                    <span>Rs. {{ number_format($return->subtotal, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-secondary">Tax Reversed:</span>
                                    <span>Rs. {{ number_format($return->tax_amount, 2) }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fs-6 fw-bold text-danger">Net Debit Value:</span>
                                    <span class="fs-4 fw-bold text-danger">- Rs. {{ number_format($return->total_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Authorization Signatures for Printing --}}
                <div class="row mt-5 pt-4 text-center d-none d-print-flex">
                    <div class="col-4">
                        <div class="border-top pt-2 small fw-bold">Prepared By / Store Incharge</div>
                    </div>
                    <div class="col-4">
                        <div class="border-top pt-2 small fw-bold">Quality Inspector / Checked By</div>
                    </div>
                    <div class="col-4">
                        <div class="border-top pt-2 small fw-bold">Vendor Acknowledgment / Sign</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
