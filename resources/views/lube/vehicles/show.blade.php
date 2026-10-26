@extends('layouts.app')
@section('title', 'Vehicle History - ' . $vehicle->plate_number)

@section('content')
<div class="container-fluid px-0">
    {{-- TOP BACK BAR & TITLE --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('lube.vehicles.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Vehicles
            </a>
            <div>
                <h1 class="h4 fw-bold mb-0">
                    <span class="badge bg-dark font-monospace text-uppercase me-2">{{ $vehicle->plate_number }}</span>
                    {{ $vehicle->make }} {{ $vehicle->model }}
                </h1>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('lube.pos') }}?plate={{ $vehicle->plate_number }}" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> New Service For This Car
            </a>
        </div>
    </div>

    {{-- VEHICLE INFO SUMMARY CARD --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <small class="text-muted d-block" style="font-size: 11px;">Vehicle Profile</small>
                    <strong class="d-block text-dark">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year ?? 'N/A' }})</strong>
                    <span class="badge bg-light text-secondary border text-uppercase" style="font-size: 10px;">{{ $vehicle->vehicle_type }}</span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block" style="font-size: 11px;">Customer Contact</small>
                    <strong class="d-block text-dark">{{ $vehicle->customer_name }}</strong>
                    <span class="font-monospace text-muted small"><i class="bi bi-telephone me-1"></i>{{ $vehicle->customer_phone }}</span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block" style="font-size: 11px;">Last Recorded Odometer</small>
                    <span class="font-monospace text-primary fw-bold fs-5">{{ $vehicle->last_odometer ? number_format($vehicle->last_odometer) . ' KM' : '-' }}</span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block" style="font-size: 11px;">Lifetime Bay Visits</small>
                    <span class="font-monospace text-success fw-bold fs-5">{{ $vehicle->service_records_count }} Completed</span>
                </div>
            </div>
        </div>
    </div>

    {{-- TIMELINE OF PAST SERVICES --}}
    <h2 class="h5 fw-bold text-dark mb-3"><i class="bi bi-clock-history text-secondary me-2"></i>Service Timeline &amp; History Log</h2>

    <div class="d-flex flex-column gap-3">
        @forelse($services as $srv)
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-bottom py-2.5 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary font-monospace">{{ $srv->created_at->format('d-M-Y') }}</span>
                        <small class="text-muted">{{ $srv->created_at->format('h:i A') }}</small>
                        @if($srv->sale)
                            <span class="badge bg-light text-dark border font-monospace">Invoice #{{ $srv->sale->invoice_no }}</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($srv->sale)
                            <a href="{{ route('lube.pos.receipt', $srv->sale) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0.5 px-2">
                                <i class="bi bi-receipt me-1"></i> Invoice
                            </a>
                        @endif
                        <a href="{{ route('lube.pos.sticker', $srv) }}" target="_blank" class="btn btn-sm btn-outline-success py-0.5 px-2">
                            <i class="bi bi-sticky me-1"></i> Sticker
                        </a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block" style="font-size: 11px;">Odometer At Service</small>
                            <span class="font-monospace fw-bold fs-6">{{ number_format($srv->current_odometer) }} KM</span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block" style="font-size: 11px;">Oil Viscosity Installed</small>
                            <span class="badge bg-primary-subtle text-primary font-monospace fs-6">{{ $srv->oil_grade }}</span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block" style="font-size: 11px;">Next Service Target</small>
                            <span class="font-monospace text-success fw-bold fs-6">{{ number_format($srv->next_due_odometer) }} KM</span>
                            @if($srv->next_due_date)
                                <small class="text-muted d-block" style="font-size: 11px;">Est: {{ $srv->next_due_date->format('d-M-Y') }}</small>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block" style="font-size: 11px;">Pit Technician</small>
                            <strong class="text-dark">{{ $srv->technician->name ?? 'Unassigned' }}</strong>
                            <small class="text-muted d-block" style="font-size: 11px;">Bay {{ $srv->technician->bay_number ?? '1' }}</small>
                        </div>
                    </div>

                    {{-- Consumables Breakdown --}}
                    @if($srv->sale && $srv->sale->items->count() > 0)
                        <div class="mt-3 pt-3 border-top">
                            <strong class="small text-secondary d-block mb-1">Billed Items &amp; Consumables:</strong>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($srv->sale->items as $it)
                                    <span class="badge bg-light text-dark border p-1.5 font-monospace">
                                        {{ $it->inventoryItem->item_name ?? 'Item' }} 
                                        ({{ number_format($it->quantity, 2) }}) • Rs. {{ number_format($it->line_total, 2) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Inspection Checklist --}}
                    @if(!empty($srv->inspection_checklist))
                        <div class="mt-2 pt-2 border-top">
                            <strong class="small text-secondary d-block mb-1">Pit Checklist Passed:</strong>
                            <div class="d-flex flex-wrap gap-1.5">
                                @foreach($srv->inspection_checklist as $chkKey => $chkVal)
                                    @if($chkVal)
                                        <span class="badge bg-success-subtle text-success py-1 px-2" style="font-size: 11px;">
                                            <i class="bi bi-check2 me-1"></i> {{ ucwords(str_replace('_', ' ', $chkKey)) }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($srv->notes)
                        <div class="mt-2 text-muted small fst-italic">
                            <i class="bi bi-chat-left-text me-1"></i> {{ $srv->notes }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="card border-0 shadow-sm p-4 text-center text-muted" style="border-radius: 12px;">
                No service records on file for this vehicle yet.
            </div>
        @endforelse
    </div>
</div>
@endsection
