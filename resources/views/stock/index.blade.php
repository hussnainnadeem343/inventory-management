@extends('layouts.app')
@section('title', 'Stock Management')
@section('content')
<x-errors />

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-{{ auth()->user()->isSuperAdmin() ? '3' : '4' }} col-md-6">
            <label class="form-label">Search Product</label>
            <input class="form-control" name="search" value="{{ $filters['search'] }}" placeholder="Product name or SKU">
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-sm-6 col-lg-2 col-md-3">
                <label class="form-label">Shop</label>
                <select class="form-select" name="shop_id">
                    <option value="">All Shops</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected(($filters['shop_id'] ?? null) == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-sm-6 col-lg-2 col-md-3">
            <label class="form-label">Brand</label>
            <select class="form-select" name="brand_id">
                <option value="">All Brands</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected($filters['brand_id'] == $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-2 col-md-3">
            <label class="form-label">Category</label>
            <select class="form-select" name="category_id">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category_id'] == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-1 col-md-3">
            <label class="form-label">Stock Status</label>
            <select class="form-select" name="stock_status">
                <option value="">All</option>
                <option value="low_stock" @selected(($filters['stock_status'] ?? null) === 'low_stock')>Low Stock</option>
                <option value="out_of_stock" @selected(($filters['stock_status'] ?? null) === 'out_of_stock')>Out of Stock</option>
            </select>
        </div>
        <div class="col-sm-6 col-lg-1 col-md-3">
            <label class="form-label">Per page</label>
            <select class="form-select" name="per_page">
                @foreach([10, 20, 30, 50, 100] as $size)
                    <option @selected($perPage === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-1 d-flex gap-1">
            <button class="btn btn-primary w-100" type="submit">Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('stock.index') }}" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2">
        <span class="badge text-bg-success px-2 py-1">In Stock</span>
        <span class="badge text-bg-warning px-2 py-1">Low Stock (Alert)</span>
        <span class="badge text-bg-danger px-2 py-1">Out of Stock</span>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->hasPermission('pos.access'))
            <a class="btn btn-success" href="{{ route('pos') }}">
                <i class="bi bi-cart3 me-1"></i>+ Multi-Item Sale (POS)
            </a>
        @endif
        @if(auth()->user()->hasPermission('stock.export'))
            <a class="btn btn-outline-success" href="{{ route('stock.export', request()->except('page')) }}">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
            </a>
        @endif
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product / SKU</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th>Shop</th>
                    @endif
                    <th>Brand & Category</th>
                    <th>Variant / Pack</th>
                    <th>Initial Qty</th>
                    <th>Sold Qty</th>
                    <th>Remaining Stock</th>
                    <th>Price (Rs.)</th>
                    <th>Quick Actions</th>
                    <th>Logs</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $remaining = (float) $product->quantity;
                        $threshold = (float) ($product->alert_quantity ?? 5);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $product->item_name }}</strong><br>
                            <small class="text-secondary font-monospace">{{ $product->sku ?: '-' }}</small>
                            @if($product->expiry_date)
                                <br>
                                @if($product->expiry_status === 'expired')
                                    <span class="badge text-bg-danger" style="font-size: 0.7rem;">Expired: {{ $product->expiry_date->format('d-M-Y') }}</span>
                                @elseif($product->expiry_status === 'expiring_soon')
                                    <span class="badge text-bg-warning" style="font-size: 0.7rem;">Expiring Soon: {{ $product->expiry_date->format('d-M-Y') }}</span>
                                @endif
                            @endif
                        </td>
                        @if(auth()->user()->isSuperAdmin())
                            <td><span class="badge text-bg-light border">{{ $product->shop->name ?? 'Default' }}</span></td>
                        @endif
                        <td>
                            <span>{{ $product->brand->name ?? '-' }}</span><br>
                            <small class="text-muted">{{ $product->category->name ?? '-' }}</small>
                        </td>
                        <td>{{ $product->pack_label ?: '-' }}</td>
                        <td class="text-secondary fw-semibold">{{ number_format($product->initial_quantity, 2) }}</td>
                        <td class="text-primary fw-semibold">{{ number_format($product->sold_quantity, 2) }}</td>
                        <td>
                            @if($remaining <= 0)
                                <span class="badge text-bg-danger px-2 py-1">Out of Stock (0.00)</span>
                            @elseif($remaining <= $threshold)
                                <span class="badge text-bg-warning px-2 py-1" title="Low Stock Warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>{{ number_format($remaining, 2) }}
                                </span>
                            @else
                                <span class="badge text-bg-success px-2 py-1">{{ number_format($remaining, 2) }}</span>
                            @endif

                            @php
                                $batchesCount = $product->batches->count();
                                $activeBatchesCount = $product->batches->where('quantity', '>', 0)->count();
                            @endphp
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-info text-nowrap btn-batches-action mt-1 py-0 px-2" 
                                        style="font-size: 0.72rem;"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#batchesModal"
                                        data-product-name="{{ $product->item_name }}"
                                        data-batches='@json($product->batches)'>
                                    <i class="bi bi-layers me-1"></i>{{ $batchesCount }} {{ Str::plural('Batch', $batchesCount) }} ({{ $activeBatchesCount }} active)
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <span class="fw-semibold text-primary">Sell: Rs. {{ number_format($product->selling_price, 2) }}</span>
                                @if(auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin())
                                    <br><span class="text-muted">Cost: Rs. {{ number_format($product->purchase_price, 2) }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @php
                                $canAddStock = auth()->user()->hasPermission('stock.add');
                                $canSellStock = auth()->user()->hasPermission('stock.sell');
                                $canReturnStock = auth()->user()->hasPermission('stock.return');
                                $canDamageStock = auth()->user()->hasPermission('stock.damage');
                                $canExchangeStock = auth()->user()->hasPermission('stock.exchange');
                                $hasMoreActions = $canReturnStock || $canDamageStock || $canExchangeStock;
                            @endphp
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                @if($canAddStock)
                                    <button class="btn btn-sm btn-outline-success text-nowrap btn-stock-action" 
                                            type="button" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#addStockModal"
                                            data-product-id="{{ $product->id }}"
                                            data-product-name="{{ $product->item_name }}"
                                            data-current-cost="{{ $product->purchase_price }}"
                                            data-current-selling-price="{{ $product->selling_price }}"
                                            data-current-expiry="{{ $product->expiry_date?->format('Y-m-d') }}">
                                        <i class="bi bi-plus-lg me-1"></i>+ Add Stock
                                    </button>
                                @endif
                                
                                @if($canSellStock)
                                    <button class="btn btn-sm btn-primary text-nowrap btn-sell-action" 
                                            type="button" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#sellModal"
                                            data-product-id="{{ $product->id }}"
                                            data-product-name="{{ $product->item_name }}"
                                            data-selling-price="{{ $product->selling_price }}"
                                            data-available="{{ $remaining }}"
                                            @disabled($remaining <= 0)>
                                        <i class="bi bi-dash-lg me-1"></i>- Sell
                                    </button>
                                @endif

                                @if($hasMoreActions)
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            More
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if($canReturnStock)
                                                <li>
                                                    <a class="dropdown-item btn-return-action" href="#" 
                                                       data-bs-toggle="modal" 
                                                       data-bs-target="#returnModal"
                                                       data-product-id="{{ $product->id }}"
                                                       data-product-name="{{ $product->item_name }}"
                                                       data-selling-price="{{ $product->selling_price }}">
                                                        <i class="bi bi-arrow-return-left text-info me-2"></i>Customer Return (Wapsi)
                                                    </a>
                                                </li>
                                            @endif
                                            @if($canDamageStock)
                                                <li>
                                                    <a class="dropdown-item btn-damage-action" href="#" 
                                                       data-bs-toggle="modal" 
                                                       data-bs-target="#damageModal"
                                                       data-product-id="{{ $product->id }}"
                                                       data-product-name="{{ $product->item_name }}"
                                                       data-available="{{ $remaining }}">
                                                        <i class="bi bi-trash text-danger me-2"></i>Damage / Write-Off
                                                    </a>
                                                </li>
                                            @endif
                                            @if($canExchangeStock)
                                                <li>
                                                    <a class="dropdown-item btn-exchange-action" href="#" 
                                                       data-bs-toggle="modal" 
                                                       data-bs-target="#exchangeModal"
                                                       data-product-id="{{ $product->id }}"
                                                       data-product-name="{{ $product->item_name }}">
                                                        <i class="bi bi-arrow-left-right text-warning me-2"></i>Product Exchange
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif

                                @if(! $canAddStock && ! $canSellStock && ! $hasMoreActions)
                                    <span class="text-muted small">View Only</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if(auth()->user()->hasPermission('stock.history'))
                                <a class="btn btn-sm btn-outline-secondary text-nowrap" href="{{ route('stock.history', $product) }}">
                                    <i class="bi bi-journal-text me-1"></i>Logs
                                </a>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 10 : 9 }}" class="text-center py-4">No products found in stock.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $products->onEachSide(1)->links() }}</div>

<!-- 1. Modal: Add Stock -->
<div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" id="addStockForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addStockModalLabel">+ Add Stock: <span id="addStockProductName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Quantity to Add <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" step="0.01" min="0.01" name="quantity" required placeholder="e.g. 10">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Selling Price (Rs.)</label>
                    <input class="form-control" type="number" step="0.01" min="0" name="selling_price" id="addStockSellPrice" placeholder="New Selling Price">
                    <div class="form-text">Updated retail selling rate for this batch.</div>
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin())
                    <div class="col-md-6">
                        <label class="form-label">Purchase Price / Cost (Rs.)</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="purchase_price" id="addStockCost" placeholder="Optional price override">
                        <div class="form-text">If changed, updates batch cost & average cost.</div>
                    </div>
                @endif
                <div class="col-md-{{ auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin() ? '6' : '12' }}">
                    <label class="form-label">Batch / Lot Number (Optional)</label>
                    <input class="form-control" type="text" name="batch_no" id="addStockBatchNo" placeholder="Leave blank to auto-generate (e.g. BATCH-001)">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expiry Date</label>
                    <input class="form-control" type="date" name="expiry_date" id="addStockExpiry">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reference / Supplier Note</label>
                    <input class="form-control" name="notes" placeholder="e.g. Invoice #1024, New shipment">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">+ Add to Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal: Sell Item -->
<div class="modal fade" id="sellModal" tabindex="-1" aria-labelledby="sellModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="sellForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="sellModalLabel">- Sell Item: <span id="sellProductName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Quantity to Sell <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" step="0.01" min="0.01" name="sell_quantity" required placeholder="e.g. 1">
                    <div class="form-text">Available stock: <strong id="sellAvailableQty" class="text-success"></strong></div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Selling Rate per Unit (Rs.)</label>
                    <input class="form-control" type="number" step="0.01" min="0" name="selling_price" id="sellPriceInput">
                    <div class="form-text">Change only if offering discount / counter negotiation.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Customer / Bill Note (Optional)</label>
                    <input class="form-control" name="notes" placeholder="e.g. Walk-in customer, Bill #45">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">- Complete Sale</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal: Customer Return -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="returnForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="returnModalLabel">Customer Return: <span id="returnProductName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Quantity Returned <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" step="0.01" min="0.01" name="return_quantity" value="1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Total Refund Given (Rs.)</label>
                    <input class="form-control" type="number" step="0.01" min="0" name="refund_amount" id="returnRefundAmount">
                </div>
                <div class="col-12">
                    <label class="form-label">Reason for Return <span class="text-danger">*</span></label>
                    <input class="form-control" name="reason" required placeholder="e.g. Wrong shade, size didn't fit, defective">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white">Process Return & Restock</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal: Damage / Loss Write-Off -->
<div class="modal fade" id="damageModal" tabindex="-1" aria-labelledby="damageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="damageForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="damageModalLabel">Write-Off Damaged / Expired Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <p class="text-muted small mb-1">Product: <strong id="damageProductName" class="text-dark"></strong></p>
                    <p class="text-muted small mb-2">Available: <span id="damageAvailableQty" class="text-danger fw-semibold"></span></p>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Quantity to Remove <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" step="0.01" min="0.01" name="damage_quantity" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cause / Reason <span class="text-danger">*</span></label>
                    <select class="form-select" name="damage_type" required>
                        <option value="expired">Expired Goods</option>
                        <option value="damaged_broken">Broken / Leaked / Damaged</option>
                        <option value="lost_audit">Shelf Audit Discrepancy / Lost</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <input class="form-control" name="notes" placeholder="e.g. Expired lot discarded, bottle cracked in transit">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Write-Off</button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Modal: Product Exchange -->
<div class="modal fade" id="exchangeModal" tabindex="-1" aria-labelledby="exchangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="exchangeForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="exchangeModalLabel">Exchange Product: <span id="exchangeProductName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Returned Qty <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" step="0.01" min="0.01" name="return_quantity" value="1" required>
                    <div class="form-text">Restocks returned product.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">New Item Given Qty <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" step="0.01" min="0.01" name="exchange_quantity" value="1" required>
                </div>
                <div class="col-12">
                    <label class="form-label">New Product to Give Customer <span class="text-danger">*</span></label>
                    <select class="form-select searchable-select" name="new_product_id" required>
                        <option value="">Select replacement item</option>
                        @foreach($activeShopProducts as $ap)
                            <option value="{{ $ap->id }}">{{ $ap->item_name }} ({{ $ap->sku ?: 'No SKU' }}) - Rs. {{ number_format($ap->selling_price, 2) }} [Stock: {{ number_format($ap->quantity, 2) }}]</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Exchange Notes</label>
                    <input class="form-control" name="notes" placeholder="e.g. Swapped for Size 42, customer paid Rs. 200 diff">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Confirm Exchange</button>
            </div>
        </form>
    </div>
</div>

<!-- 6. Modal: Product Batches Viewer -->
<div class="modal fade" id="batchesModal" tabindex="-1" aria-labelledby="batchesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchesModalLabel">
                    <i class="bi bi-layers text-info me-2"></i>Product Batches: <span id="batchesModalProductName" class="text-primary fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Batch / Lot #</th>
                                <th>Selling Price</th>
                                @if(auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin())
                                    <th>Purchase Cost</th>
                                @endif
                                <th>Remaining / Initial</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="batchesModalTableBody">
                            <!-- Injected by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.btn-stock-action').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.productId;
        document.getElementById('addStockProductName').innerText = btn.dataset.productName;
        document.getElementById('addStockForm').action = "{{ url('stock') }}/" + id + "/add";
        const costInput = document.getElementById('addStockCost');
        if (costInput) costInput.value = btn.dataset.currentCost || '';
        const sellInput = document.getElementById('addStockSellPrice');
        if (sellInput) sellInput.value = btn.dataset.currentSellingPrice || '';
        const batchInput = document.getElementById('addStockBatchNo');
        if (batchInput) batchInput.value = '';
        const expInput = document.getElementById('addStockExpiry');
        if (expInput) expInput.value = btn.dataset.currentExpiry || '';
    });
});

document.querySelectorAll('.btn-batches-action').forEach(btn => {
    btn.addEventListener('click', () => {
        const productName = btn.dataset.productName;
        document.getElementById('batchesModalProductName').innerText = productName;
        const batches = JSON.parse(btn.dataset.batches || '[]');
        const tbody = document.getElementById('batchesModalTableBody');
        tbody.innerHTML = '';

        const isCostVisible = {{ (auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin()) ? 'true' : 'false' }};
        const colCount = isCostVisible ? 6 : 5;

        if (!batches || batches.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center py-4 text-muted"><i class="bi bi-info-circle me-1"></i>No batches recorded for this item yet.</td></tr>`;
            return;
        }

        batches.forEach(b => {
            const qty = parseFloat(b.quantity || 0);
            const initialQty = parseFloat(b.initial_quantity || 0);
            const sellPrice = parseFloat(b.selling_price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const costPrice = parseFloat(b.purchase_price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            let statusBadge = '';
            if (b.status === 'depleted' || qty <= 0) {
                statusBadge = '<span class="badge bg-secondary">Depleted</span>';
            } else if (b.status === 'expired') {
                statusBadge = '<span class="badge bg-danger">Expired</span>';
            } else {
                statusBadge = '<span class="badge bg-success">Active</span>';
            }

            const expiry = b.expiry_date ? b.expiry_date.substring(0, 10) : '<span class="text-muted">-</span>';
            const costCol = isCostVisible ? `<td>Rs. ${costPrice}</td>` : '';

            const row = `
                <tr>
                    <td class="fw-semibold text-primary"><i class="bi bi-box-seam me-1"></i>${b.batch_no || 'N/A'}</td>
                    <td class="fw-semibold">Rs. ${sellPrice}</td>
                    ${costCol}
                    <td>
                        <span class="fw-bold ${qty > 0 ? 'text-success' : 'text-muted'}">${qty.toFixed(2)}</span>
                        <span class="text-muted small"> / ${initialQty.toFixed(2)}</span>
                    </td>
                    <td>${expiry}</td>
                    <td>${statusBadge}</td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    });
});

document.querySelectorAll('.btn-sell-action').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.productId;
        document.getElementById('sellProductName').innerText = btn.dataset.productName;
        document.getElementById('sellAvailableQty').innerText = btn.dataset.available + ' units';
        document.getElementById('sellPriceInput').value = btn.dataset.sellingPrice || '';
        document.getElementById('sellForm').action = "{{ url('stock') }}/" + id + "/sell";
    });
});

document.querySelectorAll('.btn-return-action').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.productId;
        document.getElementById('returnProductName').innerText = btn.dataset.productName;
        document.getElementById('returnRefundAmount').value = btn.dataset.sellingPrice || '';
        document.getElementById('returnForm').action = "{{ url('stock') }}/" + id + "/return";
    });
});

document.querySelectorAll('.btn-damage-action').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.productId;
        document.getElementById('damageProductName').innerText = btn.dataset.productName;
        document.getElementById('damageAvailableQty').innerText = btn.dataset.available + ' units';
        document.getElementById('damageForm').action = "{{ url('stock') }}/" + id + "/damage";
    });
});

document.querySelectorAll('.btn-exchange-action').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.productId;
        document.getElementById('exchangeProductName').innerText = btn.dataset.productName;
        document.getElementById('exchangeForm').action = "{{ url('stock') }}/" + id + "/exchange";
    });
});
</script>
@endpush
@endsection
