@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="row g-3 mb-4">@foreach([['Total Items',$totalItems,'primary'],['Initial Quantity',number_format($totalInitial,2),'info'],['Sold Quantity',number_format($totalSold,2),'warning'],['Remaining Quantity',number_format($totalRemaining,2),'success'],['Total Brands',$totalBrands,'secondary'],['Total Categories',$totalCategories,'dark']] as [$label,$value,$color])<div class="col-sm-6 col-xl-4"><div class="card p-4 border-start border-4 border-{{$color}}"><div class="text-secondary">{{$label}}</div><div class="display-6 fw-semibold">{{$value}}</div></div></div>@endforeach</div>
<div class="row g-4"><div class="col-xl-6"><div class="card p-3"><h2 class="h5">Inventory By Category</h2><div style="height:320px"><canvas id="categoryChart"></canvas></div></div></div><div class="col-xl-6"><div class="card p-3"><h2 class="h5">Inventory By Brand</h2><div style="height:320px"><canvas id="brandChart"></canvas></div></div></div></div>
@endsection
@push('scripts')<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script><script>
const makeChart=(id,labels,data,color)=>new Chart(document.getElementById(id),{type:'bar',data:{labels,datasets:[{label:'Remaining Quantity',data,backgroundColor:color,borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
makeChart('categoryChart',@json($byCategory->pluck('label')),@json($byCategory->pluck('total')),'rgba(37,99,235,.75)');
makeChart('brandChart',@json($byBrand->pluck('label')),@json($byBrand->pluck('total')),'rgba(22,163,74,.75)');
</script>@endpush
