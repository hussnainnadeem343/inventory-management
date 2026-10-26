@extends('layouts.app')
@section('title', $brand->exists ? 'Edit Brand' : 'Add Brand')

@section('content')
<x-errors />
<div class="row justify-content-center">
    <div class="col-lg-7">
        <form class="card p-4 shadow-sm border-0" style="border-radius: 12px;" method="post" action="{{ $brand->exists ? route('brands.update', $brand) : route('brands.store') }}">
            @csrf
            @if($brand->exists)
                @method('put')
            @endif

            <h5 class="fw-bold mb-3">{{ $brand->exists ? 'Edit Brand' : 'Register New Brand' }}</h5>

            @if(auth()->user()->isSuperAdmin() && isset($shops) && $shops->count() > 0)
                <div class="mb-3">
                    <label class="form-label fw-semibold">Shop / Branch <span class="text-danger">*</span></label>
                    <select class="form-select @error('shop_id') is-invalid @enderror" name="shop_id" required>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" @selected(old('shop_id', $brand->shop_id ?? $selectedShopId) == $shop->id)>
                                {{ $shop->name }} ({{ $shop->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('shop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Brand names are isolated and unique per shop.</div>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Brand Name <span class="text-danger">*</span></label>
                <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $brand->name) }}" placeholder="e.g. Mobil, Shell, Total, Nike" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Optional brand description...">{{ old('description', $brand->description) }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                    <option value="active" @selected(old('status', $brand->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $brand->status) === 'inactive')>Inactive</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('brands.index') }}">Cancel</a>
                <button class="btn btn-primary fw-semibold" type="submit">{{ $brand->exists ? 'Update Brand' : 'Save Brand' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
