@extends('layouts.app')
@section('title', 'Mechanic Commissions')

@section('content')
<div class="container-fluid px-0">
    {{-- HEADER --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-wallet2 text-success me-2"></i>Mechanic Commissions &amp; Bay Payouts</h1>
            <p class="text-muted small mb-0">Track job-by-job commission earnings and settle wage payouts for pit technicians.</p>
        </div>
        <div>
            <button type="button" class="btn btn-success fw-semibold" data-bs-toggle="modal" data-bs-target="#bulkSettleModal">
                <i class="bi bi-cash-coin me-1"></i> Settle Mechanic Payout
            </button>
        </div>
    </div>

    {{-- 4 SUMMARY METRIC CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm p-3" style="border-radius: 12px;">
                <span class="text-muted small fw-semibold d-block mb-1">Total Labor Generated</span>
                <h3 class="h4 fw-bold text-dark font-monospace mb-0">Rs. {{ number_format($totalLabor, 2) }}</h3>
                <small class="text-muted" style="font-size: 11px;">Pit service billing</small>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm p-3" style="border-radius: 12px;">
                <span class="text-muted small fw-semibold d-block mb-1">Total Commission Accrued</span>
                <h3 class="h4 fw-bold text-primary font-monospace mb-0">Rs. {{ number_format($totalCommissionDue, 2) }}</h3>
                <small class="text-muted" style="font-size: 11px;">Lifetime earned</small>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm p-3" style="border-radius: 12px;">
                <span class="text-muted small fw-semibold d-block mb-1">Commission Paid Out</span>
                <h3 class="h4 fw-bold text-success font-monospace mb-0">Rs. {{ number_format($totalCommissionPaid, 2) }}</h3>
                <small class="text-muted" style="font-size: 11px;">Settled to mechanics</small>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm p-3" style="border-radius: 12px;">
                <span class="text-muted small fw-semibold d-block mb-1">Pending Payout Balance</span>
                <h3 class="h4 fw-bold text-danger font-monospace mb-0">Rs. {{ number_format($pendingCommission, 2) }}</h3>
                <small class="text-muted" style="font-size: 11px;">Unpaid balance</small>
            </div>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('lube.commissions.index') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="technician_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Technicians --</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" @selected(request('technician_id') == $tech->id)>{{ $tech->name }} (Bay {{ $tech->bay_number ?? '1' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Statuses --</option>
                        <option value="pending" @selected(request('status') === 'pending')>Unpaid / Pending</option>
                        <option value="paid" @selected(request('status') === 'paid')>Settled / Paid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To Date">
                </div>
                <div class="col-md-2 text-md-end">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                    <a href="{{ route('lube.commissions.index') }}" class="btn btn-sm btn-light border">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- COMMISSION JOBS TABLE --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th>Date</th>
                        <th>Invoice #</th>
                        <th>Vehicle Plate</th>
                        <th>Pit Mechanic</th>
                        <th class="text-end">Labor Amount</th>
                        <th class="text-end">Commission</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                        <tr>
                            <td class="small text-muted">{{ $rec->created_at->format('d-M-Y H:i') }}</td>
                            <td>
                                @if($rec->sale)
                                    <a href="{{ route('lube.pos.receipt', $rec->sale) }}" target="_blank" class="badge bg-light text-primary border text-decoration-none">
                                        {{ $rec->sale->invoice_no }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-dark font-monospace text-uppercase">{{ $rec->vehicle->plate_number ?? '-' }}</span>
                            </td>
                            <td>
                                <strong class="text-dark">{{ $rec->technician->name ?? '-' }}</strong>
                                <small class="text-muted d-block" style="font-size: 11px;">Bay {{ $rec->technician->bay_number ?? '1' }}</small>
                            </td>
                            <td class="text-end font-monospace">
                                Rs. {{ number_format($rec->labor_amount, 2) }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-success fs-6">
                                Rs. {{ number_format($rec->technician_commission, 2) }}
                            </td>
                            <td class="text-center">
                                @if($rec->commission_status === 'paid')
                                    <span class="badge bg-success-subtle text-success">Paid</span>
                                    @if($rec->commission_paid_at)
                                        <small class="text-muted d-block" style="font-size: 10px;">{{ $rec->commission_paid_at->format('d-M') }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($rec->commission_status === 'pending')
                                    <form method="POST" action="{{ route('lube.commissions.pay', $rec) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success py-0.5 px-2.5">
                                            <i class="bi bi-check2 me-1"></i> Mark as Paid
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small"><i class="bi bi-check-all text-success"></i> Settled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No commission records found for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="card-footer bg-white border-top py-2.5">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL: BULK SETTLE PAYOUT --}}
<div class="modal fade" id="bulkSettleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6"><i class="bi bi-cash-coin text-success me-2"></i>Settle Technician Commission Payout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('lube.commissions.bulk_pay') }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">Mark all outstanding pending commission records as <strong>Paid</strong> for the selected pit technician.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Pit Technician <span class="text-danger">*</span></label>
                        <select name="technician_id" class="form-select form-select-sm" required>
                            <option value="">-- Choose Mechanic --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }} (Bay {{ $tech->bay_number ?? '1' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-semibold">Mark All Pending as Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
