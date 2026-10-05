@extends('layouts.app')
@section('title', 'New Stock Transfer')
@section('content')
<form method="post" action="{{ route('transfers.store') }}" id="transferForm">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Inter-Warehouse Stock Transfer</h4>
            <small class="text-secondary">Dispatch stock from one godown to another</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-arrow-left-right me-1"></i> Execute Transfer
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
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Source Warehouse (From) <span class="text-danger">*</span></label>
                    <select class="form-select @error('from_warehouse_id') is-invalid @enderror" name="from_warehouse_id" required>
                        <option value="">Select Origin</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('from_warehouse_id') == $wh->id)>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                    @error('from_warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Destination Warehouse (To) <span class="text-danger">*</span></label>
                    <select class="form-select @error('to_warehouse_id') is-invalid @enderror" name="to_warehouse_id" required>
                        <option value="">Select Destination</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('to_warehouse_id') == $wh->id)>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                    @error('to_warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Transfer Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('transfer_date') is-invalid @enderror" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" required>
                    @error('transfer_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Items to transfer -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam me-1"></i> Products to Transfer</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                <i class="bi bi-plus-lg me-1"></i> Add Item Line
            </button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="transferTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60%;">Product</th>
                        <th style="width: 35%;">Quantity to Transfer</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="transferBody">
                    <!-- Dynamic rows -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <label class="form-label fw-semibold">Transfer Notes / Gate Pass Details</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Driver name, vehicle number, dispatch notes...">{{ old('notes') }}</textarea>
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
            options += `<option value="${p.id}" ${p.id == selectedId ? 'selected' : ''}>${p.item_name} (Avail: ${avail} ${p.unit})</option>`;
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

        document.getElementById('transferBody').appendChild(tr);

        tr.querySelector('.remove-row-btn').addEventListener('click', function() {
            if (document.querySelectorAll('#transferBody tr').length > 1) {
                tr.remove();
            } else {
                alert('Transfer must contain at least one item.');
            }
        });

        rowIndex++;
    }

    document.getElementById('addRowBtn').addEventListener('click', () => createRow());

    createRow();
</script>
@endpush
@endsection
