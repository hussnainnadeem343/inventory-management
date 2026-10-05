@extends('layouts.app')
@section('title', 'Receive Goods (GRN)')
@section('content')
<form method="post" action="{{ route('purchases.grn.store') }}" id="grnForm">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Goods Received Note (Inward)</h4>
            <small class="text-secondary">Receive product batches and increment stock</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.grn.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-success px-4">
                <i class="bi bi-box-arrow-in-down me-1"></i> Confirm & Receive Stock
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

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        @if($selectedPo)
                            <input type="hidden" name="purchase_order_id" value="{{ $selectedPo->id }}">
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0 d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-info-circle me-1"></i> Inwarding against <strong>{{ $selectedPo->po_number }}</strong>
                                    </span>
                                    <a href="{{ route('purchases.grn.create') }}" class="btn btn-sm btn-outline-primary">Switch to Direct Inward</a>
                                </div>
                            </div>
                        @else
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Load from Purchase Order (Optional)</label>
                                <select class="form-select" onchange="if(this.value) window.location.href='{{ route('purchases.grn.create') }}?purchase_order_id=' + this.value">
                                    <option value="">-- Direct Inward (No PO) --</option>
                                    @foreach($pendingOrders as $po)
                                        <option value="{{ $po->id }}">{{ $po->po_number }} ({{ $po->order_date->format('d M') }}) - Rs. {{ number_format($po->grand_total, 2) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier / Vendor <span class="text-danger">*</span></label>
                            <select class="form-select searchable-select" name="supplier_id" id="supplierSelect" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedPo?->supplier_id) == $supplier->id)>
                                        {{ $supplier->name }} {{ $supplier->company_name ? "({$supplier->company_name})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Received Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="received_date" value="{{ old('received_date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Supplier Invoice No.</label>
                            <input type="text" class="form-control" name="supplier_invoice_no" value="{{ old('supplier_invoice_no') }}" placeholder="e.g. INV-9872">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-boxes me-1"></i> Stock Items & Batches</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Item Line
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 32%;">Product</th>
                                <th style="width: 18%;">Batch No.</th>
                                <th style="width: 16%;">Expiry Date</th>
                                <th style="width: 14%;">Qty</th>
                                <th style="width: 15%;">Unit Cost</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Injected rows -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Side summary -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Inward Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Total Inward Value:</span>
                        <h4 class="mb-0 fw-bold text-success" id="displayTotal">Rs. 0.00</h4>
                    </div>

                    <div class="alert alert-secondary mt-3 mb-0 small">
                        <i class="bi bi-shield-check me-1"></i>
                        Confirming will immediately create/increment inventory batches and record stock transaction ledger.
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <label class="form-label fw-semibold">Receiving Notes / Remarks</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Condition of goods, vehicle details, etc.">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    const productsData = @json($products);
    const prefillItems = @json($selectedPo ? $selectedPo->items : []);
    let rowIndex = 0;

    function createRow(selectedId = '', batchNo = '', expiry = '', qty = 1, cost = 0) {
        const tr = document.createElement('tr');
        tr.dataset.index = rowIndex;

        let optionsHtml = '<option value="">Select Product</option>';
        productsData.forEach(p => {
            const isSelected = p.id == selectedId ? 'selected' : '';
            optionsHtml += `<option value="${p.id}" data-cost="${p.purchase_price || 0}" ${isSelected}>${p.item_name} (${p.sku})</option>`;
        });

        tr.innerHTML = `
            <td>
                <select class="form-select product-select" name="items[${rowIndex}][inventory_item_id]" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="items[${rowIndex}][batch_number]" value="${batchNo}" placeholder="Auto-generate">
            </td>
            <td>
                <input type="date" class="form-control form-control-sm" name="items[${rowIndex}][expiry_date]" value="${expiry}">
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm qty-input" name="items[${rowIndex}][quantity]" value="${qty}" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm cost-input" name="items[${rowIndex}][unit_cost]" value="${cost}" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn"><i class="bi bi-trash"></i></button>
            </td>
        `;

        document.getElementById('itemsBody').appendChild(tr);

        const select = tr.querySelector('.product-select');
        const qtyIn = tr.querySelector('.qty-input');
        const costIn = tr.querySelector('.cost-input');

        select.addEventListener('change', function() {
            const opt = select.options[select.selectedIndex];
            if (opt && opt.dataset.cost && parseFloat(costIn.value) === 0) {
                costIn.value = opt.dataset.cost;
            }
            recalc();
        });

        qtyIn.addEventListener('input', recalc);
        costIn.addEventListener('input', recalc);

        tr.querySelector('.remove-row-btn').addEventListener('click', function() {
            if (document.querySelectorAll('#itemsBody tr').length > 1) {
                tr.remove();
                recalc();
            } else {
                alert('GRN must contain at least one item.');
            }
        });

        rowIndex++;
        recalc();
    }

    function recalc() {
        let total = 0;
        document.querySelectorAll('#itemsBody tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
            const cost = parseFloat(tr.querySelector('.cost-input').value) || 0;
            total += qty * cost;
        });

        document.getElementById('displayTotal').textContent = 'Rs. ' + total.toFixed(2);
    }

    document.getElementById('addRowBtn').addEventListener('click', () => createRow());

    // If prefill from PO
    if (prefillItems && prefillItems.length > 0) {
        prefillItems.forEach(item => {
            const remaining = Math.max(0, item.quantity - item.received_quantity);
            if (remaining > 0) {
                createRow(item.inventory_item_id, '', '', remaining, item.unit_cost);
            }
        });
    } else {
        createRow();
    }
</script>
@endpush
@endsection
