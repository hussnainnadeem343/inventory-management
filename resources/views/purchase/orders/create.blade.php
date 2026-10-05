@extends('layouts.app')
@section('title', 'Create Purchase Order')
@section('content')
<form method="post" action="{{ route('purchases.orders.store') }}" id="poForm">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">New Purchase Order</h4>
            <small class="text-secondary">Place an order to a vendor/supplier</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i> Submit Purchase Order
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
            <!-- Order Header Info -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier / Vendor <span class="text-danger">*</span></label>
                            <select class="form-select searchable-select" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', request('supplier_id')) == $supplier->id)>
                                        {{ $supplier->name }} {{ $supplier->company_name ? "({$supplier->company_name})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Order Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Expected Delivery</label>
                            <input type="date" class="form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-cart3 me-1"></i> Order Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Item Line
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 45%;">Product</th>
                                <th style="width: 20%;">Quantity</th>
                                <th style="width: 20%;">Unit Cost (Rs.)</th>
                                <th style="width: 10%;">Subtotal</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Dynamic rows injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary & Totals -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Order Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Subtotal:</span>
                        <strong id="displaySubtotal">Rs. 0.00</strong>
                    </div>

                    <div class="my-3">
                        <label class="form-label small fw-semibold text-secondary">Discount (Rs.):</label>
                        <input type="number" step="0.01" class="form-control" name="discount_amount" id="discountInput" value="{{ old('discount_amount', '0.00') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tax / GST (Rs.):</label>
                        <input type="number" step="0.01" class="form-control" name="tax_amount" id="taxInput" value="{{ old('tax_amount', '0.00') }}">
                    </div>

                    <div class="d-flex justify-content-between py-3 border-top border-2">
                        <h5 class="mb-0 fw-bold">Grand Total:</h5>
                        <h5 class="mb-0 fw-bold text-primary" id="displayGrandTotal">Rs. 0.00</h5>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <label class="form-label fw-semibold">Notes / Terms</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Payment terms, delivery instructions...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    const productsData = @json($products);
    let rowIndex = 0;

    function createRow(selectedId = '', qty = 1, cost = 0) {
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
                <input type="number" step="0.01" min="0.01" class="form-control qty-input" name="items[${rowIndex}][quantity]" value="${qty}" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control cost-input" name="items[${rowIndex}][unit_cost]" value="${cost}" required>
            </td>
            <td class="fw-semibold line-subtotal">Rs. 0.00</td>
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
            if (opt && opt.dataset.cost) {
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
                alert('Order must contain at least one item.');
            }
        });

        rowIndex++;
        recalc();
    }

    function recalc() {
        let subtotal = 0;
        document.querySelectorAll('#itemsBody tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
            const cost = parseFloat(tr.querySelector('.cost-input').value) || 0;
            const line = qty * cost;
            tr.querySelector('.line-subtotal').textContent = 'Rs. ' + line.toFixed(2);
            subtotal += line;
        });

        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const tax = parseFloat(document.getElementById('taxInput').value) || 0;
        const grandTotal = Math.max(0, subtotal - discount + tax);

        document.getElementById('displaySubtotal').textContent = 'Rs. ' + subtotal.toFixed(2);
        document.getElementById('displayGrandTotal').textContent = 'Rs. ' + grandTotal.toFixed(2);
    }

    document.getElementById('addRowBtn').addEventListener('click', () => createRow());
    document.getElementById('discountInput').addEventListener('input', recalc);
    document.getElementById('taxInput').addEventListener('input', recalc);

    // Initial first row
    createRow();
</script>
@endpush
@endsection
