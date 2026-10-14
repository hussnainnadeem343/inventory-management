@extends('layouts.app')
@section('title', 'Inward Gate Pass ' . $igp->igp_number)
@section('content')
{{-- Printable Official Inward Gate Pass Letterhead Header (Print Only) --}}
<div class="d-none d-print-block mb-4 pb-3 border-bottom">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h2 class="h4 fw-bold mb-1 text-dark">{{ auth()->user()->shop ? auth()->user()->shop->name : 'DenFin ERP' }}</h2>
            <div class="text-secondary small">
                @if(auth()->user()->shop && auth()->user()->shop->address)
                    <div><i class="bi bi-geo-alt me-1"></i>{{ auth()->user()->shop->address }}</div>
                @endif
                @if(auth()->user()->shop && auth()->user()->shop->phone)
                    <div><i class="bi bi-telephone me-1"></i>Phone: {{ auth()->user()->shop->phone }}</div>
                @endif
                <div>Branch Code: <strong>{{ auth()->user()->shop ? auth()->user()->shop->code : 'MAIN' }}</strong></div>
            </div>
        </div>
        <div class="text-end">
            <span class="badge border border-dark px-3 py-1 fs-6 text-uppercase fw-bold mb-1 d-inline-block">INWARD GATE PASS (IGP)</span>
            <div class="font-monospace fw-bold fs-5 text-dark mt-1">{{ $igp->igp_number }}</div>
            <div class="small text-secondary">Gate Entry Date: <strong>{{ $igp->igp_date->format('d M, Y') }}</strong></div>
            <div class="small text-secondary">Gate Status: <strong class="text-uppercase">{{ str_replace('_', ' ', $igp->status) }}</strong></div>
        </div>
    </div>
</div>

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
                <div class="d-flex gap-2 d-print-none">
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
                                    <th class="text-end">Packages</th>
                                    <th class="text-end">Declared Qty</th>
                                    <th class="text-end text-danger">Rejected Qty</th>
                                    <th class="text-end text-primary">Net Inward Qty</th>
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
                                        <td class="text-end">{{ number_format($line->declared_quantity, 2) }} {{ $line->inventoryItem->unit }}</td>
                                        <td class="text-end text-danger">{{ $line->rejected_quantity > 0 ? number_format($line->rejected_quantity, 2) : '0.00' }}</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($line->net_accepted_quantity, 2) }} {{ $line->inventoryItem->unit }}</td>
                                        <td class="pe-3 text-secondary small">{{ $line->remarks ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Linked Goods Received Notes --}}
                @if($igp->goodsReceivedNotes->isNotEmpty())
                    <div class="card border bg-light mb-4 d-print-none">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-2"><i class="bi bi-box-arrow-in-down me-1"></i> Generated Goods Received Notes (GRN)</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($igp->goodsReceivedNotes as $grn)
                                    <a href="{{ route('purchases.grn.show', $grn) }}" class="badge bg-white text-dark border p-2 text-decoration-none shadow-xs">
                                        <i class="bi bi-check-circle me-1 text-success"></i> {{ $grn->grn_number }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Printable Official Authorization Signatures Footer (Print Only) --}}
                <div class="d-none d-print-block print-signature-block">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-top border-dark pt-2 mx-3">
                                <strong class="d-block text-dark small text-uppercase">Gate Security Officer</strong>
                                <span class="text-secondary" style="font-size: 11px;">{{ $igp->receiver?->name ?? 'Security Incharge' }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-top border-dark pt-2 mx-3">
                                <strong class="d-block text-dark small text-uppercase">Driver / Carrier Sign</strong>
                                <span class="text-secondary" style="font-size: 11px;">{{ $igp->driver_name ?? 'Carrier Acknowledgment' }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-top border-dark pt-2 mx-3">
                                <strong class="d-block text-dark small text-uppercase">Warehouse Receiving</strong>
                                <span class="text-secondary" style="font-size: 11px;">Store Officer Signature</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-center text-secondary mt-4" style="font-size: 10px;">
                        Generated on {{ now()->format('d M, Y h:i A') }} &bull; DenFin ERP Enterprise Document &bull; Inward Material Gate Pass
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
