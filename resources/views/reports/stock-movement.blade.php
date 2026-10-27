@extends('layouts.app')
@section('title', 'Stock Movement Audit Report')
@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">Reports</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Stock Movement Audit</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right text-primary me-2"></i>Stock Movement Audit Report</h4>
            <small class="text-secondary">Full transactional audit trail of inventory additions, customer sales, returns, and damage write-offs</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reports.stock.export', request()->all()) }}" class="btn btn-success d-inline-flex align-items-center shadow-sm">
                <i class="bi bi-file-earmark-excel me-1.5 fs-6"></i>
                <span class="fw-semibold">Download Excel (.xlsx)</span>
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>All Reports
            </a>
        </div>
    </div>
</div>

{{-- Standalone Filter Bar (No Dashboard Widgets) --}}
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="get" action="{{ route('reports.stock') }}" class="d-flex flex-column gap-3">
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
                    <a href="{{ route('reports.stock', array_merge(request()->except(['start_date', 'end_date', 'page']), ['preset' => $key])) }}" 
                       class="btn btn-sm {{ ($preset === $key) ? 'btn-primary text-white shadow-sm fw-semibold' : 'btn-outline-secondary' }}"
                       style="font-size: 12px; padding: 4px 10px;">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Range, Branch & Type Filter Inputs --}}
            <div class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Start Date</label>
                    <input type="date" class="form-control form-control-sm" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">End Date</label>
                    <input type="date" class="form-control form-control-sm" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Movement Type</label>
                    <select class="form-select form-select-sm" name="type">
                        <option value="">All Movement Types</option>
                        <option value="STOCK_IN" @selected(($type ?? '') === 'STOCK_IN')>Stock In / Purchases</option>
                        <option value="SALE" @selected(($type ?? '') === 'SALE')>Sales</option>
                        <option value="CUSTOMER_RETURN" @selected(($type ?? '') === 'CUSTOMER_RETURN')>Customer Returns</option>
                        <option value="DAMAGE_LOSS" @selected(($type ?? '') === 'DAMAGE_LOSS')>Damage / Loss</option>
                        <option value="TRANSFER_IN" @selected(($type ?? '') === 'TRANSFER_IN')>Transfer In</option>
                        <option value="TRANSFER_OUT" @selected(($type ?? '') === 'TRANSFER_OUT')>Transfer Out</option>
                    </select>
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
                <div class="col-sm-6 col-md-{{ isset($shops) && $shops->count() > 1 ? '3' : '6' }}">
                    <label class="form-label small fw-semibold text-secondary mb-1">Search Product</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="search" placeholder="Item name or SKU" value="{{ $search ?? '' }}">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
                        @if(request()->hasAny(['preset', 'start_date', 'end_date', 'shop_id', 'type', 'search']))
                            <a class="btn btn-outline-secondary" href="{{ route('reports.stock') }}" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Standalone Transactions Audit Table --}}
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text text-primary me-1"></i>Inventory Movement Audit Log</h6>
            <small class="text-secondary">Logged activities from {{ $startDate }} to {{ $endDate }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-light border px-2.5 py-1.5 fw-semibold text-secondary">
                Showing Latest {{ min(10, $transactions->count()) }} of {{ number_format($totalLogsCount ?? $transactions->count()) }} Logs
            </span>
            <a href="{{ route('reports.stock.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">Date &amp; Time</th>
                    <th>Product Item</th>
                    @if(isset($shops) && $shops->count() > 1)
                        <th>Branch</th>
                    @endif
                    <th class="text-center">Movement Type</th>
                    <th class="text-end">Quantity Changed</th>
                    <th class="text-center">Balance Before &rarr; After</th>
                    <th>Operator</th>
                    <th class="pe-3">Notes / Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                    @php
                        $badgeClass = match($txn->transaction_type) {
                            'STOCK_IN' => 'text-bg-success',
                            'SALE' => 'text-bg-primary',
                            'CUSTOMER_RETURN' => 'text-bg-info text-white',
                            'TRANSFER_IN' => 'text-bg-success',
                            'TRANSFER_OUT' => 'text-bg-warning',
                            'DAMAGE_LOSS' => 'text-bg-danger',
                            default => 'text-bg-secondary'
                        };
                        $isInc = in_array($txn->transaction_type, ['STOCK_IN', 'CUSTOMER_RETURN', 'TRANSFER_IN']);
                    @endphp
                    <tr>
                        <td class="ps-3 small text-secondary">
                            <div>{{ $txn->created_at?->format('d M Y') }}</div>
                            <small class="text-muted">{{ $txn->created_at?->format('h:i A') }}</small>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $txn->inventoryItem->item_name ?? 'Deleted Item' }}</div>
                            <code class="small text-secondary">{{ $txn->inventoryItem->sku ?? '-' }}</code>
                        </td>
                        @if(isset($shops) && $shops->count() > 1)
                            <td>
                                <span class="badge text-bg-light border">{{ $txn->shop->name ?? 'Default' }}</span>
                            </td>
                        @endif
                        <td class="text-center">
                            <span class="badge {{ $badgeClass }} px-2 py-1" style="font-size: 11px;">
                                {{ str_replace('_', ' ', strtoupper($txn->transaction_type)) }}
                            </span>
                        </td>
                        <td class="text-end fw-bold {{ $isInc ? 'text-success' : 'text-danger' }}">
                            {{ $isInc ? '+' : '-' }}{{ number_format($txn->quantity, 2) }}
                        </td>
                        <td class="text-center small">
                            <span class="text-muted">{{ number_format($txn->balance_before ?? 0, 2) }}</span>
                            <i class="bi bi-arrow-right mx-1 text-secondary"></i>
                            <strong class="text-dark">{{ number_format($txn->balance_after ?? 0, 2) }}</strong>
                        </td>
                        <td class="small text-secondary">{{ $txn->creator->name ?? 'System' }}</td>
                        <td class="small pe-3 text-muted">{{ $txn->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ (isset($shops) && $shops->count() > 1) ? 8 : 7 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-arrow-left-right fs-2 d-block mb-2 text-secondary"></i>
                            No stock movement transactions recorded for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(($totalLogsCount ?? $transactions->count()) > 10)
        <div class="card-footer bg-light py-2.5 px-3 border-top text-secondary small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span>
                <i class="bi bi-info-circle text-primary me-1"></i>
                Showing latest <strong>{{ $transactions->count() }}</strong> audit transactions in UI preview. For complete stock movement ledger, export to Excel.
            </span>
            <a href="{{ route('reports.stock.export', request()->all()) }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Download Full Excel (.xlsx)
            </a>
        </div>
    @else
        <div class="card-footer bg-white py-2 px-3 border-top text-secondary small d-flex justify-content-between align-items-center">
            <span>Showing all {{ $transactions->count() }} transaction log{{ $transactions->count() === 1 ? '' : 's' }} for this period.</span>
            <a href="{{ route('reports.stock.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    @endif
</div>
@endsection
