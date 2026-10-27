<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cash Closing Slip - {{ $closing->closing_date->format('d M Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
        }
        .slip-card {
            max-width: 440px;
            margin: 24px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            padding: 24px;
        }
        .divider {
            border-top: 1px dashed #cbd5e1;
            margin: 14px 0;
        }
        .double-divider {
            border-top: 2px solid #0f172a;
            margin: 14px 0;
        }
        @media print {
            body {
                background: #fff;
            }
            .slip-card {
                box-shadow: none;
                margin: 0 auto;
                padding: 10px;
                max-width: 100%;
            }
            .d-print-none {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="container py-3">
    <div class="d-flex justify-content-center gap-2 mb-3 d-print-none">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm">
            <i class="bi bi-printer me-1"></i> Print Slip
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-x-lg me-1"></i> Close
        </button>
    </div>

    <div class="slip-card">
        {{-- Shop Header --}}
        <div class="text-center mb-3">
            <h5 class="fw-bold mb-0 text-dark">{{ $closing->shop->name }}</h5>
            <div class="text-muted small">Branch Code: <span class="font-monospace fw-semibold">{{ $closing->shop->code }}</span></div>
            <div class="badge text-bg-dark text-uppercase mt-2 px-3 py-1.5" style="letter-spacing: 1px; font-size: 11px;">
                Cash Register Closing Slip
            </div>
        </div>

        <div class="divider"></div>

        {{-- Meta details --}}
        <div class="small">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Closing Date:</span>
                <span class="fw-bold">{{ $closing->closing_date->format('d M Y') }}</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Closed Timestamp:</span>
                <span class="font-monospace">{{ $closing->closed_at->format('d M Y, h:i A') }}</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Cashier / Closed By:</span>
                <span class="fw-semibold">{{ $closing->user->name }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Total Invoices:</span>
                <span class="fw-bold">{{ number_format($closing->total_invoices) }}</span>
            </div>
        </div>

        <div class="divider"></div>

        {{-- Cash Drawer Reconciliation Breakdown --}}
        <h6 class="fw-bold text-uppercase small text-secondary mb-2" style="letter-spacing: .5px;">
            Drawer Cash Reconciliation
        </h6>

        <table class="w-100 small mb-2">
            <tr>
                <td class="text-muted py-1">Opening Cash Float:</td>
                <td class="text-end fw-semibold py-1">Rs. {{ number_format($closing->opening_float, 2) }}</td>
            </tr>
            <tr>
                <td class="text-muted py-1">(+) Cash Sales Collected:</td>
                <td class="text-end text-success fw-bold py-1">Rs. {{ number_format($closing->cash_sales, 2) }}</td>
            </tr>
            @if($closing->cash_expenses > 0)
                <tr>
                    <td class="text-muted py-1">(-) Cash Expenses Paid:</td>
                    <td class="text-end text-danger fw-semibold py-1">- Rs. {{ number_format($closing->cash_expenses, 2) }}</td>
                </tr>
            @endif
            <tr style="border-top: 1px solid #e2e8f0;">
                <td class="fw-bold py-1.5">Net Expected Cash in Drawer:</td>
                <td class="text-end fw-bold py-1.5">Rs. {{ number_format($closing->expected_cash, 2) }}</td>
            </tr>
            <tr style="border-top: 1px solid #e2e8f0;">
                <td class="fw-bold py-1.5 text-primary">Physical Cash Counted:</td>
                <td class="text-end fw-bold py-1.5 text-primary fs-6">Rs. {{ number_format($closing->actual_cash, 2) }}</td>
            </tr>
        </table>

        {{-- Variance Card --}}
        <div class="p-2.5 rounded-3 border text-center mb-3 {{ $closing->isBalanced() ? 'bg-success-subtle border-success' : ($closing->isShortage() ? 'bg-danger-subtle border-danger' : 'bg-info-subtle border-info') }}">
            <small class="d-block text-uppercase fw-semibold" style="font-size: 11px;">Reconciliation Difference</small>
            <div class="h5 mb-0 fw-bold {{ $closing->isBalanced() ? 'text-success' : ($closing->isShortage() ? 'text-danger' : 'text-primary') }}">
                @if($closing->isBalanced())
                    Rs. 0.00 &bull; Balanced (Exact)
                @elseif($closing->isShortage())
                    - Rs. {{ number_format(abs($closing->difference), 2) }} &bull; Shortage
                @else
                    + Rs. {{ number_format($closing->difference, 2) }} &bull; Excess
                @endif
            </div>
        </div>

        {{-- Other Payment Channels --}}
        <h6 class="fw-bold text-uppercase small text-secondary mb-2" style="letter-spacing: .5px;">
            Other Channels (Non-Cash)
        </h6>
        <table class="w-100 small mb-2">
            <tr>
                <td class="text-muted py-1">Card / POS Swipe:</td>
                <td class="text-end fw-semibold py-1">Rs. {{ number_format($closing->card_total, 2) }}</td>
            </tr>
            <tr>
                <td class="text-muted py-1">Online Bank Transfers:</td>
                <td class="text-end fw-semibold py-1">Rs. {{ number_format($closing->bank_total, 2) }}</td>
            </tr>
            <tr>
                <td class="text-muted py-1">Customer Credit (Udhaar):</td>
                <td class="text-end text-warning fw-semibold py-1">Rs. {{ number_format($closing->credit_total, 2) }}</td>
            </tr>
            <tr style="border-top: 2px solid #0f172a;">
                <td class="fw-bold py-1.5 text-dark">Total Net Sales Revenue:</td>
                <td class="text-end fw-bold py-1.5 text-dark">Rs. {{ number_format($closing->total_net_sales, 2) }}</td>
            </tr>
        </table>

        @if($closing->notes)
            <div class="divider"></div>
            <div class="small">
                <span class="text-muted d-block fw-semibold mb-1">Handover Notes:</span>
                <div class="p-2 bg-light rounded text-dark fst-italic">{{ $closing->notes }}</div>
            </div>
        @endif

        <div class="double-divider"></div>

        {{-- Signatures --}}
        <div class="row pt-4 small text-center text-muted">
            <div class="col-6">
                <div class="border-top border-dark pt-1">
                    <strong>Cashier Signature</strong>
                </div>
            </div>
            <div class="col-6">
                <div class="border-top border-dark pt-1">
                    <strong>Manager / Owner</strong>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 small text-muted font-monospace" style="font-size: 10px;">
            DenFin ERP &bull; End-of-Day Cash Audit Trail
        </div>
    </div>
</div>
</body>
</html>
