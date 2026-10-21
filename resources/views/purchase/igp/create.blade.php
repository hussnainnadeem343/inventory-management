@extends('layouts.app')
@section('title', 'Log Inward Gate Pass')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Inward Gate Pass (IGP)</h5>
                    <small class="text-secondary">Stage 3: Physical gate security check-in for arriving carrier & transport</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('purchases.igp.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Gate Passes
                    </a>
                </div>
            </div>

            <div class="card-body p-4">
                <form method="post" action="{{ route('purchases.igp.store') }}" id="igpForm">
                    @csrf

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier / Vendor <span class="text-danger">*</span></label>
                            <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplierSelect" required>
                                <option value="">Select Vendor</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" @selected(old('supplier_id', $selectedPo?->supplier_id) == $sup->id)>
                                        {{ $sup->name }} @if($sup->company_name) - {{ $sup->company_name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <small class="text-muted">Selecting vendor automatically filters pending purchase orders below.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Purchase Order (Select to Load Items)</label>
                            <select class="form-select" name="purchase_order_id" id="poSelect">
                                <option value="">Select Linked PO (Or Direct Gate Entry)</option>
                                @foreach($pendingOrders as $po)
                                    <option value="{{ $po->id }}" @selected(old('purchase_order_id', $selectedPo?->id) == $po->id) data-supplier="{{ $po->supplier_id }}">
                                        {{ $po->po_number }} - {{ $po->supplier->name }} (Rs. {{ number_format($po->grand_total, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted text-primary"><i class="bi bi-info-circle me-1"></i>Selecting a PO will automatically load all ordered products and quantities into the table.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Arrival Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="igp_date" value="{{ old('igp_date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Gate Entry Time</label>
                            <input type="time" class="form-control" name="gate_entry_time" value="{{ old('gate_entry_time', date('H:i')) }}">
                        </div>

                        <div class="col-md-4">
                            <div class="d-flex justify-content-between">
                                <label class="form-label fw-semibold">Received Via <span class="text-danger">*</span></label>
                                <a href="{{ route('purchases.transport_modes.index') }}" target="_blank" class="small text-decoration-none">Manage Modes</a>
                            </div>
                            <select class="form-select" name="received_via" required>
                                @if(isset($transportModes) && $transportModes->isNotEmpty())
                                    @foreach($transportModes as $tm)
                                        <option value="{{ $tm->name }}" @selected(old('received_via') === $tm->name)>{{ $tm->name }}</option>
                                    @endforeach
                                @else
                                    <option value="Road / Truck" @selected(old('received_via') === 'Road / Truck')>Road / Truck</option>
                                    <option value="Suzuki / Pickup" @selected(old('received_via') === 'Suzuki / Pickup')>Suzuki / Pickup</option>
                                    <option value="Courier / Cargo" @selected(old('received_via') === 'Courier / Cargo')>Courier / Cargo Service</option>
                                    <option value="By Hand" @selected(old('received_via') === 'By Hand')>By Hand / Person</option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Vehicle Number</label>
                            <input type="text" class="form-control" name="vehicle_number" value="{{ old('vehicle_number') }}" placeholder="e.g. LES-1234, KHI-8902">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Driver Name</label>
                            <input type="text" class="form-control" name="driver_name" value="{{ old('driver_name') }}" placeholder="e.g. Muhammad Aslam">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Driver Phone</label>
                            <input type="text" class="form-control" name="driver_phone" value="{{ old('driver_phone') }}" placeholder="0300-1234567">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bilty / Consignment Tracking #</label>
                            <input type="text" class="form-control" name="bilty_number" value="{{ old('bilty_number') }}" placeholder="e.g. BLT-987654">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Vendor Delivery Challan #</label>
                            <input type="text" class="form-control" name="challan_number" value="{{ old('challan_number') }}" placeholder="e.g. DC-55442">
                        </div>
                    </div>

                    {{-- Arrived Packages Table --}}
                    <div class="card border mb-4">
                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-semibold small text-uppercase">Arrived Delivery Cargo & Gate Inspection</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddIgpRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Item Line
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="igpItemsTable">
                                <thead class="small text-secondary bg-white">
                                    <tr>
                                        <th style="width: 28%;">Product <span class="text-danger">*</span></th>
                                        <th style="width: 12%;">Packages</th>
                                        <th style="width: 14%;">Vendor Qty</th>
                                        <th style="width: 14%;">Received Qty <span class="text-danger">*</span></th>
                                        <th style="width: 12%;">Rejected Qty (Gate)</th>
                                        <th style="width: 10%;">Net Inward Qty</th>
                                        <th style="width: 12%;">Remarks</th>
                                        <th style="width: 5%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="igpItemsBody">
                                    @if($selectedPo && $selectedPo->items->isNotEmpty())
                                        @foreach($selectedPo->items as $idx => $poLine)
                                            @php
                                                $pendingToReceive = max(0, $poLine->quantity - $poLine->received_quantity);
                                                $lineQty = $pendingToReceive > 0 ? $pendingToReceive : $poLine->quantity;
                                            @endphp
                                            <tr class="igp-row">
                                                <td>
                                                    <select class="form-select form-select-sm igp-product-select" name="items[{{ $idx }}][inventory_item_id]" required>
                                                        @foreach($products as $prod)
                                                            <option value="{{ $prod->id }}" @selected($prod->id == $poLine->inventory_item_id)>
                                                                {{ $prod->item_name }} ({{ $prod->sku }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end auto-clear-zero" name="items[{{ $idx }}][packages_count]" value="1" placeholder="1.00">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end vendor-qty auto-clear-zero" name="items[{{ $idx }}][vendor_quantity]" value="{{ $lineQty }}" placeholder="0.00">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end declared-qty auto-clear-zero" name="items[{{ $idx }}][declared_quantity]" value="{{ $lineQty }}" required placeholder="0.00">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end rejected-qty auto-clear-zero" name="items[{{ $idx }}][rejected_quantity]" value="0.00" placeholder="0.00">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm text-end bg-light fw-bold net-inward-qty" value="{{ number_format($lineQty, 2) }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="items[{{ $idx }}][remarks]" placeholder="Condition notes">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr class="igp-row">
                                            <td>
                                                <select class="form-select form-select-sm igp-product-select" name="items[0][inventory_item_id]" required>
                                                    <option value="">Select Product</option>
                                                    @foreach($products as $prod)
                                                        <option value="{{ $prod->id }}">{{ $prod->item_name }} ({{ $prod->sku }})</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end auto-clear-zero" name="items[0][packages_count]" value="1" placeholder="1.00">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end vendor-qty auto-clear-zero" name="items[0][vendor_quantity]" value="1" placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end declared-qty auto-clear-zero" name="items[0][declared_quantity]" value="1" required placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end rejected-qty auto-clear-zero" name="items[0][rejected_quantity]" value="0.00" placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm text-end bg-light fw-bold net-inward-qty" value="1.00" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="items[0][remarks]" placeholder="Packaging info">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" disabled>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Gate Remarks / Packaging Condition</label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="e.g. Cartons intact, vendor sealed, physical security verified at gate...">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('purchases.igp.index') }}" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-shield-check me-1"></i> Save & Log Inward Gate Pass
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const productsData = @json($products);
    let rowIndex = {{ $selectedPo && $selectedPo->items->isNotEmpty() ? $selectedPo->items->count() : 1 }};
    const body = document.getElementById('igpItemsBody');
    const btnAdd = document.getElementById('btnAddIgpRow');
    const supplierSelect = document.getElementById('supplierSelect');
    const poSelect = document.getElementById('poSelect');

    function filterPOsBySupplier() {
        if (!supplierSelect || !poSelect) return;
        const selectedSupplier = supplierSelect.value;
        const options = poSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) {
                opt.style.display = '';
                return;
            }
            const poSupplier = opt.getAttribute('data-supplier');
            if (!selectedSupplier || poSupplier === selectedSupplier) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });

        const currentOpt = poSelect.options[poSelect.selectedIndex];
        if (currentOpt && currentOpt.value && selectedSupplier && currentOpt.getAttribute('data-supplier') !== selectedSupplier) {
            poSelect.value = '';
        }
    }

    // Auto-load PO items dynamically on change
    if (poSelect) {
        poSelect.addEventListener('change', function () {
            const poId = this.value;
            if (!poId) return;

            fetch(`/purchases/orders/${poId}/items-json`)
                .then(res => res.json())
                .then(data => {
                    if (data.supplier_id && supplierSelect) {
                        supplierSelect.value = data.supplier_id;
                    }
                    if (data.items && data.items.length > 0) {
                        body.innerHTML = '';
                        rowIndex = 0;
                        data.items.forEach(it => {
                            const pendingQty = it.pending_quantity > 0 ? it.pending_quantity : it.ordered_quantity;
                            createIgpRow(it.inventory_item_id, 1, pendingQty, pendingQty, 0, '');
                        });
                        updateRemoveButtons();
                    }
                })
                .catch(err => console.error('Error fetching PO items:', err));
        });
    }

    if (supplierSelect) {
        supplierSelect.addEventListener('change', filterPOsBySupplier);
    }

    function setupAutoClear(el) {
        el.addEventListener('focus', function () {
            if (this.value === '0.00' || this.value === '0' || this.value === '0.0') {
                this.select();
            }
        });
        el.addEventListener('blur', function () {
            if (this.value.trim() === '') {
                this.value = this.classList.contains('declared-qty') ? '1.00' : '0.00';
            }
        });
    }

    function updateRowNet(row) {
        const declared = parseFloat(row.querySelector('.declared-qty')?.value) || 0;
        const rejected = parseFloat(row.querySelector('.rejected-qty')?.value) || 0;
        const net = Math.max(0, declared - rejected);
        const netEl = row.querySelector('.net-inward-qty');
        if (netEl) {
            netEl.value = net.toFixed(2);
        }
    }

    function attachListeners(row) {
        const btnRemove = row.querySelector('.btn-remove-row');
        if (btnRemove) {
            btnRemove.addEventListener('click', function () {
                if (body.querySelectorAll('.igp-row').length > 1) {
                    row.remove();
                    updateRemoveButtons();
                }
            });
        }

        row.querySelectorAll('.auto-clear-zero').forEach(setupAutoClear);

        const declaredInput = row.querySelector('.declared-qty');
        const rejectedInput = row.querySelector('.rejected-qty');

        if (declaredInput) declaredInput.addEventListener('input', () => updateRowNet(row));
        if (rejectedInput) rejectedInput.addEventListener('input', () => updateRowNet(row));
    }

    function updateRemoveButtons() {
        const rows = body.querySelectorAll('.igp-row');
        rows.forEach(r => {
            const btn = r.querySelector('.btn-remove-row');
            if (btn) btn.disabled = rows.length === 1;
        });
    }

    function createIgpRow(selectedId = '', pkgs = 1, vendorQty = 1, declaredQty = 1, rejectedQty = 0, remarks = '') {
        const tr = document.createElement('tr');
        tr.className = 'igp-row';

        let optionsHtml = '<option value="">Select Product</option>';
        productsData.forEach(p => {
            const isSelected = p.id == selectedId ? 'selected' : '';
            optionsHtml += `<option value="${p.id}" ${isSelected}>${p.item_name} (${p.sku})</option>`;
        });

        tr.innerHTML = `
            <td>
                <select class="form-select form-select-sm igp-product-select" name="items[${rowIndex}][inventory_item_id]" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end auto-clear-zero" name="items[${rowIndex}][packages_count]" value="${pkgs}" placeholder="1.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end vendor-qty auto-clear-zero" name="items[${rowIndex}][vendor_quantity]" value="${vendorQty}" placeholder="0.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end declared-qty auto-clear-zero" name="items[${rowIndex}][declared_quantity]" value="${declaredQty}" required placeholder="0.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end rejected-qty auto-clear-zero" name="items[${rowIndex}][rejected_quantity]" value="${rejectedQty}" placeholder="0.00">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-end bg-light fw-bold net-inward-qty" value="${(declaredQty - rejectedQty).toFixed(2)}" readonly>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="items[${rowIndex}][remarks]" value="${remarks}" placeholder="Condition notes">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        body.appendChild(tr);
        attachListeners(tr);
        rowIndex++;
    }

    body.querySelectorAll('.igp-row').forEach(attachListeners);

    btnAdd.addEventListener('click', function () {
        createIgpRow('', 1, 1, 1, 0, '');
        updateRemoveButtons();
    });

    filterPOsBySupplier();
});
</script>
@endpush
@endsection
