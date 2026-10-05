@extends('layouts.app')
@section('title', 'Stock Adjustments')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Stock Adjustments & Physical Audits</h4>
        <small class="text-secondary">Reconcile physical stock discrepancy, damaged items, and audit losses</small>
    </div>
    <a class="btn btn-primary" href="{{ route('adjustments.create') }}">
        <i class="bi bi-plus-lg me-1"></i> New Adjustment
    </a>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-6 col-md-8">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Adjustment # or reason...">
        </div>
        <div class="col-lg-6 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('adjustments.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Adjustment #</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Warehouse</th>
                    <th>Reason</th>
                    <th>Items</th>
                    <th class="text-end">Value Effect</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adjustments as $adj)
                    <tr>
                        <td>
                            <a href="{{ route('adjustments.show', $adj) }}" class="fw-bold font-monospace text-decoration-none">
                                {{ $adj->adjustment_number }}
                            </a>
                        </td>
                        <td>{{ $adj->adjustment_date->format('d M Y') }}</td>
                        <td>
                            @if($adj->type === 'addition')
                                <span class="badge bg-success-subtle text-success">+ Stock Addition</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">- Stock Deduction</span>
                            @endif
                        </td>
                        <td>{{ $adj->warehouse ? $adj->warehouse->name : 'All' }}</td>
                        <td>{{ $adj->reason }}</td>
                        <td>{{ $adj->items->count() }} items</td>
                        <td class="text-end fw-bold {{ $adj->type === 'addition' ? 'text-success' : 'text-danger' }}">
                            {{ $adj->type === 'addition' ? '+' : '-' }} Rs. {{ number_format($adj->total_amount, 2) }}
                        </td>
                        <td class="text-end">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('adjustments.show', $adj) }}">
                                <i class="bi bi-eye me-1"></i> View Audit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No stock adjustments recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($adjustments->hasPages())
        <div class="p-3 border-top">
            {{ $adjustments->links() }}
        </div>
    @endif
</div>
@endsection
