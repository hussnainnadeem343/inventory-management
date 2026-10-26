@extends('layouts.app')
@section('title', 'Station Analytics')

@section('content')
<div class="container-fluid px-0">
    {{-- HEADER BAR --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-speedometer2 text-primary me-2"></i>Station Analytics &amp; Operations</h1>
            <p class="text-muted small mb-0">Live throughput, dispensed oil volume, bay technicians, and service performance.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('lube.pos') }}" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> Open Lube POS Counter
            </a>
            <a href="{{ route('lube.vehicles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-car-front me-1"></i> Vehicle History
            </a>
        </div>
    </div>

    {{-- TOP 5 KPI METRICS --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm h-100 p-3" style="border-radius: 12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Vehicles Serviced</span>
                    <span class="rounded-3 bg-primary-subtle text-primary p-2"><i class="bi bi-car-front fs-5"></i></span>
                </div>
                <h3 class="h4 fw-bold mb-0 font-monospace text-primary">{{ number_format($todayServicesCount) }}</h3>
                <small class="text-muted" style="font-size: 11px;">Today's tickets</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-4 col-6">
            <div class="card border-0 shadow-sm h-100 p-3" style="border-radius: 12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Today's Revenue</span>
                    <span class="rounded-3 bg-success-subtle text-success p-2"><i class="bi bi-cash-stack fs-5"></i></span>
                </div>
                <h3 class="h4 fw-bold mb-0 font-monospace text-success">Rs. {{ number_format($todayRevenue, 2) }}</h3>
                <small class="text-muted" style="font-size: 11px;">Consumables &amp; Labor</small>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm h-100 p-3" style="border-radius: 12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Liters Dispensed</span>
                    <span class="rounded-3 bg-info-subtle text-info p-2"><i class="bi bi-fuel-pump fs-5"></i></span>
                </div>
                <h3 class="h4 fw-bold mb-0 font-monospace text-info">{{ number_format($todayLitersDispensed, 2) }} L</h3>
                <small class="text-muted" style="font-size: 11px;">Engine oil &amp; fluids</small>
            </div>
        </div>

        <div class="col-xl-2 col-md-6 col-6">
            <div class="card border-0 shadow-sm h-100 p-3" style="border-radius: 12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Active Techs</span>
                    <span class="rounded-3 bg-warning-subtle text-warning-emphasis p-2"><i class="bi bi-people fs-5"></i></span>
                </div>
                <h3 class="h4 fw-bold mb-0 font-monospace text-dark">{{ number_format($activeTechsCount) }}</h3>
                <small class="text-muted" style="font-size: 11px;">Bay mechanics</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-12">
            <div class="card border-0 shadow-sm h-100 p-3" style="border-radius: 12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Overdue Reminders</span>
                    <span class="rounded-3 bg-danger-subtle text-danger p-2"><i class="bi bi-bell fs-5"></i></span>
                </div>
                <h3 class="h4 fw-bold mb-0 font-monospace text-danger">{{ number_format($overdueCount) }}</h3>
                <small class="text-muted" style="font-size: 11px;">
                    <a href="{{ route('lube.reminders.index') }}" class="text-decoration-none text-danger fw-semibold">Send WhatsApp reminders &rarr;</a>
                </small>
            </div>
        </div>
    </div>

    {{-- MIDDLE ROW: Oil Grades & Tech Leaderboard --}}
    <div class="row g-3 mb-4">
        {{-- Popular Oil Viscosities --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <strong class="text-dark"><i class="bi bi-droplet-half text-primary me-2"></i>Popular Oil Viscosities Dispensed</strong>
                    <span class="badge bg-light text-secondary">All-Time Breakdown</span>
                </div>
                <div class="card-body p-3">
                    @php $totalGradeServices = $gradeBreakdown->sum('count'); @endphp
                    @forelse($gradeBreakdown as $grade)
                        @php $pct = $totalGradeServices > 0 ? round(($grade->count / $totalGradeServices) * 100, 1) : 0; @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-semibold mb-1">
                                <span><span class="badge bg-primary-subtle text-primary font-monospace me-2">{{ $grade->oil_grade }}</span></span>
                                <span class="font-monospace text-muted">{{ $grade->count }} cars ({{ $pct }}%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4 small">No oil service data logged yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Top Pit Technicians Leaderboard --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <strong class="text-dark"><i class="bi bi-trophy text-warning me-2"></i>Pit Crew Leaderboard (Last 30 Days)</strong>
                    <a href="{{ route('lube.commissions.index') }}" class="small text-decoration-none">Commissions &rarr;</a>
                </div>
                <div class="card-body p-3">
                    @forelse($technicianStats as $tech)
                        <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 mb-2 bg-light">
                            <div class="d-flex align-items-center gap-3">
                                <span class="rounded-circle bg-white border text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    {{ substr($tech->name, 0, 1) }}
                                </span>
                                <div>
                                    <strong class="text-dark d-block mb-0">{{ $tech->name }}</strong>
                                    <small class="text-muted">Bay {{ $tech->bay_number ?? '1' }} • {{ $tech->phone ?? 'No phone' }}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success-subtle text-success font-monospace">{{ $tech->service_records_count }} Jobs</span>
                                <small class="text-muted d-block font-monospace mt-0.5" style="font-size: 11px;">Rs. {{ number_format($tech->service_records_sum_technician_commission ?? 0) }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4 small">No technicians registered yet. <a href="{{ route('lube.technicians.index') }}">Add Pit Tech</a></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- RECENT SERVICES LOG TABLE --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <strong class="text-dark"><i class="bi bi-clock-history text-secondary me-2"></i>Recent Station Services</strong>
            <a href="{{ route('lube.vehicles.index') }}" class="btn btn-sm btn-outline-primary">View Full History</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Plate #</th>
                        <th>Vehicle &amp; Customer</th>
                        <th>Odometer</th>
                        <th>Oil Grade</th>
                        <th>Next Target</th>
                        <th>Technician</th>
                        <th>Invoice</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentServices as $srv)
                        <tr>
                            <td class="small text-muted">{{ $srv->created_at->format('d-M-Y H:i') }}</td>
                            <td>
                                <span class="badge bg-dark font-monospace text-uppercase">{{ $srv->vehicle->plate_number ?? '-' }}</span>
                            </td>
                            <td>
                                <strong class="d-block text-dark">{{ $srv->vehicle->make ?? '' }} {{ $srv->vehicle->model ?? '' }}</strong>
                                <small class="text-muted">{{ $srv->vehicle->customer_name ?? '' }} ({{ $srv->vehicle->customer_phone ?? '' }})</small>
                            </td>
                            <td class="font-monospace fw-semibold">{{ number_format($srv->current_odometer) }} KM</td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary font-monospace">{{ $srv->oil_grade }}</span>
                            </td>
                            <td class="font-monospace text-success fw-bold">
                                {{ number_format($srv->next_due_odometer) }} KM
                                @if($srv->next_due_date)
                                    <small class="text-muted d-block fw-normal" style="font-size: 11px;">{{ $srv->next_due_date->format('d-M-Y') }}</small>
                                @endif
                            </td>
                            <td>{{ $srv->technician->name ?? 'Unassigned' }}</td>
                            <td>
                                @if($srv->sale)
                                    <a href="{{ route('lube.pos.receipt', $srv->sale) }}" target="_blank" class="badge bg-light text-primary border text-decoration-none">
                                        {{ $srv->sale->invoice_no }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('lube.pos.sticker', $srv) }}" target="_blank" class="btn btn-sm btn-outline-success py-0.5 px-2" title="Print Sticker">
                                    <i class="bi bi-sticky"></i> Sticker
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No vehicle services logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
