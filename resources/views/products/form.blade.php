@extends('layouts.app')
@section('title', $product->exists ? 'Edit Product' : 'Add Product')
@section('content')
<x-errors />
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card p-4">
            <h2 class="h5 mb-3">{{ $product->exists ? 'Edit Product Details' : 'Register New Product in Catalog' }}</h2>
            <form method="post" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
                @csrf
                @if($product->exists)
                    @method('put')
                @endif

                <div class="row g-3">
                    @if(auth()->user()->isSuperAdmin() && ! $product->exists)
                        <div class="col-md-12">
                            <label class="form-label">Shop / Branch <span class="text-danger">*</span></label>
                            <select class="form-select @error('shop_id') is-invalid @enderror" name="shop_id" required>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}" @selected(old('shop_id', $product->shop_id) == $shop->id)>
                                        {{ $shop->name }} ({{ $shop->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('shop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-md-8">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input class="form-control @error('item_name') is-invalid @enderror" name="item_name" value="{{ old('item_name', $product->item_name) }}" required placeholder="Enter Item Name">
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

                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Measure Unit <span class="text-danger">*</span></label>
                            <a class="small text-decoration-none" href="{{ route('units.index') }}" target="_blank">+ New Unit</a>
                        </div>
                        <select class="form-select searchable-select @error('unit') is-invalid @enderror" name="unit" required>
                            <option value="">Select Unit</option>
                            @foreach($units as $u)
                                @php $unitCode = is_string($u) ? $u : $u->code; $unitName = is_string($u) ? $u : $u->name; @endphp
                                <option value="{{ $unitCode }}" @selected(old('unit', $product->unit) == $unitCode)>
                                    {{ $unitName }} ({{ $unitCode }})
                                </option>
                            @endforeach
                        </select>
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold" id="litersFieldLabel">Liters / Capacity (L)</label>
                        <div class="input-group">
                            <input class="form-control font-monospace @error('liters') is-invalid @enderror" type="number" step="any" min="0.001" name="liters" id="litersInput" value="{{ old('liters', $product->liters ?? $product->pack_size) }}" placeholder="e.g. 280 (for Drum) or 1 (for Loose)">
                            <span class="input-group-text bg-light text-muted">Liters</span>
                        </div>
                        @error('liters') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-text" id="litersHelpText">Drum: capacity (e.g. 280L, 520L). Loose: unit volume (e.g. 1L).</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Low Stock Alert Quantity</label>
                        <input class="form-control @error('alert_quantity') is-invalid @enderror" type="number" step="1" min="0" name="alert_quantity" value="{{ old('alert_quantity', $product->alert_quantity ?? 5) }}">
                        @error('alert_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Triggers yellow warning when stock reaches this limit.</div>
                    </div>

                    {{-- Product Classification / Loose Product Specification --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Product Classification / Type</label>
                        <select class="form-select @error('product_type') is-invalid @enderror" name="product_type" id="productTypeSelect">
                            <option value="standard" @selected(old('product_type', $product->product_type ?? 'standard') === 'standard')>Standard / Packaged Item (Bottles, Parts, Filters)</option>
                            <option value="loose" @selected(old('product_type', $product->product_type) === 'loose' || $product->is_loose)>Loose Product (Dispensed Liters / Oil from Drums)</option>
                            <option value="drum" @selected(old('product_type', $product->product_type) === 'drum')>Bulk Drum (208L Barrel / Decanting Source)</option>
                        </select>
                        @error('product_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Marking as <strong>Loose Product</strong> allows it to be targeted for Drum Decanting and POS Loose Oil dispensing.</div>
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check p-3 border rounded-3 w-100 bg-light mt-1">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="is_loose" id="isLooseCheck" value="1" @checked(old('is_loose', $product->is_loose || $product->product_type === 'loose'))>
                            <label class="form-check-label fw-semibold text-dark" for="isLooseCheck">
                                <i class="bi bi-droplet-half text-primary me-1"></i> Mark as Loose Product (Sold per Liter)
                            </label>
                            <small class="text-muted d-block ms-4" style="font-size: 11.5px;">Auto-filters in Auto Lube Station Loose Oil Counter &amp; Decanting.</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-light border d-flex align-items-center gap-2 mb-0 py-2 px-3 small text-secondary">
                            <i class="bi bi-info-circle text-primary fs-5"></i>
                            <span><strong>Procurement Workflow:</strong> Stock quantities, batch numbers, and purchase costs are managed dynamically via <strong>Purchase Orders & Goods Received Notes (GRN)</strong>.</span>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('productTypeSelect');
    const looseCheck = document.getElementById('isLooseCheck');
    const unitSelect = document.querySelector('select[name="unit"]');
    const litersLabel = document.getElementById('litersFieldLabel');
    const litersInput = document.getElementById('litersInput');
    const litersHelp = document.getElementById('litersHelpText');

    function syncTypeUI() {
        if (!typeSelect) return;
        const val = typeSelect.value;
        if (val === 'drum') {
            if (looseCheck) looseCheck.checked = false;
            if (unitSelect && (!unitSelect.value || unitSelect.value === 'PCS' || unitSelect.value === 'LITER')) {
                unitSelect.value = 'DRUM';
            }
            if (litersLabel) litersLabel.innerHTML = '<i class="bi bi-fuel-pump text-primary me-1"></i> Drum Capacity (Liters) <span class="text-danger">*</span>';
            if (litersInput) litersInput.placeholder = 'e.g. 280 or 520';
            if (litersHelp) litersHelp.textContent = 'Total volume capacity of this drum (e.g. 280L, 520L). Auto-populates decanting usable liters.';
        } else if (val === 'loose') {
            if (looseCheck) looseCheck.checked = true;
            if (unitSelect && (!unitSelect.value || unitSelect.value === 'PCS' || unitSelect.value === 'DRUM')) {
                unitSelect.value = 'LITER';
            }
            if (litersLabel) litersLabel.innerHTML = '<i class="bi bi-droplet-half text-info me-1"></i> Loose Unit Volume (Liters) <span class="text-danger">*</span>';
            if (litersInput) {
                litersInput.placeholder = 'e.g. 1 or 2';
                if (!litersInput.value) litersInput.value = '1';
            }
            if (litersHelp) litersHelp.textContent = 'Defined volume per loose unit (e.g. 1L). Decanting 250L into this product results in 250 available quantity.';
        } else {
            if (looseCheck) looseCheck.checked = false;
            if (litersLabel) litersLabel.textContent = 'Variant / Pack Size in Liters';
            if (litersInput) litersInput.placeholder = 'e.g. 4 (for 4L can)';
            if (litersHelp) litersHelp.textContent = 'Optional pack volume in liters for standard packaged items.';
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', syncTypeUI);
    }

    if (looseCheck) {
        looseCheck.addEventListener('change', function () {
            if (this.checked) {
                typeSelect.value = 'loose';
            } else if (typeSelect.value === 'loose') {
                typeSelect.value = 'standard';
            }
            syncTypeUI();
        });
    }

    syncTypeUI();
});
</script>
@endpush
@endsection
