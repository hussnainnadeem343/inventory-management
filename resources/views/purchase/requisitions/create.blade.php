@extends('layouts.app')
@section('title', 'New Purchase Requisition')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Create Purchase Requisition (PR)</h5>
                    <small class="text-secondary">Demand note requesting stock replenishment</small>
                </div>
                <a href="{{ route('purchases.requisitions.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Requisitions
                </a>
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('purchases.requisitions.store') }}" id="prForm">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Requisition Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('requisition_date') is-invalid @enderror" name="requisition_date" value="{{ old('requisition_date', date('Y-m-d')) }}" required>
                            @error('requisition_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Required By Date</label>
                            <input type="date" class="form-control @error('required_by_date') is-invalid @enderror" name="required_by_date" value="{{ old('required_by_date', date('Y-m-d', strtotime('+7 days'))) }}">
                            @error('required_by_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Priority Level <span class="text-danger">*</span></label>
                            <select class="form-select @error('priority') is-invalid @enderror" name="priority" required>
                                <option value="low" @selected(old('priority') === 'low')>Low Priority</option>
                                <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium Normal</option>
                                <option value="high" @selected(old('priority') === 'high')>High Priority</option>
                                <option value="urgent" @selected(old('priority') === 'urgent')>Urgent / Critical</option>
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department / Station</label>
                            <input type="text" class="form-control" name="department" value="{{ old('department', 'Main Store') }}" placeholder="e.g. Godown #1, Showroom, Kitchen, Pharmacy">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Warehouse / Godown</label>
                            <select class="form-select" name="warehouse_id">
                                <option value="">Select Destination Godown (Optional)</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Requisition Items Table --}}
                    <div class="card border mb-4">
                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-semibold small text-uppercase">Requested Products</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPrRow">
                                <i class="bi bi-plus-circle me-1"></i> Add Product
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="prItemsTable">
                                <thead class="small text-secondary bg-white">
                                    <tr>
                                        <th style="width: 40%;">Product <span class="text-danger">*</span></th>
                                        <th style="width: 20%;">Required Qty <span class="text-danger">*</span></th>
                                        <th style="width: 20%;">Est. Unit Rate (Rs.)</th>
                                        <th style="width: 15%;">Remarks</th>
                                        <th style="width: 5%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="prItemsBody">
                                    <tr class="pr-row">
                                        <td>
                                            <select class="form-select form-select-sm pr-product-select" name="items[0][inventory_item_id]" required>
                                                <option value="">Select Product</option>
                                                @foreach($products as $prod)
                                                    <option value="{{ $prod->id }}" data-rate="{{ $prod->purchase_price }}" data-unit="{{ $prod->unit }}">
                                                        {{ $prod->item_name }} (Stock: {{ (float)$prod->quantity }} {{ $prod->unit }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm pr-qty" name="items[0][quantity]" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm pr-rate" name="items[0][estimated_unit_cost]" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="items[0][description]" placeholder="e.g. Urgent stock out">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notes / Business Justification</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Explain reason for stock requisition...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('purchases.requisitions.index') }}" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Submit Requisition
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = 1;
    const body = document.getElementById('prItemsBody');
    const btnAdd = document.getElementById('btnAddPrRow');

    function attachListeners(row) {
        const select = row.querySelector('.pr-product-select');
        const rateInput = row.querySelector('.pr-rate');
        const btnRemove = row.querySelector('.btn-remove-row');

        select.addEventListener('change', function () {
            const opt = select.options[select.selectedIndex];
            if (opt && opt.dataset.rate) {
                rateInput.value = parseFloat(opt.dataset.rate).toFixed(2);
            }
        });

        btnRemove.addEventListener('click', function () {
            if (body.querySelectorAll('.pr-row').length > 1) {
                row.remove();
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

    attachListeners(body.querySelector('.pr-row'));

    btnAdd.addEventListener('click', function () {
        const firstRow = body.querySelector('.pr-row');
        const clone = firstRow.cloneNode(true);

        clone.querySelectorAll('input').forEach(i => {
            i.value = i.classList.contains('pr-qty') ? '1' : '';
        });
        clone.querySelector('select').selectedIndex = 0;

        clone.querySelector('.pr-product-select').name = `items[${rowIndex}][inventory_item_id]`;
        clone.querySelector('.pr-qty').name = `items[${rowIndex}][quantity]`;
        clone.querySelector('.pr-rate').name = `items[${rowIndex}][estimated_unit_cost]`;
        clone.querySelector('input[name*="[description]"]').name = `items[${rowIndex}][description]`;

        attachListeners(clone);
        body.appendChild(clone);
        rowIndex++;
        updateRemoveButtons();
    });
});
</script>
@endsection
