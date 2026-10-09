@extends('layouts.app')
@section('title', 'Purchase Invoice ' . $invoice->invoice_number)
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold">{{ $invoice->invoice_number }}</h5>
                    @if($invoice->manual_invoice_number)
                        <span class="badge bg-light text-dark border font-monospace">{{ $invoice->manual_invoice_number }}</span>
                    @endif
                    @php
                        $statusBadges = [
                            'unpaid' => ['danger', 'Unpaid'],
                            'partially_paid' => ['warning', 'Partially Paid'],
                            'paid' => ['success', 'Paid'],
                        ];
                        $sb = $statusBadges[$invoice->payment_status] ?? ['secondary', ucfirst($invoice->payment_status)];
                    @endphp
                    <span class="badge bg-{{ $sb[0] }}-subtle text-{{ $sb[0] }} fs-6">
                        {{ $sb[1] }}
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('purchases.invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <button class="btn btn-outline-dark btn-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Bill
                    </button>
                    @if($invoice->payment_status !== 'paid')
                        <a href="{{ route('finance.payments.create', ['supplier_id' => $invoice->supplier_id]) }}" class="btn btn-success btn-sm">
                            <i class="bi bi-credit-card me-1"></i> Record Payment
                        </a>
                    @endif
                    <a href="{{ route('purchases.returns.create', ['goods_received_note_id' => $invoice->goods_received_note_id]) }}" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-arrow-return-left me-1"></i> Return Goods
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Supplier / Vendor</small>
                        <h6 class="fw-bold mb-0 text-primary">{{ $invoice->supplier->name }}</h6>
                        <small class="text-muted">Balance: Rs. {{ number_format($invoice->supplier->current_balance, 2) }}</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Document Date</small>
                        <span class="fw-semibold">{{ $invoice->invoice_date->format('d M, Y') }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Due Date</small>
                        <span class="fw-semibold">{{ $invoice->due_date ? $invoice->due_date->format('d M, Y') : 'Immediate' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Vendor Bill / Invoice #</small>
                        <span class="fw-bold">{{ $invoice->supplier_invoice_no ?? 'N/A' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Invoice Type</small>
                        <span class="badge bg-light text-dark border text-uppercase">{{ $invoice->invoice_type }} ({{ $invoice->currency }})</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Payment Terms</small>
                        <span>{{ $invoice->payment_terms ?? 'Net 30 Days' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Linked GRN</small>
                        @if($invoice->goodsReceivedNote)
                            <a href="{{ route('purchases.grn.show', $invoice->goodsReceivedNote) }}" class="fw-semibold text-decoration-none">
                                {{ $invoice->goodsReceivedNote->grn_number }}
                            </a>
                        @else
                            <span class="text-muted">Direct Billed</span>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Station / Branch</small>
                        <span>{{ $invoice->station ?? 'Main Store' }}</span>
                    </div>
                </div>

                {{-- Tabs --}}
                <ul class="nav nav-tabs border-bottom mb-3" id="invShowTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tabShowProds" type="button">Product Details</button>
                    </li>
                    @if($invoice->expenses->where('category', 'inventory')->isNotEmpty())
                        <li class="nav-item">
                            <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabShowInvExp" type="button">Inventory Expenses</button>
                        </li>
                    @endif
                    @if($invoice->expenses->where('category', 'other')->isNotEmpty())
                        <li class="nav-item">
                            <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabShowOtherExp" type="button">Other Expenses</button>
                        </li>
                    @endif
                    @if($invoice->commissions->isNotEmpty())
                        <li class="nav-item">
                            <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabShowComm" type="button">Commission</button>
                        </li>
                    @endif
                    @if($invoice->terms || $invoice->notes)
                        <li class="nav-item">
                            <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabShowTerms" type="button">Terms & Notes</button>
                        </li>
                    @endif
                </ul>

                <div class="tab-content" id="invShowTabContent">
                    {{-- Products --}}
                    <div class="tab-pane fade show active" id="tabShowProds">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Item Description</th>
                                        <th>SKU</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Rate</th>
                                        <th class="text-end">Discount</th>
                                        <th class="text-end">Tax Amount</th>
                                        <th class="text-end pe-3">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $item)
                                        <tr>
                                            <td class="ps-3 fw-semibold">{{ $item->inventoryItem->item_name }}</td>
                                            <td><code>{{ $item->inventoryItem->sku }}</code></td>
                                            <td class="text-end fw-bold">{{ number_format($item->quantity, 2) }} {{ $item->inventoryItem->unit }}</td>
                                            <td class="text-end">Rs. {{ number_format($item->unit_cost, 2) }}</td>
                                            <td class="text-end text-danger">{{ $item->discount_amount > 0 ? 'Rs. ' . number_format($item->discount_amount, 2) : '-' }}</td>
                                            <td class="text-end">{{ $item->tax_amount > 0 ? 'Rs. ' . number_format($item->tax_amount, 2) : '-' }}</td>
                                            <td class="text-end pe-3 fw-bold">Rs. {{ number_format($item->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Inventory Expenses --}}
                    @if($invoice->expenses->where('category', 'inventory')->isNotEmpty())
                        <div class="tab-pane fade" id="tabShowInvExp">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Expense Head</th>
                                        <th>Account</th>
                                        <th>Comments</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end pe-3">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->expenses->where('category', 'inventory') as $exp)
                                        <tr>
                                            <td class="ps-3 fw-semibold">{{ $exp->expense_type }}</td>
                                            <td>{{ $exp->account ? $exp->account->name : 'N/A' }}</td>
                                            <td>{{ $exp->comments ?? '-' }}</td>
                                            <td class="text-end">Rs. {{ number_format($exp->debit > 0 ? $exp->debit : ($exp->rate * $exp->quantity), 2) }}</td>
                                            <td class="text-end pe-3">Rs. {{ number_format($exp->credit, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Other Expenses --}}
                    @if($invoice->expenses->where('category', 'other')->isNotEmpty())
                        <div class="tab-pane fade" id="tabShowOtherExp">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Expense Head</th>
                                        <th>Account</th>
                                        <th>Comments</th>
                                        <th class="text-end pe-3">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->expenses->where('category', 'other') as $exp)
                                        <tr>
                                            <td class="ps-3 fw-semibold">{{ $exp->expense_type }}</td>
                                            <td>{{ $exp->account ? $exp->account->name : 'N/A' }}</td>
                                            <td>{{ $exp->comments ?? '-' }}</td>
                                            <td class="text-end pe-3">Rs. {{ number_format($exp->debit > 0 ? $exp->debit : ($exp->rate * $exp->quantity), 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Commissions --}}
                    @if($invoice->commissions->isNotEmpty())
                        <div class="tab-pane fade" id="tabShowComm">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Agent</th>
                                        <th>Type</th>
                                        <th>Deduction</th>
                                        <th class="text-end">Rate</th>
                                        <th class="text-end">Amount</th>
                                        <th class="pe-3">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->commissions as $comm)
                                        <tr>
                                            <td class="ps-3 fw-semibold">{{ $comm->agent_name }}</td>
                                            <td>{{ $comm->commission_type }} ({{ $comm->calculation_type }})</td>
                                            <td><span class="badge bg-light text-dark border text-uppercase">{{ $comm->deduction_type }}</span></td>
                                            <td class="text-end">{{ $comm->calculation_type === 'percentage' ? number_format($comm->rate, 2) . '%' : '-' }}</td>
                                            <td class="text-end fw-bold">Rs. {{ number_format($comm->amount, 2) }}</td>
                                            <td class="pe-3 text-muted small">{{ $comm->remarks ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Terms --}}
                    @if($invoice->terms || $invoice->notes)
                        <div class="tab-pane fade" id="tabShowTerms">
                            <div class="p-3 bg-light rounded">
                                @if($invoice->terms)
                                    <h6 class="fw-bold mb-1">Terms:</h6>
                                    <p class="mb-3 whitespace-pre-line small">{{ $invoice->terms }}</p>
                                @endif
                                @if($invoice->notes)
                                    <h6 class="fw-bold mb-1">Notes:</h6>
                                    <p class="mb-0 small">{{ $invoice->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Summary Box --}}
                <div class="card bg-light border-0 mt-4 p-3">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-md-7">
                            <div class="d-flex flex-wrap gap-3 small text-secondary">
                                <div>Items Subtotal: <strong>Rs. {{ number_format($invoice->subtotal, 2) }}</strong></div>
                                <div>Discount: <strong class="text-danger">Rs. {{ number_format($invoice->discount_amount, 2) }}</strong></div>
                                <div>Tax: <strong>Rs. {{ number_format($invoice->tax_amount, 2) }}</strong></div>
                                <div>Landed Expenses: <strong>Rs. {{ number_format($invoice->inventory_expenses_total, 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-5 text-end">
                            <span class="text-secondary small fw-bold text-uppercase d-block">Grand Total Payable</span>
                            <span class="fs-4 fw-bold text-primary">Rs. {{ number_format($invoice->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
