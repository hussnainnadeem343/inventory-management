@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Chart of Accounts (COA)</h4>
        <small class="text-secondary">Master ledger accounts categorized by financial heads</small>
    </div>
    @if(auth()->user()->hasPermission('accounts.manage'))
        <a class="btn btn-primary" href="{{ route('finance.accounts.create') }}">
            <i class="bi bi-plus-lg me-1"></i> Add Account
        </a>
    @endif
</div>

@foreach($heads as $head)
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-uppercase">
                <span class="badge bg-primary me-2">{{ $head->code }}</span>
                {{ $head->name }} ({{ ucfirst($head->nature) }} Normal)
            </h6>
            <span class="badge bg-secondary-subtle text-secondary">{{ $head->accounts->count() }} Accounts</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="small text-secondary">
                    <tr>
                        <th style="width: 15%;">Account Code</th>
                        <th style="width: 45%;">Account Name</th>
                        <th style="width: 15%;">Nature</th>
                        <th style="width: 15%;" class="text-end">Current Balance</th>
                        <th style="width: 10%;" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($head->accounts as $acc)
                        <tr>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $acc->code }}</span></td>
                            <td>
                                <a href="{{ route('finance.accounts.ledger', $acc) }}" class="fw-semibold text-decoration-none">
                                    {{ $acc->name }}
                                </a>
                                @if($acc->is_system)
                                    <span class="badge bg-info-subtle text-info ms-1 small">System</span>
                                @endif
                            </td>
                            <td><small class="text-secondary">{{ ucfirst($acc->nature) }}</small></td>
                            <td class="text-end fw-bold {{ $acc->current_balance < 0 ? 'text-danger' : 'text-dark' }}">
                                Rs. {{ number_format($acc->current_balance, 2) }}
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('finance.accounts.ledger', $acc) }}">
                                    <i class="bi bi-journal-text me-1"></i> Ledger
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No accounts configured under this head.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection
