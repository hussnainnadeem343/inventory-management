@extends('layouts.app')
@section('title', 'Create Journal Voucher')
@section('content')
<form method="post" action="{{ route('finance.journal.store') }}" id="jvForm">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Manual Journal Voucher (JV)</h4>
            <small class="text-secondary">Record double-entry manual adjustments & transactions</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.journal.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                <i class="bi bi-check2-circle me-1"></i> Post Journal Voucher
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Entry Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Description / Narration <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="description" value="{{ old('description') }}" placeholder="e.g. Month-end adjustment, owner capital investment, or loan adjustment" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-1"></i> Debit & Credit Lines</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                <i class="bi bi-plus-lg me-1"></i> Add Line
            </button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="linesTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40%;">Account</th>
                        <th style="width: 25%;">Narration / Detail</th>
                        <th style="width: 15%;" class="text-end">Debit (Rs.)</th>
                        <th style="width: 15%;" class="text-end">Credit (Rs.)</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="linesBody">
                    <!-- Dynamic lines -->
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-end">Totals:</td>
                        <td class="text-end text-success" id="totalDebitDisplay">Rs. 0.00</td>
                        <td class="text-end text-danger" id="totalCreditDisplay">Rs. 0.00</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-center" id="balanceStatus">
                            <span class="badge bg-success-subtle text-success fs-6">Balanced</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</form>

@push('scripts')
<script>
    const accountsData = @json($accounts);
    let rowIndex = 0;

    function createRow(accId = '', narration = '', dr = 0, cr = 0) {
        const tr = document.createElement('tr');
        tr.dataset.index = rowIndex;

        let options = '<option value="">Select Account</option>';
        accountsData.forEach(a => {
            options += `<option value="${a.id}" ${a.id == accId ? 'selected' : ''}>${a.code} - ${a.name} (${a.nature})</option>`;
        });

        tr.innerHTML = `
            <td>
                <select class="form-select account-select" name="items[${rowIndex}][account_id]" required>
                    ${options}
                </select>
            </td>
            <td>
                <input type="text" class="form-control" name="items[${rowIndex}][narration]" value="${narration}" placeholder="Line note">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control text-end dr-input" name="items[${rowIndex}][debit]" value="${dr > 0 ? dr : ''}" placeholder="0.00">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="form-control text-end cr-input" name="items[${rowIndex}][credit]" value="${cr > 0 ? cr : ''}" placeholder="0.00">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm remove-line-btn"><i class="bi bi-trash"></i></button>
            </td>
        `;

        document.getElementById('linesBody').appendChild(tr);

        const drIn = tr.querySelector('.dr-input');
        const crIn = tr.querySelector('.cr-input');

        // Prevent both debit and credit on same line
        drIn.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) crIn.value = '';
            recalc();
        });

        crIn.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) drIn.value = '';
            recalc();
        });

        tr.querySelector('.remove-line-btn').addEventListener('click', function() {
            if (document.querySelectorAll('#linesBody tr').length > 2) {
                tr.remove();
                recalc();
            } else {
                alert('A journal voucher must have at least 2 lines.');
            }
        });

        rowIndex++;
        recalc();
    }

    function recalc() {
        let totalDr = 0;
        let totalCr = 0;

        document.querySelectorAll('#linesBody tr').forEach(tr => {
            totalDr += parseFloat(tr.querySelector('.dr-input').value) || 0;
            totalCr += parseFloat(tr.querySelector('.cr-input').value) || 0;
        });

        document.getElementById('totalDebitDisplay').textContent = 'Rs. ' + totalDr.toFixed(2);
        document.getElementById('totalCreditDisplay').textContent = 'Rs. ' + totalCr.toFixed(2);

        const diff = Math.abs(totalDr - totalCr);
        const statusBox = document.getElementById('balanceStatus');
        const submitBtn = document.getElementById('submitBtn');

        if (totalDr > 0 && diff < 0.01) {
            statusBox.innerHTML = '<span class="badge bg-success-subtle text-success fs-6"><i class="bi bi-check-circle me-1"></i> Balanced (Debits = Credits)</span>';
            submitBtn.disabled = false;
        } else {
            statusBox.innerHTML = `<span class="badge bg-danger-subtle text-danger fs-6"><i class="bi bi-exclamation-triangle me-1"></i> Out of Balance (Difference: Rs. ${diff.toFixed(2)})</span>`;
            submitBtn.disabled = true;
        }
    }

    document.getElementById('addRowBtn').addEventListener('click', () => createRow());

    // 2 initial empty rows
    createRow();
    createRow();
</script>
@endpush
@endsection
