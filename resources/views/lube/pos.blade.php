@extends('layouts.app')
@section('title', 'Auto Lube Service Counter')

@section('content')
<div class="container-fluid px-0">
    {{-- MAIN TWO-COLUMN SERVICE COUNTER GRID --}}
    <form id="lubePosForm" onsubmit="return false;">
        @csrf
        <div class="row g-3">
            {{-- LEFT COLUMN: Vehicle Info, Mileage Calc & Consumables --}}
            <div class="col-lg-8">
                
                {{-- 1. QUICK VEHICLE & CUSTOMER LOOKUP (HIGHLIGHTED) --}}
                <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #f0f7ff 0%, #e0e7ff 100%); border: 1.5px solid #93c5fd !important; border-radius: 12px;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white px-2 py-1 shadow-sm">
                                    <i class="bi bi-search me-1"></i> QUICK LOOKUP
                                </span>
                                <strong class="text-primary-emphasis small text-uppercase" style="letter-spacing: .5px;">Quick Vehicle &amp; Customer Lookup</strong>
                            </div>
                            <div class="d-flex align-items-center gap-1.5">
                                <button class="btn btn-sm btn-outline-secondary bg-white text-secondary py-0.5 px-2.5" type="button" id="btnResetLookup" title="Reset all fields">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                                @if(auth()->user()->hasPermission('reports.sales'))
                                    <a href="{{ route('reports.cash-register') }}" class="btn btn-sm btn-outline-danger bg-white py-0.5 px-2.5" title="Close Cash Drawer">
                                        <i class="bi bi-lock-fill me-1"></i> Close Cash
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-primary fw-bold border-end-0">
                                        <i class="bi bi-car-front me-1"></i> Plate
                                    </span>
                                    <input type="text" id="lookupPlateInput" class="form-control form-control-sm text-uppercase font-monospace fw-bold bg-white" placeholder="e.g. LEA-1234 or BWP-786" autocomplete="off">
                                    <button class="btn btn-primary btn-sm px-3 fw-semibold" type="button" id="btnSearchPlate">
                                        <i class="bi bi-search me-1"></i> Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-success fw-bold border-end-0">
                                        <i class="bi bi-telephone me-1"></i> Phone
                                    </span>
                                    <input type="text" id="lookupPhoneInput" class="form-control form-control-sm font-monospace bg-white" placeholder="e.g. 0300-1234567" autocomplete="off">
                                    <button class="btn btn-success btn-sm px-3 fw-semibold" type="button" id="btnSearchPhone">
                                        <i class="bi bi-telephone-inbound me-1"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. VEHICLE & CUSTOMER PROFILE --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom py-2.5 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-car-front-fill text-primary"></i>
                            <strong class="text-dark">Vehicle &amp; Customer Profile</strong>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary" id="vehicleStatusBadge">New / Unsaved</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2.5">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Plate # <span class="text-danger">*</span></label>
                                <input type="text" name="plate_number" id="plateNumber" class="form-control form-control-sm text-uppercase font-monospace fw-bold" placeholder="LEA-24-1234" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Vehicle Type</label>
                                <select name="vehicle_type" id="vehicleType" class="form-select form-select-sm">
                                    @foreach($vehicleTypes as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Make <span class="text-danger">*</span></label>
                                <input type="text" name="make" id="vehicleMake" class="form-control form-control-sm" placeholder="e.g. Honda, Suzuki" list="makesList" required>
                                <datalist id="makesList">
                                    <option value="Toyota">
                                    <option value="Honda">
                                    <option value="Suzuki">
                                    <option value="Hyundai">
                                    <option value="Kia">
                                    <option value="Changan">
                                    <option value="MG">
                                    <option value="Nissan">
                                    <option value="Daihatsu">
                                </datalist>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Model <span class="text-danger">*</span></label>
                                <input type="text" name="model" id="vehicleModel" class="form-control form-control-sm" placeholder="e.g. Civic, Alto" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" id="customerName" class="form-control form-control-sm" placeholder="Customer Full Name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Mobile Phone <span class="text-danger">*</span></label>
                                <input type="text" name="customer_phone" id="customerPhone" class="form-control form-control-sm font-monospace" placeholder="0300-1234567" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Model Year / Notes</label>
                                <input type="text" name="year" id="vehicleYear" class="form-control form-control-sm" placeholder="e.g. 2023, 1.5L Turbo">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. MILEAGE & NEXT SERVICE CALCULATOR --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom py-2.5 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-speedometer2 text-info"></i>
                            <strong class="text-dark">Mileage &amp; Next Service Calculator</strong>
                        </div>
                        <span class="badge bg-primary-subtle text-primary">Live Auto-Computed</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Current Odometer (KM) <small class="text-muted fw-normal">(Optional)</small></label>
                                <input type="number" name="current_odometer" id="currentOdometer" class="form-control fw-bold font-monospace text-primary fs-5" placeholder="e.g. 45000" min="0">
                                <div class="form-text text-muted" style="font-size: 11px;">Optional: Leave blank for labor-only services.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Oil Change Interval</label>
                                <div class="input-group input-group-sm mb-1.5">
                                    <span class="input-group-text bg-light text-secondary"><i class="bi bi-speedometer"></i></span>
                                    <input type="number" name="interval_km" id="customIntervalKm" class="form-control font-monospace fw-bold text-center" placeholder="e.g. 5000" value="5000" step="500" min="500">
                                    <span class="input-group-text bg-light text-muted">KM</span>
                                </div>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <button type="button" class="btn btn-outline-secondary py-0.5 px-1 btn-interval-preset" data-km="3000">3k</button>
                                    <button type="button" class="btn btn-outline-primary py-0.5 px-1 btn-interval-preset active" data-km="5000">5k</button>
                                    <button type="button" class="btn btn-outline-secondary py-0.5 px-1 btn-interval-preset" data-km="7000">7k</button>
                                    <button type="button" class="btn btn-outline-secondary py-0.5 px-1 btn-interval-preset" data-km="10000">10k</button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2.5 rounded-3 bg-light border text-center">
                                    <small class="text-muted d-block fw-semibold" style="font-size: 11px;">Next Due Mileage</small>
                                    <div class="fw-bold text-success fs-5 font-monospace" id="displayNextDue">-</div>
                                    <small class="text-muted" style="font-size: 11px;">Est. Date: <span id="displayEstDate" class="fw-semibold text-dark">-</span></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Oil Grade Installed <small class="text-muted fw-normal">(Optional)</small></label>
                                <select name="oil_grade" id="oilGrade" class="form-select form-select-sm">
                                    <option value="">Choose Installed Viscosity / Grade (Optional) --</option>
                                    @foreach($viscosityGrades as $grade)
                                        <option value="{{ $grade }}" @selected($grade === '5W-30')>{{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold mb-0">Mechanic / Pit Tech</label>
                                    <a href="#" class="small text-decoration-none" data-bs-toggle="modal" data-bs-target="#newTechModal">+ New Tech</a>
                                </div>
                                <select name="pit_technician_id" id="pitTechnicianSelect" class="form-select form-select-sm">
                                    <option value="">Select Pit Technician (Optional) --</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}">{{ $tech->name }} (Bay {{ $tech->bay_number ?? '1' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. AUTO LUBE BAY SERVICES & LOOSE OIL --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom py-2.5 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-droplet-half text-info fs-5"></i>
                            <strong class="text-dark">Auto Lube Bay Services &amp; Bulk Oil</strong>
                        </div>
                        <span class="badge bg-info-subtle text-info-emphasis">Bay Operations</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2.5">
                            {{-- 1st Dropdown: Packages --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1 text-primary d-flex align-items-center gap-1">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5" style="font-size: 10px;">1</span>
                                    <i class="bi bi-boxes"></i> Apply Service Package (Combo Bundle):
                                </label>
                                <select id="packageSelectPicker" class="form-select form-select-sm border-primary-subtle">
                                    <option value="">Choose Service Package (Auto-Applies Bundle) --</option>
                                    @foreach($packages as $pkg)
                                        <option value="{{ $pkg->id }}" 
                                                data-name="{{ $pkg->name }}" 
                                                data-price="{{ $pkg->package_price }}" 
                                                data-labor="{{ $pkg->labor_amount }}"
                                                data-items='@json($pkg->items)'>
                                            📦 {{ $pkg->name }} (Rs. {{ number_format($pkg->package_price) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 2nd Dropdown: Pit Services & Labor Fee --}}
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold mb-0 text-warning-emphasis d-flex align-items-center gap-1">
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-1.5 py-0.5" style="font-size: 10px;">2</span>
                                        <i class="bi bi-wrench-adjustable"></i> Add Pit Service / Labor Fee:
                                    </label>
                                    <a href="{{ route('lube.services.index') }}" target="_blank" class="small text-decoration-none" style="font-size: 11px;">
                                        <i class="bi bi-gear"></i> Manage Catalog &rarr;
                                    </a>
                                </div>
                                <select id="serviceAutoPicker" class="form-select form-select-sm border-warning-subtle">
                                    <option value="">Select Labor &amp; Pit Service (Auto-Adds)</option>
                                    @php $groupedServices = $laborServices->groupBy('category'); @endphp
                                    @foreach($groupedServices as $catName => $srvList)
                                        <optgroup label="{{ $catName }}">
                                            @foreach($srvList as $ls)
                                                <option value="{{ $ls->id }}" data-name="{{ $ls->name }}" data-price="{{ $ls->price }}">
                                                    {{ $ls->name }} — Rs. {{ number_format($ls->price, 2) }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <div class="d-flex flex-wrap gap-1 mt-1.5">
                                    @foreach($laborServices->take(2) as $topSrv)
                                        <button type="button" class="btn btn-sm btn-outline-warning text-dark py-0 px-2 chip-labor" data-name="{{ $topSrv->name }}" data-price="{{ $topSrv->price }}" style="font-size: 10.5px;">
                                            + {{ $topSrv->name }} (Rs. {{ number_format($topSrv->price, 2) }})
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- 3rd Dropdown: Separate Selection of Loose Product --}}
                            <div class="col-12 mt-2">
                                <label class="form-label small fw-semibold mb-1 text-info-emphasis d-flex align-items-center gap-1">
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-1.5 py-0.5" style="font-size: 10px;">3</span>
                                    <i class="bi bi-droplet-half text-info"></i> Loose Oil / Liters (From Bay Tanks &amp; Drums):
                                </label>
                                <select id="looseProductPicker" class="form-select form-select-sm border-info-subtle">
                                    <option value="">Select Loose Oil / Liters (Auto-Adds on Click)</option>
                                    @forelse($looseProducts as $lprod)
                                        <option value="{{ $lprod->id }}" 
                                                data-name="{{ $lprod->item_name }}" 
                                                data-price="{{ $lprod->selling_price }}" 
                                                data-unit="{{ $lprod->unit ?? 'LITER' }}" 
                                                data-stock="{{ $lprod->quantity }}"
                                                data-isloose="1">
                                            🛢️ {{ $lprod->item_name }} (Rs. {{ number_format($lprod->selling_price, 2) }}/L | Stock: {{ $lprod->quantity }} {{ $lprod->unit }})
                                        </option>
                                    @empty
                                        <option value="" disabled>No in-stock loose products with defined pricing found.</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. PACKAGED PRODUCTS, FILTERS & PARTS (SEPARATE SECTION) --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                    <div class="card-header bg-white border-bottom py-2.5 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 11px;">4</span>
                            <i class="bi bi-box-seam text-success"></i>
                            <strong class="text-dark">Packaged Oil, Filters &amp; Parts</strong>
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Packaged Bottles, Filters &amp; Parts &bull; Auto-Adds to Cart</small>
                    </div>
                    <div class="card-body p-3">
                        <label class="form-label small fw-semibold mb-1 text-success d-flex align-items-center gap-1">
                            <i class="bi bi-search"></i> Select Packaged Product / Filter / Part:
                        </label>
                        <select id="productAutoPicker" class="form-select form-select-sm border-success-subtle">
                            <option value="">Select Packaged Product / Filter / Part (Auto-Adds)</option>
                            @forelse($products as $prod)
                                <option value="{{ $prod->id }}" 
                                        data-name="{{ $prod->item_name }}" 
                                        data-price="{{ $prod->selling_price }}" 
                                        data-unit="{{ $prod->unit ?? 'PCS' }}" 
                                        data-stock="{{ $prod->quantity }}"
                                        data-isloose="0">
                                    📦 {{ $prod->item_name }} (Rs. {{ number_format($prod->selling_price, 2) }} | Stock: {{ $prod->quantity }} {{ $prod->unit }})
                                </option>
                            @empty
                                <option value="" disabled>No in-stock packaged products with defined pricing found.</option>
                            @endforelse
                        </select>
                    </div>
                </div>

                {{-- 6. ACTIVE SERVICE CONSUMABLES & CART TABLE (SEPARATE SECTION) --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom py-2.5 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-cart3 text-primary"></i>
                            <strong class="text-dark">Active Service Consumables &amp; Cart</strong>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 11px;">Per-Item Discounts Available</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive border rounded-3">
                            <table class="table table-sm table-hover align-middle mb-0" id="cartTable">
                                <thead class="table-light text-secondary small">
                                    <tr>
                                        <th>Item / Consumable</th>
                                        <th style="width: 100px;" class="text-center">Qty / Liters</th>
                                        <th style="width: 110px;" class="text-end">Unit Price</th>
                                        <th style="width: 110px;" class="text-end">Discount (Rs.)</th>
                                        <th style="width: 120px;" class="text-end">Net Total</th>
                                        <th style="width: 45px;" class="text-center"><i class="bi bi-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <tr id="emptyCartRow">
                                        <td colspan="6" class="text-center text-muted py-3 small">
                                            <em>No consumables added yet. Select a product, package, or service charge above.</em>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            {{-- RIGHT COLUMN: Pit Inspection (Moved Above!) & Billing Checkout --}}
            <div class="col-lg-4">

                {{-- 4. MULTI-POINT PIT INSPECTION (MOVED UP TO THE TOP!) --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px; border-top: 3px solid #10b981 !important;">
                    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check2-square text-success fs-5"></i>
                            <div>
                                <strong class="text-dark small text-uppercase">Multi-Point Pit Inspection</strong>
                                <small class="text-muted d-block" style="font-size: 10.5px;">Printed on service sticker &amp; receipt</small>
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1.5" id="btnToggleAllInspection" style="font-size: 10px;">Select All</button>
                        </div>
                    </div>
                    <div class="card-body p-2.5">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-check form-switch ps-4 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch" name="inspection[oil_drained]" id="chkOilDrained" checked>
                                    <label class="form-check-label small fw-semibold" for="chkOilDrained" style="font-size: 11.5px;">Oil Drained</label>
                                </div>
                                <div class="form-check form-switch ps-4 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch" name="inspection[oil_filter]" id="chkOilFilter" checked>
                                    <label class="form-check-label small fw-semibold" for="chkOilFilter" style="font-size: 11.5px;">Oil Filter</label>
                                </div>
                                <div class="form-check form-switch ps-4 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch" name="inspection[air_filter]" id="chkAirFilter" checked>
                                    <label class="form-check-label small fw-semibold" for="chkAirFilter" style="font-size: 11.5px;">Air Filter</label>
                                </div>
                                <div class="form-check form-switch ps-4 mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="inspection[coolant]" id="chkCoolant">
                                    <label class="form-check-label small text-secondary" for="chkCoolant" style="font-size: 11.5px;">Coolant</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch ps-4 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch" name="inspection[brake_fluid]" id="chkBrakeFluid">
                                    <label class="form-check-label small text-secondary" for="chkBrakeFluid" style="font-size: 11.5px;">Brake Fluid</label>
                                </div>
                                <div class="form-check form-switch ps-4 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch" name="inspection[tire_pressure]" id="chkTirePressure">
                                    <label class="form-check-label small text-secondary" for="chkTirePressure" style="font-size: 11.5px;">Tire Pressure</label>
                                </div>
                                <div class="form-check form-switch ps-4 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch" name="inspection[washer_fluid]" id="chkWasherFluid">
                                    <label class="form-check-label small text-secondary" for="chkWasherFluid" style="font-size: 11.5px;">Washer Fluid</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. CHECKOUT & BILLING --}}
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-white border-bottom py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-wallet2 text-primary"></i>
                            <strong class="text-dark">Checkout &amp; Billing</strong>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-1.5 small text-secondary">
                            <span>Total Consumables:</span>
                            <span class="fw-bold text-dark font-monospace" id="displayTotalItems">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1.5 small text-secondary">
                            <span>Gross Subtotal:</span>
                            <span class="fw-bold text-dark font-monospace" id="displayGrossSubtotal">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small text-secondary">
                            <span>Item Discounts:</span>
                            <span class="text-danger font-monospace" id="displayItemDiscounts">- Rs. 0.00</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Special Order Discount (Rs.)</label>
                            <input type="number" id="orderDiscount" class="form-control form-control-sm text-end font-monospace" value="0" min="0" step="10">
                        </div>

                        <div class="p-3 rounded-3 bg-light border text-center mb-3">
                            <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: .5px; font-size: 11px;">NET TOTAL PAYABLE</small>
                            <div class="display-6 fw-bold text-success font-monospace" id="displayNetPayable">Rs. 0.00</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Payment Method</label>
                            <select name="payment_method" id="paymentMethod" class="form-select form-select-sm fw-semibold">
                                <option value="cash" selected>💵 Cash</option>
                                <option value="card">💳 Card / POS Machine</option>
                                <option value="bank">🏦 Bank Transfer</option>
                                <option value="credit">📝 Credit Ledger (Unpaid)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Bay Notes / Mechanic Remarks</label>
                            <textarea id="serviceNotes" class="form-control form-control-sm" rows="2" placeholder="e.g. Minor oil sump weep, recommend plug washer next service..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary fw-semibold py-2.5 shadow-sm" id="btnSubmitService">
                                <i class="bi bi-printer me-1"></i> Complete &amp; Print Invoice
                            </button>
                            <button type="button" class="btn btn-outline-success fw-semibold py-2" id="btnPrintStickerOnly">
                                <i class="bi bi-sticky me-1"></i> Print Windshield Sticker
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- MODAL: QUICK ADD PIT TECHNICIAN --}}
<div class="modal fade" id="newTechModal" tabindex="-1" aria-labelledby="newTechModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6" id="newTechModalLabel"><i class="bi bi-person-plus me-1 text-primary"></i> Register New Pit Technician</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickTechForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mechanic Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Muhammad Aslam" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="0300-1234567">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Bay / Pit Assigned</label>
                            <input type="text" name="bay_number" class="form-control form-control-sm" placeholder="e.g. Pit 1">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Commission Type</label>
                            <select name="commission_type" class="form-select form-select-sm">
                                <option value="fixed">Fixed Rs per Job</option>
                                <option value="percentage">% of Labor Fee</option>
                                <option value="none">No Commission</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Commission Rate</label>
                            <input type="number" name="commission_rate" class="form-control form-control-sm" value="100" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i> Save Technician</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: SERVICE SUCCESS MODAL --}}
<div class="modal fade" id="serviceSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <div class="mb-3">
                <span class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                    <i class="bi bi-check2-circle display-5"></i>
                </span>
            </div>
            <h4 class="h5 fw-bold mb-1">Service Order Completed!</h4>
            <p class="text-muted small mb-3" id="successMsgText">Invoice generated successfully.</p>

            <div class="bg-light p-3 rounded-3 text-start mb-3 font-monospace small">
                <div class="d-flex justify-content-between mb-1"><span>Invoice #:</span><strong id="modalInvoiceNo">-</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>Vehicle Plate:</span><strong id="modalPlate">-</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>Next Due Mileage:</span><strong class="text-success" id="modalNextDue">-</strong></div>
                <div class="d-flex justify-content-between"><span>Est. Next Date:</span><strong id="modalNextDate">-</strong></div>
            </div>

            <div class="d-grid gap-2">
                <a href="#" id="modalBtnReceipt" target="_blank" class="btn btn-primary fw-semibold">
                    <i class="bi bi-receipt me-1"></i> Open &amp; Print Service Invoice
                </a>
                <a href="#" id="modalBtnSticker" target="_blank" class="btn btn-outline-success fw-semibold">
                    <i class="bi bi-sticky me-1"></i> Print Windshield Sticker
                </a>
                <button type="button" class="btn btn-light mt-2" onclick="location.reload();">
                    <i class="bi bi-plus-circle me-1"></i> Next Vehicle Service
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let cartItems = [];
    let laborAmount = 0;

    const currentOdoInput = document.getElementById('currentOdometer');
    const customIntervalInput = document.getElementById('customIntervalKm');
    const intervalPresetBtns = document.querySelectorAll('.btn-interval-preset');
    const displayNextDue = document.getElementById('displayNextDue');
    const displayEstDate = document.getElementById('displayEstDate');

    // 1. Calculate Next Due Mileage & Est Date
    function recalculateNextDue() {
        const currentKm = parseInt(currentOdoInput.value) || 0;
        const selectedInterval = parseInt(customIntervalInput ? customIntervalInput.value : 5000) || 5000;

        if (currentKm > 0) {
            const nextKm = currentKm + selectedInterval;
            displayNextDue.textContent = nextKm.toLocaleString() + ' KM';

            const daysToAdd = Math.max(30, Math.round(selectedInterval / 50));
            const estDate = new Date();
            estDate.setDate(estDate.getDate() + daysToAdd);
            const dateStr = estDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            displayEstDate.textContent = dateStr;
        } else {
            displayNextDue.textContent = '-';
            displayEstDate.textContent = '-';
        }
    }

    currentOdoInput.addEventListener('input', recalculateNextDue);
    if (customIntervalInput) {
        customIntervalInput.addEventListener('input', recalculateNextDue);
    }
    intervalPresetBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            intervalPresetBtns.forEach(b => {
                b.classList.remove('active', 'btn-outline-primary');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('active', 'btn-outline-primary');
            if (customIntervalInput) {
                customIntervalInput.value = this.dataset.km;
            }
            recalculateNextDue();
        });
    });

    // 2. Search & Lookup
    const plateInput = document.getElementById('lookupPlateInput');
    const phoneInput = document.getElementById('lookupPhoneInput');
    const vehicleStatusBadge = document.getElementById('vehicleStatusBadge');

    function performSearch(term) {
        if (!term || term.trim() === '') return;
        fetch(`{{ route('lube.pos.search') }}?query=${encodeURIComponent(term.trim())}`)
            .then(res => res.json())
            .then(data => {
                if (data.found && data.vehicle) {
                    const v = data.vehicle;
                    document.getElementById('plateNumber').value = v.plate_number;
                    document.getElementById('vehicleType').value = v.vehicle_type || 'sedan';
                    document.getElementById('vehicleMake').value = v.make;
                    document.getElementById('vehicleModel').value = v.model;
                    document.getElementById('customerName').value = v.customer_name;
                    document.getElementById('customerPhone').value = v.customer_phone || '';
                    document.getElementById('vehicleYear').value = v.year || '';
                    
                    if (v.last_oil_grade) {
                        document.getElementById('oilGrade').value = v.last_oil_grade;
                    }

                    vehicleStatusBadge.className = 'badge bg-success-subtle text-success';
                    vehicleStatusBadge.innerHTML = `<i class="bi bi-check-circle me-1"></i> Registered (Last: ${v.last_odometer ? v.last_odometer.toLocaleString() + ' KM' : 'N/A'})`;
                    
                    if (v.last_odometer) {
                        currentOdoInput.setAttribute('placeholder', 'Prev: ' + v.last_odometer.toLocaleString() + ' KM');
                    }
                } else {
                    vehicleStatusBadge.className = 'badge bg-warning-subtle text-warning-emphasis';
                    vehicleStatusBadge.innerHTML = `<i class="bi bi-info-circle me-1"></i> New Vehicle`;
                }
            })
            .catch(err => console.error(err));
    }

    document.getElementById('btnSearchPlate').addEventListener('click', () => performSearch(plateInput.value));
    plateInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') performSearch(plateInput.value); });

    document.getElementById('btnSearchPhone').addEventListener('click', () => performSearch(phoneInput.value));
    phoneInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') performSearch(phoneInput.value); });

    document.getElementById('btnResetLookup').addEventListener('click', () => {
        plateInput.value = '';
        phoneInput.value = '';
        document.getElementById('lubePosForm').reset();
        if (customIntervalInput) customIntervalInput.value = 5000;
        intervalPresetBtns.forEach(b => {
            b.classList.remove('active', 'btn-outline-primary');
            b.classList.add('btn-outline-secondary');
            if (b.dataset.km === '5000') {
                b.classList.remove('btn-outline-secondary');
                b.classList.add('active', 'btn-outline-primary');
            }
        });
        cartItems = [];
        laborAmount = 0;
        renderCart();
        recalculateNextDue();
        vehicleStatusBadge.className = 'badge bg-secondary-subtle text-secondary';
        vehicleStatusBadge.textContent = 'New / Unsaved';
    });

    // Toggle all inspection switches
    const btnToggleAll = document.getElementById('btnToggleAllInspection');
    if (btnToggleAll) {
        let allSelected = false;
        btnToggleAll.addEventListener('click', function() {
            allSelected = !allSelected;
            const checkboxes = document.querySelectorAll('input[name^="inspection["]');
            checkboxes.forEach(cb => cb.checked = allSelected);
            btnToggleAll.textContent = allSelected ? 'Deselect All' : 'Select All';
        });
    }

    // 3. Auto-Add Loose Product on Selection
    const looseProductPicker = document.getElementById('looseProductPicker');
    if (looseProductPicker) {
        looseProductPicker.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) return;

            const itemId = parseInt(opt.value);
            const name = opt.dataset.name;
            const price = parseFloat(opt.dataset.price) || 0;
            const defaultQty = 1.00;
            const lineTotal = price * defaultQty;

            cartItems.push({
                item_id: itemId,
                name: name,
                quantity: defaultQty,
                unit_price: price,
                discount: 0,
                line_total: lineTotal,
                is_labor: false
            });

            this.value = '';
            renderCart();
        });
    }

    // 4. Auto-Add All Items / Packaged Products on Selection
    const productAutoPicker = document.getElementById('productAutoPicker');
    productAutoPicker.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;

        const itemId = parseInt(opt.value);
        const name = opt.dataset.name;
        const price = parseFloat(opt.dataset.price) || 0;
        const unit = opt.dataset.unit || 'PCS';
        const defaultQty = unit === 'LITER' ? 4.00 : 1.00;
        const lineTotal = price * defaultQty;

        cartItems.push({
            item_id: itemId,
            name: name,
            quantity: defaultQty,
            unit_price: price,
            discount: 0,
            line_total: lineTotal,
            is_labor: false
        });

        this.value = '';
        renderCart();
    });

    // 4. Auto-Add Service / Labor Fee on Selection
    const serviceAutoPicker = document.getElementById('serviceAutoPicker');
    serviceAutoPicker.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;

        const name = opt.dataset.name;
        const price = parseFloat(opt.dataset.price) || 0;

        cartItems.push({
            item_id: null,
            name: name,
            quantity: 1,
            unit_price: price,
            discount: 0,
            line_total: price,
            is_labor: true
        });

        this.value = '';
        renderCart();
    });

    // Quick Labor Chips
    document.querySelectorAll('.chip-labor').forEach(chip => {
        chip.addEventListener('click', function() {
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price) || 0;
            
            cartItems.push({
                item_id: null,
                name: name,
                quantity: 1,
                unit_price: price,
                discount: 0,
                line_total: price,
                is_labor: true
            });
            renderCart();
        });
    });

    // 5. Apply Service Package Dropdown
    const packageSelectPicker = document.getElementById('packageSelectPicker');
    packageSelectPicker.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;

        const pkgName = opt.dataset.name;
        const pkgLabor = parseFloat(opt.dataset.labor) || 0;
        const items = JSON.parse(opt.dataset.items || '[]');

        items.forEach(it => {
            if (it.inventory_item) {
                const price = parseFloat(it.inventory_item.selling_price) || 0;
                const qty = parseFloat(it.quantity) || 1;
                cartItems.push({
                    item_id: it.inventory_item.id,
                    name: it.inventory_item.item_name,
                    quantity: qty,
                    unit_price: price,
                    discount: 0,
                    line_total: price * qty,
                    is_labor: false
                });
            }
        });

        if (pkgLabor > 0) {
            cartItems.push({
                item_id: null,
                name: `${pkgName} Service Labor`,
                quantity: 1,
                unit_price: pkgLabor,
                discount: 0,
                line_total: pkgLabor,
                is_labor: true
            });
        }

        this.value = '';
        renderCart();
    });

    // 6. Render Cart Table with Editable Qty & Per-Item Discounts
    function renderCart() {
        const tbody = document.getElementById('cartTableBody');
        tbody.innerHTML = '';

        if (cartItems.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyCartRow">
                    <td colspan="6" class="text-center text-muted py-3 small">
                        <em>No consumables added yet. Select a product, package, or service charge above.</em>
                    </td>
                </tr>`;
            updateTotals();
            return;
        }

        cartItems.forEach((it, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <span class="fw-semibold text-dark">${it.name}</span>
                    ${it.is_labor ? '<span class="badge bg-warning-subtle text-warning-emphasis ms-1" style="font-size:10px;">Labor</span>' : ''}
                </td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm text-center font-monospace px-1" 
                           value="${it.quantity}" 
                           style="width: 80px; margin: 0 auto;"
                           onchange="updateItemQty(${idx}, this.value)">
                </td>
                <td class="text-end font-monospace">Rs. ${it.unit_price.toFixed(2)}</td>
                <td class="text-end">
                    <input type="number" class="form-control form-control-sm text-end font-monospace text-danger px-1" 
                           value="${it.discount || 0}" min="0" step="10" 
                           style="width: 85px; margin-left: auto;"
                           id="discInput_${idx}"
                           oninput="updateItemDiscount(${idx}, this.value)">
                </td>
                <td class="text-end font-monospace fw-bold text-dark" id="netLineDisplay_${idx}">
                    Rs. ${it.line_total.toFixed(2)}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-danger p-0 border-0" onclick="removeCartItem(${idx})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>`;
            tbody.appendChild(tr);
        });

        updateTotals();
    }

    window.updateItemQty = function(idx, val) {
        const qty = Math.max(0.01, parseFloat(val) || 1);
        cartItems[idx].quantity = qty;
        cartItems[idx].line_total = Math.max(0, (qty * cartItems[idx].unit_price) - (cartItems[idx].discount || 0));
        renderCart();
    };

    window.updateItemDiscount = function(idx, val) {
        const disc = Math.max(0, parseFloat(val) || 0);
        cartItems[idx].discount = disc;
        cartItems[idx].line_total = Math.max(0, (cartItems[idx].quantity * cartItems[idx].unit_price) - disc);
        
        const lineEl = document.getElementById(`netLineDisplay_${idx}`);
        if (lineEl) {
            lineEl.textContent = 'Rs. ' + cartItems[idx].line_total.toFixed(2);
        }
        updateTotals();
    };

    window.removeCartItem = function(idx) {
        cartItems.splice(idx, 1);
        renderCart();
    };

    function updateTotals() {
        let grossSubtotal = 0;
        let itemDiscountsTotal = 0;
        laborAmount = 0;

        cartItems.forEach(it => {
            grossSubtotal += (it.quantity * it.unit_price);
            itemDiscountsTotal += (it.discount || 0);
            if (it.is_labor) {
                laborAmount += it.line_total;
            }
        });

        const orderDiscount = parseFloat(document.getElementById('orderDiscount').value) || 0;
        const netPayable = Math.max(0, grossSubtotal - itemDiscountsTotal - orderDiscount);

        document.getElementById('displayTotalItems').textContent = cartItems.length;
        document.getElementById('displayGrossSubtotal').textContent = 'Rs. ' + grossSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('displayItemDiscounts').textContent = '- Rs. ' + itemDiscountsTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('displayNetPayable').textContent = 'Rs. ' + netPayable.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    document.getElementById('orderDiscount').addEventListener('input', updateTotals);

    // 4. Quick Add Pit Technician AJAX
    const quickTechForm = document.getElementById('quickTechForm');
    quickTechForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(quickTechForm);
        fetch(`{{ route('lube.technicians.store') }}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.technician) {
                const opt = document.createElement('option');
                opt.value = data.technician.id;
                opt.textContent = `${data.technician.name} (Bay ${data.technician.bay_number || '1'})`;
                opt.selected = true;
                document.getElementById('pitTechnicianSelect').appendChild(opt);

                const modal = bootstrap.Modal.getInstance(document.getElementById('newTechModal'));
                if (modal) modal.hide();
                quickTechForm.reset();
            }
        })
        .catch(err => alert('Failed to register technician. Please verify input.'));
    });

    // 5. Submit Service Order
    const btnSubmit = document.getElementById('btnSubmitService');
    btnSubmit.addEventListener('click', function() {
        // Basic Validations
        const plate = document.getElementById('plateNumber').value.trim();
        const make = document.getElementById('vehicleMake').value.trim();
        const model = document.getElementById('vehicleModel').value.trim();
        const custName = document.getElementById('customerName').value.trim();
        const custPhone = document.getElementById('customerPhone').value.trim();
        const currentOdo = parseInt(currentOdoInput.value) || 0;
        const oilGrade = document.getElementById('oilGrade').value;

        if (!plate || !make || !model || !custName || !custPhone) {
            alert('Please complete all required vehicle & customer profile fields.');
            return;
        }

        const selectedInterval = parseInt(customIntervalInput ? customIntervalInput.value : 5000) || 5000;

        const inspection = {
            oil_drained: document.getElementById('chkOilDrained').checked,
            oil_filter: document.getElementById('chkOilFilter').checked,
            air_filter: document.getElementById('chkAirFilter').checked,
            coolant: document.getElementById('chkCoolant').checked,
            brake_fluid: document.getElementById('chkBrakeFluid').checked,
            tire_pressure: document.getElementById('chkTirePressure').checked,
            washer_fluid: document.getElementById('chkWasherFluid').checked,
        };

        const payload = {
            _token: '{{ csrf_token() }}',
            plate_number: plate,
            vehicle_type: document.getElementById('vehicleType').value,
            make: make,
            model: model,
            year: document.getElementById('vehicleYear').value,
            customer_name: custName,
            customer_phone: custPhone,
            current_odometer: currentOdo > 0 ? currentOdo : null,
            interval_km: selectedInterval,
            oil_grade: oilGrade || null,
            pit_technician_id: document.getElementById('pitTechnicianSelect').value || null,
            inspection_checklist: inspection,
            items: cartItems,
            labor_amount: laborAmount,
            discount: parseFloat(document.getElementById('orderDiscount').value) || 0,
            payment_method: document.getElementById('paymentMethod').value,
            notes: document.getElementById('serviceNotes').value,
        };

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Processing Service...`;

        fetch(`{{ route('lube.pos.store') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = `<i class="bi bi-printer me-1"></i> Complete &amp; Print Invoice`;

            if (data.success) {
                document.getElementById('modalInvoiceNo').textContent = data.invoice_no;
                document.getElementById('modalPlate').textContent = data.plate_number;
                document.getElementById('modalNextDue').textContent = data.next_due_odometer;
                document.getElementById('modalNextDate').textContent = data.next_due_date;
                document.getElementById('modalBtnReceipt').href = data.receipt_url;
                document.getElementById('modalBtnSticker').href = data.sticker_url;

                // Open Sticker in small popup window
                window.open(data.sticker_url, 'stickerPopup', 'width=450,height=550');

                const successModal = new bootstrap.Modal(document.getElementById('serviceSuccessModal'));
                successModal.show();
            } else {
                alert(data.message || 'An error occurred while saving the service record.');
            }
        })
        .catch(err => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = `<i class="bi bi-printer me-1"></i> Complete &amp; Print Invoice`;
            alert('Service processing failed. Please check your network and fields.');
            console.error(err);
        });
    });

});
</script>
@endpush
@endsection
