@extends('layouts.app')
@section('title', 'Edit Purchase Order ' . $order->po_number)
@section('content')
<form method="post" action="{{ route('purchases.orders.update', $order) }}" id="poEditForm">
    @csrf
    @method('PUT')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Edit Purchase Order: {{ $order->po_number }}</h4>
            <small class="text-secondary">Stage 2: Official procurement order to vendor/supplier</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.orders.show', $order) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Order
            </a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Update Purchase Order
            </button>
        </div>
    </div>

    @if($order->purchaseRequisition)
        <div class="alert alert-info py-2 mb-3">
            <i class="bi bi-link-45deg me-1"></i> Connected to Requisition: <strong>{{ $order->purchaseRequisition->pr_number }}</strong>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <!-- Order Header Info -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Supplier / Vendor <span class="text-danger">*</span></label>
                            <select class="form-select searchable-select" name="supplier_id" id="supplierSelect" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $order->supplier_id) == $supplier->id)>
                                        {{ $supplier->name }} {{ $supplier->company_name ? "({$supplier->company_name})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Order Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="order_date" value="{{ old('order_date', $order->order_date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Expected Delivery Date</label>
                            <input type="date" class="form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date', $order->expected_delivery_date?->format('Y-m-d')) }}">
                        </div>

                        <!-- Receiving Station -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Receiving Station / Warehouse</label>
                            <div class="input-group">
                                <select class="form-select" id="stationPresetSelect">
                                    <option value="">Select Preset Station</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->name }}" @selected($order->delivery_station == $wh->name)>{{ $wh->name }} ({{ $wh->code }})</option>
                                    @endforeach
                                    <option value="__custom__">+ Custom Station Name</option>
                                </select>
                                <input type="text" class="form-control" name="delivery_station" id="stationCustomInput" value="{{ old('delivery_station', $order->delivery_station) }}" placeholder="Or type station manually">
                            </div>
                        </div>

                        <!-- Payment Mode -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_mode" required>
                                <option value="bank" @selected(old('payment_mode', $order->payment_mode ?? 'bank') === 'bank')>Bank</option>
                                <option value="cash" @selected(old('payment_mode', $order->payment_mode) === 'cash')>Cash</option>
                                <option value="cheque" @selected(old('payment_mode', $order->payment_mode) === 'cheque')>Cheque</option>
                            </select>
                        </div>

                        <!-- Payment Terms -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Payment Terms</label>
                            <select class="form-select" name="payment_terms" id="paymentTermsSelect">
                                <option value="Net 30" @selected(old('payment_terms', $order->payment_terms) === 'Net 30')>Net 30 Days</option>
                                <option value="Net 15" @selected(old('payment_terms', $order->payment_terms) === 'Net 15')>Net 15 Days</option>
                                <option value="COD" @selected(old('payment_terms', $order->payment_terms) === 'COD')>Cash on Delivery (COD)</option>
                                <option value="Advance" @selected(old('payment_terms', $order->payment_terms) === 'Advance')>Advance Payment</option>
                                <option value="Net 60" @selected(old('payment_terms', $order->payment_terms) === 'Net 60')>Net 60 Days</option>
                                <option value="Other" @selected(old('payment_terms', $order->payment_terms) === 'Other')>Other</option>
                            </select>
                            <input type="text" class="form-control form-control-sm mt-1" name="payment_term_custom" id="paymentTermCustom" value="{{ old('payment_term_custom', $order->payment_term_custom) }}" placeholder="Specify custom term..." style="{{ old('payment_terms', $order->payment_terms) === 'Other' ? '' : 'display: none;' }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-cart3 me-1 text-primary"></i> Order Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Item Line
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Product</th>
                                <th style="width: 15%;">Quantity</th>
                                <th style="width: 15%;">Unit Cost (Rs.)</th>
                                <th style="width: 12%;">Discount (Rs.)</th>
                                <th style="width: 10%;">VAT (%)</th>
                                <th style="width: 13%;" class="text-end">Subtotal</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary & Totals -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Commercial Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Items Subtotal:</span>
                        <strong id="displaySubtotal">Rs. {{ number_format($order->subtotal, 2) }}</strong>
                    </div>

                    <!-- Discount -->
                    <div class="my-3">
                        <label class="form-label small fw-semibold text-secondary">Overall Discount (Rs.):</label>
                        <input type="number" step="0.01" min="0" class="form-control auto-clear-zero text-end" name="discount_amount" id="discountInput" value="{{ old('discount_amount', (float)$order->discount_amount) }}" placeholder="0.00">
                    </div>

                    <!-- Tax Ratios Selector -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tax Ratio / GST Rate (Optional):</label>
                        <select class="form-select mb-2" id="taxRatioPreset" name="tax_percent">
                            <option value="0" @selected((float)$order->tax_percent == 0)>0% - None / Tax Exempt</option>
                            <option value="5" @selected((float)$order->tax_percent == 5)>5% - Reduced Rate</option>
                            <option value="10" @selected((float)$order->tax_percent == 10)>10% - Standard Services</option>
                            <option value="18" @selected((float)$order->tax_percent == 18)>18% - Standard GST</option>
                            <option value="custom" @selected(!in_array((float)$order->tax_percent, [0, 5, 10, 18]))>Custom Ratio %</option>
                        </select>
                        <div class="input-group" id="customTaxGroup" style="{{ !in_array((float)$order->tax_percent, [0, 5, 10, 18]) ? '' : 'display: none;' }}">
                            <span class="input-group-text">Rate %</span>
                            <input type="number" step="0.01" min="0" max="100" class="form-control auto-clear-zero" id="customTaxRate" value="{{ (float)$order->tax_percent }}" placeholder="0.00">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Tax Calculated (Rs.):</span>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end auto-clear-zero" name="tax_amount" id="taxInput" value="{{ old('tax_amount', (float)$order->tax_amount) }}" style="max-width: 140px;" placeholder="0.00">
                    </div>

                    <div class="d-flex justify-content-between py-3 border-top border-2">
                        <h5 class="mb-0 fw-bold">Grand Total:</h5>
                        <h5 class="mb-0 fw-bold text-primary" id="displayGrandTotal">Rs. {{ number_format($order->grand_total, 2) }}</h5>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label fw-semibold">Notes / Procurement Terms</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Vendor specifications, delivery remarks...">{{ old('notes', $order->notes) }}</textarea>
                </div>
            </div>
        </div>
    </div>

</form>

@push('scripts')
<script>
    const productsData = @json($products);
    const orderItems = @json($order->items);
    let rowIndex = 0;

    // Station dropdown helper
    const stationPreset = document.getElementById('stationPresetSelect');
    const stationCustom = document.getElementById('stationCustomInput');
    if (stationPreset && stationCustom) {
        stationPreset.addEventListener('change', function () {
            if (this.value === '__custom__') {
                stationCustom.value = '';
                stationCustom.focus();
            } else if (this.value) {
                stationCustom.value = this.value;
            }
        });
    }

    // Payment Terms Other toggle
    const paymentTerms = document.getElementById('paymentTermsSelect');
    const paymentTermCustom = document.getElementById('paymentTermCustom');
    if (paymentTerms && paymentTermCustom) {
        paymentTerms.addEventListener('change', function () {
            if (this.value === 'Other') {
                paymentTermCustom.style.display = 'block';
                paymentTermCustom.focus();
            } else {
                paymentTermCustom.style.display = 'none';
            }
        });
    }

    function setupAutoClear(el) {
        el.addEventListener('focus', function () {
            if (this.value === '0.00' || this.value === '0' || this.value === '0.0') {
                this.select();
            }
        });
        el.addEventListener('blur', function () {
            if (this.value.trim() === '') {
                this.value = this.classList.contains('qty-input') ? '1' : '0.00';
                recalc();
            }
        });
    }

    document.querySelectorAll('.auto-clear-zero').forEach(setupAutoClear);

    function createRow(selectedId = '', qty = 1, cost = 0, disc = 0, taxPct = 0) {
        const tr = document.createElement('tr');
        tr.dataset.index = rowIndex;

        let optionsHtml = '<option value="">Select Product</option>';
        productsData.forEach(p => {
            const isSelected = p.id == selectedId ? 'selected' : '';
            optionsHtml += `<option value="${p.id}" data-cost="${p.purchase_price || 0}" ${isSelected}>${p.item_name} (${p.sku})</option>`;
        });

        tr.innerHTML = `
            <td>
                <select class="form-select form-select-sm product-select" name="items[${rowIndex}][inventory_item_id]" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm qty-input auto-clear-zero" name="items[${rowIndex}][quantity]" value="${qty}" required placeholder="1.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm cost-input auto-clear-zero" name="items[${rowIndex}][unit_cost]" value="${cost}" required placeholder="0.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm line-disc-input auto-clear-zero" name="items[${rowIndex}][discount_amount]" value="${disc}" placeholder="0.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm line-vat-input auto-clear-zero" name="items[${rowIndex}][tax_percent]" value="${taxPct}" placeholder="0%">
            </td>
            <td class="fw-semibold text-end line-subtotal">Rs. 0.00</td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn"><i class="bi bi-trash"></i></button>
            </td>
        `;

        document.getElementById('itemsBody').appendChild(tr);

        const select = tr.querySelector('.product-select');
        const qtyIn = tr.querySelector('.qty-input');
        const costIn = tr.querySelector('.cost-input');
        const discIn = tr.querySelector('.line-disc-input');
        const vatIn = tr.querySelector('.line-vat-input');

        [qtyIn, costIn, discIn, vatIn].forEach(setupAutoClear);

        select.addEventListener('change', function() {
            const opt = select.options[select.selectedIndex];
            if (opt && opt.dataset.cost && parseFloat(costIn.value || 0) === 0) {
                costIn.value = opt.dataset.cost;
            }
            recalc();
        });

        [qtyIn, costIn, discIn, vatIn].forEach(input => {
            input.addEventListener('input', recalc);
        });

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

    const taxPreset = document.getElementById('taxRatioPreset');
    const customTaxGroup = document.getElementById('customTaxGroup');
    const customTaxRate = document.getElementById('customTaxRate');
    const taxInput = document.getElementById('taxInput');

    taxPreset.addEventListener('change', function () {
        if (this.value === 'custom') {
            customTaxGroup.style.display = 'flex';
        } else {
            customTaxGroup.style.display = 'none';
        }
        recalc();
    });

    customTaxRate.addEventListener('input', recalc);

    function getActiveTaxRate() {
        if (taxPreset.value === 'custom') {
            return parseFloat(customTaxRate.value) || 0;
        }
        return parseFloat(taxPreset.value) || 0;
    }

    function recalc() {
        let subtotal = 0;
        let lineDiscountTotal = 0;
        let totalLineVat = 0;

        const activePresetRate = getActiveTaxRate();

        document.querySelectorAll('#itemsBody tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
            const cost = parseFloat(tr.querySelector('.cost-input').value) || 0;
            const lineDisc = parseFloat(tr.querySelector('.line-disc-input').value) || 0;
            const lineVatPct = parseFloat(tr.querySelector('.line-vat-input').value) || 0;

            const rawLine = qty * cost;
            const lineAfterDisc = Math.max(0, rawLine - lineDisc);
            const lineVatAmount = lineAfterDisc * (lineVatPct / 100);
            const lineTotal = lineAfterDisc + lineVatAmount;

            tr.querySelector('.line-subtotal').textContent = 'Rs. ' + lineTotal.toFixed(2);

            subtotal += rawLine;
            lineDiscountTotal += lineDisc;
            totalLineVat += lineVatAmount;
        });

        const overallDiscountInput = document.getElementById('discountInput');
        let discount = parseFloat(overallDiscountInput.value) || 0;
        if (lineDiscountTotal > 0 && discount === 0) {
            discount = lineDiscountTotal;
            overallDiscountInput.value = lineDiscountTotal.toFixed(2);
        }

        const taxFromRatio = activePresetRate > 0 ? Math.max(0, subtotal - discount) * (activePresetRate / 100) : 0;
        const totalTax = totalLineVat + taxFromRatio;
        taxInput.value = totalTax.toFixed(2);

        const grandTotal = Math.max(0, subtotal - discount + totalTax);

        document.getElementById('displaySubtotal').textContent = 'Rs. ' + subtotal.toFixed(2);
        document.getElementById('displayGrandTotal').textContent = 'Rs. ' + grandTotal.toFixed(2);
    }

    document.getElementById('addRowBtn').addEventListener('click', () => createRow());
    document.getElementById('discountInput').addEventListener('input', recalc);
    document.getElementById('taxInput').addEventListener('input', recalc);

    if (orderItems && orderItems.length > 0) {
        orderItems.forEach(it => {
            createRow(it.inventory_item_id, parseFloat(it.quantity), parseFloat(it.unit_cost), parseFloat(it.discount_amount || 0), parseFloat(it.tax_percent || 0));
        });
    } else {
        createRow();
    }
</script>
@endpush
@endsection
