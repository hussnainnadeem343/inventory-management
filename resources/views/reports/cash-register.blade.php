@extends('layouts.app')
@section('title', 'Daily Cash Register & Payments Report')
@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">Reports</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Cash Register &amp; Payments</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-cash-stack text-success me-2"></i>Daily Cash Register &amp; Payments Report</h4>
            <!-- <small class="text-secondary">Track actual physical cash in drawer, POS card swipes, online bank transfers, and customer credit (udhaar)</small> -->
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary d-inline-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#closeCashModal">
                <i class="bi bi-lock-fill me-1.5 fs-6"></i>
                <span class="fw-semibold">Close Cash Drawer</span>
            </button>
            <a href="{{ route('reports.cash-register.export', request()->all()) }}" class="btn btn-success d-inline-flex align-items-center shadow-sm">
                <i class="bi bi-file-earmark-excel me-1.5 fs-6"></i>
                <span class="fw-semibold">Download Excel (.xlsx)</span>
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>All Reports
            </a>
        </div>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success d-flex align-items-center justify-content-between p-3 rounded-3 shadow-sm mb-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
            <span class="fw-semibold">{{ session('status') }}</span>
        </div>
        @if(session('just_closed_id'))
            <a href="{{ route('reports.cash-register.slip', session('just_closed_id')) }}" target="_blank" class="btn btn-sm btn-dark">
                <i class="bi bi-printer me-1"></i>Print Closing Slip
            </a>
        @endif
    </div>
@endif

{{-- Standalone Filter Bar (No Dashboard Widgets) --}}
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="get" action="{{ route('reports.cash-register') }}" class="d-flex flex-column gap-3">
            {{-- Date Preset Quick Buttons --}}
            <div class="d-flex flex-wrap align-items-center gap-1.5 border-bottom pb-2.5">
                <span class="text-secondary small fw-semibold me-2"><i class="bi bi-calendar3 me-1"></i>Presets:</span>
                @php
                    $presets = [
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'this_week' => 'This Week',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'this_year' => 'This Year',
                    ];
                @endphp
                @foreach($presets as $key => $label)
                    <a href="{{ route('reports.cash-register', array_merge(request()->except(['start_date', 'end_date', 'page']), ['preset' => $key])) }}" 
                       class="btn btn-sm {{ ($preset === $key) ? 'btn-primary text-white shadow-sm fw-semibold' : 'btn-outline-secondary' }}"
                       style="font-size: 12px; padding: 4px 10px;">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Range & Branch Filter Inputs --}}
            <div class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Start Date</label>
                    <input type="date" class="form-control form-control-sm" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">End Date</label>
                    <input type="date" class="form-control form-control-sm" name="end_date" value="{{ $endDate }}">
                </div>
                @if(isset($shops) && $shops->count() > 1)
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Branch Scope</label>
                        <select class="form-select form-select-sm" name="shop_id">
                            <option value="all" {{ empty($shopId) ? 'selected' : '' }}>All Branches (Consolidated)</option>
                            @foreach($shops as $s)
                                <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ $s->code }}){{ $s->isBusiness() ? ' - HQ' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-sm-6 col-md-{{ isset($shops) && $shops->count() > 1 ? '3' : '6' }} d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-filter me-1"></i>Filter Register</button>
                    @if(request()->hasAny(['preset', 'start_date', 'end_date', 'shop_id']))
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.cash-register') }}">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modern Period Register Totals KPI Summary --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Cash in Drawer</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #ecfdf5; color: #10b981; font-size: 18px;">
                    <i class="bi bi-cash-stack"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-success">Rs. {{ number_format($financialSummary['cashTotal'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Physical cash collected</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Card / Swipe Terminal</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #eff6ff; color: #2563eb; font-size: 18px;">
                    <i class="bi bi-credit-card"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-primary">Rs. {{ number_format($financialSummary['digitalTotal'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">POS card machines &amp; bank</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Customer Credit (Udhaar)</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #fffbeb; color: #f59e0b; font-size: 18px;">
                    <i class="bi bi-person-exclamation"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-warning">Rs. {{ number_format($financialSummary['creditTotal'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Pending receivables on account</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Net Revenue</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #f8fafc; color: #0f172a; font-size: 18px;">
                    <i class="bi bi-wallet2"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-dark">Rs. {{ number_format($financialSummary['netSales'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Combined drawer &amp; digital totals</small>
        </div>
    </div>
</div>

{{-- Standalone Cash Register Daily Table --}}
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-wallet2 text-primary me-1"></i>Daily Cash Drawer &amp; Channel Breakdown</h6>
            <small class="text-secondary">Collections recorded from {{ $startDate }} to {{ $endDate }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-light border px-2.5 py-1.5 fw-semibold text-secondary">
                Showing Latest {{ min(10, $registerRows->count()) }} of {{ number_format($totalRecordsCount ?? $registerRows->count()) }} Daily Records
            </span>
            <a href="{{ route('reports.cash-register.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">Date</th>
                    <th>Branch / Shop</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-end">Cash in Drawer</th>
                    <th class="text-end">Card / Swipe</th>
                    <th class="text-end">Bank Transfer</th>
                    <th class="text-end">Credit (Udhaar)</th>
                    <th class="text-end">Total Net Sales</th>
                    <th class="text-center pe-3">Closing Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registerRows as $row)
                    @php
                        $rowShopId = $row->shop_id ?? ($shopId ?: auth()->user()->shop_id);
                        $closingKey = $rowShopId . '_' . $row->sale_date;
                        $closing = isset($closings) ? $closings->get($closingKey) : null;
                    @endphp
                    <tr>
                        <td class="ps-3 fw-semibold text-dark">
                            {{ \Carbon\Carbon::parse($row->sale_date)->format('d M Y') }}
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $row->shop_name }}</div>
                            <code class="small text-secondary">{{ $row->shop_code }}</code>
                        </td>
                        <td class="text-center fw-semibold">{{ number_format($row->total_invoices) }}</td>
                        <td class="text-end text-success fw-bold">Rs. {{ number_format($row->cash_total, 2) }}</td>
                        <td class="text-end text-primary">Rs. {{ number_format($row->card_total, 2) }}</td>
                        <td class="text-end text-info">Rs. {{ number_format($row->bank_total, 2) }}</td>
                        <td class="text-end text-warning">Rs. {{ number_format($row->credit_total, 2) }}</td>
                        <td class="text-end fw-bold text-dark">Rs. {{ number_format($row->net_sales, 2) }}</td>
                        <td class="text-center pe-3 text-nowrap">
                            @if($closing)
                                <span class="badge text-bg-success py-1 px-2">
                                    <i class="bi bi-check-circle me-1"></i>Closed
                                </span>
                                @if(!$closing->isBalanced())
                                    <span class="badge {{ $closing->difference < 0 ? 'text-bg-danger' : 'text-bg-info text-white' }} py-1 px-1.5 ms-1" title="{{ $closing->varianceLabel() }}">
                                        {{ $closing->difference < 0 ? '- Rs. ' . number_format(abs($closing->difference)) : '+ Rs. ' . number_format($closing->difference) }}
                                    </span>
                                @endif
                                <a href="{{ route('reports.cash-register.slip', $closing) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0.5 px-2 ms-1" title="View &amp; Print Closing Slip">
                                    <i class="bi bi-receipt"></i>
                                </a>
                            @else
                                <span class="badge text-bg-warning text-dark py-1 px-2">
                                    <i class="bi bi-clock me-1"></i>Open
                                </span>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary py-0.5 px-2 ms-1" 
                                        onclick="openCloseCashModal('{{ $row->sale_date }}', {{ $rowShopId }})" 
                                        title="Close Cash Drawer for {{ \Carbon\Carbon::parse($row->sale_date)->format('d M Y') }}">
                                    <i class="bi bi-lock-fill me-1"></i>Close
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-cash-stack fs-2 d-block mb-2 text-secondary"></i>
                            No cash register collections recorded for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($registerRows->count() > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3" colspan="2">Period Totals:</td>
                        <td class="text-center">{{ number_format($financialSummary['totalInvoices']) }}</td>
                        <td class="text-end text-success">Rs. {{ number_format($financialSummary['cashTotal'], 2) }}</td>
                        <td class="text-end text-primary">Rs. {{ number_format($financialSummary['cardTotal'], 2) }}</td>
                        <td class="text-end text-info">Rs. {{ number_format($financialSummary['bankTotal'], 2) }}</td>
                        <td class="text-end text-warning">Rs. {{ number_format($financialSummary['creditTotal'], 2) }}</td>
                        <td class="text-end text-dark">Rs. {{ number_format($financialSummary['netSales'], 2) }}</td>
                        <td class="text-center pe-3 text-muted">-</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @if(($totalRecordsCount ?? $registerRows->count()) > 10)
        <div class="card-footer bg-light py-2.5 px-3 border-top text-secondary small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span>
                <i class="bi bi-info-circle text-primary me-1"></i>
                Showing latest <strong>{{ $registerRows->count() }}</strong> daily register summaries in UI preview. For complete multi-day history, export to Excel.
            </span>
            <a href="{{ route('reports.cash-register.export', request()->all()) }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Download Full Excel (.xlsx)
            </a>
        </div>
    @else
        <div class="card-footer bg-white py-2 px-3 border-top text-secondary small d-flex justify-content-between align-items-center">
            <span>Showing all {{ $registerRows->count() }} daily record{{ $registerRows->count() === 1 ? '' : 's' }} for this period.</span>
            <a href="{{ route('reports.cash-register.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    @endif
</div>

{{-- Interactive Close Cash Drawer Modal --}}
<div class="modal fade" id="closeCashModal" tabindex="-1" aria-labelledby="closeCashModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-3">
            <form method="post" action="{{ route('reports.cash-register.close') }}" id="closeCashForm">
                @csrf
                <div class="modal-header bg-light py-3 border-bottom">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="closeCashModalLabel">
                        <i class="bi bi-lock-fill text-primary"></i>
                        <span>Close Cash Drawer &amp; Reconcile</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Branch & Date Selection --}}
                    <div class="row g-3 mb-3">
                        @if(isset($shops) && $shops->count() > 1)
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Branch / Store</label>
                                <select class="form-select form-select-sm" name="shop_id" id="modalShopId" onchange="fetchRegisterSummary()">
                                    @foreach($shops as $s)
                                        <option value="{{ $s->id }}" {{ ($shopId == $s->id || auth()->user()->shop_id == $s->id) ? 'selected' : '' }}>
                                            {{ $s->name }} ({{ $s->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="shop_id" id="modalShopId" value="{{ auth()->user()->shop_id ?? ($shopId ?? 1) }}">
                        @endif
                        <div class="col-md-{{ isset($shops) && $shops->count() > 1 ? '6' : '12' }}">
                            <label class="form-label small fw-semibold text-secondary">Closing Date</label>
                            <input type="date" class="form-control form-control-sm" name="closing_date" id="modalClosingDate" value="{{ now()->format('Y-m-d') }}" onchange="fetchRegisterSummary()" required>
                        </div>
                    </div>

                    {{-- Live System Calculated Breakdown Card --}}
                    <div class="card border rounded-3 bg-light p-3 mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-bold text-uppercase text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">
                                System Recorded Collections for <span id="summaryDisplayDate" class="text-dark">{{ now()->format('d M Y') }}</span>
                            </span>
                            <span id="closingStatusIndicator" class="badge text-bg-warning text-dark">Open Drawer</span>
                        </div>
                        <div class="row g-2 text-center">
                            <div class="col-3">
                                <div class="bg-white p-2 rounded border">
                                    <small class="text-secondary d-block" style="font-size: 10.5px;">Cash Sales</small>
                                    <strong class="text-success small" id="dispCashSales">Rs. 0.00</strong>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-white p-2 rounded border">
                                    <small class="text-secondary d-block" style="font-size: 10.5px;">Card / Bank</small>
                                    <strong class="text-primary small" id="dispCardBank">Rs. 0.00</strong>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-white p-2 rounded border">
                                    <small class="text-secondary d-block" style="font-size: 10.5px;">Credit (Udhaar)</small>
                                    <strong class="text-warning small" id="dispCredit">Rs. 0.00</strong>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-white p-2 rounded border">
                                    <small class="text-secondary d-block" style="font-size: 10.5px;">Total Net Sales</small>
                                    <strong class="text-dark small" id="dispTotalNet">Rs. 0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Counting Inputs --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Opening Cash Float (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary">Rs.</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="opening_float" id="openingFloatInput" value="0.00" oninput="calculateLiveVariance()" placeholder="0.00">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Starting cash in drawer at shift start</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Physical Cash Counted in Drawer (Rs.) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white fw-bold">Rs.</span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-lg fw-bold text-primary" name="actual_cash" id="actualCashInput" oninput="calculateLiveVariance()" placeholder="0.00" required autofocus>
                            </div>
                            <small class="text-secondary" style="font-size: 11px;">Count physical cash bills &amp; coins in drawer</small>
                        </div>
                    </div>

                    {{-- Live Reconciliation Variance Card --}}
                    <div class="p-3 rounded-3 border mb-3" id="varianceCard" style="background: #f8fafc; border-color: #cbd5e1 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-secondary small fw-semibold">Expected Cash in Drawer:</span>
                            <span class="fw-bold text-dark" id="dispExpectedCash">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-secondary small fw-semibold">Counted Physical Cash:</span>
                            <span class="fw-bold text-primary" id="dispCountedCash">Rs. 0.00</span>
                        </div>
                        <hr class="my-1.5 text-secondary">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-dark">Reconciliation Variance:</span>
                            <span class="fw-bold fs-6" id="dispVarianceBadge">Rs. 0.00 (Balanced)</span>
                        </div>
                    </div>

                    {{-- Handover & Audit Notes --}}
                    <div>
                        <label class="form-label small fw-semibold text-secondary">Handover / Reconciliation Notes</label>
                        <textarea class="form-control form-control-sm" name="notes" id="closingNotes" rows="2" placeholder="e.g. Cash handed over to owner / Shortage of Rs. 20 due to roundoff..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm" id="btnSubmitCloseCash">
                        <i class="bi bi-lock-fill me-1"></i>Confirm &amp; Close Cash Drawer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentSummary = {
    cash_sales: 0,
    cash_expenses: 0,
    opening_float: 0,
    expected_cash: 0
};

function openCloseCashModal(date, shopId) {
    if (date) {
        document.getElementById('modalClosingDate').value = date;
    }
    if (shopId) {
        const shopSelect = document.getElementById('modalShopId');
        if (shopSelect) shopSelect.value = shopId;
    }
    const modalEl = document.getElementById('closeCashModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    fetchRegisterSummary();
}

function fetchRegisterSummary() {
    const date = document.getElementById('modalClosingDate').value;
    const shopId = document.getElementById('modalShopId')?.value || '{{ auth()->user()->shop_id }}';

    fetch(`{{ route('reports.cash-register.summary') }}?date=${date}&shop_id=${shopId}`)
        .then(res => res.json())
        .then(data => {
            currentSummary = data;
            document.getElementById('summaryDisplayDate').innerText = data.formatted_date || date;
            document.getElementById('dispCashSales').innerText = 'Rs. ' + Number(data.cash_sales).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('dispCardBank').innerText = 'Rs. ' + (Number(data.card_total) + Number(data.bank_total)).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('dispCredit').innerText = 'Rs. ' + Number(data.credit_total).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('dispTotalNet').innerText = 'Rs. ' + Number(data.total_net_sales).toLocaleString('en-US', {minimumFractionDigits: 2});

            const statusIndicator = document.getElementById('closingStatusIndicator');
            if (data.is_closed) {
                statusIndicator.className = 'badge text-bg-success';
                statusIndicator.innerHTML = '<i class="bi bi-check-circle me-1"></i>Closed by ' + data.closed_by;
                document.getElementById('openingFloatInput').value = data.opening_float;
                document.getElementById('actualCashInput').value = data.actual_cash;
                document.getElementById('closingNotes').value = data.notes || '';
            } else {
                statusIndicator.className = 'badge text-bg-warning text-dark';
                statusIndicator.innerHTML = '<i class="bi bi-clock me-1"></i>Open Drawer';
                if (!document.getElementById('actualCashInput').value) {
                    document.getElementById('actualCashInput').value = data.cash_sales;
                }
            }

            calculateLiveVariance();
        })
        .catch(err => console.error('Error fetching register summary:', err));
}

function calculateLiveVariance() {
    const openingFloat = parseFloat(document.getElementById('openingFloatInput').value) || 0;
    const actualCash = parseFloat(document.getElementById('actualCashInput').value) || 0;
    const cashSales = parseFloat(currentSummary.cash_sales) || 0;
    const cashExpenses = parseFloat(currentSummary.cash_expenses) || 0;

    const expectedCash = openingFloat + cashSales - cashExpenses;
    const difference = actualCash - expectedCash;

    document.getElementById('dispExpectedCash').innerText = 'Rs. ' + expectedCash.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('dispCountedCash').innerText = 'Rs. ' + actualCash.toLocaleString('en-US', {minimumFractionDigits: 2});

    const badge = document.getElementById('dispVarianceBadge');
    const card = document.getElementById('varianceCard');

    if (Math.abs(difference) < 0.01) {
        badge.className = 'fw-bold fs-6 text-success';
        badge.innerText = 'Rs. 0.00 \u2022 Balanced (Exact)';
        card.style.background = '#f0fdf4';
        card.style.borderColor = '#86efac';
    } else if (difference < 0) {
        badge.className = 'fw-bold fs-6 text-danger';
        badge.innerText = '- Rs. ' + Math.abs(difference).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' \u2022 Shortage';
        card.style.background = '#fef2f2';
        card.style.borderColor = '#fca5a5';
    } else {
        badge.className = 'fw-bold fs-6 text-primary';
        badge.innerText = '+ Rs. ' + difference.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' \u2022 Excess';
        card.style.background = '#eff6ff';
        card.style.borderColor = '#93c5fd';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('closeCashModal');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            fetchRegisterSummary();
        });
    }
});
</script>
@endsection
