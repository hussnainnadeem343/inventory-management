@extends('layouts.app')
@section('title', 'Oil Due Reminders & CRM')

@section('content')
<div class="container-fluid px-0">
    {{-- HEADER --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-bell text-danger me-2"></i>Oil Due Reminders &amp; Retention CRM</h1>
            <p class="text-muted small mb-0">Follow up with customers whose vehicles are approaching or past their oil change interval.</p>
        </div>
        <div>
            <a href="{{ route('lube.pos') }}" class="btn btn-primary fw-semibold">
                <i class="bi bi-cart3 me-1"></i> Open Lube POS Counter
            </a>
        </div>
    </div>

    {{-- STATUS TABS --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2">
            <ul class="nav nav-pills gap-2">
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ $filter === 'due_soon' ? 'active' : '' }}" href="{{ route('lube.reminders.index', ['tab' => 'due_soon']) }}">
                        <i class="bi bi-clock-history me-1"></i> Due Soon (Next 14 Days)
                        <span class="badge bg-white text-dark ms-1.5 font-monospace">{{ $dueSoonCount }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ $filter === 'overdue' ? 'active' : '' }} text-danger" href="{{ route('lube.reminders.index', ['tab' => 'overdue']) }}">
                        <i class="bi bi-exclamation-triangle me-1"></i> Overdue Vehicles
                        <span class="badge bg-danger text-white ms-1.5 font-monospace">{{ $overdueCount }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ $filter === 'contacted' ? 'active' : '' }}" href="{{ route('lube.reminders.index', ['tab' => 'contacted']) }}">
                        <i class="bi bi-check2-all me-1"></i> Contacted Customers
                        <span class="badge bg-secondary text-white ms-1.5 font-monospace">{{ $contactedCount }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- REMINDERS TABLE --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th>Plate #</th>
                        <th>Vehicle &amp; Customer</th>
                        <th>Mobile Number</th>
                        <th>Last Visited At</th>
                        <th>Target Mileage</th>
                        <th>Estimated Date</th>
                        <th>Status</th>
                        <th class="text-end">Follow-Up Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reminders as $srv)
                        @php
                            $phoneClean = preg_replace('/[^0-9]/', '', $srv->vehicle->customer_phone ?? '');
                            if (str_starts_with($phoneClean, '03')) {
                                $phoneClean = '92' . substr($phoneClean, 1);
                            }
                            $msgText = "Salam " . ($srv->vehicle->customer_name ?? 'Sir') . "! Reminder from " . ($shop->name ?? 'DenFin Auto Lube') . ": Your vehicle " . ($srv->vehicle->plate_number ?? '') . " (" . ($srv->vehicle->make ?? '') . " " . ($srv->vehicle->model ?? '') . ") is due for an oil service at " . number_format($srv->next_due_odometer) . " KM. Visit our service bay this week to keep your engine running smooth!";
                            $waUrl = "https://api.whatsapp.com/send?phone={$phoneClean}&text=" . urlencode($msgText);
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace text-uppercase fs-6">{{ $srv->vehicle->plate_number ?? '-' }}</span>
                            </td>
                            <td>
                                <strong class="d-block text-dark">{{ $srv->vehicle->make ?? '' }} {{ $srv->vehicle->model ?? '' }}</strong>
                                <small class="text-muted">{{ $srv->vehicle->customer_name ?? '' }}</small>
                            </td>
                            <td>
                                <span class="font-monospace fw-semibold"><i class="bi bi-telephone text-success me-1"></i>{{ $srv->vehicle->customer_phone ?? 'N/A' }}</span>
                            </td>
                            <td class="font-monospace text-muted small">
                                {{ number_format($srv->current_odometer) }} KM<br>
                                <span style="font-size: 11px;">{{ $srv->created_at->format('d-M-Y') }}</span>
                            </td>
                            <td class="font-monospace text-primary fw-bold">
                                {{ number_format($srv->next_due_odometer) }} KM
                            </td>
                            <td class="small font-monospace">
                                @if($srv->next_due_date)
                                    <span class="{{ $srv->next_due_date->isPast() ? 'text-danger fw-bold' : 'text-dark' }}">
                                        {{ $srv->next_due_date->format('d-M-Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($srv->next_due_date && $srv->next_due_date->isPast())
                                    <span class="badge bg-danger-subtle text-danger">Overdue ({{ $srv->next_due_date->diffForHumans() }})</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis">Due Soon</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1.5">
                                    @if($srv->vehicle && $srv->vehicle->customer_phone)
                                        <a href="{{ $waUrl }}" target="_blank" class="btn btn-sm btn-success fw-semibold py-1 px-2.5">
                                            <i class="bi bi-whatsapp me-1"></i> WhatsApp
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('lube.reminders.contacted', $srv) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Mark as Contacted">
                                            <i class="bi bi-check2"></i> Mark Contacted
                                        </button>
                                    </form>
                                    <a href="{{ route('lube.pos') }}?plate={{ $srv->vehicle->plate_number ?? '' }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="Start Service Ticket">
                                        <i class="bi bi-plus-lg"></i> Service
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-check2-circle text-success fs-3 d-block mb-1"></i>
                                No reminders in this category. All customer vehicles are up-to-date!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reminders->hasPages())
            <div class="card-footer bg-white border-top py-2.5">
                {{ $reminders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
