@extends('layouts.app')
@section('title', 'Record Stock Adjustment')
@section('content')
<form method="post" action="{{ route('adjustments.store') }}" id="adjForm">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Record Stock Adjustment</h4>
            <small class="text-secondary">Physical audit reconciliation, damages, and write-offs</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('adjustments.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i> Apply Adjustment
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Adjustment Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                        <option value="addition" @selected(old('type') === 'addition')>+ Addition (Found Stock / Recount)</option>
                        <option value="subtraction" @selected(old('type') === 'subtraction')>- Subtraction (Damage / Expired / Loss)</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Warehouse / Location</label>
                    <select class="form-select" name="warehouse_id">
                        <option value="">-- General Stock --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('adjustment_date') is-invalid @enderror" name="adjustment_date" value="{{ old('adjustment_date', date('Y-m-d')) }}" required>
                    @error('adjustment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Reason / Audit Tag <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('reason') is-invalid @enderror" name="reason" value="{{ old('reason') }}" placeholder="e.g. Broken in transit, Audit variance" required>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-boxes me-1"></i> Adjusted Items</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                <i class="bi bi-plus-lg me-1"></i> Add Product Line
            </button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="adjTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60%;">Product</th>
                        <th style="width: 35%;">Adjustment Quantity</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="adjBody">
                    <!-- Dynamic rows -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <label class="form-label fw-semibold">Notes / Auditor Remarks</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Audit approved by manager...">{{ old('notes') }}</textarea>
        </div>
    </div>
</form>

@push('scripts')
<script>
    const productsData = @json($products);
    let rowIndex = 0;

    function createRow(selectedId = '', qty = 1) {
        const tr = document.createElement('tr');
        tr.dataset.index = rowIndex;

        let options = '<option value="">Select Product</option>';
        productsData.forEach(p => {
            const avail = p.quantity - p.sold_quantity;
            options += `<option value="${p.id}" ${p.id == selectedId ? 'selected' : ''}>${p.item_name} (Current Stock: ${avail} ${p.unit})</option>`;
        });

        tr.innerHTML = `
            <td>
                <select class="form-select" name="items[${rowIndex}][inventory_item_id]" required>
                    ${options}
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control" name="items[${rowIndex}][quantity]" value="${qty}" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn"><i class="bi bi-trash"></i></button>
            </td>
        `;

        document.getElementById('adjBody').appendChild(tr);

        tr.querySelector('.remove-row-btn').addEventListener('click', function() {
            if (document.querySelectorAll('#adjBody tr').length > 1) {
                tr.remove();
            } else {
                alert('Adjustment must contain at least one item.');
            }
        });

        rowIndex++;
    }

    document.getElementById('addRowBtn').addEventListener('click', () => createRow());

    createRow();
</script>
@endpush
@endsection
