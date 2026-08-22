@extends('layouts.app') @section('title','Inventory') @section('content')<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
    <form class="d-flex gap-2" method="get"><input class="form-control" name="search" value="{{$search}}" placeholder="Search item, SKU, brand or category"><button class="btn btn-outline-primary">Search</button>@if($search)<a class="btn btn-outline-secondary" href="{{route('inventory.index')}}">Clear</a>@endif</form><a class="btn btn-primary" href="{{route('inventory.create')}}">+ Add Inventory</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Item / SKU</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Purchase</th>
                    <th>Selling</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Created By / At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>@forelse($items as $item)<tr>
                    <td>{{$item->id}}</td>
                    <td><strong>{{$item->item_name}}</strong><br><small>{{$item->sku}}</small></td>
                    <td>{{$item->brand->name}}</td>
                    <td>{{$item->category->name}}</td>
                    <td>{{number_format($item->quantity,2)}}</td>
                    <td>{{$item->unit}}</td>
                    <td>{{number_format($item->purchase_price,2)}}</td>
                    <td>{{number_format($item->selling_price,2)}}</td>
                    <td>{{$item->supplier?:'-'}}</td>
                    <td><span class="badge text-bg-{{$item->status==='active'?'success':'secondary'}}">{{ucfirst($item->status)}}</span></td>
                    <td>{{$item->creator->name}}<br><small>{{$item->created_at->format('d-M-Y h:i A')}}</small></td>
                    <td>
                        <div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{route('inventory.edit',$item)}}">Edit</a>
                            <form method="post" action="{{route('inventory.destroy',$item)}}" data-confirm="Are you sure you want to delete this item?">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </div>
                    </td>
                </tr>@empty<tr>
                    <td colspan="12" class="text-center py-4">No inventory items found.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{$items->links()}}</div>@endsection