@extends('layouts.app')
@section('title', 'Stock Transfers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Inter-Warehouse Stock Transfers</h4>
        <small class="text-secondary">Transfer inventory between shops and godowns</small>
    </div>
    <a class="btn btn-primary" href="{{ route('transfers.create') }}">
        <i class="bi bi-arrow-left-right me-1"></i> New Transfer
    </a>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-6 col-md-8">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Transfer number...">
        </div>
        <div class="col-lg-6 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('transfers.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Transfer #</th>
                    <th>Date</th>
                    <th>From Warehouse</th>
                    <th>To Warehouse</th>
                    <th>Items Count</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $trf)
                    <tr>
                        <td>
                            <a href="{{ route('transfers.show', $trf) }}" class="fw-bold font-monospace text-decoration-none">
                                {{ $trf->transfer_number }}
                            </a>
                        </td>
                        <td>{{ $trf->transfer_date->format('d M Y') }}</td>
                        <td><span class="badge bg-danger-subtle text-danger">{{ $trf->fromWarehouse->name }}</span></td>
                        <td><span class="badge bg-success-subtle text-success">{{ $trf->toWarehouse->name }}</span></td>
                        <td>{{ $trf->items->count() }} items</td>
                        <td>
                            <span class="badge bg-success-subtle text-success">Completed</span>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('transfers.show', $trf) }}">
                                <i class="bi bi-eye me-1"></i> View Note
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No stock transfers recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transfers->hasPages())
        <div class="p-3 border-top">
            {{ $transfers->links() }}
        </div>
    @endif
</div>
@endsection
