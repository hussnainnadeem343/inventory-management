@extends('layouts.app')
@section('title', 'New Purchase Requisition')
@section('content')
<form method="post" action="{{ route('purchases.requisitions.store') }}" id="prForm">
    @csrf

    {{-- Top Action Header Bar (Full Width like Screenshot 2) --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Create Purchase Requisition (PR)</h4>
            <!-- <small class="text-secondary">Stage 1: Internal demand note requesting stock replenishment</small> -->
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.requisitions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Submit Requisition
            </button>
        </div>
    </div>

    {{-- Header Meta Strip (Full-Width 100% Edge-to-Edge) --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Department / Station</label>
                    <input type="text" class="form-control form-control-sm" name="department" value="{{ old('department', auth()->user()->shop ? auth()->user()->shop->name : 'Main Store') }}" placeholder="e.g. Godown #1, Showroom, Kitchen">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Target Warehouse / Godown</label>
                    <select class="form-select form-select-sm" name="warehouse_id">
                        <option value="">Default Warehouse (Optional)</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Requisition Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm @error('requisition_date') is-invalid @enderror" name="requisition_date" value="{{ old('requisition_date', date('Y-m-d')) }}" required>
                    @error('requisition_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Required By Date</label>
                    <input type="date" class="form-control form-control-sm @error('required_by_date') is-invalid @enderror" name="required_by_date" value="{{ old('required_by_date', date('Y-m-d', strtotime('+7 days'))) }}">
                    @error('required_by_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Priority Level <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm @error('priority') is-invalid @enderror" name="priority" required>
                        <option value="low" @selected(old('priority') === 'low')>Low</option>
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                        <option value="high" @selected(old('priority') === 'high')>High</option>
                        <option value="urgent" @selected(old('priority') === 'urgent')>Urgent / Critical</option>
                    </select>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Requested Products Table (Full-Width Edge-to-Edge Table Form like Screenshot 2) --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-uppercase text-secondary small">
                <i class="bi bi-cart3 me-1 text-primary"></i> Requested Products & Stock Demand
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPrRow">
                <i class="bi bi-plus-lg me-1"></i> Add Product Line
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" id="prItemsTable">
                <thead class="table-light text-secondary text-uppercase small">
                    <tr>
                        <th style="width: 5%;" class="text-center">SR</th>
                        <th style="width: 38%;">Product <span class="text-danger">*</span></th>
                        <th style="width: 14%;" class="text-center">Current Stock</th>
                        <th style="width: 8%;" class="text-center">UOM</th>
                        <th style="width: 15%;" class="text-end">Required Qty <span class="text-danger">*</span></th>
                        <th style="width: 15%;">Remarkss</th>
                        <th style="width: 5%;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="prItemsBody">
                    <tr class="pr-row">
                        <td class="text-center fw-bold text-secondary row-sr">1</td>
                        <td>
                            <select class="form-select form-select-sm pr-product-select" name="items[0][inventory_item_id]" required>
                                <option value="">Select Product...</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}" 
                                            data-unit="{{ $prod->unit }}"
                                            data-stock="{{ (float)$prod->quantity }}">
                                        {{ $prod->item_name }} @if($prod->sku)({{ $prod->sku }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border pr-stock-badge">--</span>
                        </td>
                        <td class="text-center">
                            <span class="text-muted small pr-uom-text">--</span>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end pr-qty" name="items[0][quantity]" value="1" required>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="items[0][description]" placeholder="Remarks">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" disabled title="Remove line">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end text-secondary">Total Quantity:</td>
                        <td class="text-end text-primary" id="displayTotalQty">1.00</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Bottom Section: Business Justification & Notes --}}
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <label class="form-label fw-semibold small mb-1">Notes</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Enter any additional notes...">{{ old('notes') }}</textarea>
                </div>
                <div class="col-lg-4 text-end mt-3 mt-lg-0">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('purchases.requisitions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Submit Requisition
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = 1;
    const body = document.getElementById('prItemsBody');
    const btnAdd = document.getElementById('btnAddPrRow');
    const displayTotalQty = document.getElementById('displayTotalQty');

    function calculateTotals() {
        let totalQty = 0;
        const rows = body.querySelectorAll('.pr-row');
        rows.forEach((row, idx) => {
            const srCell = row.querySelector('.row-sr');
            if (srCell) srCell.textContent = idx + 1;

            const qtyInput = row.querySelector('.pr-qty');
            const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            totalQty += qty;
        });

        if (displayTotalQty) {
            displayTotalQty.textContent = totalQty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    function attachListeners(row) {
        const select = row.querySelector('.pr-product-select');
        const qtyInput = row.querySelector('.pr-qty');
        const stockBadge = row.querySelector('.pr-stock-badge');
        const uomText = row.querySelector('.pr-uom-text');
        const btnRemove = row.querySelector('.btn-remove-row');

        select.addEventListener('change', function () {
            const opt = select.options[select.selectedIndex];
            if (opt && opt.value) {
                if (stockBadge) {
                    stockBadge.textContent = opt.dataset.stock !== undefined ? opt.dataset.stock + ' ' + (opt.dataset.unit || '') : '--';
                }
                if (uomText) {
                    uomText.textContent = opt.dataset.unit || 'PCS';
                }
            } else {
                if (stockBadge) stockBadge.textContent = '--';
                if (uomText) uomText.textContent = '--';
            }
        });

        qtyInput.addEventListener('input', calculateTotals);

        btnRemove.addEventListener('click', function () {
            if (body.querySelectorAll('.pr-row').length > 1) {
                row.remove();
                calculateTotals();
                updateRemoveButtons();
            }
        });
    }

    function updateRemoveButtons() {
        const rows = body.querySelectorAll('.pr-row');
        rows.forEach(r => {
            const btn = r.querySelector('.btn-remove-row');
            btn.disabled = rows.length === 1;
        });
    }

    // Attach to initial row
    const initialRow = body.querySelector('.pr-row');
    if (initialRow) {
        attachListeners(initialRow);
    }

    btnAdd.addEventListener('click', function () {
        const firstRow = body.querySelector('.pr-row');
        const clone = firstRow.cloneNode(true);

        clone.querySelectorAll('input').forEach(i => {
            i.value = i.classList.contains('pr-qty') ? '1' : '';
        });
        clone.querySelector('select').selectedIndex = 0;
        clone.querySelector('.pr-stock-badge').textContent = '--';
        clone.querySelector('.pr-uom-text').textContent = '--';

        clone.querySelector('.pr-product-select').name = `items[${rowIndex}][inventory_item_id]`;
        clone.querySelector('.pr-qty').name = `items[${rowIndex}][quantity]`;
        clone.querySelector('input[name*="[description]"]').name = `items[${rowIndex}][description]`;

        attachListeners(clone);
        body.appendChild(clone);
        rowIndex++;
        calculateTotals();
        updateRemoveButtons();

        // Focus newly added product select
        clone.querySelector('.pr-product-select').focus();
    });

    calculateTotals();
});
</script>
@endpush
@endsection
