@extends('layouts.app')
@section('title', 'Inward Gate Pass ' . $igp->igp_number)
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold">{{ $igp->igp_number }}</h5>
                    @if($igp->status === 'pending_inspection')
                        <span class="badge bg-warning-subtle text-warning-emphasis fs-6">Pending Inspection</span>
                    @elseif($igp->status === 'grn_completed')
                        <span class="badge bg-success-subtle text-success fs-6">GRN Completed</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary fs-6">{{ ucfirst($igp->status) }}</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('purchases.igp.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Pass
                    </button>
                    @if($igp->status === 'pending_inspection')
                        <a href="{{ route('purchases.grn.create', ['inward_gate_pass_id' => $igp->id]) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Proceed to GRN Inward
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Gate Entry Date</small>
                        <span class="fw-semibold">{{ $igp->igp_date->format('d M, Y') }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Entry Time</small>
                        <span class="fw-semibold">{{ $igp->gate_entry_time ?? 'N/A' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Supplier / Vendor</small>
                        <span class="fw-semibold">{{ $igp->supplier->name }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Linked Purchase Order</small>
                        @if($igp->purchaseOrder)
                            <a href="{{ route('purchases.orders.show', $igp->purchaseOrder) }}" class="fw-semibold text-decoration-none">
                                {{ $igp->purchaseOrder->po_number }}
                            </a>
                        @else
                            <span class="text-muted">None (Direct Inward)</span>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Carrier / Received Via</small>
                        <span>{{ $igp->received_via }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Vehicle Number</small>
                        <span class="badge bg-light text-dark border font-monospace">{{ $igp->vehicle_number ?? 'N/A' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Driver</small>
                        <span>{{ $igp->driver_name ?? 'N/A' }} {{ $igp->driver_phone ? "({$igp->driver_phone})" : '' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Gate Security Staff</small>
                        <span>{{ $igp->receiver?->name ?? 'Security' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Bilty / Tracking #</small>
                        <span>{{ $igp->bilty_number ?? 'N/A' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Delivery Challan #</small>
                        <span>{{ $igp->challan_number ?? 'N/A' }}</span>
                    </div>
                </div>

                @if($igp->remarks)
                    <div class="alert alert-light border py-2 px-3 mb-4 small">
                        <strong>Gate Remarks:</strong> {{ $igp->remarks }}
                    </div>
                @endif

                {{-- Items Table --}}
                <div class="card border mb-4">
                    <div class="card-header bg-light py-2">
                        <span class="fw-semibold small text-uppercase">Declared Delivery Cargo</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="small text-secondary bg-white">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th class="text-end">Packages (Cartons/Bags)</th>
                                    <th class="text-end">Declared Qty</th>
                                    <th class="pe-3">Cargo Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($igp->items as $idx => $line)
                                    <tr>
                                        <td class="ps-3 text-secondary">{{ $idx + 1 }}</td>
                                        <td class="fw-semibold">{{ $line->inventoryItem->item_name }}</td>
                                        <td><code>{{ $line->inventoryItem->sku }}</code></td>
                                        <td class="text-end">{{ number_format($line->packages_count, 0) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($line->declared_quantity, 2) }} {{ $line->inventoryItem->unit }}</td>
                                        <td class="pe-3 text-secondary small">{{ $line->remarks ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Linked Goods Received Notes --}}
                @if($igp->goodsReceivedNotes->isNotEmpty())
                    <div class="card border bg-light">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-2"><i class="bi bi-box-arrow-in-down me-1"></i> Generated Goods Received Notes (GRN)</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($igp->goodsReceivedNotes as $grn)
                                    <a href="{{ route('purchases.grn.show', $grn) }}" class="badge bg-white text-dark border p-2 text-decoration-none shadow-xs">
                                        <i class="bi bi-check-circle me-1 text-success"></i> {{ $grn->grn_number }} (Rs. {{ number_format($grn->total_amount, 2) }})
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
