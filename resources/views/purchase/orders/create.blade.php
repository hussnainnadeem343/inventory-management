@extends('layouts.app')
@section('title', 'Create Purchase Order')
@section('content')
<form method="post" action="{{ route('purchases.orders.store') }}" id="poForm">
    @csrf

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">New Purchase Order</h4>
            <small class="text-secondary">Stage 2: Official procurement order to vendor/supplier</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#loadRequisitionModal">
                <i class="bi bi-file-earmark-text me-1"></i> Load Requisition (Optional)
            </button>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Submit Purchase Order
            </button>
        </div>
    </div>

    @if($selectedRequisition)
        <div class="alert alert-info py-2 mb-3 d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-check-circle-fill me-1"></i> Loaded from Requisition: <strong>{{ $selectedRequisition->requisition_number }}</strong> (Date: {{ $selectedRequisition->requisition_date->format('d M, Y') }})
            </span>
            <a href="{{ route('purchases.orders.create') }}" class="btn btn-sm btn-outline-primary">Clear Requisition</a>
        </div>
        <input type="hidden" name="purchase_requisition_id" value="{{ $selectedRequisition->id }}">
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
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedRequisition?->supplier_id ?? request('supplier_id')) == $supplier->id)>
                                        {{ $supplier->name }} {{ $supplier->company_name ? "({$supplier->company_name})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Order Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Expected Delivery Date</label>
                            <input type="date" class="form-control" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}">
                        </div>

                        <!-- Hybrid Station (Point 9) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Receiving Station / Warehouse</label>
                            <div class="input-group">
                                <select class="form-select" id="stationPresetSelect">
                                    <option value="">Select Preset Station</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->name }}">{{ $wh->name }} ({{ $wh->code }})</option>
                                    @endforeach
                                    <option value="__custom__">+ Custom Station Name</option>
                                </select>
                                <input type="text" class="form-control" name="delivery_station" id="stationCustomInput" value="{{ old('delivery_station', auth()->user()->shop ? auth()->user()->shop->name : 'Main Store') }}" placeholder="Or type station manually">
                            </div>
                            <small class="text-muted">Choose from predefined godowns or type a manual station name.</small>
                        </div>

                        <!-- Payment Terms -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Terms</label>
                            <select class="form-select" name="payment_terms">
                                <option value="Net 30" @selected(old('payment_terms') === 'Net 30')>Net 30 Days</option>
                                <option value="Net 15" @selected(old('payment_terms') === 'Net 15')>Net 15 Days</option>
                                <option value="COD" @selected(old('payment_terms') === 'COD')>Cash on Delivery (COD)</option>
                                <option value="Advance" @selected(old('payment_terms') === 'Advance')>Advance Payment</option>
                                <option value="Net 60" @selected(old('payment_terms') === 'Net 60')>Net 60 Days</option>
                            </select>
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
                                <th style="width: 10%;">Tax (%)</th>
                                <th style="width: 13%;" class="text-end">Subtotal</th>
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
                    <h6 class="mb-0 fw-bold">Commercial Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Items Subtotal:</span>
                        <strong id="displaySubtotal">Rs. 0.00</strong>
                    </div>

                    <!-- Discount -->
                    <div class="my-3">
                        <label class="form-label small fw-semibold text-secondary">Overall Discount (Rs.):</label>
                        <input type="number" step="0.01" min="0" class="form-control auto-clear-zero text-end" name="discount_amount" id="discountInput" value="{{ old('discount_amount', '0.00') }}" placeholder="0.00">
                    </div>

                    <!-- Point 3: Tax Ratios Selector & Auto-calculation -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tax Ratio / GST Rate (Optional):</label>
                        <select class="form-select mb-2" id="taxRatioPreset" name="tax_percent">
                            <option value="0" selected>0% - None / Tax Exempt</option>
                            <option value="5">5% - Reduced Rate</option>
                            <option value="10">10% - Standard Services</option>
                            <option value="18">18% - Standard GST</option>
                            <option value="custom">Custom Ratio %</option>
                        </select>
                        <div class="input-group" id="customTaxGroup" style="display: none;">
                            <span class="input-group-text">Rate %</span>
                            <input type="number" step="0.01" min="0" max="100" class="form-control auto-clear-zero" id="customTaxRate" value="0" placeholder="0.00">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Tax Calculated (Rs.):</span>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end auto-clear-zero" name="tax_amount" id="taxInput" value="{{ old('tax_amount', '0.00') }}" style="max-width: 140px;" placeholder="0.00">
                    </div>

                    <div class="d-flex justify-content-between py-3 border-top border-2">
                        <h5 class="mb-0 fw-bold">Grand Total:</h5>
                        <h5 class="mb-0 fw-bold text-primary" id="displayGrandTotal">Rs. 0.00</h5>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label fw-semibold">Notes / Procurement Terms</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Vendor specifications, delivery remarks...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

</form>

<!-- Point 1: Load from Requisition Modal (Optional) -->
<div class="modal fade" id="loadRequisitionModal" tabindex="-1" aria-labelledby="loadRequisitionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="loadRequisitionLabel"><i class="bi bi-file-earmark-text text-primary me-2"></i>Select Approved Purchase Requisition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">Requisition #</th>
                                <th>Date</th>
                                <th>Department</th>
                                <th>Items Count</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($approvedRequisitions as $pr)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $pr->pr_number }}</td>
                                    <td>{{ $pr->requisition_date->format('d M, Y') }}</td>
                                    <td>{{ $pr->department ?? 'General' }}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary">{{ $pr->items->count() }} items</span></td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('purchases.orders.create', ['requisition_id' => $pr->id]) }}" class="btn btn-sm btn-info text-white">
                                            Load Items
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No approved pending requisitions found. You can proceed with Direct PO.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const productsData = @json($products);
    const preloadedItems = @json($selectedRequisition?->items ?? []);
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

    // Point 4: Auto-clear 0.00 helper
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
                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm line-tax-input auto-clear-zero" name="items[${rowIndex}][tax_percent]" value="${taxPct}" placeholder="0%">
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
        const taxIn = tr.querySelector('.line-tax-input');

        [qtyIn, costIn, discIn, taxIn].forEach(setupAutoClear);

        select.addEventListener('change', function() {
            const opt = select.options[select.selectedIndex];
            if (opt && opt.dataset.cost && parseFloat(costIn.value || 0) === 0) {
                costIn.value = opt.dataset.cost;
            }
            recalc();
        });

        [qtyIn, costIn, discIn, taxIn].forEach(input => {
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

    // Point 3: Tax ratio preset logic
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
        let lineTaxTotal = 0;

        const activePresetRate = getActiveTaxRate();

        document.querySelectorAll('#itemsBody tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
            const cost = parseFloat(tr.querySelector('.cost-input').value) || 0;
            const lineDisc = parseFloat(tr.querySelector('.line-disc-input').value) || 0;
            let lineTaxPct = parseFloat(tr.querySelector('.line-tax-input').value);

            if (isNaN(lineTaxPct) || lineTaxPct === 0) {
                lineTaxPct = activePresetRate;
            }

            const rawLine = qty * cost;
            const lineAfterDisc = Math.max(0, rawLine - lineDisc);
            const lineTaxAmount = lineAfterDisc * (lineTaxPct / 100);
            const lineTotal = lineAfterDisc + lineTaxAmount;

            tr.querySelector('.line-subtotal').textContent = 'Rs. ' + lineTotal.toFixed(2);

            subtotal += rawLine;
            lineDiscountTotal += lineDisc;
            lineTaxTotal += lineTaxAmount;
        });

        const overallDiscountInput = document.getElementById('discountInput');
        let discount = parseFloat(overallDiscountInput.value) || 0;
        if (lineDiscountTotal > 0 && discount === 0) {
            discount = lineDiscountTotal;
            overallDiscountInput.value = lineDiscountTotal.toFixed(2);
        }

        // Auto-calculate tax if tax ratio preset is selected
        let tax = 0;
        if (activePresetRate > 0 && lineTaxTotal <= 0) {
            tax = Math.max(0, subtotal - discount) * (activePresetRate / 100);
            taxInput.value = tax.toFixed(2);
        } else if (lineTaxTotal > 0) {
            tax = lineTaxTotal;
            taxInput.value = tax.toFixed(2);
        } else {
            tax = parseFloat(taxInput.value) || 0;
        }

        const grandTotal = Math.max(0, subtotal - discount + tax);

        document.getElementById('displaySubtotal').textContent = 'Rs. ' + subtotal.toFixed(2);
        document.getElementById('displayGrandTotal').textContent = 'Rs. ' + grandTotal.toFixed(2);
    }

    document.getElementById('addRowBtn').addEventListener('click', () => createRow());
    document.getElementById('discountInput').addEventListener('input', recalc);
    document.getElementById('taxInput').addEventListener('input', recalc);

    // If preloaded from Requisition, populate items
    if (preloadedItems && preloadedItems.length > 0) {
        preloadedItems.forEach(it => {
            createRow(it.inventory_item_id, it.required_quantity, it.estimated_unit_cost || 0);
        });
    } else {
        createRow();
    }
</script>
@endpush
@endsection
