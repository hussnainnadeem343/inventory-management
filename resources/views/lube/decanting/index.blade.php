@extends('layouts.app')
@section('title', 'Drum Decanting')

@section('content')
<div class="container-fluid px-0">
    {{-- HEADER --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-fuel-pump text-primary me-2"></i>Drum Decanting &amp; Loose Stock</h1>
            <p class="text-muted small mb-0">Decant purchased bulk 208L oil drums into dispensed loose liters with residue tracking.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#decantModal">
                <i class="bi bi-plus-lg me-1"></i> Decant Drum to Liters
            </button>
        </div>
    </div>

    {{-- INFO BANNER --}}
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-3 mb-4" style="border-radius: 12px;">
        <span class="rounded-circle bg-white text-info p-2.5 fs-5 d-flex align-items-center justify-content-center shadow-sm">
            <i class="bi bi-info-circle-fill"></i>
        </span>
        <div class="small">
            <strong>How Drum Decanting Works:</strong> When you purchase large 208-liter or 50-liter oil drums, use this tool to "pour" them into your Loose Liter inventory SKU. It automatically decrements your drum stock, increments loose liters stock, and computes the exact cost per liter for accurate POS profit margins.
        </div>
    </div>

    {{-- DECANTINGS LOG TABLE --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <strong class="text-dark"><i class="bi bi-journal-text me-2"></i>Decanting Operations Log</strong>
            <span class="badge bg-light text-secondary border font-monospace">{{ $decantings->total() }} Batches</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Source Bulk Drum</th>
                        <th class="text-center">Drums Used</th>
                        <th>Destination Loose SKU</th>
                        <th class="text-end">Liters Yielded</th>
                        <th class="text-end">Residue / Shrinkage</th>
                        <th class="text-end">Cost / Liter</th>
                        <th class="text-end">Selling / Liter</th>
                        <th>Operator</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($decantings as $dec)
                        <tr>
                            <td class="small text-muted">{{ $dec->created_at->format('d-M-Y H:i') }}</td>
                            <td>
                                <strong class="d-block text-dark">{{ $dec->drumItem->item_name ?? 'Drum' }}</strong>
                                <small class="text-muted font-monospace">SKU: {{ $dec->drumItem->sku ?? '-' }}</small>
                            </td>
                            <td class="text-center font-monospace fw-semibold">
                                <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ number_format($dec->drums_used, 2) }}</span>
                            </td>
                            <td>
                                <strong class="d-block text-dark">{{ $dec->looseItem->item_name ?? 'Loose Oil' }}</strong>
                                <small class="text-muted font-monospace">SKU: {{ $dec->looseItem->sku ?? '-' }}</small>
                            </td>
                            <td class="text-end font-monospace fw-bold text-success">
                                {{ number_format($dec->liters_produced, 2) }} L
                            </td>
                            <td class="text-end font-monospace text-danger small">
                                {{ $dec->shrinkage_liters > 0 ? number_format($dec->shrinkage_liters, 2) . ' L' : '-' }}
                            </td>
                            <td class="text-end font-monospace fw-semibold">
                                Rs. {{ number_format($dec->cost_per_liter, 2) }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-primary">
                                Rs. {{ number_format($dec->selling_price_per_liter ?? 0, 2) }}
                            </td>
                            <td class="small text-secondary">
                                <i class="bi bi-person me-1"></i>{{ $dec->creator->name ?? 'Admin' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No drum decanting records logged yet. Click <strong>Decant Drum to Liters</strong> above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($decantings->hasPages())
            <div class="card-footer bg-white border-top py-2.5">
                {{ $decantings->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL: DECANT DRUM --}}
<div class="modal fade" id="decantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6"><i class="bi bi-fuel-pump text-primary me-2"></i>Decant Bulk Drum to Loose Liters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('lube.decanting.store') }}" id="decantingForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Source Bulk Drum in Stock <span class="text-danger">*</span></label>
                        <select name="drum_item_id" id="modalDrumSelect" class="form-select form-select-sm" required>
                            <option value="">Choose Drum SKU (Only Drum Products)</option>
                            @forelse($drumProducts as $prod)
                                @php $drumCap = (float) ($prod->liters ?? $prod->pack_size ?? 280); @endphp
                                <option value="{{ $prod->id }}"
                                        data-name="{{ $prod->item_name }}"
                                        data-liters="{{ $drumCap }}"
                                        data-stock="{{ $prod->quantity }}"
                                        data-unit="{{ $prod->unit }}"
                                        data-cost="{{ $prod->purchase_price ?? 0 }}"
                                        data-price="{{ $prod->selling_price ?? 0 }}">
                                    🛢️ {{ $prod->item_name }} ({{ number_format($drumCap, 0) }}L | Available: {{ $prod->quantity }} {{ $prod->unit }} | Sale: Rs. {{ number_format($prod->selling_price, 2) }})
                                </option>
                            @empty
                                <option value="" disabled>⚠️ No bulk drum products in stock! Please add a product with type 'Bulk Drum'.</option>
                            @endforelse
                        </select>
                        <div class="form-text" style="font-size: 11px;">
                            Only products defined with <strong>Product Type = Drum</strong> appear here.
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Destination Loose Liters SKU <span class="text-danger">*</span></label>
                            <a href="{{ route('products.create') }}" target="_blank" class="small text-decoration-none" style="font-size: 11px;">+ New Loose Product</a>
                        </div>
                        <select name="loose_item_id" id="modalLooseSelect" class="form-select form-select-sm" required>
                            <option value="">Choose Loose Oil SKU (Only Loose Products)</option>
                            @forelse($looseProducts as $prod)
                                @php $looseVol = (float) ($prod->liters ?? $prod->pack_size ?? 1); @endphp
                                <option value="{{ $prod->id }}"
                                        data-name="{{ $prod->item_name }}"
                                        data-liters="{{ $looseVol }}"
                                        data-stock="{{ $prod->quantity }}"
                                        data-unit="{{ $prod->unit ?? 'LITER' }}"
                                        data-cost="{{ $prod->purchase_price ?? 0 }}"
                                        data-price="{{ $prod->selling_price ?? 0 }}">
                                    🪣 {{ $prod->item_name }} ({{ number_format($looseVol, 0) }}L | Current Stock: {{ $prod->quantity }} {{ $prod->unit ?? 'L' }})
                                </option>
                            @empty
                                <option value="" disabled>⚠️ No loose products found! Please add a product with type 'Loose Product'.</option>
                            @endforelse
                        </select>
                        <div class="form-text" style="font-size: 11px;">
                            Only products defined with <strong>Product Type = Loose</strong> appear here.
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Drums to Decant <span class="text-danger">*</span></label>
                            <input type="number" name="drums_used" id="modalDrumsUsed" class="form-control form-control-sm font-monospace text-center fw-bold" value="1.00" step="any" min="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Usable Liters Dispensed <span class="text-danger">*</span></label>
                            <input type="number" name="liters_produced" id="modalLitersProduced" class="form-control form-control-sm font-monospace text-center fw-bold text-success" value="280.00" step="any" min="0.1" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Shrinkage / Residue (L)</label>
                            <input type="number" name="shrinkage_liters" id="modalShrinkage" class="form-control form-control-sm font-monospace text-danger text-center" value="0.00" step="any" min="0">
                            <small class="text-muted" style="font-size: 10px;">Sludge / pipe residue (e.g. 0 or 30L).</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-primary">Selling Price / L (Rs.)</label>
                            <input type="number" name="selling_price_per_liter" id="modalSellingPrice" class="form-control form-control-sm font-monospace text-primary text-center fw-bold" value="0" step="any" min="0">
                            <small class="text-muted" style="font-size: 10px;">Calculated from Drum Selling Price.</small>
                        </div>
                    </div>

                    {{-- LIVE REAL-TIME CALCULATION & STOCK PREVIEW --}}
                    <div class="card border border-primary-subtle bg-light p-2.5 mb-3" style="border-radius: 10px;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="small text-uppercase text-primary d-flex align-items-center gap-1" style="font-size: 11px;">
                                <i class="bi bi-calculator"></i> Live Decanting &amp; Stock Preview
                            </strong>
                            <span class="badge bg-primary-subtle text-primary" style="font-size: 10px;">Auto-Computed</span>
                        </div>
                        <div class="row g-2 small font-monospace">
                            <div class="col-6 border-end">
                                <span class="text-muted d-block" style="font-size: 10px;">LOOSE OIL STOCK (DESTINATION):</span>
                                <div style="font-size: 11.5px;">Current: <strong id="previewCurrentLoose">0.00</strong> L</div>
                                <div style="font-size: 11.5px;">+ Yield: <strong class="text-success" id="previewProducedLiters">250.00</strong> L</div>
                                <div class="border-top pt-1 mt-1 text-success fw-bold" style="font-size: 12px;">
                                    = Available: <span id="previewNewLooseStock">250.00</span> L
                                </div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 10px;">BULK DRUM STOCK (SOURCE):</span>
                                <div style="font-size: 11.5px;">Current: <strong id="previewCurrentDrum">1.00</strong></div>
                                <div style="font-size: 11.5px;">- Decanted: <strong class="text-danger" id="previewUsedDrums">1.00</strong></div>
                                <div class="border-top pt-1 mt-1 text-danger fw-bold" style="font-size: 12px;">
                                    = Remaining: <span id="previewNewDrumStock">0.00</span>
                                </div>
                            </div>
                            <div class="col-12 border-top pt-2 mt-1">
                                <div class="row g-1 text-center">
                                    <div class="col-6">
                                        <div class="p-1.5 bg-white border rounded">
                                            <small class="text-muted d-block" style="font-size: 10px;">COST PER LITER</small>
                                            <strong class="text-dark font-monospace" id="previewCostPerLiter">Rs. 0.00</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-1.5 bg-white border rounded">
                                            <small class="text-muted d-block" style="font-size: 10px;">SELLING PRICE PER LITER</small>
                                            <strong class="text-success font-monospace" id="previewSellingPerLiter">Rs. 0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Decanting Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. 280L Drum decanted into 250L loose oil in Bay dispenser"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Execute Decanting</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const drumSelect = document.getElementById('modalDrumSelect');
    const looseSelect = document.getElementById('modalLooseSelect');
    const drumsUsedInput = document.getElementById('modalDrumsUsed');
    const litersProducedInput = document.getElementById('modalLitersProduced');
    const shrinkageInput = document.getElementById('modalShrinkage');
    const sellingPriceInput = document.getElementById('modalSellingPrice');

    const previewCurrentDrum = document.getElementById('previewCurrentDrum');
    const previewUsedDrums = document.getElementById('previewUsedDrums');
    const previewNewDrumStock = document.getElementById('previewNewDrumStock');

    const previewCurrentLoose = document.getElementById('previewCurrentLoose');
    const previewProducedLiters = document.getElementById('previewProducedLiters');
    const previewNewLooseStock = document.getElementById('previewNewLooseStock');

    const previewCostPerLiter = document.getElementById('previewCostPerLiter');
    const previewSellingPerLiter = document.getElementById('previewSellingPerLiter');

    let userEditedSellingPrice = false;
    sellingPriceInput.addEventListener('input', function() {
        userEditedSellingPrice = true;
    });

    function getDrumLiters() {
        const drumOpt = drumSelect.options[drumSelect.selectedIndex];
        return drumOpt ? (parseFloat(drumOpt.dataset.liters) || 280) : 280;
    }

    function getLooseUnitLiters() {
        const looseOpt = looseSelect.options[looseSelect.selectedIndex];
        return looseOpt ? (parseFloat(looseOpt.dataset.liters) || 1) : 1;
    }

    function calculatePreview() {
        const drumOpt = drumSelect.options[drumSelect.selectedIndex];
        const looseOpt = looseSelect.options[looseSelect.selectedIndex];

        const drumStock = drumOpt ? (parseFloat(drumOpt.dataset.stock) || 0) : 0;
        const drumCost = drumOpt ? (parseFloat(drumOpt.dataset.cost) || 0) : 0;
        const drumSale = drumOpt ? (parseFloat(drumOpt.dataset.price) || 0) : 0;

        const looseStock = looseOpt ? (parseFloat(looseOpt.dataset.stock) || 0) : 0;
        const looseUnitLiters = getLooseUnitLiters();

        const drumsUsed = parseFloat(drumsUsedInput.value) || 0;
        const litersProduced = parseFloat(litersProducedInput.value) || 0;

        const newDrumStock = Math.max(0, drumStock - drumsUsed);
        
        // Loose units produced = Liters / unit volume (e.g. 250L / 1L = 250 units)
        const unitsProduced = looseUnitLiters > 0 ? (litersProduced / looseUnitLiters) : litersProduced;
        const newLooseStock = looseStock + unitsProduced;

        const costPerL = litersProduced > 0 ? (drumsUsed * drumCost) / litersProduced : 0;
        const sellingPerL = litersProduced > 0 ? (drumsUsed * drumSale) / litersProduced : 0;

        previewCurrentDrum.textContent = drumStock.toFixed(2);
        previewUsedDrums.textContent = drumsUsed.toFixed(2);
        previewNewDrumStock.textContent = newDrumStock.toFixed(2);

        previewCurrentLoose.textContent = looseStock.toFixed(2);
        previewProducedLiters.textContent = unitsProduced.toFixed(2) + ' (' + litersProduced.toFixed(1) + 'L)';
        previewNewLooseStock.textContent = newLooseStock.toFixed(2);

        previewCostPerLiter.textContent = 'Rs. ' + costPerL.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        previewSellingPerLiter.textContent = 'Rs. ' + sellingPerL.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

        if (!userEditedSellingPrice && sellingPerL > 0) {
            sellingPriceInput.value = sellingPerL.toFixed(2);
        }
    }

    // Auto-compute Usable Liters when Drum is chosen
    drumSelect.addEventListener('change', function() {
        userEditedSellingPrice = false;
        const drumCap = getDrumLiters();
        const drumsUsed = parseFloat(drumsUsedInput.value) || 1;
        const shrink = parseFloat(shrinkageInput.value) || 0;
        const usable = Math.max(0, (drumsUsed * drumCap) - shrink);
        litersProducedInput.value = usable.toFixed(2);
        calculatePreview();
    });

    // When drums used changes
    drumsUsedInput.addEventListener('input', function() {
        const drumCap = getDrumLiters();
        const drumsUsed = parseFloat(drumsUsedInput.value) || 1;
        const shrink = parseFloat(shrinkageInput.value) || 0;
        litersProducedInput.value = Math.max(0, (drumsUsed * drumCap) - shrink).toFixed(2);
        calculatePreview();
    });

    // When shrinkage changes: recalculate usable liters
    shrinkageInput.addEventListener('input', function() {
        const drumCap = getDrumLiters();
        const drumsUsed = parseFloat(drumsUsedInput.value) || 1;
        const shrink = parseFloat(this.value) || 0;
        litersProducedInput.value = Math.max(0, (drumsUsed * drumCap) - shrink).toFixed(2);
        calculatePreview();
    });

    // When usable liters is directly edited: recalculate shrinkage
    litersProducedInput.addEventListener('input', function() {
        const drumCap = getDrumLiters();
        const drumsUsed = parseFloat(drumsUsedInput.value) || 1;
        const usable = parseFloat(this.value) || 0;
        const shrink = Math.max(0, (drumsUsed * drumCap) - usable);
        shrinkageInput.value = shrink.toFixed(2);
        calculatePreview();
    });

    looseSelect.addEventListener('change', calculatePreview);

    calculatePreview();
});
</script>
@endpush
        </div>
    </div>
</div>
@endsection
