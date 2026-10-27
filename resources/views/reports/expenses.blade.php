@extends('layouts.app')
@section('title', 'Daily Expense Report & Sheet')
@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">Reports</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Daily Expense Report</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-cash-stack text-danger me-2"></i>Daily Expense Report &amp; Sheet</h4>
            <!-- <small class="text-secondary">Comprehensive operating expenditure ledger, petty cash disbursements, and bank payouts</small> -->
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reports.expenses.export', request()->all()) }}" class="btn btn-success d-inline-flex align-items-center shadow-sm">
                <i class="bi bi-file-earmark-excel me-1.5 fs-6"></i>
                <span class="fw-semibold">Download Excel (.xlsx)</span>
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>All Reports
            </a>
        </div>
    </div>
</div>

{{-- Standalone Filter Bar --}}
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="get" action="{{ route('reports.expenses') }}" class="d-flex flex-column gap-3">
            {{-- Quick Date Presets --}}
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
                    <a href="{{ route('reports.expenses', array_merge(request()->except(['start_date', 'end_date', 'page']), ['preset' => $key])) }}" 
                       class="btn btn-sm {{ ($preset === $key) ? 'btn-primary text-white shadow-sm fw-semibold' : 'btn-outline-secondary' }}"
                       style="font-size: 12px; padding: 4px 10px;">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Filters --}}
            <div class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Start Date</label>
                    <input type="date" class="form-control form-control-sm" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">End Date</label>
                    <input type="date" class="form-control form-control-sm" name="end_date" value="{{ $endDate }}">
                </div>
                @if(isset($shops) && $shops->count() > 1)
                    <div class="col-sm-6 col-md-2">
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
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Expense Head</label>
                    <select class="form-select form-select-sm" name="expense_account_id">
                        <option value="">All Expense Heads</option>
                        @foreach($expenseAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ ($selectedAccountId ?? '') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }} ({{ $acc->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Category</label>
                    <select class="form-select form-select-sm" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ ($category ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Search</label>
                    <input type="text" class="form-control form-control-sm" name="search" value="{{ $search ?? '' }}" placeholder="Voucher #, desc, receipt...">
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 pt-2 border-top">
                    <button class="btn btn-primary btn-sm px-3" type="submit"><i class="bi bi-filter me-1"></i>Filter Expenses</button>
                    @if(request()->hasAny(['preset', 'start_date', 'end_date', 'shop_id', 'category', 'expense_account_id', 'search']))
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.expenses') }}">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modern Expense Summary KPI Cards --}}
<div class="row g-3 mb-4">
    {{-- KPI 1: Total Expenses --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Expenses</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #fef2f2; color: #ef4444; font-size: 18px;">
                    <i class="bi bi-cash-coin"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-danger">Rs. {{ number_format($summary['totalAmount'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">
                Top Category: <span class="fw-semibold text-dark">{{ $summary['topCategory'] }}</span> (Rs. {{ number_format($summary['topCategoryAmount'], 2) }})
            </small>
        </div>
    </div>

    {{-- KPI 2: Total Vouchers --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Vouchers Recorded</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #eff6ff; color: #2563eb; font-size: 18px;">
                    <i class="bi bi-receipt-cutoff"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-dark">{{ number_format($summary['totalCount']) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Expense payment vouchers created</small>
        </div>
    </div>

    {{-- KPI 3: Cash Disbursements --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Cash in Hand Paid</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #f0fdf4; color: #16a34a; font-size: 18px;">
                    <i class="bi bi-wallet2"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-success">Rs. {{ number_format($summary['cashPaid'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Petty cash drawer payouts</small>
        </div>
    </div>

    {{-- KPI 4: Bank / Digital Paid --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Bank &amp; Digital Paid</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #eef2ff; color: #6366f1; font-size: 18px;">
                    <i class="bi bi-bank"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-indigo" style="color: #4f46e5;">Rs. {{ number_format($summary['bankPaid'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Direct bank transfers &amp; cards</small>
        </div>
    </div>
</div>

{{-- Standalone Expense Records Table --}}
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check text-danger me-1"></i>Expense Disbursements Ledger</h6>
            <small class="text-secondary">Transactions recorded from {{ $startDate }} to {{ $endDate }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-light border px-2.5 py-1.5 fw-semibold text-secondary">
                Showing Latest {{ min(10, $expenses->count()) }} of {{ number_format($totalRecordsCount) }} Records
            </span>
            <a href="{{ route('reports.expenses.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    </div>

    {{-- Banner Callout for Latest 10 Preview --}}
    <div class="bg-light px-3 py-2 border-bottom d-flex align-items-center justify-content-between small text-secondary">
        <span>
            <i class="bi bi-info-circle text-primary me-1"></i>
            Showing latest 10 expense records in UI preview. For complete period records, export to Excel.
        </span>
        <a href="{{ route('reports.expenses.export', request()->all()) }}" class="fw-semibold text-decoration-none text-success">
            Download full sheet ({{ number_format($totalRecordsCount) }} records) &rarr;
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">Voucher #</th>
                    <th>Date</th>
                    @if(isset($shops) && $shops->count() > 1)
                        <th>Branch</th>
                    @endif
                    <th>Expense Head</th>
                    <th>Paid From</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Receipt Ref</th>
                    <th class="text-end">Amount</th>
                    <th class="pe-3">Recorded By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $exp)
                    <tr>
                        <td class="ps-3 fw-bold text-dark font-monospace">{{ $exp->voucher_no }}</td>
                        <td class="small text-secondary">{{ $exp->date?->format('d M Y') }}</td>
                        @if(isset($shops) && $shops->count() > 1)
                            <td>
                                <span class="badge text-bg-light border">{{ $exp->shop->name ?? 'Default' }}</span>
                            </td>
                        @endif
                        <td>
                            <span class="badge bg-danger-subtle text-danger">{{ $exp->expenseAccount->name ?? 'Expense' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $exp->paymentAccount->name ?? 'Cash/Bank' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $exp->category ?: 'General' }}</span>
                        </td>
                        <td class="text-truncate" style="max-width: 220px;" title="{{ $exp->description }}">{{ $exp->description }}</td>
                        <td>
                            <small class="text-muted font-monospace">{{ $exp->receipt_ref ?: '-' }}</small>
                        </td>
                        <td class="text-end fw-bold text-danger">Rs. {{ number_format((float) $exp->amount, 2) }}</td>
                        <td class="small text-muted pe-3">{{ $exp->creator->name ?? 'Staff' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ (isset($shops) && $shops->count() > 1) ? 10 : 9 }}" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            No expense records found for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($expenses->isNotEmpty())
                <tfoot class="table-light small">
                    <tr>
                        <td colspan="{{ (isset($shops) && $shops->count() > 1) ? 8 : 7 }}" class="text-end fw-bold ps-3">
                            Preview Subtotal (Latest {{ $expenses->count() }}):
                        </td>
                        <td class="text-end fw-bold text-danger">
                            Rs. {{ number_format($expenses->sum('amount'), 2) }}
                        </td>
                        <td class="pe-3"></td>
                    </tr>
                    <tr>
                        <td colspan="{{ (isset($shops) && $shops->count() > 1) ? 8 : 7 }}" class="text-end fw-bold ps-3 text-secondary">
                            Full Period Total ({{ number_format($totalRecordsCount) }} records):
                        </td>
                        <td class="text-end fw-bold text-danger fs-6">
                            Rs. {{ number_format($summary['totalAmount'], 2) }}
                        </td>
                        <td class="pe-3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
