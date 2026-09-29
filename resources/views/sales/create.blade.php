@extends('layouts.app')

@section('title', 'POS Counter Billing')

@section('content')
<style>
    /* Remove native number spinners completely in POS */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }
    input[type=number] {
        -moz-appearance: textfield !important;
    }

    /* Cart Table Input Sizing */
    .cart-qty-box {
        min-width: 105px;
        max-width: 120px;
    }
    .cart-price-box {
        min-width: 115px;
        max-width: 135px;
    }
    .cart-discount-box {
        min-width: 95px;
        max-width: 115px;
    }
    .cart-qty-box .input-qty {
        font-weight: 700 !important;
        font-size: 0.95rem !important;
        padding-left: 3px !important;
        padding-right: 3px !important;
    }
    .cart-price-box .input-price,
    .cart-discount-box .input-item-discount {
        font-weight: 600 !important;
        font-size: 0.92rem !important;
        padding-left: 6px !important;
        padding-right: 6px !important;
    }
</style>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-cart3 text-primary me-2"></i>POS Counter Billing</h2>
        <p class="text-secondary mb-0">
            Active Store: <strong class="text-dark">{{ $currentShop->name }}</strong> ({{ $currentShop->code }})
        </p>
    </div>

    <div class="d-flex align-items-center gap-2">
        @if(auth()->user()->isSuperAdmin() && $shops->count() > 1)
            <form method="get" action="{{ route('pos') }}" class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fw-semibold text-secondary small text-nowrap">Shop:</label>
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" @selected($currentShop->id == $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
            </form>
        @endif
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-receipt me-1"></i>Past Invoices
        </a>
    </div>
</div>

<x-errors />

<form id="pos_form" method="post" action="{{ route('sales.store') }}">
    @csrf
    @if(auth()->user()->isSuperAdmin())
        <input type="hidden" name="shop_id" value="{{ $currentShop->id }}">
    @endif

    <div class="row g-3">
        {{-- Left Column: Search & Cart Items --}}
        <div class="col-lg-8">
            {{-- Fast Product Picker --}}
            <div class="card p-3 mb-3 shadow-sm border-primary-subtle">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <label class="form-label mb-0 fw-bold"><i class="bi bi-upc-scan me-1 text-primary"></i>Scan Barcode or Search Product:</label>
                </div>
                <div class="row g-2">
                    <div class="col-md-9">
                        <select id="product_search_picker" class="form-select searchable-select">
                            <option value="">-- Type product name, SKU or brand... --</option>
                            @foreach($products as $prod)
                                @php
                                    $activeBatch = $prod->batches?->first();
                                    $effectivePrice = ($activeBatch && $activeBatch->selling_price > 0)
                                        ? (float) $activeBatch->selling_price
                                        : (float) ($prod->selling_price ?? 0);
                                @endphp
                                <option value="{{ $prod->id }}"
                                    data-id="{{ $prod->id }}"
                                    data-name="{{ $prod->item_name }}"
                                    data-sku="{{ $prod->sku }}"
                                    data-stock="{{ (float) $prod->quantity }}"
                                    data-price="{{ $effectivePrice }}"
                                    data-batch="{{ $activeBatch?->batch_no ?? '' }}"
                                    data-brand="{{ $prod->brand?->name ?? '' }}"
                                    data-pack="{{ $prod->pack_label ?? '' }}">
                                    {{ $prod->item_name }} ({{ $prod->sku }}) &bull; Stock: {{ number_format($prod->quantity, 0) }} &bull; Rs. {{ number_format($effectivePrice, 2) }}@if($activeBatch && $activeBatch->batch_no) [{{ $activeBatch->batch_no }}]@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="btn_add_to_cart" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i>Add to Bill
                        </button>
                    </div>
                </div>
            </div>

            {{-- Cart Items Table --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <div class="fw-bold"><i class="bi bi-basket3 me-1"></i>Sale Basket (<span id="cart_count_badge">0</span> items)</div>
                    <button type="button" id="btn_clear_cart" class="btn btn-sm btn-outline-danger py-0" style="display: none;">
                        <i class="bi bi-trash3 me-1"></i>Clear All
                    </button>
                </div>
                <div class="table-responsive" style="min-height: 280px;">
                    <table class="table table-hover align-middle mb-0" id="cart_table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 3%;" class="text-center">#</th>
                                <th style="width: 32%;">Product</th>
                                <th style="width: 18%;" class="text-center">Quantity</th>
                                <th style="width: 15%;" class="text-end">Price (Rs.)</th>
                                <th style="width: 15%;" class="text-end">Discount (Rs.)</th>
                                <th style="width: 14%;" class="text-end">Total (Rs.)</th>
                                <th style="width: 3%;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="cart_items_tbody">
                            <tr id="cart_empty_row">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <strong>Your sale basket is empty</strong>
                                    <div class="small">Search and add products above to start billing.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right Column: Checkout & Summary --}}
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 85px;">
                <div class="card-header bg-light py-2">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-calculator me-1"></i>Bill & Checkout</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Total Line Items:</span>
                        <strong id="summary_total_items">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Gross Subtotal:</span>
                        <strong class="fs-6" id="summary_subtotal">Rs. 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Item Discounts:</span>
                        <strong id="summary_items_discount">- Rs. 0.00</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary mb-1">Bill Discount / Round-off (Rs.):</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="any" min="0" name="discount" id="input_discount" class="form-control text-end fw-semibold" value="0" {{ auth()->user()->hasPermission('pos.discount_bill') ? '' : 'readonly title="Requires bill discount permission"' }}>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="bg-primary-subtle p-3 rounded-3 mb-3 border border-primary-subtle">
                        <div class="text-secondary small fw-semibold">Net Payable:</div>
                        <div class="text-primary fw-bold display-6" id="summary_net_total" style="font-size: 2rem;">Rs. 0.00</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Payment Method <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="cash" checked>
                            <label class="btn btn-outline-secondary btn-sm" for="pay_cash"><i class="bi bi-cash me-1"></i>Cash</label>

                            <input type="radio" class="btn-check" name="payment_method" id="pay_card" value="card">
                            <label class="btn btn-outline-secondary btn-sm" for="pay_card"><i class="bi bi-credit-card me-1"></i>Card</label>

                            <input type="radio" class="btn-check" name="payment_method" id="pay_bank" value="bank_transfer">
                            <label class="btn btn-outline-secondary btn-sm" for="pay_bank"><i class="bi bi-bank me-1"></i>Bank</label>

                            <input type="radio" class="btn-check" name="payment_method" id="pay_credit" value="credit">
                            <label class="btn btn-outline-secondary btn-sm" for="pay_credit"><i class="bi bi-journal-text me-1"></i>Credit</label>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary mb-1">Cash Received:</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" step="any" min="0" name="paid_amount" id="input_paid_amount" class="form-control text-end fw-bold" placeholder="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary mb-1">Change Return:</label>
                            <div class="form-control form-control-sm text-end fw-bold bg-light text-success" id="display_change">
                                Rs. 0.00
                            </div>
                        </div>
                    </div>

                    {{-- Customer details (Optional) --}}
                    <div class="accordion accordion-flush mb-3" id="customerAccordion">
                        <div class="accordion-item border rounded-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2 px-3 small text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#customerFields">
                                    <i class="bi bi-person me-2"></i>Customer Info / Notes (Optional)
                                </button>
                            </h2>
                            <div id="customerFields" class="accordion-collapse collapse p-2">
                                <div class="mb-2">
                                    <input type="text" name="customer_name" class="form-control form-control-sm" placeholder="Customer Name">
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="customer_phone" class="form-control form-control-sm" placeholder="Phone Number">
                                </div>
                                <div>
                                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Sale Notes..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="btn_submit_sale" class="btn btn-success btn-lg w-100 py-3 fw-bold shadow" disabled>
                        <i class="bi bi-lightning-charge-fill me-1"></i>Complete Sale & Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const productPicker = document.getElementById('product_search_picker');
    const btnAddToCart = document.getElementById('btn_add_to_cart');
    const tbody = document.getElementById('cart_items_tbody');
    const emptyRow = document.getElementById('cart_empty_row');
    const cartCountBadge = document.getElementById('cart_count_badge');
    const btnClearCart = document.getElementById('btn_clear_cart');
    const summaryTotalItems = document.getElementById('summary_total_items');
    const summarySubtotal = document.getElementById('summary_subtotal');
    const inputDiscount = document.getElementById('input_discount');
    const summaryNetTotal = document.getElementById('summary_net_total');
    const inputPaidAmount = document.getElementById('input_paid_amount');
    const displayChange = document.getElementById('display_change');
    const btnSubmitSale = document.getElementById('btn_submit_sale');

    let cart = [];

    function addSelectedProduct() {
        const selectedOption = productPicker.options[productPicker.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const id = parseInt(selectedOption.getAttribute('data-id'));
        const name = selectedOption.getAttribute('data-name');
        const sku = selectedOption.getAttribute('data-sku');
        const stock = parseFloat(selectedOption.getAttribute('data-stock')) || 0;
        const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        const batch = selectedOption.getAttribute('data-batch') || '';
        const brand = selectedOption.getAttribute('data-brand') || '';
        const pack = selectedOption.getAttribute('data-pack') || '';

        if (stock <= 0) {
            alert(`'${name}' is currently out of stock (Stock: 0).`);
            return;
        }

        // Check if already in cart
        const existing = cart.find(item => item.id === id);
        if (existing) {
            if (existing.quantity + 1 > stock) {
                alert(`Cannot add more. Only ${stock} units in stock for '${name}'.`);
                return;
            }
            existing.quantity += 1;
        } else {
            cart.push({
                id: id,
                name: name,
                sku: sku,
                batch: batch,
                stock: stock,
                price: price,
                discount: 0,
                quantity: 1,
                brand: brand,
                pack: pack
            });
        }

        // Reset selector
        if (productPicker.tomselect) {
            productPicker.tomselect.clear();
        } else {
            productPicker.value = '';
        }

        renderCart();
    }

    btnAddToCart.addEventListener('click', addSelectedProduct);

    // Auto add when selecting in TomSelect
    if (productPicker.tomselect) {
        productPicker.tomselect.on('change', function(val) {
            if (val) {
                addSelectedProduct();
            }
        });
    }

    function renderCart() {
        tbody.innerHTML = '';

        if (cart.length === 0) {
            tbody.appendChild(emptyRow);
            cartCountBadge.textContent = '0';
            btnClearCart.style.display = 'none';
            btnSubmitSale.disabled = true;
            updateTotals();
            return;
        }

        btnClearCart.style.display = 'inline-block';
        btnSubmitSale.disabled = false;
        cartCountBadge.textContent = cart.length;

        cart.forEach((item, index) => {
            const tr = document.createElement('tr');
            const grossLine = item.quantity * item.price;
            const itemDisc = Math.max(0, parseFloat(item.discount) || 0);
            const lineTotal = Math.max(0, grossLine - itemDisc).toFixed(2);

            tr.innerHTML = `
                <td class="text-secondary small text-center">${index + 1}</td>
                <td>
                    <div class="fw-semibold text-dark text-break">${item.name}</div>
                    <div class="small text-secondary">
                        <code>${item.sku}</code>
                        ${item.brand ? '&bull; ' + item.brand : ''}
                        ${item.pack ? '&bull; ' + item.pack : ''}
                        ${item.batch ? '&bull; <span class="badge text-bg-info text-dark border"><i class="bi bi-box-seam me-1"></i>' + item.batch + '</span>' : ''}
                        &bull; <span class="badge text-bg-light border">Stock: ${item.stock}</span>
                    </div>
                    <input type="hidden" name="items[${index}][inventory_item_id]" value="${item.id}">
                </td>
                <td class="text-center">
                    <div class="input-group input-group-sm flex-nowrap cart-qty-box mx-auto">
                        <button type="button" class="btn btn-outline-secondary px-2 btn-qty-minus" data-index="${index}">-</button>
                        <input type="number" step="any" min="0.01" max="${item.stock}" name="items[${index}][quantity]"
                               class="form-control text-center input-qty" data-index="${index}" value="${item.quantity}">
                        <button type="button" class="btn btn-outline-secondary px-2 btn-qty-plus" data-index="${index}">+</button>
                    </div>
                </td>
                <td class="text-end text-nowrap fw-semibold text-secondary">
                    Rs. ${Number(item.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    <input type="hidden" name="items[${index}][unit_price]" value="${item.price.toFixed(2)}">
                </td>
                <td class="text-end">
                    <div class="input-group input-group-sm flex-nowrap cart-discount-box ms-auto">
                        <span class="input-group-text px-2 py-0 text-muted small bg-light">Rs.</span>
                        <input type="number" step="any" min="0" name="items[${index}][discount]"
                               class="form-control text-end input-item-discount text-danger" data-index="${index}" value="${itemDisc > 0 ? itemDisc : ''}" placeholder="0" {{ auth()->user()->hasPermission('pos.discount_item') ? '' : 'readonly title="Requires item discount permission"' }}>
                    </div>
                </td>
                <td class="text-end fw-bold text-dark fs-6 text-nowrap">
                    Rs. <span class="line-total">${Number(lineTotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-item" data-index="${index}" title="Remove item">
                        <i class="bi bi-x-circle fs-5"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });

        attachRowEvents();
        updateTotals();
    }

    function attachRowEvents() {
        document.querySelectorAll('.btn-qty-plus').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                if (cart[idx].quantity + 1 > cart[idx].stock) {
                    alert(`Maximum stock limit reached (${cart[idx].stock} units).`);
                    return;
                }
                cart[idx].quantity += 1;
                renderCart();
            });
        });

        document.querySelectorAll('.btn-qty-minus').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                if (cart[idx].quantity > 1) {
                    cart[idx].quantity -= 1;
                    renderCart();
                } else {
                    cart.splice(idx, 1);
                    renderCart();
                }
            });
        });

        document.querySelectorAll('.input-qty').forEach(input => {
            input.addEventListener('change', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                let val = parseFloat(this.value) || 1;
                if (val <= 0) val = 1;
                if (val > cart[idx].stock) {
                    alert(`Only ${cart[idx].stock} units available.`);
                    val = cart[idx].stock;
                }
                cart[idx].quantity = val;
                renderCart();
            });
        });

        document.querySelectorAll('.input-price').forEach(input => {
            input.addEventListener('change', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                let val = parseFloat(this.value) || 0;
                if (val < 0) val = 0;
                cart[idx].price = val;
                renderCart();
            });
        });

        document.querySelectorAll('.input-item-discount').forEach(input => {
            input.addEventListener('input', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                let val = parseFloat(this.value) || 0;
                if (val < 0) val = 0;
                cart[idx].discount = val;
                const gross = cart[idx].quantity * cart[idx].price;
                const net = Math.max(0, gross - val);
                const row = this.closest('tr');
                if (row) {
                    const lineTotalSpan = row.querySelector('.line-total');
                    if (lineTotalSpan) {
                        lineTotalSpan.textContent = Number(net.toFixed(2)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
                updateTotals();
            });
        });

        document.querySelectorAll('.btn-remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                cart.splice(idx, 1);
                renderCart();
            });
        });
    }

    function updateTotals() {
        let grossSubtotal = 0;
        let itemsDiscountTotal = 0;
        let totalItems = 0;

        cart.forEach(item => {
            const gross = (item.quantity * item.price);
            const disc = Math.max(0, parseFloat(item.discount) || 0);
            grossSubtotal += gross;
            itemsDiscountTotal += disc;
            totalItems += item.quantity;
        });

        const billDiscount = Math.max(0, parseFloat(inputDiscount.value) || 0);
        const netTotal = Math.max(0, grossSubtotal - itemsDiscountTotal - billDiscount);

        summaryTotalItems.textContent = totalItems.toFixed(totalItems % 1 === 0 ? 0 : 2);
        summarySubtotal.textContent = 'Rs. ' + grossSubtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const summaryItemsDisc = document.getElementById('summary_items_discount');
        if (summaryItemsDisc) {
            summaryItemsDisc.textContent = '- Rs. ' + itemsDiscountTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        summaryNetTotal.textContent = 'Rs. ' + netTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // Calculate change
        const paid = parseFloat(inputPaidAmount.value) || 0;
        const change = Math.max(0, paid - netTotal);

        if (paid > 0) {
            displayChange.textContent = 'Rs. ' + change.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (paid < netTotal) {
                displayChange.className = 'form-control form-control-sm text-end fw-bold bg-light text-danger';
                displayChange.textContent = 'Due: Rs. ' + (netTotal - paid).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            } else {
                displayChange.className = 'form-control form-control-sm text-end fw-bold bg-light text-success';
            }
        } else {
            displayChange.textContent = 'Rs. 0.00';
            displayChange.className = 'form-control form-control-sm text-end fw-bold bg-light text-muted';
        }
    }

    inputDiscount.addEventListener('input', updateTotals);
    inputPaidAmount.addEventListener('input', updateTotals);

    btnClearCart.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear all items from the sale basket?')) {
            cart = [];
            renderCart();
        }
    });
});
</script>
@endpush
