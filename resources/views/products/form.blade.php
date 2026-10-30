@extends('layouts.app')
@section('title', $product->exists ? 'Edit Product' : 'Add Product')
@section('content')
<x-errors />
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">{{ $product->exists ? 'Edit Product Details' : 'Register New Product in Catalog' }}</h2>
                @if($isLubeBusiness)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" id="businessTypeBadge">
                        <i class="bi bi-droplet-half me-1"></i> Auto Lube Business
                    </span>
                @else
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" id="businessTypeBadge">
                        <i class="bi bi-shop me-1"></i> Standard Retail Catalog
                    </span>
                @endif
            </div>

            <form method="post" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" id="productForm">
                @csrf
                @if($product->exists)
                    @method('put')
                @endif

                {{-- Hidden State Fields --}}
                <input type="hidden" name="product_type" id="hiddenProductType" value="{{ old('product_type', $product->product_type ?? 'standard') }}">
                <input type="hidden" name="is_loose" id="hiddenIsLoose" value="{{ old('is_loose', $product->is_loose ? '1' : '0') }}">

                @php
                    // Determine if the product being edited is an engine oil/drum/loose item or a general item
                    $isOil = false;
                    $oilType = 'packaged';

                    if ($product->is_loose || $product->product_type === 'loose' || old('oil_sub_type') === 'loose') {
                        $isOil = true;
                        $oilType = 'loose';
                    } elseif ($product->product_type === 'drum' || str_contains(strtoupper($product->unit ?? ''), 'DRUM') || old('oil_sub_type') === 'drum') {
                        $isOil = true;
                        $oilType = 'drum';
                    } elseif (($product->liters > 0 && in_array(strtoupper($product->unit ?? ''), ['LTR', 'LITER', 'CAN', 'BOTTLE'])) || old('oil_sub_type') === 'packaged') {
                        $isOil = true;
                        $oilType = 'packaged';
                    }

                    if (old('product_nature')) {
                        $isOil = old('product_nature') === 'oil';
                    }
                @endphp

                <div class="row g-3">
                    @if(auth()->user()->isSuperAdmin() && ! $product->exists)
                        <div class="col-md-12">
                            <label class="form-label">Shop / Branch <span class="text-danger">*</span></label>
                            <select class="form-select @error('shop_id') is-invalid @enderror" name="shop_id" id="shopSelect" required>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}" data-is-lube="{{ $shop->isLube() ? '1' : '0' }}" @selected(old('shop_id', $product->shop_id ?? $currentShop?->id) == $shop->id)>
                                        {{ $shop->name }} ({{ $shop->code }}) &mdash; {{ $shop->business_type ?: 'Retail & General Business' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('shop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-md-8">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input class="form-control @error('item_name') is-invalid @enderror" name="item_name" value="{{ old('item_name', $product->item_name) }}" required placeholder="Enter Item Name (e.g. Air Freshener, Oil Filter, Shell Helix 10W-40)">
                        @error('item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">SKU / Barcode</label>
                        <input class="form-control font-monospace @error('sku') is-invalid @enderror" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="e.g. LIP-01, BAR-89472">
                        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Leave blank to auto-generate or scan barcode.</div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Brand <span class="text-danger">*</span></label>
                            <a class="small text-decoration-none" href="{{ route('brands.create') }}" target="_blank">+ New Brand</a>
                        </div>
                        <select class="form-select searchable-select @error('brand_id') is-invalid @enderror" name="brand_id" required>
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Category <span class="text-danger">*</span></label>
                            <a class="small text-decoration-none" href="{{ route('categories.create') }}" target="_blank">+ New Category</a>
                        </div>
                        <select class="form-select searchable-select @error('category_id') is-invalid @enderror" name="category_id" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- AUTO LUBE BUSINESS SPECIFIC SECTION --}}
                    <div class="col-12" id="lubeSectionWrapper" style="{{ $isLubeBusiness ? '' : 'display: none;' }}">
                        <div class="p-3 border rounded-3 bg-light">
                            <label class="form-label fw-bold mb-2 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-tag-fill text-primary"></i> Product Classification
                            </label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="card h-100 p-3 border product-nature-card {{ ! $isOil ? 'active' : '' }}" id="cardNatureGeneral">
                                        <div class="form-check ps-0 mb-0">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input ms-0 mt-1" type="radio" name="product_nature" id="radioGeneral" value="general" @checked(! $isOil)>
                                                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="radioGeneral">
                                                    <span class="d-block text-dark">
                                                        <i class="bi bi-box-seam text-secondary me-1"></i> Standard Product / Part / Accessory
                                                    </span>
                                                    <small class="text-muted fw-normal d-block mt-1" style="font-size: 12px;">
                                                        For Air Fresheners, Air/Oil/AC Filters, Wiper Blades, Spark Plugs, Coolants, Hardware, etc.
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 p-3 border product-nature-card {{ $isOil ? 'active-oil' : '' }}" id="cardNatureOil">
                                        <div class="form-check ps-0 mb-0">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input ms-0 mt-1" type="radio" name="product_nature" id="radioOil" value="oil" @checked($isOil)>
                                                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="radioOil">
                                                    <span class="d-block text-warning-emphasis">
                                                        <i class="bi bi-droplet-half text-warning me-1"></i> Engine Oil / Bulk Lubricant
                                                    </span>
                                                    <small class="text-muted fw-normal d-block mt-1" style="font-size: 12px;">
                                                        For Bulk 208L/280L Drums, Dispensed Loose Oil per Liter, or Sealed Packaged Oil Cans.
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Oil Specification Properties (Only visible when "Engine Oil / Bulk Lubricant" is selected) --}}
                            <div class="mt-3 pt-3 border-top" id="oilConfigPanel" style="{{ $isOil ? '' : 'display: none;' }}">
                                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-droplet text-warning"></i> Oil Packaging &amp; Dispensing Properties
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Oil Sub-Type <span class="text-danger">*</span></label>
                                        <select class="form-select" name="oil_sub_type" id="oilSubTypeSelect">
                                            <option value="packaged" @selected($oilType === 'packaged')>
                                                Packaged / Bottled Oil (e.g. 4L Can, 1L Bottle)
                                            </option>
                                            <option value="loose" @selected($oilType === 'loose')>
                                                Loose Dispensed Oil (Sold per Liter at Counter / Decanting Destination)
                                            </option>
                                            <option value="drum" @selected($oilType === 'drum')>
                                                Bulk Drum (208L / 280L Barrel / Decanting Source)
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-6" id="oilVolumeWrapper">
                                        <label class="form-label fw-semibold" id="oilVolumeLabel">Can / Bottle Volume (Liters)</label>
                                        <div class="input-group">
                                            <input class="form-control font-monospace" type="number" step="any" min="0.001" name="liters" id="oilVolumeInput" value="{{ old('liters', $product->liters) }}" placeholder="e.g. 4 for 4L can">
                                            <span class="input-group-text bg-white text-muted">Liters</span>
                                        </div>
                                        <div class="form-text" id="oilVolumeHelp">Volume in liters for standard packaged oil cans.</div>
                                    </div>

                                    <div class="col-12">
                                        <div class="alert alert-light border py-2 px-3 small mb-0 d-flex align-items-center gap-2" id="oilAlertNotice">
                                            <i class="bi bi-info-circle-fill text-primary"></i>
                                            <span id="oilAlertText">Packaged oil cans are sold as sealed units (e.g. 4L can) at POS.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- MEASURE UNIT, PACK SIZE & ALERT QUANTITY --}}
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Measure Unit <span class="text-danger">*</span></label>
                            <a class="small text-decoration-none" href="{{ route('units.index') }}" target="_blank">+ New Unit</a>
                        </div>
                        <select class="form-select searchable-select @error('unit') is-invalid @enderror" name="unit" id="unitSelect" required>
                            <option value="">Select Unit</option>
                            @foreach($units as $u)
                                @php $unitCode = is_string($u) ? $u : $u->code; $unitName = is_string($u) ? $u : $u->name; @endphp
                                <option value="{{ $unitCode }}" @selected(old('unit', $product->unit ?? 'PCS') == $unitCode)>
                                    {{ $unitName }} ({{ $unitCode }})
                                </option>
                            @endforeach
                        </select>
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4" id="generalPackSizeCol">
                        <label class="form-label" id="generalPackSizeLabel">Pack Size / Pieces per Pack</label>
                        <input class="form-control @error('pack_size') is-invalid @enderror" type="number" step="any" min="0" name="pack_size" id="generalPackSizeInput" value="{{ old('pack_size', $product->pack_size) }}" placeholder="e.g. 1 or 12">
                        @error('pack_size') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text" id="generalPackSizeHelp">Optional: Number of units or pieces per carton/box.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Low Stock Alert Quantity</label>
                        <input class="form-control @error('alert_quantity') is-invalid @enderror" type="number" step="1" min="0" name="alert_quantity" value="{{ old('alert_quantity', $product->alert_quantity ?? 5) }}">
                        @error('alert_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Triggers yellow warning when stock reaches this limit.</div>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-light border d-flex align-items-center gap-2 mb-0 py-2 px-3 small text-secondary">
                            <i class="bi bi-info-circle text-primary fs-5"></i>
                            <span><strong>Procurement Workflow:</strong> Stock quantities, batch numbers, and purchase costs are managed dynamically via <strong>Purchase Orders &amp; Goods Received Notes (GRN)</strong>.</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="active" @selected(old('status', $product->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $product->status) === 'inactive')>Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Cancel</a>
                    <button class="btn btn-primary" type="submit">{{ $product->exists ? 'Update Product' : 'Save Product to Catalog' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.product-nature-card {
    transition: all 0.2s ease;
    border: 1.5px solid #e2e8f0;
    cursor: pointer;
}
.product-nature-card:hover {
    border-color: #cbd5e1;
    background-color: #f8fafc;
}
.product-nature-card.active {
    border-color: #2563eb !important;
    background-color: #eff6ff !important;
}
.product-nature-card.active-oil {
    border-color: #f59e0b !important;
    background-color: #fffbeb !important;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const shopSelect = document.getElementById('shopSelect');
    const lubeSectionWrapper = document.getElementById('lubeSectionWrapper');
    const businessTypeBadge = document.getElementById('businessTypeBadge');

    const radioGeneral = document.getElementById('radioGeneral');
    const radioOil = document.getElementById('radioOil');
    const cardNatureGeneral = document.getElementById('cardNatureGeneral');
    const cardNatureOil = document.getElementById('cardNatureOil');

    const oilConfigPanel = document.getElementById('oilConfigPanel');
    const oilSubTypeSelect = document.getElementById('oilSubTypeSelect');
    const oilVolumeWrapper = document.getElementById('oilVolumeWrapper');
    const oilVolumeLabel = document.getElementById('oilVolumeLabel');
    const oilVolumeInput = document.getElementById('oilVolumeInput');
    const oilVolumeHelp = document.getElementById('oilVolumeHelp');
    const oilAlertText = document.getElementById('oilAlertText');

    const unitSelect = document.getElementById('unitSelect');
    const generalPackSizeCol = document.getElementById('generalPackSizeCol');
    const generalPackSizeInput = document.getElementById('generalPackSizeInput');

    const hiddenProductType = document.getElementById('hiddenProductType');
    const hiddenIsLoose = document.getElementById('hiddenIsLoose');

    // Helper to select an option by code or fallback
    function setUnitValue(code, fallbackCode) {
        if (!unitSelect) return;
        const upperCode = code.toUpperCase();
        let found = false;
        for (let i = 0; i < unitSelect.options.length; i++) {
            if (unitSelect.options[i].value.toUpperCase() === upperCode) {
                unitSelect.selectedIndex = i;
                found = true;
                break;
            }
        }
        if (!found && fallbackCode) {
            for (let i = 0; i < unitSelect.options.length; i++) {
                if (unitSelect.options[i].value.toUpperCase() === fallbackCode.toUpperCase()) {
                    unitSelect.selectedIndex = i;
                    found = true;
                    break;
                }
            }
        }
    }

    function syncOilSubtypeUI() {
        if (!oilSubTypeSelect) return;
        const subType = oilSubTypeSelect.value;

        if (subType === 'loose') {
            if (oilVolumeWrapper) oilVolumeWrapper.style.display = 'none';
            if (oilAlertText) {
                oilAlertText.textContent = 'Loose oil is dispensed in exact/fractional liters (e.g. 0.25L, 3.5L) at POS counter and acts as the destination for drum decanting.';
            }
            if (hiddenProductType) hiddenProductType.value = 'loose';
            if (hiddenIsLoose) hiddenIsLoose.value = '1';
            setUnitValue('LTR', 'LITER');
        } else if (subType === 'drum') {
            if (oilVolumeWrapper) oilVolumeWrapper.style.display = 'block';
            if (oilVolumeLabel) oilVolumeLabel.innerHTML = 'Drum Total Capacity (Liters) <span class="text-danger">*</span>';
            if (oilVolumeInput) oilVolumeInput.placeholder = 'e.g. 280 or 208';
            if (oilVolumeHelp) oilVolumeHelp.textContent = 'Total volume capacity of this drum (e.g. 280L). Used as source for decanting.';
            if (oilAlertText) {
                oilAlertText.textContent = 'Bulk drums are tracked as single barrel units and decanted into loose oil in the Auto Lube Station.';
            }
            if (hiddenProductType) hiddenProductType.value = 'drum';
            if (hiddenIsLoose) hiddenIsLoose.value = '0';
            setUnitValue('DRUM', 'PCS');
        } else {
            // Packaged / Bottled Can
            if (oilVolumeWrapper) oilVolumeWrapper.style.display = 'block';
            if (oilVolumeLabel) oilVolumeLabel.textContent = 'Can / Bottle Volume (Liters)';
            if (oilVolumeInput) oilVolumeInput.placeholder = 'e.g. 4 for 4L can, 1 for 1L bottle';
            if (oilVolumeHelp) oilVolumeHelp.textContent = 'Volume in liters for standard packaged oil cans.';
            if (oilAlertText) {
                oilAlertText.textContent = 'Packaged oil cans are sold as sealed units (e.g. 4L can) at POS.';
            }
            if (hiddenProductType) hiddenProductType.value = 'standard';
            if (hiddenIsLoose) hiddenIsLoose.value = '0';
            if (unitSelect && (unitSelect.value === 'DRUM' || unitSelect.value === 'LTR')) {
                setUnitValue('CAN', 'PCS');
            }
        }
    }

    function syncNatureUI() {
        // Check if lube section is active
        const isLube = lubeSectionWrapper && lubeSectionWrapper.style.display !== 'none';

        if (!isLube) {
            // Standard non-lube business: completely generic form
            if (oilConfigPanel) oilConfigPanel.style.display = 'none';
            if (generalPackSizeCol) generalPackSizeCol.style.display = 'block';
            if (hiddenProductType) hiddenProductType.value = 'standard';
            if (hiddenIsLoose) hiddenIsLoose.value = '0';
            return;
        }

        const isOil = radioOil && radioOil.checked;

        if (cardNatureGeneral) {
            cardNatureGeneral.classList.toggle('active', !isOil);
        }
        if (cardNatureOil) {
            cardNatureOil.classList.toggle('active-oil', isOil);
        }

        if (isOil) {
            // Lube business: Oil product selected
            if (oilConfigPanel) oilConfigPanel.style.display = 'block';
            if (generalPackSizeCol) generalPackSizeCol.style.display = 'none';
            syncOilSubtypeUI();
        } else {
            // Lube business: Standard general item (Air Freshener, Air Filter, etc.)
            if (oilConfigPanel) oilConfigPanel.style.display = 'none';
            if (generalPackSizeCol) generalPackSizeCol.style.display = 'block';
            if (hiddenProductType) hiddenProductType.value = 'standard';
            if (hiddenIsLoose) hiddenIsLoose.value = '0';
            if (oilVolumeInput) oilVolumeInput.value = '';
            if (unitSelect && (unitSelect.value === 'DRUM' || unitSelect.value === 'LTR')) {
                setUnitValue('PCS', null);
            }
        }
    }

    if (radioGeneral) {
        radioGeneral.addEventListener('change', syncNatureUI);
    }
    if (radioOil) {
        radioOil.addEventListener('change', syncNatureUI);
    }
    if (cardNatureGeneral) {
        cardNatureGeneral.addEventListener('click', function () {
            if (radioGeneral) {
                radioGeneral.checked = true;
                syncNatureUI();
            }
        });
    }
    if (cardNatureOil) {
        cardNatureOil.addEventListener('click', function () {
            if (radioOil) {
                radioOil.checked = true;
                syncNatureUI();
            }
        });
    }
    if (oilSubTypeSelect) {
        oilSubTypeSelect.addEventListener('change', syncOilSubtypeUI);
    }

    // Dynamic Shop switcher for Super Admin
    if (shopSelect) {
        shopSelect.addEventListener('change', function () {
            const selectedOpt = shopSelect.options[shopSelect.selectedIndex];
            const isLubeShop = selectedOpt.getAttribute('data-is-lube') === '1';

            if (lubeSectionWrapper) {
                lubeSectionWrapper.style.display = isLubeShop ? 'block' : 'none';
            }
            if (businessTypeBadge) {
                if (isLubeShop) {
                    businessTypeBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1';
                    businessTypeBadge.innerHTML = '<i class="bi bi-droplet-half me-1"></i> Auto Lube Business';
                } else {
                    businessTypeBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1';
                    businessTypeBadge.innerHTML = '<i class="bi bi-shop me-1"></i> Standard Retail Catalog';
                }
            }
            syncNatureUI();
        });
    }

    syncNatureUI();
});
</script>
@endpush
@endsection
