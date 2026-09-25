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
                        <input class="form-control @error('item_name') is-invalid @enderror" name="item_name" value="{{ old('item_name', $product->item_name) }}" required placeholder="e.g. Matte Lipstick, Leather Loafers, Mineral Water">
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
                        <label class="form-label">Variant / Pack Size</label>
                        <input class="form-control @error('pack_size') is-invalid @enderror" type="number" step="0.001" min="0.001" name="pack_size" value="{{ old('pack_size', $product->pack_size) }}" placeholder="e.g. 50, 42, 500">
                        @error('pack_size') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Measure Unit</label>
                        <input class="form-control @error('unit') is-invalid @enderror" name="unit" value="{{ old('unit', $product->unit) }}" placeholder="e.g. ML, PCS, BOX, PACK">
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Expiry Date (Optional)</label>
                        <input class="form-control @error('expiry_date') is-invalid @enderror" type="date" name="expiry_date" value="{{ old('expiry_date', $product->expiry_date?->format('Y-m-d')) }}">
                        @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Purchase Price (Cost) <span class="text-secondary small">(Rs.)</span></label>
                        <input class="form-control @error('purchase_price') is-invalid @enderror" type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" placeholder="0.00">
                        @error('purchase_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Selling Price <span class="text-secondary small">(Rs.)</span></label>
                        <input class="form-control @error('selling_price') is-invalid @enderror" type="number" step="0.01" min="0" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" placeholder="0.00">
                        @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Low Stock Alert Quantity</label>
                        <input class="form-control @error('alert_quantity') is-invalid @enderror" type="number" step="1" min="0" name="alert_quantity" value="{{ old('alert_quantity', $product->alert_quantity ?? 5) }}">
                        @error('alert_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Triggers yellow warning when stock reaches this limit.</div>
                    </div>

                    @if(! $product->exists)
                        <div class="col-md-6">
                            <label class="form-label">Opening Stock Quantity</label>
                            <input class="form-control @error('initial_quantity') is-invalid @enderror" type="number" step="1" min="0" name="initial_quantity" value="{{ old('initial_quantity', 0) }}">
                            @error('initial_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Stock to record immediately into inventory.</div>
                        </div>
                    @endif

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
@endsection
