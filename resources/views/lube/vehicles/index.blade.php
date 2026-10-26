@extends('layouts.app')
@section('title', 'Vehicle History & Digital Service Book')

@section('content')
<div class="container-fluid px-0">
    {{-- HEADER --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-car-front text-primary me-2"></i>Customer Vehicles Registry</h1>
            <p class="text-muted small mb-0">Search registered vehicles and inspect complete digital service histories.</p>
        </div>
        <div>
            <a href="{{ route('lube.pos') }}" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-lg me-1"></i> New Service Ticket
            </a>
        </div>
    </div>

    {{-- SEARCH & FILTER BAR --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('lube.vehicles.index') }}" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by Plate (LEA-1234), Mobile Number, or Customer Name..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="vehicle_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Vehicle Types --</option>
                        <option value="sedan" @selected(request('vehicle_type') === 'sedan')>Cars / Sedans</option>
                        <option value="suv" @selected(request('vehicle_type') === 'suv')>SUVs / Crossovers</option>
                        <option value="commercial" @selected(request('vehicle_type') === 'commercial')>Commercial / Vans</option>
                        <option value="bike" @selected(request('vehicle_type') === 'bike')>Motorcycles</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Search</button>
                    <a href="{{ route('lube.vehicles.index') }}" class="btn btn-sm btn-light border">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- VEHICLES TABLE --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th>Plate #</th>
                        <th>Vehicle Details</th>
                        <th>Customer Contact</th>
                        <th>Last Odometer</th>
                        <th>Last Visited</th>
                        <th>Last Oil Grade</th>
                        <th class="text-center">Total Visits</th>
                        <th class="text-end">Service History</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $veh)
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace text-uppercase fs-6">{{ $veh->plate_number }}</span>
                            </td>
                            <td>
                                <strong class="d-block text-dark">{{ $veh->make }} {{ $veh->model }}</strong>
                                <small class="text-muted text-uppercase" style="font-size: 11px;">{{ $veh->vehicle_type }} {{ $veh->year ? '• ' . $veh->year : '' }}</small>
                            </td>
                            <td>
                                <strong class="d-block text-dark">{{ $veh->customer_name }}</strong>
                                <span class="font-monospace text-muted small"><i class="bi bi-telephone me-1"></i>{{ $veh->customer_phone }}</span>
                            </td>
                            <td class="font-monospace fw-semibold">
                                {{ $veh->last_odometer ? number_format($veh->last_odometer) . ' KM' : '-' }}
                            </td>
                            <td class="small text-muted">
                                {{ $veh->last_service_date ? $veh->last_service_date->format('d-M-Y') : 'Never' }}
                            </td>
                            <td>
                                @if($veh->latestService)
                                    <span class="badge bg-primary-subtle text-primary font-monospace">{{ $veh->latestService->oil_grade }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border font-monospace">{{ $veh->service_records_count }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('lube.vehicles.show', $veh) }}" class="btn btn-sm btn-outline-primary fw-semibold py-1 px-2.5">
                                    <i class="bi bi-journal-text me-1"></i> View History
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No vehicles found matching the criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vehicles->hasPages())
            <div class="card-footer bg-white border-top py-2.5">
                {{ $vehicles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
