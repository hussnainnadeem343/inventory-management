@extends('layouts.app') @section('title',$item->exists?'Edit Inventory':'Add Inventory') @section('content')<x-errors />
<form class="card p-4" method="post" action="{{$item->exists?route('inventory.update',$item):route('inventory.store')}}">@csrf @if($item->exists)@method('put')@endif<div class="row g-3">
        <div class="col-md-6"><label class="form-label">Item Name *</label><input class="form-control @error('item_name') is-invalid @enderror" name="item_name" value="{{old('item_name',$item->item_name)}}" required></div>
        <div class="col-md-6"><label class="form-label">SKU</label><input class="form-control @error('sku') is-invalid @enderror" name="sku" value="{{old('sku',$item->sku)}}"></div>
        <div class="col-md-6"><label class="form-label">Brand *</label><select class="form-select searchable-select" name="brand_id" required>
                <option value="">Select Brand</option>@foreach($brands as $brand)<option value="{{$brand->id}}" @selected(old('brand_id',$item->brand_id)==$brand->id)>{{$brand->name}}</option>@endforeach
            </select></div>
        <div class="col-md-6"><label class="form-label">Category *</label><select class="form-select searchable-select" name="category_id" required>
                <option value="">Select Category</option>@foreach($categories as $category)<option value="{{$category->id}}" @selected(old('category_id',$item->category_id)==$category->id)>{{$category->name}}</option>@endforeach
            </select></div>
        <div class="col-md-4"><label class="form-label">Initial Quantity *</label><input class="form-control" type="number" step="0.01" min="{{$item->exists?$item->sold_quantity:0}}" name="quantity" value="{{old('quantity',$item->quantity??0)}}" required><div class="form-text">@if($item->exists)Cannot be less than sold quantity ({{number_format($item->sold_quantity,2)}}).@else Original stocked quantity.@endif</div></div>
        <div class="col-md-4"><label class="form-label">Unit</label><input class="form-control @error('unit') is-invalid @enderror" name="unit" value="{{old('unit',$item->unit)}}"></div>
        <!-- <div class="col-md-4"><label class="form-label">Unit *</label><select class="form-select" name="unit" required>@foreach($units as $unit)<option @selected(old('unit',$item->unit)===$unit)>{{$unit}}</option>@endforeach</select></div> -->
        <div class="col-md-4"><label class="form-label">Status *</label><select class="form-select" name="status">
                <option value="active" @selected(old('status',$item->status??'active')==='active')>Active</option>
                <option value="inactive" @selected(old('status',$item->status)==='inactive')>Inactive</option>
            </select></div>
        @if(auth()->user()->isSuperAdmin())<div class="col-md-4"><label class="form-label">Purchase Price</label><input class="form-control" type="number" step="0.01" min="0" name="purchase_price" value="{{old('purchase_price',$item->purchase_price)}}"></div>
        <div class="col-md-4"><label class="form-label">Selling Price</label><input class="form-control" type="number" step="0.01" min="0" name="selling_price" value="{{old('selling_price',$item->selling_price)}}"></div>
        @endif
        <div class="col-md-4"><label class="form-label">Supplier</label><input class="form-control" name="supplier" value="{{old('supplier',$item->supplier)}}"></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary">Save Inventory</button><a class="btn btn-light" href="{{route('inventory.index')}}">Cancel</a></div>
</form>@endsection
