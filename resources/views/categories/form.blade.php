@extends('layouts.app')
@section('title', $category->exists ? 'Edit Category' : 'Add Category')

@section('content')
<x-errors />
<div class="row justify-content-center">
    <div class="col-lg-7">
        <form class="card p-4 shadow-sm border-0" style="border-radius: 12px;" method="post" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
            @csrf
            @if($category->exists)
                @method('put')
            @endif

            <h5 class="fw-bold mb-3">{{ $category->exists ? 'Edit Category' : 'Create New Category' }}</h5>

            @if(auth()->user()->isSuperAdmin() && isset($shops) && $shops->count() > 0)
                <div class="mb-3">
                    <label class="form-label fw-semibold">Shop / Branch <span class="text-danger">*</span></label>
                    <select class="form-select @error('shop_id') is-invalid @enderror" name="shop_id" required>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" @selected(old('shop_id', $category->shop_id ?? $selectedShopId) == $shop->id)>
                                {{ $shop->name }} ({{ $shop->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('shop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Category names are isolated and unique per shop.</div>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $category->name) }}" placeholder="e.g. Engine Oils, Brake Services, Hair Care" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Optional category description...">{{ old('description', $category->description) }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                    <option value="active" @selected(old('status', $category->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $category->status) === 'inactive')>Inactive</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('categories.index') }}">Cancel</a>
                <button class="btn btn-primary fw-semibold" type="submit">{{ $category->exists ? 'Update Category' : 'Save Category' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
