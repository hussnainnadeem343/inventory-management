@extends('layouts.app')
@section('title', 'Purchase Invoice')
@section('content')
<form method="post" action="{{ route('purchases.invoices.store') }}" id="invoiceForm">
    @csrf

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Purchase / Purchase Invoice</h4>
            <small class="text-secondary">Stage 5: Record vendor commercial bill, inventory expenses & agent commissions</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.invoices.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loadGrnModal">
                <i class="bi bi-upload me-1"></i> Load from GRN
            </button>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Save Purchase Invoice
            </button>
        </div>
    </div>

    @if($selectedGrn)
        <div class="alert alert-info py-2 mb-3 d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-box-seam me-1"></i> Loaded from Goods Received Note: <strong>{{ $selectedGrn->grn_number }}</strong> (Date: {{ $selectedGrn->received_date->format('d M, Y') }}, Total Inward: Rs. {{ number_format($selectedGrn->total_amount, 2) }})
            </span>
            <a href="{{ route('purchases.invoices.create') }}" class="btn btn-sm btn-outline-primary">Clear GRN</a>
        </div>
        <input type="hidden" name="goods_received_note_id" value="{{ $selectedGrn->id }}">
    @endif

    {{-- Header Section (Matching Screenshots 1, 2, 3) --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Company</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="{{ auth()->user()->shop ? auth()->user()->shop->name : 'Friends Particle' }}" readonly>
                </div>

                <div class="col-md-5">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold small mb-0">Vendor <span class="text-danger">*</span></label>
                        <span class="small text-muted" id="vendorBalanceDisplay">
                            Balance: Rs. <strong class="text-danger">0.00</strong> | Credit Limit: <strong>0.00</strong>
                        </span>
                    </div>
                    <select class="form-select form-select-sm searchable-select" name="supplier_id" id="vendorSelect" required>
                        <option value="">Select Vendor</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" 
                                    data-balance="{{ $sup->current_balance }}" 
                                    @selected(old('supplier_id', $selectedGrn?->supplier_id) == $sup->id)>
                                {{ $sup->name }} @if($sup->company_name) - {{ $sup->company_name }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Select Station / Branch</label>
                    <input type="text" class="form-control form-control-sm" name="station" value="{{ old('station', auth()->user()->shop ? auth()->user()->shop->name : 'Main Store') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Manual Invoice Number</label>
                    <input type="text" class="form-control form-control-sm" name="manual_invoice_number" value="{{ old('manual_invoice_number') }}" placeholder="e.g. MINV-2026-001">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Document Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Type <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" name="invoice_type" required>
                        <option value="credit" @selected(old('invoice_type') === 'credit')>Credit</option>
                        <option value="cash" @selected(old('invoice_type') === 'cash')>Cash</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Currency</label>
                    <select class="form-select form-select-sm" name="currency">
                        <option value="PKR" selected>PKR</option>
                        <option value="USD">USD</option>
                        <option value="AED">AED</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Due Date</label>
                    <input type="date" class="form-control form-control-sm" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Supplier Invoice No.</label>
                    <input type="text" class="form-control form-control-sm" name="supplier_invoice_no" value="{{ old('supplier_invoice_no', $selectedGrn?->supplier_invoice_no) }}" placeholder="Vendor's original printed bill #">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Supplier Invoice Date</label>
                    <input type="date" class="form-control form-control-sm" name="supplier_invoice_date" value="{{ old('supplier_invoice_date', date('Y-m-d')) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Payment Terms</label>
                    <select class="form-select form-select-sm" name="payment_terms">
                        <option value="Net 30">Net 30 Days</option>
                        <option value="Net 15">Net 15 Days</option>
                        <option value="COD">Cash on Delivery (COD)</option>
                        <option value="Advance">Advance Payment</option>
                        <option value="Net 60">Net 60 Days</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs (Matching Screenshots 1, 2, 3) --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-header bg-white border-bottom-0 pb-0 pt-2 px-3">
            <ul class="nav nav-tabs border-bottom" id="piTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-primary px-3 py-2" id="pi-prod-tab" data-bs-toggle="tab" data-bs-target="#tabPiProducts" type="button" role="tab">
                        Product Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-secondary px-3 py-2" id="pi-invexp-tab" data-bs-toggle="tab" data-bs-target="#tabPiInvExpenses" type="button" role="tab">
                        Inventory Expenses
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-secondary px-3 py-2" id="pi-otherexp-tab" data-bs-toggle="tab" data-bs-target="#tabPiOtherExpenses" type="button" role="tab">
                        Other Expenses
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-secondary px-3 py-2" id="pi-comm-tab" data-bs-toggle="tab" data-bs-target="#tabPiCommission" type="button" role="tab">
                        Commission
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-secondary px-3 py-2" id="pi-terms-tab" data-bs-toggle="tab" data-bs-target="#tabPiTerms" type="button" role="tab">
                        Terms
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="piTabContent">
                {{-- TAB 1: PRODUCT DETAILS --}}
                <div class="tab-pane fade show active" id="tabPiProducts" role="tabpanel">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <span class="small text-secondary fw-semibold text-uppercase">Billed Product Line Items</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPiProdRow">
                            <i class="bi bi-plus-circle me-1"></i> Add Item
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="piProductsTable">
                            <thead class="text-white small" style="background-color: #1e3a8a !important;">
                                <tr>
                                    <th style="width: 4%;" class="text-center">Sr</th>
                                    <th style="width: 32%;">Product <span class="text-danger">*</span></th>
                                    <th style="width: 8%;" class="text-center">UOM</th>
                                    <th style="width: 12%;" class="text-end">Quantity <span class="text-danger">*</span></th>
                                    <th style="width: 12%;" class="text-end">Rate / Cost (Rs.) <span class="text-danger">*</span></th>
                                    <th style="width: 10%;" class="text-end">Discount</th>
                                    <th style="width: 8%;" class="text-end">Tax %</th>
                                    <th style="width: 11%;" class="text-end">Net Subtotal (Rs.)</th>
                                    <th style="width: 3%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="piProductsBody">
                                @if($selectedGrn && $selectedGrn->items->isNotEmpty())
                                    @foreach($selectedGrn->items as $idx => $gItem)
                                        <tr class="pi-prod-row">
                                            <td class="text-center pi-sr">{{ $idx + 1 }}</td>
                                            <td>
                                                <select class="form-select form-select-sm pi-prod-select" name="items[{{ $idx }}][inventory_item_id]" required>
                                                    <option value="{{ $gItem->inventory_item_id }}" data-uom="{{ $gItem->inventoryItem->unit }}" selected>
                                                        {{ $gItem->inventoryItem->item_name }} ({{ $gItem->inventoryItem->sku }})
                                                    </option>
                                                </select>
                                                <input type="hidden" name="items[{{ $idx }}][goods_received_item_id]" value="{{ $gItem->id }}">
                                            </td>
                                            <td class="text-center pi-uom fw-bold">{{ $gItem->inventoryItem->unit }}</td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end pi-qty" name="items[{{ $idx }}][quantity]" value="{{ $gItem->quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end pi-rate" name="items[{{ $idx }}][unit_cost]" value="{{ $gItem->unit_cost }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end pi-disc" name="items[{{ $idx }}][discount_amount]" value="0">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end pi-tax-pct" name="items[{{ $idx }}][tax_percent]" value="0">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm text-end bg-light fw-bold pi-line-subtotal" value="0.00" readonly>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-pi-prod">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="pi-prod-row">
                                        <td class="text-center pi-sr">1</td>
                                        <td>
                                            <select class="form-select form-select-sm pi-prod-select" name="items[0][inventory_item_id]" required>
                                                <option value="">Select Product</option>
                                                @foreach($products as $prod)
                                                    <option value="{{ $prod->id }}" data-uom="{{ $prod->unit }}" data-cost="{{ $prod->purchase_price }}">
                                                        {{ $prod->item_name }} ({{ $prod->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center pi-uom fw-bold">PCS</td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end pi-qty" name="items[0][quantity]" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end pi-rate" name="items[0][unit_cost]" value="0" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end pi-disc" name="items[0][discount_amount]" value="0">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end pi-tax-pct" name="items[0][tax_percent]" value="0">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm text-end bg-light fw-bold pi-line-subtotal" value="0.00" readonly>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-pi-prod" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="7" class="text-end">Products Subtotal:</td>
                                    <td class="text-end" id="piProductsSubtotal">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- TAB 2: INVENTORY EXPENSES (Screenshots 1 & 2) --}}
                <div class="tab-pane fade" id="tabPiInvExpenses" role="tabpanel">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <span class="small text-secondary fw-semibold text-uppercase">Inventory / Freight / Landed Expenses (Capitalized in stock value)</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddInvExp">
                            <i class="bi bi-plus-circle me-1"></i> Add Inventory Expense
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="invExpTable">
                            <thead class="text-white small" style="background-color: #1e3a8a !important;">
                                <tr>
                                    <th style="width: 25%;">Account</th>
                                    <th style="width: 20%;">Comments</th>
                                    <th style="width: 15%;">Type</th>
                                    <th style="width: 10%;" class="text-end">Qty</th>
                                    <th style="width: 10%;" class="text-end">Rate</th>
                                    <th style="width: 10%;" class="text-end">Debit</th>
                                    <th style="width: 7%;" class="text-end">Credit</th>
                                    <th style="width: 3%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="invExpBody">
                                <tr class="invexp-row">
                                    <td>
                                        <select class="form-select form-select-sm" name="expenses[0][account_id]">
                                            <option value="">Select Expense Account</option>
                                            @foreach($expenseAccounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="expenses[0][category]" value="inventory">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="expenses[0][comments]" placeholder="Comments">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" name="expenses[0][expense_type]">
                                            <option value="Freight">Freight Inward</option>
                                            <option value="Carriage">Carriage</option>
                                            <option value="Unloading">Unloading / Mazdoori</option>
                                            <option value="Customs">Customs Duty</option>
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end ie-qty" name="expenses[0][quantity]" value="1"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end ie-rate" name="expenses[0][rate]" value="0"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end ie-debit" name="expenses[0][debit]" value="0"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end ie-credit" name="expenses[0][credit]" value="0"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-invexp"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light fw-bold small">
                                <tr>
                                    <td colspan="5" class="text-end">Total Debit:</td>
                                    <td class="text-end" id="ieTotalDebit">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end">Total Credit:</td>
                                    <td class="text-end" id="ieTotalCredit">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end">Net Total Inventory Expenses:</td>
                                    <td class="text-end text-primary" id="ieNetTotal">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- TAB 3: OTHER EXPENSES --}}
                <div class="tab-pane fade" id="tabPiOtherExpenses" role="tabpanel">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <span class="small text-secondary fw-semibold text-uppercase">Other General Vendor / Procurement Expenses</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddOtherExp">
                            <i class="bi bi-plus-circle me-1"></i> Add Other Expense
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="otherExpTable">
                            <thead class="text-white small" style="background-color: #1e3a8a !important;">
                                <tr>
                                    <th style="width: 25%;">Account</th>
                                    <th style="width: 20%;">Comments</th>
                                    <th style="width: 15%;">Type</th>
                                    <th style="width: 10%;" class="text-end">Qty</th>
                                    <th style="width: 10%;" class="text-end">Rate</th>
                                    <th style="width: 10%;" class="text-end">Debit</th>
                                    <th style="width: 7%;" class="text-end">Credit</th>
                                    <th style="width: 3%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="otherExpBody">
                                <tr class="otherexp-row">
                                    <td>
                                        <select class="form-select form-select-sm" name="expenses[100][account_id]">
                                            <option value="">Select Account</option>
                                            @foreach($expenseAccounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="expenses[100][category]" value="other">
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="expenses[100][comments]" placeholder="Comments"></td>
                                    <td>
                                        <select class="form-select form-select-sm" name="expenses[100][expense_type]">
                                            <option value="Handling">Handling Charges</option>
                                            <option value="Documentation">Documentation Fee</option>
                                            <option value="Other">Other Administrative</option>
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end oe-qty" name="expenses[100][quantity]" value="1"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end oe-rate" name="expenses[100][rate]" value="0"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end oe-debit" name="expenses[100][debit]" value="0"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end oe-credit" name="expenses[100][credit]" value="0"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-otherexp"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light fw-bold small">
                                <tr>
                                    <td colspan="5" class="text-end">Net Total Other Expenses:</td>
                                    <td class="text-end text-primary" id="oeNetTotal">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- TAB 4: COMMISSION (Screenshot 3) --}}
                <div class="tab-pane fade" id="tabPiCommission" role="tabpanel">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <span class="small text-secondary fw-semibold text-uppercase">Agent / Broker Commission Management</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddComm">
                            <i class="bi bi-plus-circle me-1"></i> Add Commission Line
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="commTable">
                            <thead class="text-white small" style="background-color: #1e3a8a !important;">
                                <tr>
                                    <th style="width: 4%;" class="text-center">Sr</th>
                                    <th style="width: 20%;">Agent <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Commission Type</th>
                                    <th style="width: 10%;">Type</th>
                                    <th style="width: 12%;">Deduction Type</th>
                                    <th style="width: 12%;" class="text-end">Invoice Net Total</th>
                                    <th style="width: 8%;" class="text-end">Rate (%)</th>
                                    <th style="width: 10%;" class="text-end">Amount</th>
                                    <th style="width: 6%;">Remarks</th>
                                    <th style="width: 3%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="commBody">
                                <tr class="comm-row">
                                    <td class="text-center comm-sr">1</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="commissions[0][agent_name]" placeholder="Agent Name">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" name="commissions[0][commission_type]">
                                            <option value="Buying Agent">Buying Agent</option>
                                            <option value="Local Broker">Local Broker</option>
                                            <option value="Transporter Agent">Transporter Agent</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm comm-type" name="commissions[0][calculation_type]">
                                            <option value="percentage">Percentage</option>
                                            <option value="fixed">Fixed Amount</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm comm-deduction" name="commissions[0][deduction_type]">
                                            <option value="deductible">Deductible</option>
                                            <option value="payable">Payable Extra</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm text-end bg-light comm-inv-total" value="0.00" readonly>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end comm-rate" name="commissions[0][rate]" value="0">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end comm-amount" name="commissions[0][amount]" value="0">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="commissions[0][remarks]" placeholder="Remarks">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-comm"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light fw-bold small">
                                <tr>
                                    <td colspan="7" class="text-end">Invoice Net Total:</td>
                                    <td class="text-end" id="commInvoiceNetTotal">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="text-end">Deductible Commission:</td>
                                    <td class="text-end text-danger" id="commDeductibleTotal">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="text-end">Net Total:</td>
                                    <td class="text-end text-primary" id="commNetTotal">0.00</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- TAB 5: TERMS --}}
                <div class="tab-pane fade" id="tabPiTerms" role="tabpanel">
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Terms & Conditions</label>
                                <textarea class="form-control" name="terms" rows="4" placeholder="Enter terms & conditions for this vendor purchase invoice...">{{ old('terms', "1. Payment within 30 days from document date.\n2. Inward subject to physical QA check.\n3. Defective items eligible for debit note.") }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Internal Accounts Notes</label>
                                <textarea class="form-control" name="notes" rows="4" placeholder="Auditor notes, bank transfer reference...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Final Financial Summary Banner --}}
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body p-3">
            <div class="row align-items-center justify-content-between">
                <div class="col-md-7">
                    <div class="d-flex gap-4 small text-secondary">
                        <div>Products: <strong class="text-dark" id="sumProducts">0.00</strong></div>
                        <div>Landed Expenses: <strong class="text-dark" id="sumLanded">0.00</strong></div>
                        <div>Other Expenses: <strong class="text-dark" id="sumOther">0.00</strong></div>
                    </div>
                </div>
                <div class="col-md-5 text-end">
                    <span class="fs-6 me-2 text-secondary fw-semibold">Grand Total Payable:</span>
                    <span class="fs-4 fw-bold text-primary" id="finalGrandTotal">Rs. 0.00</span>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Load GRN Modal --}}
<div class="modal fade" id="loadGrnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i> Select Goods Received Note (GRN) to Bill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">GRN #</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th class="text-end">Inward Amount</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingGrns as $pgrn)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $pgrn->grn_number }}</td>
                                    <td>{{ $pgrn->received_date->format('d M, Y') }}</td>
                                    <td>{{ $pgrn->supplier->name }}</td>
                                    <td class="text-end fw-semibold">Rs. {{ number_format($pgrn->total_amount, 2) }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('purchases.invoices.create', ['goods_received_note_id' => $pgrn->id]) }}" class="btn btn-sm btn-primary">
                                            Load into Invoice
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No unbilled Goods Received Notes found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let piProdIndex = {{ ($selectedGrn && $selectedGrn->items->isNotEmpty()) ? $selectedGrn->items->count() : 1 }};
    let invExpIndex = 1;
    let otherExpIndex = 101;
    let commIndex = 1;

    const vendorSelect = document.getElementById('vendorSelect');
    const balanceDisplay = document.getElementById('vendorBalanceDisplay');

    if (vendorSelect) {
        vendorSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.balance !== undefined) {
                const bal = parseFloat(opt.dataset.balance) || 0;
                balanceDisplay.innerHTML = `Balance: Rs. <strong class="${bal > 0 ? 'text-danger' : 'text-success'}">${bal.toFixed(2)}</strong> | Credit Limit: <strong>0.00</strong>`;
            }
        });
        vendorSelect.dispatchEvent(new Event('change'));
    }

    function calculatePiTotals() {
        let prodSub = 0;
        document.querySelectorAll('#piProductsBody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.pi-qty')?.value) || 0;
            const rate = parseFloat(row.querySelector('.pi-rate')?.value) || 0;
            const disc = parseFloat(row.querySelector('.pi-disc')?.value) || 0;
            const taxPct = parseFloat(row.querySelector('.pi-tax-pct')?.value) || 0;

            const base = (qty * rate) - disc;
            const lineTotal = Math.max(0, base + (base * (taxPct / 100)));
            const subInput = row.querySelector('.pi-line-subtotal');
            if (subInput) subInput.value = lineTotal.toFixed(2);
            prodSub += lineTotal;
        });

        document.getElementById('piProductsSubtotal').textContent = 'Rs. ' + prodSub.toFixed(2);
        document.getElementById('sumProducts').textContent = 'Rs. ' + prodSub.toFixed(2);

        // Inventory Expenses
        let ieDebit = 0;
        let ieCredit = 0;
        document.querySelectorAll('#invExpBody tr').forEach(row => {
            const debit = parseFloat(row.querySelector('.ie-debit')?.value) || 0;
            const credit = parseFloat(row.querySelector('.ie-credit')?.value) || 0;
            const rate = parseFloat(row.querySelector('.ie-rate')?.value) || 0;
            const qty = parseFloat(row.querySelector('.ie-qty')?.value) || 1;
            ieDebit += debit > 0 ? debit : (rate * qty);
            ieCredit += credit;
        });
        const ieNet = ieDebit - ieCredit;
        document.getElementById('ieTotalDebit').textContent = 'Rs. ' + ieDebit.toFixed(2);
        document.getElementById('ieTotalCredit').textContent = 'Rs. ' + ieCredit.toFixed(2);
        document.getElementById('ieNetTotal').textContent = 'Rs. ' + ieNet.toFixed(2);
        document.getElementById('sumLanded').textContent = 'Rs. ' + ieNet.toFixed(2);

        // Other Expenses
        let oeDebit = 0;
        let oeCredit = 0;
        document.querySelectorAll('#otherExpBody tr').forEach(row => {
            const debit = parseFloat(row.querySelector('.oe-debit')?.value) || 0;
            const credit = parseFloat(row.querySelector('.oe-credit')?.value) || 0;
            const rate = parseFloat(row.querySelector('.oe-rate')?.value) || 0;
            const qty = parseFloat(row.querySelector('.oe-qty')?.value) || 1;
            oeDebit += debit > 0 ? debit : (rate * qty);
            oeCredit += credit;
        });
        const oeNet = oeDebit - oeCredit;
        document.getElementById('oeNetTotal').textContent = 'Rs. ' + oeNet.toFixed(2);
        document.getElementById('sumOther').textContent = 'Rs. ' + oeNet.toFixed(2);

        const grand = prodSub + ieNet + oeNet;
        document.getElementById('finalGrandTotal').textContent = 'Rs. ' + grand.toFixed(2);

        // Commission Tab updates
        let deductibleComm = 0;
        document.querySelectorAll('#commBody tr').forEach(row => {
            const invTotalInput = row.querySelector('.comm-inv-total');
            if (invTotalInput) invTotalInput.value = grand.toFixed(2);

            const type = row.querySelector('.comm-type')?.value;
            const dedType = row.querySelector('.comm-deduction')?.value;
            const rate = parseFloat(row.querySelector('.comm-rate')?.value) || 0;
            const amtInput = row.querySelector('.comm-amount');

            let commAmt = 0;
            if (type === 'percentage') {
                commAmt = grand * (rate / 100);
                if (amtInput) amtInput.value = commAmt.toFixed(2);
            } else {
                commAmt = parseFloat(amtInput?.value) || 0;
            }

            if (dedType === 'deductible') {
                deductibleComm += commAmt;
            }
        });

        document.getElementById('commInvoiceNetTotal').textContent = 'Rs. ' + grand.toFixed(2);
        document.getElementById('commDeductibleTotal').textContent = 'Rs. ' + deductibleComm.toFixed(2);
        document.getElementById('commNetTotal').textContent = 'Rs. ' + (grand - deductibleComm).toFixed(2);
    }

    function attachPiProdListeners(row) {
        const select = row.querySelector('.pi-prod-select');
        const uom = row.querySelector('.pi-uom');
        const rate = row.querySelector('.pi-rate');
        const qty = row.querySelector('.pi-qty');
        const disc = row.querySelector('.pi-disc');
        const tax = row.querySelector('.pi-tax-pct');
        const btnRemove = row.querySelector('.btn-remove-pi-prod');

        if (select) {
            select.addEventListener('change', function () {
                const opt = select.options[select.selectedIndex];
                if (opt && opt.dataset.uom) {
                    uom.textContent = opt.dataset.uom;
                    if (opt.dataset.cost && rate) {
                        rate.value = parseFloat(opt.dataset.cost).toFixed(2);
                    }
                }
                calculatePiTotals();
            });
        }

        [qty, rate, disc, tax].forEach(i => {
            if (i) i.addEventListener('input', calculatePiTotals);
        });

        if (btnRemove) {
            btnRemove.addEventListener('click', function () {
                if (document.querySelectorAll('#piProductsBody tr').length > 1) {
                    row.remove();
                    updatePiSr();
                    calculatePiTotals();
                }
            });
        }
    }

    function updatePiSr() {
        document.querySelectorAll('#piProductsBody tr').forEach((r, idx) => {
            r.querySelector('.pi-sr').textContent = idx + 1;
            r.querySelector('.btn-remove-pi-prod').disabled = document.querySelectorAll('#piProductsBody tr').length === 1;
        });
    }

    document.querySelectorAll('#piProductsBody tr').forEach(attachPiProdListeners);

    // Add Product Line
    document.getElementById('btnAddPiProdRow').addEventListener('click', function () {
        const first = document.querySelector('#piProductsBody tr');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(i => {
            if (i.classList.contains('pi-qty')) i.value = '1';
            else if (i.classList.contains('pi-rate') || i.classList.contains('pi-disc') || i.classList.contains('pi-tax-pct')) i.value = '0';
            else i.value = '';
        });
        clone.querySelector('select').selectedIndex = 0;

        clone.querySelector('.pi-prod-select').name = `items[${piProdIndex}][inventory_item_id]`;
        clone.querySelector('.pi-qty').name = `items[${piProdIndex}][quantity]`;
        clone.querySelector('.pi-rate').name = `items[${piProdIndex}][unit_cost]`;
        clone.querySelector('.pi-disc').name = `items[${piProdIndex}][discount_amount]`;
        clone.querySelector('.pi-tax-pct').name = `items[${piProdIndex}][tax_percent]`;

        attachPiProdListeners(clone);
        document.getElementById('piProductsBody').appendChild(clone);
        piProdIndex++;
        updatePiSr();
        calculatePiTotals();
    });

    // Expenses listeners
    function attachExpListeners(tableId, rowClass) {
        document.querySelectorAll(`#${tableId} .${rowClass}`).forEach(row => {
            row.querySelectorAll('input').forEach(i => i.addEventListener('input', calculatePiTotals));
            const btn = row.querySelector('button');
            if (btn) {
                btn.addEventListener('click', function () {
                    if (document.querySelectorAll(`#${tableId} .${rowClass}`).length > 1) {
                        row.remove();
                        calculatePiTotals();
                    }
                });
            }
        });
    }

    attachExpListeners('invExpTable', 'invexp-row');
    attachExpListeners('otherExpTable', 'otherexp-row');

    // Add Inv Exp
    document.getElementById('btnAddInvExp').addEventListener('click', function () {
        const first = document.querySelector('#invExpBody tr');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(i => i.value = i.classList.contains('ie-qty') ? '1' : '0');
        clone.querySelector('input[type="text"]').value = '';
        clone.querySelector('select').selectedIndex = 0;

        clone.querySelector('select[name*="[account_id]"]').name = `expenses[${invExpIndex}][account_id]`;
        clone.querySelector('input[name*="[category]"]').name = `expenses[${invExpIndex}][category]`;
        clone.querySelector('input[name*="[comments]"]').name = `expenses[${invExpIndex}][comments]`;
        clone.querySelector('select[name*="[expense_type]"]').name = `expenses[${invExpIndex}][expense_type]`;
        clone.querySelector('.ie-qty').name = `expenses[${invExpIndex}][quantity]`;
        clone.querySelector('.ie-rate').name = `expenses[${invExpIndex}][rate]`;
        clone.querySelector('.ie-debit').name = `expenses[${invExpIndex}][debit]`;
        clone.querySelector('.ie-credit').name = `expenses[${invExpIndex}][credit]`;

        document.getElementById('invExpBody').appendChild(clone);
        invExpIndex++;
        attachExpListeners('invExpTable', 'invexp-row');
        calculatePiTotals();
    });

    // Add Other Exp
    document.getElementById('btnAddOtherExp').addEventListener('click', function () {
        const first = document.querySelector('#otherExpBody tr');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(i => i.value = i.classList.contains('oe-qty') ? '1' : '0');
        clone.querySelector('input[type="text"]').value = '';
        clone.querySelector('select').selectedIndex = 0;

        clone.querySelector('select[name*="[account_id]"]').name = `expenses[${otherExpIndex}][account_id]`;
        clone.querySelector('input[name*="[category]"]').name = `expenses[${otherExpIndex}][category]`;
        clone.querySelector('input[name*="[comments]"]').name = `expenses[${otherExpIndex}][comments]`;
        clone.querySelector('select[name*="[expense_type]"]').name = `expenses[${otherExpIndex}][expense_type]`;
        clone.querySelector('.oe-qty').name = `expenses[${otherExpIndex}][quantity]`;
        clone.querySelector('.oe-rate').name = `expenses[${otherExpIndex}][rate]`;
        clone.querySelector('.oe-debit').name = `expenses[${otherExpIndex}][debit]`;
        clone.querySelector('.oe-credit').name = `expenses[${otherExpIndex}][credit]`;

        document.getElementById('otherExpBody').appendChild(clone);
        otherExpIndex++;
        attachExpListeners('otherExpTable', 'otherexp-row');
        calculatePiTotals();
    });

    // Commission Listeners
    function attachCommListeners(row) {
        row.querySelectorAll('input, select').forEach(i => i.addEventListener('input', calculatePiTotals));
        const btn = row.querySelector('.btn-remove-comm');
        if (btn) {
            btn.addEventListener('click', function () {
                if (document.querySelectorAll('#commBody tr').length > 1) {
                    row.remove();
                    calculatePiTotals();
                }
            });
        }
    }

    document.querySelectorAll('#commBody tr').forEach(attachCommListeners);

    document.getElementById('btnAddComm').addEventListener('click', function () {
        const first = document.querySelector('#commBody tr');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(i => i.value = '');
        clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);

        clone.querySelector('input[name*="[agent_name]"]').name = `commissions[${commIndex}][agent_name]`;
        clone.querySelector('select[name*="[commission_type]"]').name = `commissions[${commIndex}][commission_type]`;
        clone.querySelector('.comm-type').name = `commissions[${commIndex}][calculation_type]`;
        clone.querySelector('.comm-deduction').name = `commissions[${commIndex}][deduction_type]`;
        clone.querySelector('.comm-rate').name = `commissions[${commIndex}][rate]`;
        clone.querySelector('.comm-amount').name = `commissions[${commIndex}][amount]`;
        clone.querySelector('input[name*="[remarks]"]').name = `commissions[${commIndex}][remarks]`;

        document.getElementById('commBody').appendChild(clone);
        commIndex++;
        attachCommListeners(clone);
        calculatePiTotals();
    });

    calculatePiTotals();
});
</script>
@endsection
