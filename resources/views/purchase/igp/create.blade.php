@extends('layouts.app')
@section('title', 'New Inward Gate Pass (IGP)')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Record Inward Gate Pass (IGP)</h5>
                    <small class="text-secondary">Stage 3: Gate security entry & shipment arrival verification</small>
                </div>
                <a href="{{ route('purchases.igp.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Gate Passes
                </a>
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('purchases.igp.store') }}" id="igpForm">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier / Vendor <span class="text-danger">*</span></label>
                            <select class="form-select searchable-select @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplierSelect" required>
                                <option value="">Select Vendor</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" @selected(old('supplier_id', $selectedPo?->supplier_id) == $sup->id)>
                                        {{ $sup->name }} @if($sup->company_name) - {{ $sup->company_name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Purchase Order (Optional)</label>
                            <select class="form-select" name="purchase_order_id" id="poSelect">
                                <option value="">Select Linked PO (Optional)</option>
                                @foreach($pendingOrders as $po)
                                    <option value="{{ $po->id }}" @selected(old('purchase_order_id', $selectedPo?->id) == $po->id) data-supplier="{{ $po->supplier_id }}">
                                        {{ $po->po_number }} (Rs. {{ number_format($po->grand_total, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Linking a PO allows 1-click loading of ordered items.</small>
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
                            <label class="form-label fw-semibold">Received Via <span class="text-danger">*</span></label>
                            <select class="form-select" name="received_via" required>
                                <option value="Road / Truck" @selected(old('received_via') === 'Road / Truck')>Road / Truck</option>
                                <option value="Suzuki / Pickup" @selected(old('received_via') === 'Suzuki / Pickup')>Suzuki / Pickup</option>
                                <option value="Courier / Cargo" @selected(old('received_via') === 'Courier / Cargo')>Courier / Cargo Service</option>
                                <option value="By Hand" @selected(old('received_via') === 'By Hand')>By Hand / Person</option>
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
                            <span class="fw-semibold small text-uppercase">Declared Shipment Packages</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddIgpRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Item
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="igpItemsTable">
                                <thead class="small text-secondary bg-white">
                                    <tr>
                                        <th style="width: 45%;">Product <span class="text-danger">*</span></th>
                                        <th style="width: 20%;">Package Count (Cartons/Bags)</th>
                                        <th style="width: 20%;">Declared Qty (as per Challan) <span class="text-danger">*</span></th>
                                        <th style="width: 10%;">Remarks</th>
                                        <th style="width: 5%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="igpItemsBody">
                                    @if($selectedPo && $selectedPo->items->isNotEmpty())
                                        @foreach($selectedPo->items as $idx => $poLine)
                                            <tr class="igp-row">
                                                <td>
                                                    <select class="form-select form-select-sm" name="items[{{ $idx }}][inventory_item_id]" required>
                                                        <option value="{{ $poLine->inventory_item_id }}" selected>
                                                            {{ $poLine->inventoryItem->item_name }} (PO Qty: {{ $poLine->quantity }} {{ $poLine->inventoryItem->unit }})
                                                        </option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[{{ $idx }}][packages_count]" value="1">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" name="items[{{ $idx }}][declared_quantity]" value="{{ $poLine->quantity - $poLine->received_quantity }}" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="items[{{ $idx }}][remarks]" placeholder="Packaging info">
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
                                                <select class="form-select form-select-sm" name="items[0][inventory_item_id]" required>
                                                    <option value="">Select Product</option>
                                                    @foreach($products as $prod)
                                                        <option value="{{ $prod->id }}">{{ $prod->item_name }} ({{ $prod->sku }})</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[0][packages_count]" value="1">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" name="items[0][declared_quantity]" value="1" required>
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
                        <label class="form-label fw-semibold">Gate Remarks / Condition of Packages</label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="e.g. Cartons intact, sealed with vendor tape, no water damage observed at gate...">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('purchases.igp.index') }}" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-shield-check me-1"></i> Save & Log Inward Gate Pass
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = {{ $selectedPo ? $selectedPo->items->count() : 1 }};
    const body = document.getElementById('igpItemsBody');
    const btnAdd = document.getElementById('btnAddIgpRow');

    function attachListeners(row) {
        const btnRemove = row.querySelector('.btn-remove-row');
        btnRemove.addEventListener('click', function () {
            if (body.querySelectorAll('.igp-row').length > 1) {
                row.remove();
                updateRemoveButtons();
            }
        });
    }

    function updateRemoveButtons() {
        const rows = body.querySelectorAll('.igp-row');
        rows.forEach(r => {
            const btn = r.querySelector('.btn-remove-row');
            btn.disabled = rows.length === 1;
        });
    }

    body.querySelectorAll('.igp-row').forEach(attachListeners);

    btnAdd.addEventListener('click', function () {
        const firstRow = body.querySelector('.igp-row');
        const clone = firstRow.cloneNode(true);

        clone.querySelectorAll('input').forEach(i => i.value = '');
        clone.querySelector('select').selectedIndex = 0;

        clone.querySelector('select').name = `items[${rowIndex}][inventory_item_id]`;
        clone.querySelector('input[name*="[packages_count]"]').name = `items[${rowIndex}][packages_count]`;
        clone.querySelector('input[name*="[declared_quantity]"]').name = `items[${rowIndex}][declared_quantity]`;
        clone.querySelector('input[name*="[remarks]"]').name = `items[${rowIndex}][remarks]`;

        attachListeners(clone);
        body.appendChild(clone);
        rowIndex++;
        updateRemoveButtons();
    });
});
</script>
@endsection
