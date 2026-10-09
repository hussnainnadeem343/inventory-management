@extends('layouts.app')
@section('title', 'Purchase Returns (Debit Notes)')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Purchase Returns & Debit Notes</h4>
        <small class="text-secondary">Stage 6: Vendor rejections, damaged goods returns & debt reduction</small>
    </div>
    @if(auth()->user()->hasPermission('purchases.return') || auth()->user()->isShopAdmin() || auth()->user()->isSuperAdmin())
        <a class="btn btn-danger" href="{{ route('purchases.returns.create') }}">
            <i class="bi bi-arrow-return-left me-1"></i> New Purchase Return
        </a>
    @endif
</div>

<div class="card shadow-sm border-0 mb-3 bg-white">
    <div class="card-body py-2 px-3">
        <form method="get" action="{{ route('purchases.returns.index') }}" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control border-start-0" placeholder="Search Return #, supplier, reason..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                @if($search)
                    <a href="{{ route('purchases.returns.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="small text-secondary bg-light">
                <tr>
                    <th class="ps-3">Debit Note #</th>
                    <th>Date</th>
                    <th>Supplier / Vendor</th>
                    <th>Reason</th>
                    <th class="text-end">Returned Value (Rs.)</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $ret)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('purchases.returns.show', $ret) }}" class="fw-bold text-decoration-none">
                                {{ $ret->return_number }}
                            </a>
                            @if($ret->goodsReceivedNote)
                                <div class="small text-muted">GRN: {{ $ret->goodsReceivedNote->grn_number }}</div>
                            @endif
                        </td>
                        <td>{{ $ret->return_date->format('d M, Y') }}</td>
                        <td class="fw-semibold">{{ $ret->supplier->name }}</td>
                        <td>{{ $ret->reason }}</td>
                        <td class="text-end fw-bold text-danger">
                            - Rs. {{ number_format($ret->total_amount, 2) }}
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success">
                                {{ ucfirst($ret->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchases.returns.show', $ret) }}">
                                <i class="bi bi-eye me-1"></i> View Debit Note
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-arrow-return-left fs-3 d-block mb-2"></i>
                            No purchase returns / debit notes recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($returns->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $returns->links() }}
        </div>
    @endif
</div>
@endsection
