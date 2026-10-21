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
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loadPoModal">
                <i class="bi bi-file-earmark-text me-1"></i> Load PO
            </button>
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
    @elseif($selectedPo)
        <div class="alert alert-primary py-2 mb-3 d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-file-earmark-text me-1"></i> Loaded from Purchase Order: <strong>{{ $selectedPo->po_number }}</strong> (Supplier: {{ $selectedPo->supplier->name ?? 'N/A' }}, Total: Rs. {{ number_format($selectedPo->grand_total, 2) }})
            </span>
            <a href="{{ route('purchases.grn.create') }}" class="btn btn-sm btn-outline-primary">Clear PO</a>
        </div>
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
                        <option value="">Select Transport Mode</option>
                        @if(isset($transportModes) && $transportModes->isNotEmpty())
                            @foreach($transportModes as $tm)
                                <option value="{{ $tm->name }}" @selected(old('received_via', $selectedIgp?->received_via) === $tm->name)>
                                    {{ $tm->name }}
                                </option>
                            @endforeach
                        @else
                            <option value="Road / Truck" @selected(old('received_via', $selectedIgp?->received_via) === 'Road / Truck')>Road / Truck</option>
                            <option value="Suzuki / Pickup" @selected(old('received_via', $selectedIgp?->received_via) === 'Suzuki / Pickup')>Suzuki / Pickup</option>
                            <option value="Courier / Cargo" @selected(old('received_via', $selectedIgp?->received_via) === 'Courier / Cargo')>Courier / Cargo Service</option>
                            <option value="By Hand" @selected(old('received_via', $selectedIgp?->received_via) === 'By Hand')>By Hand</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Linked Purchase Order (Optional)</label>
                    <select class="form-select form-select-sm" name="purchase_order_id" id="poSelect">
                        <option value="">-- No PO / Direct Inward --</option>
                        @foreach($pendingOrders as $po)
                            <option value="{{ $po->id }}" @selected(old('purchase_order_id', $selectedPo?->id ?? $selectedIgp?->purchase_order_id) == $po->id) data-supplier="{{ $po->supplier_id }}">
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
                                    <th style="width: 28%;">Product <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Warehouse / Godown</th>
                                    <th style="width: 8%;" class="text-center">UOM</th>
                                    <th style="width: 12%;" class="text-end">Total Delivered <span class="text-danger">*</span></th>
                                    <th style="width: 10%;" class="text-end">Rejected Qty</th>
                                    <th style="width: 10%;" class="text-end">Accepted Qty</th>
                                    <th style="width: 10%;">Batch / Expiry</th>
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
                                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end qty-total auto-clear-zero" name="items[{{ $idx }}][total_delivered]" value="{{ $declaredQty }}" required placeholder="1.00">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-rejected text-danger auto-clear-zero" name="items[{{ $idx }}][rejected_quantity]" value="0" placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-accepted fw-bold text-success auto-clear-zero" name="items[{{ $idx }}][quantity]" value="{{ $declaredQty }}" required placeholder="1.00">
                                                <input type="hidden" class="unit-cost" name="items[{{ $idx }}][unit_cost]" value="{{ $defaultCost }}">
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
                                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end qty-total auto-clear-zero" name="items[0][total_delivered]" value="1" required placeholder="1.00">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-rejected text-danger auto-clear-zero" name="items[0][rejected_quantity]" value="0" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-accepted fw-bold text-success auto-clear-zero" name="items[0][quantity]" value="1" required placeholder="1.00">
                                            <input type="hidden" class="unit-cost" name="items[0][unit_cost]" value="0">
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
                                    <th style="width: 30%;">Expense Account <span class="text-danger">*</span></th>
                                    <th style="width: 20%;">Expense Type</th>
                                    <th style="width: 32%;">Comments / Description</th>
                                    <th style="width: 15%;" class="text-end">Amount (Rs.) <span class="text-danger">*</span></th>
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
                                        <select class="form-select form-select-sm" name="expenses[0][expense_type]">
                                            <option value="Freight">Freight / Carriage Inward</option>
                                            <option value="Loading">Loading Charges</option>
                                            <option value="Unloading">Unloading / Labour</option>
                                            <option value="Octroi">Octroi / Toll Tax</option>
                                            <option value="Other">Other Landed Expense</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="expenses[0][comments]" placeholder="e.g. Bilty freight charges">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end exp-amount auto-clear-zero" name="expenses[0][amount]" value="0" placeholder="0.00">
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
                                    <td colspan="3" class="text-end">Total Landed Expenses (Rs.):</td>
                                    <td class="text-end text-primary fw-bold" id="expNetTotal">Rs. 0.00</td>
                                    <td></td>
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

{{-- Load PO Modal --}}
<div class="modal fade" id="loadPoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i> Select Purchase Order (PO) to Load</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">PO #</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Total</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingOrders as $ppo)
                                <tr class="po-modal-row" data-supplier="{{ $ppo->supplier_id }}">
                                    <td class="ps-3 fw-bold">{{ $ppo->po_number }}</td>
                                    <td>{{ $ppo->order_date ? \Carbon\Carbon::parse($ppo->order_date)->format('d M, Y') : 'N/A' }}</td>
                                    <td>{{ $ppo->supplier->name ?? 'N/A' }}</td>
                                    <td>Rs. {{ number_format($ppo->grand_total, 2) }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('purchases.grn.create', ['purchase_order_id' => $ppo->id]) }}" class="btn btn-sm btn-primary">
                                            Load into GRN
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No pending/approved Purchase Orders found.
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
                                <tr class="igp-modal-row" data-supplier="{{ $pigp->supplier_id }}">
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

    const supplierSelect = document.getElementById('supplierSelect');
    const poSelect = document.getElementById('poSelect');

    // Point 8: Supplier Filter for PO and IGP
    function filterBySupplier() {
        if (!supplierSelect) return;
        const selectedSupplier = supplierSelect.value;

        if (poSelect) {
            poSelect.querySelectorAll('option').forEach(opt => {
                if (!opt.value) return;
                const poSup = opt.getAttribute('data-supplier');
                opt.style.display = (!selectedSupplier || poSup === selectedSupplier) ? '' : 'none';
            });
            const curOpt = poSelect.options[poSelect.selectedIndex];
            if (curOpt && curOpt.value && selectedSupplier && curOpt.getAttribute('data-supplier') !== selectedSupplier) {
                poSelect.value = '';
            }
        }

        document.querySelectorAll('.igp-modal-row').forEach(row => {
            const sup = row.getAttribute('data-supplier');
            row.style.display = (!selectedSupplier || sup === selectedSupplier) ? '' : 'none';
        });
    }

    if (supplierSelect) {
        supplierSelect.addEventListener('change', filterBySupplier);
        if (poSelect) {
            poSelect.addEventListener('change', function () {
                const opt = poSelect.options[poSelect.selectedIndex];
                if (opt && opt.value) {
                    const poSup = opt.getAttribute('data-supplier');
                    if (poSup && supplierSelect.value !== poSup) {
                        supplierSelect.value = poSup;
                        filterBySupplier();
                    }
                }
            });
        }
        filterBySupplier();
    }

    // Point 4: Auto-clear 0.00 helper
    function setupAutoClear(el) {
        el.addEventListener('focus', function () {
            if (this.value === '0.00' || this.value === '0' || this.value === '0.0') {
                this.select();
            }
        });
        el.addEventListener('blur', function () {
            if (this.value.trim() === '') {
                this.value = this.classList.contains('qty-total') || this.classList.contains('qty-accepted') ? '1.00' : '0.00';
                updateTotals();
            }
        });
    }

    document.querySelectorAll('.auto-clear-zero').forEach(setupAutoClear);

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
        let totalExpenses = 0;
        expBody.querySelectorAll('.grn-exp-row').forEach(r => {
            const amt = parseFloat(r.querySelector('.exp-amount')?.value) || 0;
            totalExpenses += amt;
        });

        const expNet = document.getElementById('expNetTotal');
        if (expNet) expNet.textContent = 'Rs. ' + totalExpenses.toFixed(2);
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
        const amt = row.querySelector('.exp-amount');
        const btnRemove = row.querySelector('.btn-remove-exp');

        if (amt) amt.addEventListener('input', updateTotals);

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
        clone.querySelectorAll('.auto-clear-zero').forEach(setupAutoClear);
        prodBody.appendChild(clone);
        prodIndex++;
        updateProdSr();
        updateTotals();
    });

    btnAddExp.addEventListener('click', function () {
        const firstRow = expBody.querySelector('.grn-exp-row');
        const clone = firstRow.cloneNode(true);

        clone.querySelectorAll('input').forEach(i => {
            if (i.classList.contains('exp-amount')) i.value = '0';
            else i.value = '';
        });
        clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);

        clone.querySelector('select[name*="[account_id]"]').name = `expenses[${expIndex}][account_id]`;
        clone.querySelector('select[name*="[expense_type]"]').name = `expenses[${expIndex}][expense_type]`;
        clone.querySelector('input[name*="[comments]"]').name = `expenses[${expIndex}][comments]`;
        clone.querySelector('.exp-amount').name = `expenses[${expIndex}][amount]`;

        attachExpListeners(clone);
        clone.querySelectorAll('.auto-clear-zero').forEach(setupAutoClear);
        expBody.appendChild(clone);
        expIndex++;
        updateTotals();
    });

    // Dynamic PO Loading on selection
    if (poSelect) {
        poSelect.addEventListener('change', function () {
            const poId = this.value;
            if (!poId) return;

            fetch(`/purchases/orders/${poId}/items-json`)
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.items || data.items.length === 0) return;

                    if (data.supplier_id && supplierSelect) {
                        supplierSelect.value = data.supplier_id;
                    }

                    prodBody.innerHTML = '';
                    prodIndex = 0;

                    data.items.forEach((item, idx) => {
                        const tr = document.createElement('tr');
                        tr.className = 'grn-prod-row';
                        const pendingQty = Math.max(0, parseFloat(item.quantity) - parseFloat(item.received_quantity || 0));
                        const defaultQty = pendingQty > 0 ? pendingQty : parseFloat(item.quantity);
                        const unitCost = parseFloat(item.unit_cost || 0);

                        tr.innerHTML = `
                            <td class="text-center row-sr fw-semibold">${idx + 1}</td>
                            <td>
                                <select class="form-select form-select-sm grn-prod-select" name="items[${idx}][inventory_item_id]" required>
                                    <option value="${item.inventory_item_id}" data-uom="${item.unit || 'PCS'}" data-cost="${unitCost}" selected>
                                        ${item.item_name} (${item.sku})
                                    </option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" name="items[${idx}][warehouse_id]">
                                    <option value="">Default</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center uom-label fw-bold">${item.unit || 'PCS'}</td>
                            <td>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end qty-total auto-clear-zero" name="items[${idx}][total_delivered]" value="${defaultQty}" required placeholder="1.00">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-rejected text-danger auto-clear-zero" name="items[${idx}][rejected_quantity]" value="0" placeholder="0.00">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end qty-accepted fw-bold text-success auto-clear-zero" name="items[${idx}][quantity]" value="${defaultQty}" required placeholder="1.00">
                                <input type="hidden" class="unit-cost" name="items[${idx}][unit_cost]" value="${unitCost}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm mb-1" name="items[${idx}][batch_number]" placeholder="Batch # (Auto)">
                                <input type="date" class="form-control form-control-sm" name="items[${idx}][expiry_date]">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        `;
                        prodBody.appendChild(tr);
                        attachProdListeners(tr);
                        tr.querySelectorAll('.auto-clear-zero').forEach(setupAutoClear);
                        prodIndex++;
                    });

                    updateProdSr();
                    updateTotals();
                })
                .catch(err => console.error('Error fetching PO items:', err));
        });
    }
});
</script>
@endsection
