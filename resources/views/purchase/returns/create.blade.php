@extends('layouts.app')
@section('title', 'Create Purchase Return (Debit Note)')
@section('content')
<form method="post" action="{{ route('purchases.returns.store') }}" id="returnForm">
    @csrf

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Add Purchase Return & Debit Note</h4>
            <small class="text-secondary">Stage 6: Return defective or excess inventory to vendor and issue vendor debit note</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.returns.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loadGrnModal">
                <i class="bi bi-box-arrow-in-down me-1"></i> Load from GRN
            </button>
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#loadInvoiceModal">
                <i class="bi bi-receipt me-1"></i> Load from Invoice
            </button>
            <button type="submit" class="btn btn-danger px-4 shadow-sm">
                <i class="bi bi-arrow-return-left me-1"></i> Issue Debit Note
            </button>
        </div>
    </div>

    @if($selectedGrn)
        <div class="alert alert-info py-2 mb-3 d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-check-circle me-1"></i> Loaded from GRN: <strong>{{ $selectedGrn->grn_number }}</strong> (Supplier: {{ $selectedGrn->supplier->name }})
            </span>
            <a href="{{ route('purchases.returns.create') }}" class="btn btn-sm btn-outline-primary">Clear Loaded Data</a>
        </div>
        <input type="hidden" name="goods_received_note_id" value="{{ $selectedGrn->id }}">
    @elseif($selectedInvoice)
        <div class="alert alert-info py-2 mb-3 d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-receipt me-1"></i> Loaded from Purchase Invoice: <strong>{{ $selectedInvoice->invoice_number }}</strong> (Supplier: {{ $selectedInvoice->supplier->name }})
            </span>
            <a href="{{ route('purchases.returns.create') }}" class="btn btn-sm btn-outline-primary">Clear Loaded Data</a>
        </div>
        <input type="hidden" name="purchase_invoice_id" value="{{ $selectedInvoice->id }}">
    @endif

    {{-- Top Details Card --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Company / Branch</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="{{ auth()->user()->shop ? auth()->user()->shop->name : 'Main Organization' }}" readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Vendor / Supplier <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" name="supplier_id" id="supplierSelect" required>
                        <option value="">Select Vendor</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" @selected(old('supplier_id', $selectedGrn?->supplier_id ?? $selectedInvoice?->supplier_id) == $sup->id)>
                                {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Return Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Primary Reason for Return <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" name="reason" required>
                        <option value="Damaged / Defective Goods" @selected(old('reason') === 'Damaged / Defective Goods')>Damaged / Defective Goods</option>
                        <option value="Expired / Near Expiry Batch" @selected(old('reason') === 'Expired / Near Expiry Batch')>Expired / Near Expiry Batch</option>
                        <option value="Specification / Quality Mismatch" @selected(old('reason') === 'Specification / Quality Mismatch')>Specification / Quality Mismatch</option>
                        <option value="Excess Quantity Delivered" @selected(old('reason') === 'Excess Quantity Delivered')>Excess Quantity Delivered</option>
                        <option value="Incorrect Items Delivered" @selected(old('reason') === 'Incorrect Items Delivered')>Incorrect Items Delivered</option>
                        <option value="Mutual Cancellation" @selected(old('reason') === 'Mutual Cancellation')>Mutual Cancellation / Other</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small mb-1">Return Notes / Remarks</label>
                    <input type="text" class="form-control form-control-sm" name="notes" value="{{ old('notes') }}" placeholder="Detailed explanation or claim reference for the vendor...">
                </div>
            </div>
        </div>
    </div>

    {{-- Items Card --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold"><i class="bi bi-box-seam me-1 text-danger"></i> Products to Return</span>
            <button type="button" class="btn btn-sm btn-outline-danger" id="addRowBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Item
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0" id="returnItemsTable">
                <thead class="bg-light small text-secondary">
                    <tr>
                        <th style="width: 30%;">Product Item <span class="text-danger">*</span></th>
                        <th style="width: 15%;">Batch # (Optional)</th>
                        <th style="width: 12%;" class="text-end">Return Qty <span class="text-danger">*</span></th>
                        <th style="width: 13%;" class="text-end">Unit Cost (Rs.) <span class="text-danger">*</span></th>
                        <th style="width: 15%;" class="text-end">Line Total (Rs.)</th>
                        <th style="width: 10%;">Item Note</th>
                        <th style="width: 5%;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="returnTableBody">
                    @php $rowIdx = 0; @endphp
                    @if($selectedGrn && $selectedGrn->items->count())
                        @foreach($selectedGrn->items as $gItem)
                            <tr class="item-row" data-row="{{ $rowIdx }}">
                                <td>
                                    <select class="form-select form-select-sm product-select" name="items[{{ $rowIdx }}][inventory_item_id]" required>
                                        <option value="{{ $gItem->inventory_item_id }}" data-cost="{{ $gItem->unit_cost }}" selected>
                                            {{ $gItem->inventoryItem->item_name }} ({{ $gItem->inventoryItem->sku ?? 'No SKU' }})
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="items[{{ $rowIdx }}][product_batch_id]" value="{{ $gItem->product_batch_id }}">
                                    <input type="text" class="form-control form-control-sm bg-light" value="{{ $gItem->batch ? $gItem->batch->batch_number : 'N/A' }}" readonly>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" max="{{ $gItem->received_quantity }}" class="form-control form-control-sm text-end qty-input" name="items[{{ $rowIdx }}][quantity]" value="{{ $gItem->rejected_quantity > 0 ? $gItem->rejected_quantity : 1 }}" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end cost-input" name="items[{{ $rowIdx }}][unit_cost]" value="{{ $gItem->unit_cost }}" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-end bg-light row-subtotal" value="0.00" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="items[{{ $rowIdx }}][reason]" value="{{ $gItem->rejected_quantity > 0 ? 'Quality rejection from GRN' : '' }}" placeholder="Reason">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 remove-row-btn">&times;</button>
                                </td>
                            </tr>
                            @php $rowIdx++; @endphp
                        @endforeach
                    @elseif($selectedInvoice && $selectedInvoice->items->count())
                        @foreach($selectedInvoice->items as $iItem)
                            <tr class="item-row" data-row="{{ $rowIdx }}">
                                <td>
                                    <select class="form-select form-select-sm product-select" name="items[{{ $rowIdx }}][inventory_item_id]" required>
                                        <option value="{{ $iItem->inventory_item_id }}" data-cost="{{ $iItem->unit_cost }}" selected>
                                            {{ $iItem->inventoryItem->item_name }} ({{ $iItem->inventoryItem->sku ?? 'No SKU' }})
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="items[{{ $rowIdx }}][product_batch_id]" value="{{ $iItem->product_batch_id }}">
                                    <input type="text" class="form-control form-control-sm bg-light" value="{{ $iItem->batch ? $iItem->batch->batch_number : 'N/A' }}" readonly>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" max="{{ $iItem->billed_quantity }}" class="form-control form-control-sm text-end qty-input" name="items[{{ $rowIdx }}][quantity]" value="1" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end cost-input" name="items[{{ $rowIdx }}][unit_cost]" value="{{ $iItem->unit_cost }}" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-end bg-light row-subtotal" value="0.00" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="items[{{ $rowIdx }}][reason]" placeholder="Reason">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 remove-row-btn">&times;</button>
                                </td>
                            </tr>
                            @php $rowIdx++; @endphp
                        @endforeach
                    @else
                        <tr class="item-row" data-row="0">
                            <td>
                                <select class="form-select form-select-sm product-select" name="items[0][inventory_item_id]" required>
                                    <option value="">Select Item</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-cost="{{ $p->purchase_price }}" data-stock="{{ $p->remaining_quantity }}">
                                            {{ $p->item_name }} ({{ $p->sku ?? 'No SKU' }}) — Stock: {{ $p->remaining_quantity }} {{ $p->unit ?? 'pcs' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm batch-select" name="items[0][product_batch_id]">
                                    <option value="">No Batch / Direct</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end qty-input" name="items[0][quantity]" value="1" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end cost-input" name="items[0][unit_cost]" value="0" required>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm text-end bg-light row-subtotal" value="0.00" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="items[0][reason]" placeholder="Item condition...">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 remove-row-btn">&times;</button>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bottom Summary Card --}}
    <div class="row g-3">
        <div class="col-md-7">
            <div class="alert alert-secondary mb-0 small">
                <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> What happens when you issue this Debit Note?</div>
                <ul class="mb-0 ps-3">
                    <li>Physical inventory in your warehouse will immediately be reduced by the returned quantities.</li>
                    <li>If a batch is specified, the batch balance will be decreased accordingly.</li>
                    <li>Vendor Accounts Payable balance will be credited/reduced by the debit note total (<strong>Debit: Accounts Payable / Credit: Inventory</strong>).</li>
                </ul>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Total Return Items:</span>
                        <strong id="summaryTotalItems">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Total Units Returned:</span>
                        <strong id="summaryTotalQty">0.00</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-5 fw-bold text-danger">Total Debit Note Value:</span>
                        <span class="fs-4 fw-bold text-danger" id="summaryTotalAmount">Rs. 0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Modal: Load from GRN --}}
<div class="modal fade" id="loadGrnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-in-down me-1"></i> Select Goods Received Note (GRN)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">GRN #</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th class="text-end">Value</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingGrns as $grn)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $grn->grn_number }}</td>
                                    <td>{{ $grn->supplier->name }}</td>
                                    <td>{{ $grn->received_date->format('d M, Y') }}</td>
                                    <td class="text-end">Rs. {{ number_format($grn->total_amount, 2) }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('purchases.returns.create', ['goods_received_note_id' => $grn->id]) }}" class="btn btn-sm btn-primary">
                                            Load GRN Items
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No Goods Received Notes found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Load from Invoice --}}
<div class="modal fade" id="loadInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-receipt me-1"></i> Select Purchase Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">Invoice #</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingInvoices as $inv)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $inv->invoice_number }}</td>
                                    <td>{{ $inv->supplier->name }}</td>
                                    <td>{{ $inv->invoice_date->format('d M, Y') }}</td>
                                    <td class="text-end">Rs. {{ number_format($inv->total_amount, 2) }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('purchases.returns.create', ['purchase_invoice_id' => $inv->id]) }}" class="btn btn-sm btn-info text-white">
                                            Load Invoice Items
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No Purchase Invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('returnTableBody');
    const addRowBtn = document.getElementById('addRowBtn');
    let rowCount = {{ max(1, $rowIdx) }};

    const productsData = @json($productsData);

    function updateCalculations() {
        let totalItems = 0;
        let totalQty = 0;
        let grandTotal = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const qtyInput = row.querySelector('.qty-input');
            const costInput = row.querySelector('.cost-input');
            const subtotalInput = row.querySelector('.row-subtotal');

            const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            const cost = parseFloat(costInput ? costInput.value : 0) || 0;
            const subtotal = qty * cost;

            if (subtotalInput) {
                subtotalInput.value = subtotal.toFixed(2);
            }

            if (qty > 0) {
                totalItems++;
                totalQty += qty;
                grandTotal += subtotal;
            }
        });

        document.getElementById('summaryTotalItems').textContent = totalItems;
        document.getElementById('summaryTotalQty').textContent = totalQty.toFixed(2);
        document.getElementById('summaryTotalAmount').textContent = 'Rs. ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function populateBatches(row, productId) {
        const batchSelect = row.querySelector('.batch-select');
        if (!batchSelect) return;

        batchSelect.innerHTML = '<option value="">No Batch / Direct</option>';
        const prod = productsData.find(p => p.id == productId);
        if (prod && prod.batches) {
            prod.batches.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = `${b.batch_number} (Avail: ${b.quantity})`;
                batchSelect.appendChild(opt);
            });
        }
    }

    function attachRowListeners(row) {
        const prodSelect = row.querySelector('.product-select');
        const qtyInput = row.querySelector('.qty-input');
        const costInput = row.querySelector('.cost-input');
        const removeBtn = row.querySelector('.remove-row-btn');

        if (prodSelect) {
            prodSelect.addEventListener('change', function () {
                const pId = this.value;
                const prod = productsData.find(p => p.id == pId);
                if (prod && costInput) {
                    costInput.value = prod.purchase_price || 0;
                }
                populateBatches(row, pId);
                updateCalculations();
            });
        }

        if (qtyInput) qtyInput.addEventListener('input', updateCalculations);
        if (costInput) costInput.addEventListener('input', updateCalculations);

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (document.querySelectorAll('.item-row').length > 1) {
                    row.remove();
                    updateCalculations();
                } else {
                    alert('At least one item is required in the purchase return.');
                }
            });
        }
    }

    document.querySelectorAll('.item-row').forEach(attachRowListeners);
    updateCalculations();

    if (addRowBtn) {
        addRowBtn.addEventListener('click', function () {
            let optionsHtml = '<option value="">Select Item</option>';
            productsData.forEach(p => {
                optionsHtml += `<option value="${p.id}" data-cost="${p.purchase_price}">${p.item_name} (${p.sku || 'No SKU'}) — Stock: ${p.remaining_quantity} ${p.unit}</option>`;
            });

            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.setAttribute('data-row', rowCount);
            tr.innerHTML = `
                <td>
                    <select class="form-select form-select-sm product-select" name="items[${rowCount}][inventory_item_id]" required>
                        ${optionsHtml}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm batch-select" name="items[${rowCount}][product_batch_id]">
                        <option value="">No Batch / Direct</option>
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end qty-input" name="items[${rowCount}][quantity]" value="1" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end cost-input" name="items[${rowCount}][unit_cost]" value="0" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm text-end bg-light row-subtotal" value="0.00" readonly>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="items[${rowCount}][reason]" placeholder="Reason">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 remove-row-btn">&times;</button>
                </td>
            `;

            tableBody.appendChild(tr);
            attachRowListeners(tr);
            rowCount++;
        });
    }
});
</script>
@endpush
@endsection
