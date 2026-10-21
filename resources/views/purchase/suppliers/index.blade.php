@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Suppliers</h4>
        <!-- <small class="text-secondary">Manage vendors, statutory tax compliance, and liability ledgers</small> -->
    </div>
    @if(auth()->user()->hasPermission('purchases.create'))
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importSupplierModal">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Excel / CSV
            </button>
            <a class="btn btn-primary" href="{{ route('purchases.suppliers.create') }}">
                <i class="bi bi-plus-lg me-1"></i> Add Supplier
            </a>
        </div>
    @endif
</div>

<div class="card p-3 mb-3 border-0 shadow-sm bg-white">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-4 col-md-5">
            <label class="form-label small fw-semibold">Search</label>
            <input class="form-control form-control-sm" name="search" value="{{ $search }}" placeholder="Supplier name, company, phone, email, NTN, STRN...">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label small fw-semibold">Status</label>
            <select class="form-select form-select-sm" name="status">
                <option value="">All Statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search me-1"></i> Search</button>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('purchases.suppliers.index') }}">Reset</a>
        </div>
        <div class="col-auto ms-auto">
            <x-per-page-select :paginator="$suppliers" />
        </div>
    </form>
</div>

<div class="card shadow-sm border-0 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary small text-uppercase">
                <tr>
                    <!-- <th style="width: 5%;">#</th> -->
                    <th style="width: 22%;">Supplier Name &amp; Company</th>
                    <th style="width: 18%;">Contact Details</th>
                    <th style="width: 18%;">Tax / Licence</th>
                    <th style="width: 15%;">Liability Account</th>
                    <th style="width: 12%;">Balance</th>
                    <th style="width: 5%;">Status</th>
                    <th style="width: 5%;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <!-- <td class="text-muted small">{{ $supplier->id }}</td> -->
                        <td>
                            <a href="{{ route('purchases.suppliers.show', $supplier) }}" class="fw-bold text-decoration-none text-dark">
                                {{ $supplier->name }}
                            </a>
                            @if($supplier->company_name)
                                <div class="text-secondary small">{{ $supplier->company_name }}</div>
                            @endif
                            <!-- @if($supplier->shop)
                                <div><small class="badge bg-light text-muted border"><i class="bi bi-shop me-1"></i>{{ $supplier->shop->name }}</small></div>
                            @endif -->
                        </td>
                        <td>
                            <div><i class="bi bi-telephone text-muted me-1"></i>{{ $supplier->phone ?? '—' }}</div>
                            @if($supplier->email)
                                <small class="text-secondary"><i class="bi bi-envelope text-muted me-1"></i>{{ $supplier->email }}</small>
                            @endif
                        </td>
                        <td class="small">
                            @if($supplier->ntn)
                                <div><span class="text-muted">NTN:</span> <strong>{{ $supplier->ntn }}</strong></div>
                            @endif
                            @if($supplier->strn)
                                <div><span class="text-muted">STRN:</span> <strong>{{ $supplier->strn }}</strong></div>
                            @endif
                            @if($supplier->license_number)
                                <div><span class="text-muted">Lic:</span> <strong>{{ $supplier->license_number }}</strong></div>
                            @endif
                            @if(!$supplier->ntn && !$supplier->strn && !$supplier->license_number)
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small">
                            @if($supplier->liabilityAccount)
                                <span class="badge bg-info-subtle text-info border">
                                    {{ $supplier->liabilityAccount->code }} - {{ $supplier->liabilityAccount->name }}
                                </span>
                            @else
                                <span class="text-muted">Default A/P</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold {{ $supplier->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                                Rs. {{ number_format($supplier->current_balance, 2) }}
                            </span>
                            @if($supplier->current_balance > 0)
                                <div class="small"><span class="badge bg-danger-subtle text-danger">Payable</span></div>
                            @endif
                        </td>
                        <td>
                            @if($supplier->status === 'active')
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="{{ route('purchases.suppliers.show', $supplier) }}" title="View Ledger">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->hasPermission('purchases.create'))
                                    <a class="btn btn-outline-primary" href="{{ route('purchases.suppliers.edit', $supplier) }}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                @if(auth()->user()->hasPermission('purchases.delete'))
                                    <form method="post" action="{{ route('purchases.suppliers.destroy', $supplier) }}" class="d-inline" data-confirm="Are you sure you want to delete this supplier?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-secondary">
                            <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                            No suppliers found. Click "Add Supplier" or "Import Excel" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-table-pagination :paginator="$suppliers" />
</div>

{{-- Excel/CSV Import Modal --}}
<div class="modal fade" id="importSupplierModal" tabindex="-1" aria-labelledby="importSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post" action="{{ route('purchases.suppliers.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="importSupplierModalLabel">
                        <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Import Suppliers from Excel / CSV
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Upload a spreadsheet file (<code>.xlsx, .xls, .csv</code>) containing your supplier directory.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Choose Spreadsheet File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="file" accept=".xlsx,.xls,.csv" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Default Liability Account</label>
                        <select class="form-select form-select-sm" name="liability_account_id">
                            <option value="">Default (Accounts Payable)</option>
                            @foreach($liabilityAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Assigned to imported suppliers for balance sheet liabilities.</small>
                    </div>

                    <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Need the standard column headers?</span>
                        <a href="{{ route('purchases.suppliers.template') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i> Download Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm">
                        <i class="bi bi-upload me-1"></i> Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
