@extends('layouts.app')
@section('title', 'Purchase Requisition ' . $requisition->pr_number)
@section('content')
{{-- Printable Official Purchase Requisition Letterhead Header (Print Only) --}}
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
            <span class="badge border border-dark px-3 py-1 fs-6 text-uppercase fw-bold mb-1 d-inline-block">PURCHASE REQUISITION</span>
            <div class="font-monospace fw-bold fs-5 text-dark mt-1">{{ $requisition->pr_number }}</div>
            <div class="small text-secondary">Requisition Date: <strong>{{ $requisition->requisition_date->format('d M, Y') }}</strong></div>
            <div class="small text-secondary">Status: <strong class="text-uppercase">{{ str_replace('_', ' ', $requisition->status) }}</strong></div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold">{{ $requisition->pr_number }}</h5>
                    @php
                        $statusBadges = [
                            'pending_approval' => ['warning', 'Pending Approval'],
                            'approved' => ['success', 'Approved'],
                            'converted_to_po' => ['primary', 'Converted to PO'],
                            'rejected' => ['danger', 'Rejected'],
                            'draft' => ['secondary', 'Draft'],
                        ];
                        $sb = $statusBadges[$requisition->status] ?? ['secondary', ucfirst($requisition->status)];
                    @endphp
                    <span class="badge bg-{{ $sb[0] }}-subtle text-{{ $sb[0] }} fs-6">
                        {{ $sb[1] }}
                    </span>
                </div>
                <div class="d-flex gap-2 d-print-none">
                    <a href="{{ route('purchases.requisitions.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <button class="btn btn-outline-dark btn-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print PR
                    </button>

                    @if($requisition->canBeEditedOrDeleted())
                        <a href="{{ route('purchases.requisitions.edit', $requisition) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                        <form method="post" action="{{ route('purchases.requisitions.destroy', $requisition) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Requisition {{ $requisition->pr_number }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </form>
                    @else
                        @php
                            $disabledReason = $requisition->isRejected() ? 'Requisition has been rejected' : 'Purchase Order already created';
                        @endphp
                        <button class="btn btn-outline-secondary btn-sm opacity-50" disabled title="Cannot edit: {{ $disabledReason }}">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>
                        <button class="btn btn-outline-secondary btn-sm opacity-50" disabled title="Cannot delete: {{ $disabledReason }}">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    @endif

                    {{-- Admin Approval Buttons --}}
                    @if($requisition->isPending() && (auth()->user()->isShopAdmin() || auth()->user()->isSuperAdmin()))
                        <form method="post" action="{{ route('purchases.requisitions.approve', $requisition) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="bi bi-check-circle me-1"></i> Approve PR
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    @endif

                    {{-- Convert to PO Button --}}
                    @if($requisition->isApproved())
                        <form method="post" action="{{ route('purchases.requisitions.convert_po', $requisition) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-cart-plus me-1"></i> Convert to Purchase Order (PO)
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Requisition Date</small>
                        <span class="fw-semibold">{{ $requisition->requisition_date->format('d M, Y') }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Required By Date</small>
                        <span class="fw-semibold">{{ $requisition->required_by_date ? $requisition->required_by_date->format('d M, Y') : 'N/A' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Department / Station</small>
                        <span class="fw-semibold">{{ $requisition->department ?? 'General Store' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Priority</small>
                        <span class="badge bg-light text-dark border text-uppercase">{{ $requisition->priority }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Requested By</small>
                        <span>{{ $requisition->requester?->name ?? 'Staff' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-secondary d-block">Target Warehouse</small>
                        <span>{{ $requisition->warehouse?->name ?? 'Default Store' }}</span>
                    </div>
                    @if($requisition->approved_by)
                        <div class="col-md-3">
                            <small class="text-secondary d-block">Action By</small>
                            <span>{{ $requisition->approver?->name }} ({{ $requisition->approved_at?->format('d M, Y') }})</span>
                        </div>
                    @endif
                </div>

                @if($requisition->rejection_reason)
                    <div class="alert alert-danger py-2 px-3 small">
                        <strong>Rejection Reason:</strong> {{ $requisition->rejection_reason }}
                    </div>
                @endif

                @if($requisition->notes)
                    <div class="alert alert-light border py-2 px-3 mb-4 small">
                        <strong>Notes:</strong> {{ $requisition->notes }}
                    </div>
                @endif

                {{-- Items Table --}}
                <div class="card border mb-3">
                    <div class="card-header bg-light py-2">
                        <span class="fw-semibold small text-uppercase">Requested Items</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="small text-secondary bg-white">
                                <tr>
                                    <th class="ps-3" style="width: 5%;">#</th>
                                    <th style="width: 45%;">Product</th>
                                    <th style="width: 20%;">SKU</th>
                                    <th class="text-end" style="width: 15%;">Required Qty</th>
                                    <th class="pe-3" style="width: 15%;">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalQty = 0; @endphp
                                @foreach($requisition->items as $idx => $line)
                                    @php
                                        $totalQty += (float) $line->quantity;
                                    @endphp
                                    <tr>
                                        <td class="ps-3 text-secondary">{{ $idx + 1 }}</td>
                                        <td class="fw-semibold">{{ $line->inventoryItem->item_name }}</td>
                                        <td><code>{{ $line->inventoryItem->sku }}</code></td>
                                        <td class="text-end fw-bold">{{ number_format($line->quantity, 2) }} {{ $line->inventoryItem->unit }}</td>
                                        <td class="pe-3 text-secondary small">{{ $line->description ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end ps-3">Total Required Quantity:</td>
                                    <td class="text-end text-primary">{{ number_format($totalQty, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Linked Purchase Orders if converted --}}
                @if($requisition->purchaseOrders->isNotEmpty())
                    <div class="card border bg-light d-print-none">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-2"><i class="bi bi-link-45deg me-1"></i> Connected Purchase Orders</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($requisition->purchaseOrders as $linkedPo)
                                    <a href="{{ route('purchases.orders.show', $linkedPo) }}" class="badge bg-white text-dark border p-2 text-decoration-none shadow-xs">
                                        <i class="bi bi-cart-check me-1 text-primary"></i> {{ $linkedPo->po_number }} (Rs. {{ number_format($linkedPo->grand_total, 2) }})
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
                                <strong class="d-block text-dark small text-uppercase">Requested By</strong>
                                <span class="text-secondary" style="font-size: 11px;">{{ $requisition->creator?->name ?? auth()->user()->name }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-top border-dark pt-2 mx-3">
                                <strong class="d-block text-dark small text-uppercase">Department Head</strong>
                                <span class="text-secondary" style="font-size: 11px;">Signature & Stamp</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-top border-dark pt-2 mx-3">
                                <strong class="d-block text-dark small text-uppercase">Management Approval</strong>
                                <span class="text-secondary" style="font-size: 11px;">Purchase Authorization</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-center text-secondary mt-4" style="font-size: 10px;">
                        Generated on {{ now()->format('d M, Y h:i A') }} &bull; DenFin ERP Enterprise Document &bull; Purchase Requisition Slip
                    </div>
                </div>
            </div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('purchases.requisitions.reject', $requisition) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Reject Purchase Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="rejection_reason" rows="3" required placeholder="Specify why this requisition is rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
