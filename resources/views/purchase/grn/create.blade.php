@extends('layouts.app')
@section('title', 'Goods Received Note (GRN)')
@section('content')
<form method="post" action="{{ route('purchases.grn.store') }}" id="grnForm">
    @csrf

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Add GRN — Goods Received Note</h4>
            <small class="text-secondary">Stage 4: Physical count, quality acceptance, batching & landed expenses</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.grn.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loadIgpModal">
                <i class="bi bi-upload me-1"></i> Load IGP
            </button>
            <button type="submit" class="btn btn-success px-4 shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Save & Receive Goods
            </button>
        </div>
    </div>

    @if($selectedIgp)
        <div class="alert alert-info py-2 mb-3 d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-truck me-1"></i> Loaded from Inward Gate Pass: <strong>{{ $selectedIgp->igp_number }}</strong> (Vehicle: {{ $selectedIgp->vehicle_number ?? 'N/A' }}, Bilty: {{ $selectedIgp->bilty_number ?? 'N/A' }})
            </span>
            <a href="{{ route('purchases.grn.create') }}" class="btn btn-sm btn-outline-primary">Clear IGP</a>
        </div>
        <input type="hidden" name="inward_gate_pass_id" value="{{ $selectedIgp->id }}">
    @endif

    {{-- Top Meta Fields (Matching Screenshots 4 & 5) --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Company / Branch</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="{{ auth()->user()->shop ? auth()->user()->shop->name : 'Main Organization' }}" readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Vendor / Supplier <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm searchable-select" name="supplier_id" id="supplierSelect" required>
                        <option value="">Select Vendor</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" @selected(old('supplier_id', $selectedIgp?->supplier_id ?? $selectedPo?->supplier_id) == $sup->id)>
                                {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">GRN Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm" name="received_date" value="{{ old('received_date', date('Y-m-d')) }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Manual GRN Number</label>
                    <input type="text" class="form-control form-control-sm" name="manual_grn_number" value="{{ old('manual_grn_number') }}" placeholder="e.g. MGRN-101">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Select Station / Store</label>
                    <select class="form-select form-select-sm" name="warehouse_id" id="mainWarehouseSelect">
                        <option value="">Default Warehouse</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Received Via</label>
                    <select class="form-select form-select-sm" name="received_via">
                        <option value="Road / Truck" @selected(old('received_via', $selectedIgp?->received_via) === 'Road / Truck')>Road / Truck</option>
                        <option value="Suzuki / Pickup" @selected(old('received_via', $selectedIgp?->received_via) === 'Suzuki / Pickup')>Suzuki / Pickup</option>
                        <option value="Courier / Cargo" @selected(old('received_via', $selectedIgp?->received_via) === 'Courier / Cargo')>Courier / Cargo Service</option>
                        <option value="By Hand" @selected(old('received_via', $selectedIgp?->received_via) === 'By Hand')>By Hand</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Linked Purchase Order (Optional)</label>
                    <select class="form-select form-select-sm" name="purchase_order_id" id="poSelect">
                        <option value="">-- No PO / Direct Inward --</option>
                        @foreach($pendingOrders as $po)
                            <option value="{{ $po->id }}" @selected(old('purchase_order_id', $selectedPo?->id ?? $selectedIgp?->purchase_order_id) == $po->id)>
                                {{ $po->po_number }} (Rs. {{ number_format($po->grand_total, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Supplier Delivery Challan #</label>
                    <input type="text" class="form-control form-control-sm" name="supplier_invoice_no" value="{{ old('supplier_invoice_no', $selectedIgp?->challan_number) }}" placeholder="e.g. CH-9902">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Received By Staff</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="{{ auth()->user()->name }}" readonly>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs (Matching Screenshots 4 & 5) --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-header bg-white border-bottom-0 pb-0 pt-2 px-3">
            <ul class="nav nav-tabs border-bottom" id="grnTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-primary px-4 py-2" id="products-tab" data-bs-toggle="tab" data-bs-target="#tabProducts" type="button" role="tab">
                        <i class="bi bi-box me-1"></i> Product Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-secondary px-4 py-2" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#tabExpenses" type="button" role="tab">
                        <i class="bi bi-cash-stack me-1"></i> GRN Expenses
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="grnTabContent">
                {{-- TAB 1: PRODUCT DETAILS --}}
                <div class="tab-pane fade show active" id="tabProducts" role="tabpanel">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <span class="small text-secondary fw-semibold text-uppercase">Inward Items & Physical Quality Acceptance</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddGrnProduct">
                            <i class="bi bi-plus-circle me-1"></i> Add Product Line
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="grnProductsTable">
                            <thead class="bg-primary text-white small" style="background-color: #1e3a8a !important;">
                                <tr>
                                    <th style="width: 4%;" class="text-center">Sr</th>
                                    <th style="width: 25%;">Product <span class="text-danger">*</span></th>
                                    <th style="width: 14%;">Warehouse / Godown</th>
                                    <th style="width: 7%;" class="text-center">UOM</th>
                                    <th style="width: 10%;" class="text-end">Total Delivered <span class="text-danger">*</span></th>
                                    <th style="width: 9%;" class="text-end">Rejected Qty</th>
                                    <th style="width: 9%;" class="text-end">Accepted Qty</th>
                                    <th style="width: 10%;" class="text-end">Unit Cost (Rs.)</th>
                                    <th style="width: 11%;">Batch / Expiry</th>
                                    <th style="width: 3%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="grnProductsBody">
                                @php
                                    $loadedItems = $selectedIgp ? $selectedIgp->items : ($selectedPo ? $selectedPo->items : collect());
                                @endphp

                                @if($loadedItems->isNotEmpty())
                                    @foreach($loadedItems as $idx => $line)
                                        @php
                                            $declaredQty = $line->declared_quantity ?? ($line->quantity - $line->received_quantity);
                                            $defaultCost = $line->unit_cost ?? $line->inventoryItem->purchase_price ?? 0;
                                        @endphp
                                        <tr class="grn-prod-row">
                                            <td class="text-center row-sr fw-semibold">{{ $idx + 1 }}</td>
                                            <td>
                                                <select class="form-select form-select-sm grn-prod-select" name="items[{{ $idx }}][inventory_item_id]" required>
                                                    <option value="{{ $line->inventory_item_id }}" data-uom="{{ $line->inventoryItem->unit }}" data-cost="{{ $defaultCost }}" selected>
                                                        {{ $line->inventoryItem->item_name }} ({{ $line->inventoryItem->sku }})
                                                    </option>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm" name="items[{{ $idx }}][warehouse_id]">
                                                    <option value="">Default</option>
                                                    @foreach($warehouses as $wh)
                                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-center uom-label fw-bold">{{ $line->inventoryItem->unit }}</td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end qty-total" name="items[{{ $idx }}][total_delivered]" value="{{ $declaredQty }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-rejected text-danger" name="items[{{ $idx }}][rejected_quantity]" value="0">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-accepted fw-bold text-success" name="items[{{ $idx }}][quantity]" value="{{ $declaredQty }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end unit-cost" name="items[{{ $idx }}][unit_cost]" value="{{ $defaultCost }}" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm mb-1" name="items[{{ $idx }}][batch_number]" placeholder="Batch # (Auto)">
                                                <input type="date" class="form-control form-control-sm" name="items[{{ $idx }}][expiry_date]">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="grn-prod-row">
                                        <td class="text-center row-sr fw-semibold">1</td>
                                        <td>
                                            <select class="form-select form-select-sm grn-prod-select" name="items[0][inventory_item_id]" required>
                                                <option value="">Select Product</option>
                                                @foreach($products as $prod)
                                                    <option value="{{ $prod->id }}" data-uom="{{ $prod->unit }}" data-cost="{{ $prod->purchase_price }}">
                                                        {{ $prod->item_name }} (Stock: {{ (float)$prod->quantity }} {{ $prod->unit }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="items[0][warehouse_id]">
                                                <option value="">Default</option>
                                                @foreach($warehouses as $wh)
                                                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center uom-label fw-bold">PCS</td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end qty-total" name="items[0][total_delivered]" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-rejected text-danger" name="items[0][rejected_quantity]" value="0">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-accepted fw-bold text-success" name="items[0][quantity]" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end unit-cost" name="items[0][unit_cost]" value="0" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm mb-1" name="items[0][batch_number]" placeholder="Batch # (Auto)">
                                            <input type="date" class="form-control form-control-sm" name="items[0][expiry_date]">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="4" class="text-end">Total Accepted Quantity:</td>
                                    <td colspan="3" class="text-start ps-3" id="totalQtySum">0.00</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- TAB 2: GRN EXPENSES (Screenshot 5) --}}
                <div class="tab-pane fade" id="tabExpenses" role="tabpanel">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <span class="small text-secondary fw-semibold text-uppercase">Landed Costs (Freight, Carriage, Offloading / Mazdoori)</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddGrnExpense">
                            <i class="bi bi-plus-circle me-1"></i> Add Expense Line
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="grnExpensesTable">
                            <thead class="bg-primary text-white small" style="background-color: #1e3a8a !important;">
                                <tr>
                                    <th style="width: 25%;">Account</th>
                                    <th style="width: 20%;">Comments</th>
                                    <th style="width: 15%;">Expense Type</th>
                                    <th style="width: 10%;" class="text-end">Qty</th>
                                    <th style="width: 10%;" class="text-end">Rate</th>
                                    <th style="width: 10%;" class="text-end">Debit (Rs.)</th>
                                    <th style="width: 7%;" class="text-end">Credit (Rs.)</th>
                                    <th style="width: 3%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="grnExpensesBody">
                                <tr class="grn-exp-row">
                                    <td>
                                        <select class="form-select form-select-sm" name="expenses[0][account_id]">
                                            <option value="">Select Expense Account</option>
                                            @foreach($expenseAccounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="expenses[0][comments]" placeholder="e.g. Bilty freight charges">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" name="expenses[0][expense_type]">
                                            <option value="Freight">Freight / Carriage Inward</option>
                                            <option value="Loading">Loading Charges</option>
                                            <option value="Unloading">Unloading / Labour</option>
                                            <option value="Octroi">Octroi / Toll Tax</option>
                                            <option value="Other">Other Landed Expense</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm text-end exp-qty" name="expenses[0][quantity]" value="1">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm text-end exp-rate" name="expenses[0][rate]" value="0">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm text-end exp-debit" name="expenses[0][debit]" value="0">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm text-end exp-credit" name="expenses[0][credit]" value="0">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-exp">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light fw-bold small">
                                <tr>
                                    <td colspan="5" class="text-end">Total Debit:</td>
                                    <td class="text-end" id="expTotalDebit">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end">Total Credit:</td>
                                    <td class="text-end" id="expTotalCredit">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end">Net Total Landed Expenses:</td>
                                    <td class="text-end text-primary" id="expNetTotal">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Remarks & Notes --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Remarks</label>
                    <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Quality inspection remarks, packing state...">{{ old('notes') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Additional Detail</label>
                    <textarea class="form-control form-control-sm" rows="2" placeholder="Inspection checklist or storage rack notes..."></textarea>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Load IGP Modal --}}
<div class="modal fade" id="loadIgpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2"></i> Select Inward Gate Pass (IGP) to Load</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">IGP #</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Vehicle</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingIgps as $pigp)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $pigp->igp_number }}</td>
                                    <td>{{ $pigp->igp_date->format('d M, Y') }}</td>
                                    <td>{{ $pigp->supplier->name }}</td>
                                    <td>{{ $pigp->vehicle_number ?? 'Road' }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('purchases.grn.create', ['inward_gate_pass_id' => $pigp->id]) }}" class="btn btn-sm btn-primary">
                                            Load into GRN
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No pending Inward Gate Passes found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let prodIndex = {{ $loadedItems->isNotEmpty() ? $loadedItems->count() : 1 }};
    let expIndex = 1;

    const prodBody = document.getElementById('grnProductsBody');
    const expBody = document.getElementById('grnExpensesBody');
    const btnAddProd = document.getElementById('btnAddGrnProduct');
    const btnAddExp = document.getElementById('btnAddGrnExpense');

    function calculateRow(row) {
        const totalInput = row.querySelector('.qty-total');
        const rejectedInput = row.querySelector('.qty-rejected');
        const acceptedInput = row.querySelector('.qty-accepted');

        const total = parseFloat(totalInput.value) || 0;
        const rejected = parseFloat(rejectedInput.value) || 0;
        const accepted = Math.max(0, total - rejected);
        acceptedInput.value = accepted.toFixed(2);
        updateTotals();
    }

    function updateTotals() {
        let sumQty = 0;
        prodBody.querySelectorAll('.qty-accepted').forEach(i => {
            sumQty += parseFloat(i.value) || 0;
        });
        document.getElementById('totalQtySum').textContent = sumQty.toFixed(2);

        // Calculate expenses
        let totalDebit = 0;
        let totalCredit = 0;
        expBody.querySelectorAll('.grn-exp-row').forEach(r => {
            const debit = parseFloat(r.querySelector('.exp-debit')?.value) || 0;
            const credit = parseFloat(r.querySelector('.exp-credit')?.value) || 0;
            const rate = parseFloat(r.querySelector('.exp-rate')?.value) || 0;
            const qty = parseFloat(r.querySelector('.exp-qty')?.value) || 1;

            totalDebit += debit > 0 ? debit : (rate * qty);
            totalCredit += credit;
        });

        document.getElementById('expTotalDebit').textContent = 'Rs. ' + totalDebit.toFixed(2);
        document.getElementById('expTotalCredit').textContent = 'Rs. ' + totalCredit.toFixed(2);
        document.getElementById('expNetTotal').textContent = 'Rs. ' + (totalDebit - totalCredit).toFixed(2);
    }

    function attachProdListeners(row) {
        const select = row.querySelector('.grn-prod-select');
        const uom = row.querySelector('.uom-label');
        const cost = row.querySelector('.unit-cost');
        const total = row.querySelector('.qty-total');
        const rejected = row.querySelector('.qty-rejected');
        const btnRemove = row.querySelector('.btn-remove-row');

        if (select) {
            select.addEventListener('change', function () {
                const opt = select.options[select.selectedIndex];
                if (opt && opt.dataset.uom) {
                    uom.textContent = opt.dataset.uom;
                    if (opt.dataset.cost && cost) {
                        cost.value = parseFloat(opt.dataset.cost).toFixed(2);
                    }
                }
            });
        }

        if (total) total.addEventListener('input', () => calculateRow(row));
        if (rejected) rejected.addEventListener('input', () => calculateRow(row));

        if (btnRemove) {
            btnRemove.addEventListener('click', function () {
                if (prodBody.querySelectorAll('.grn-prod-row').length > 1) {
                    row.remove();
                    updateProdSr();
                    updateTotals();
                }
            });
        }
    }

    function updateProdSr() {
        const rows = prodBody.querySelectorAll('.grn-prod-row');
        rows.forEach((r, idx) => {
            r.querySelector('.row-sr').textContent = idx + 1;
            r.querySelector('.btn-remove-row').disabled = rows.length === 1;
        });
    }

    function attachExpListeners(row) {
        const debit = row.querySelector('.exp-debit');
        const credit = row.querySelector('.exp-credit');
        const rate = row.querySelector('.exp-rate');
        const qty = row.querySelector('.exp-qty');
        const btnRemove = row.querySelector('.btn-remove-exp');

        [debit, credit, rate, qty].forEach(input => {
            if (input) input.addEventListener('input', updateTotals);
        });

        if (btnRemove) {
            btnRemove.addEventListener('click', function () {
                if (expBody.querySelectorAll('.grn-exp-row').length > 1) {
                    row.remove();
                    updateTotals();
                }
            });
        }
    }

    prodBody.querySelectorAll('.grn-prod-row').forEach(attachProdListeners);
    expBody.querySelectorAll('.grn-exp-row').forEach(attachExpListeners);
    updateTotals();

    btnAddProd.addEventListener('click', function () {
        const firstRow = prodBody.querySelector('.grn-prod-row');
        const clone = firstRow.cloneNode(true);

        clone.querySelectorAll('input').forEach(i => {
            if (i.classList.contains('qty-total')) i.value = '1';
            else if (i.classList.contains('qty-rejected')) i.value = '0';
            else if (i.classList.contains('qty-accepted')) i.value = '1';
            else i.value = '';
        });
        clone.querySelector('select').selectedIndex = 0;

        clone.querySelector('.grn-prod-select').name = `items[${prodIndex}][inventory_item_id]`;
        clone.querySelector('select[name*="[warehouse_id]"]').name = `items[${prodIndex}][warehouse_id]`;
        clone.querySelector('.qty-total').name = `items[${prodIndex}][total_delivered]`;
        clone.querySelector('.qty-rejected').name = `items[${prodIndex}][rejected_quantity]`;
        clone.querySelector('.qty-accepted').name = `items[${prodIndex}][quantity]`;
        clone.querySelector('.unit-cost').name = `items[${prodIndex}][unit_cost]`;
        clone.querySelector('input[name*="[batch_number]"]').name = `items[${prodIndex}][batch_number]`;
        clone.querySelector('input[name*="[expiry_date]"]').name = `items[${prodIndex}][expiry_date]`;

        attachProdListeners(clone);
        prodBody.appendChild(clone);
        prodIndex++;
        updateProdSr();
        updateTotals();
    });

    btnAddExp.addEventListener('click', function () {
        const firstRow = expBody.querySelector('.grn-exp-row');
        const clone = firstRow.cloneNode(true);

        clone.querySelectorAll('input').forEach(i => {
            if (i.classList.contains('exp-qty')) i.value = '1';
            else if (i.classList.contains('exp-rate') || i.classList.contains('exp-debit') || i.classList.contains('exp-credit')) i.value = '0';
            else i.value = '';
        });
        clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);

        clone.querySelector('select[name*="[account_id]"]').name = `expenses[${expIndex}][account_id]`;
        clone.querySelector('input[name*="[comments]"]').name = `expenses[${expIndex}][comments]`;
        clone.querySelector('select[name*="[expense_type]"]').name = `expenses[${expIndex}][expense_type]`;
        clone.querySelector('.exp-qty').name = `expenses[${expIndex}][quantity]`;
        clone.querySelector('.exp-rate').name = `expenses[${expIndex}][rate]`;
        clone.querySelector('.exp-debit').name = `expenses[${expIndex}][debit]`;
        clone.querySelector('.exp-credit').name = `expenses[${expIndex}][credit]`;

        attachExpListeners(clone);
        expBody.appendChild(clone);
        expIndex++;
        updateTotals();
    });
});
</script>
@endsection
