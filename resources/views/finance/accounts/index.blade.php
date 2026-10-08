@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Chart of Accounts (COA)</h4>
        <small class="text-secondary">Master ledger accounts categorized by financial heads</small>
    </div>
    @if(auth()->user()->hasPermission('accounts.manage'))
        <a class="btn btn-primary shadow-sm" href="{{ route('finance.accounts.create') }}">
            <i class="bi bi-plus-lg me-1"></i> Add Account
        </a>
    @endif
</div>

{{-- Top KPI Summary Cards --}}
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-2 mb-4">
    @php
        $headStyles = [
            1 => ['name' => 'Assets', 'icon' => 'bi-wallet2', 'color' => 'primary', 'badge' => 'bg-primary-subtle text-primary border-primary-subtle', 'border' => 'border-primary'],
            2 => ['name' => 'Liabilities', 'icon' => 'bi-credit-card-2-front', 'color' => 'warning', 'badge' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle', 'border' => 'border-warning'],
            3 => ['name' => 'Equity', 'icon' => 'bi-bank', 'color' => 'info', 'badge' => 'bg-info-subtle text-info-emphasis border-info-subtle', 'border' => 'border-info'],
            4 => ['name' => 'Revenue', 'icon' => 'bi-graph-up-arrow', 'color' => 'success', 'badge' => 'bg-success-subtle text-success border-success-subtle', 'border' => 'border-success'],
            5 => ['name' => 'Expenses', 'icon' => 'bi-receipt-cutoff', 'color' => 'danger', 'badge' => 'bg-danger-subtle text-danger border-danger-subtle', 'border' => 'border-danger'],
        ];
    @endphp

    @foreach($heads as $head)
        @php
            $style = $headStyles[$head->id] ?? ['name' => $head->name, 'icon' => 'bi-folder', 'color' => 'secondary', 'badge' => 'bg-light text-dark', 'border' => 'border-secondary'];
            $totalHeadBal = $head->accounts->sum('current_balance');
            $headAccCount = $head->accounts->count();
        @endphp
        <div class="col">
            <div class="card h-100 shadow-sm border-0 border-start border-4 {{ $style['border'] }} bg-white py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-secondary small fw-semibold text-uppercase">{{ $head->name }}</span>
                    <i class="bi {{ $style['icon'] }} text-{{ $style['color'] }} fs-5"></i>
                </div>
                <div class="mt-2">
                    <h5 class="mb-0 fw-bold text-dark">Rs. {{ number_format($totalHeadBal, 2) }}</h5>
                    <small class="text-muted">{{ $headAccCount }} {{ Str::plural('account', $headAccCount) }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Search & Bulk Actions Bar --}}
<div class="card shadow-sm border-0 mb-3 bg-white">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-5 col-lg-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-secondary">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="search" id="accountSearchInput" class="form-control border-start-0 ps-0" placeholder="Search account by name or code (e.g. Cash, 1001)...">
                    <span id="searchResultCount" class="badge bg-primary align-self-center mx-2 d-none"></span>
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnExpandAll" title="Expand all categories">
                    <i class="bi bi-arrows-expand me-1"></i> Expand All
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCollapseAll" title="Collapse all categories">
                    <i class="bi bi-arrows-collapse me-1"></i> Collapse All
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Collapsible Accordion COA --}}
<div class="accordion custom-coa-accordion d-flex flex-column gap-3 mb-4" id="coaAccordion">
    @foreach($heads as $head)
        @php
            $style = $headStyles[$head->id] ?? ['name' => $head->name, 'icon' => 'bi-folder', 'color' => 'secondary', 'badge' => 'bg-light text-dark', 'border' => 'border-secondary'];
            $totalHeadBal = $head->accounts->sum('current_balance');
            $headAccCount = $head->accounts->count();
        @endphp
        <div class="accordion-item shadow-sm border rounded-3 overflow-hidden coa-head-item" data-head-id="{{ $head->id }}">
            <h2 class="accordion-header" id="headingHead_{{ $head->id }}">
                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} py-3 px-3"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseHead_{{ $head->id }}"
                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                        aria-controls="collapseHead_{{ $head->id }}">
                    <div class="d-flex align-items-center flex-grow-1 flex-wrap gap-2 me-3 justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $style['badge'] }} px-2 py-1 fs-6 border">
                                <i class="bi {{ $style['icon'] }} me-1"></i> {{ $head->code }}000
                            </span>
                            <span class="fw-bold fs-6 text-dark text-uppercase">{{ $head->name }}</span>
                            <small class="text-secondary fw-normal">({{ ucfirst($head->nature) }} Normal)</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-secondary border">
                                <span class="head-item-count">{{ $headAccCount }}</span> Accounts
                            </span>
                            <span class="badge bg-white text-dark border px-3 py-1 fw-bold fs-6 shadow-xs">
                                Total: Rs. {{ number_format($totalHeadBal, 2) }}
                            </span>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapseHead_{{ $head->id }}"
                 class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                 aria-labelledby="headingHead_{{ $head->id }}">
                <div class="accordion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="small text-secondary bg-light">
                                <tr>
                                    <th style="width: 15%;" class="ps-3">Account Code</th>
                                    <th style="width: 40%;">Account Name</th>
                                    <th style="width: 15%;">Nature</th>
                                    <th style="width: 18%;" class="text-end">Current Balance</th>
                                    <th style="width: 12%;" class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($head->accounts as $acc)
                                    <tr class="coa-account-row"
                                        data-code="{{ strtolower($acc->code) }}"
                                        data-name="{{ strtolower($acc->name) }}">
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark border font-monospace px-2 py-1">{{ $acc->code }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('finance.accounts.ledger', $acc) }}" class="fw-semibold text-decoration-none text-dark">
                                                {{ $acc->name }}
                                            </a>
                                            @if($acc->is_system)
                                                <span class="badge bg-info-subtle text-info ms-1 small">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">{{ ucfirst($acc->nature) }}</span>
                                        </td>
                                        <td class="text-end fw-bold {{ $acc->current_balance < 0 ? 'text-danger' : 'text-dark' }}">
                                            Rs. {{ number_format($acc->current_balance, 2) }}
                                        </td>
                                        <td class="text-end pe-3">
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('finance.accounts.ledger', $acc) }}" title="View Ledger">
                                                <i class="bi bi-journal-text me-1"></i> Ledger
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="coa-empty-row">
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox text-secondary fs-4 d-block mb-1"></i>
                                            No accounts registered under this head yet.
                                            @if(auth()->user()->hasPermission('accounts.manage'))
                                                <div class="mt-2">
                                                    <a href="{{ route('finance.accounts.create') }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-plus-circle me-1"></i> Add {{ $head->name }} Account
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<style>
    .custom-coa-accordion .accordion-item {
        border-color: #e2e8f0 !important;
        transition: box-shadow 0.2s ease;
    }
    .custom-coa-accordion .accordion-item:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04) !important;
    }
    .custom-coa-accordion .accordion-button {
        background-color: #f8fafc;
        color: #1e293b;
        box-shadow: none !important;
    }
    .custom-coa-accordion .accordion-button:not(.collapsed) {
        background-color: #f1f5f9;
        color: #0f172a;
        box-shadow: none !important;
        border-bottom: 1px solid #e2e8f0;
    }
    .custom-coa-accordion .accordion-button:focus {
        box-shadow: none !important;
        border-color: #cbd5e1;
    }
    .shadow-xs {
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('accountSearchInput');
    const btnExpandAll = document.getElementById('btnExpandAll');
    const btnCollapseAll = document.getElementById('btnCollapseAll');
    const searchCount = document.getElementById('searchResultCount');
    const headItems = document.querySelectorAll('.coa-head-item');

    // Expand All
    if (btnExpandAll) {
        btnExpandAll.addEventListener('click', function () {
            headItems.forEach(item => {
                const collapseEl = item.querySelector('.accordion-collapse');
                const button = item.querySelector('.accordion-button');
                if (collapseEl && !collapseEl.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                    bsCollapse.show();
                    button.classList.remove('collapsed');
                    button.setAttribute('aria-expanded', 'true');
                }
            });
        });
    }

    // Collapse All
    if (btnCollapseAll) {
        btnCollapseAll.addEventListener('click', function () {
            headItems.forEach(item => {
                const collapseEl = item.querySelector('.accordion-collapse');
                const button = item.querySelector('.accordion-button');
                if (collapseEl && collapseEl.classList.contains('show')) {
                    const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                    bsCollapse.hide();
                    button.classList.add('collapsed');
                    button.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    // Live Search & Instant Filter
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let totalMatches = 0;

            headItems.forEach(item => {
                const rows = item.querySelectorAll('tbody tr.coa-account-row');
                const emptyRow = item.querySelector('tbody tr.coa-empty-row');
                const collapseEl = item.querySelector('.accordion-collapse');
                const button = item.querySelector('.accordion-button');
                let headMatches = 0;

                if (query === '') {
                    rows.forEach(r => r.style.display = '');
                    if (emptyRow) emptyRow.style.display = '';
                    item.style.display = '';
                } else {
                    if (emptyRow) emptyRow.style.display = 'none';

                    rows.forEach(r => {
                        const code = r.getAttribute('data-code') || '';
                        const name = r.getAttribute('data-name') || '';
                        if (code.includes(query) || name.includes(query)) {
                            r.style.display = '';
                            headMatches++;
                            totalMatches++;
                        } else {
                            r.style.display = 'none';
                        }
                    });

                    if (headMatches > 0) {
                        item.style.display = '';
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                        bsCollapse.show();
                        button.classList.remove('collapsed');
                        button.setAttribute('aria-expanded', 'true');
                    } else {
                        item.style.display = 'none';
                    }
                }
            });

            if (query !== '') {
                searchCount.classList.remove('d-none');
                searchCount.textContent = totalMatches + ' found';
            } else {
                searchCount.classList.add('d-none');
            }
        });
    }
});
</script>
@endsection
