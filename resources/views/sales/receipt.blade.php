<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt #{{ $sale->invoice_no }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace, monospace;
        }
        body {
            background: #fff;
            color: #000;
            font-size: 13px;
            line-height: 1.35;
            padding: 10px;
            max-width: 320px;
            margin: auto;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        .border-top { border-top: 1px dashed #000; }
        .border-bottom { border-bottom: 1px dashed #000; }
        .my-1 { margin-top: 4px; margin-bottom: 4px; }
        .my-2 { margin-top: 8px; margin-bottom: 8px; }
        .py-1 { padding-top: 4px; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { padding: 3px 0; font-size: 12px; }
        th { border-bottom: 1px dashed #000; text-align: left; }
        .no-print-bar {
            background: #f4f4f4;
            padding: 10px;
            margin-bottom: 15px;
            text-align: center;
            font-family: sans-serif;
            border-radius: 6px;
        }
        .btn {
            background: #2563eb;
            color: #fff;
            border: 0;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn-secondary {
            background: #6c757d;
            text-decoration: none;
            display: inline-block;
            margin-left: 6px;
        }
        @media print {
            .no-print-bar { display: none !important; }
            body { max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <button class="btn" onclick="window.print()">🖨️ Print Receipt</button>
        <a href="{{ route('sales.show', $sale) }}" class="btn btn-secondary">Close</a>
    </div>

    <div class="text-center">
        <h2 class="fw-bold" style="font-size: 18px; margin-bottom: 2px;">{{ $sale->shop->name }}</h2>
        <div style="font-size: 11px;">{{ $sale->shop->business_type ?? 'Retail' }} ({{ $sale->shop->code }})</div>
        @if($sale->shop->address)
            <div style="font-size: 11px;">{{ $sale->shop->address }}</div>
        @endif
        @if($sale->shop->phone)
            <div style="font-size: 11px;">Tel: {{ $sale->shop->phone }}</div>
        @endif
    </div>

    <div class="border-top my-2"></div>

    <div style="font-size: 11px;">
        <div><strong>Invoice:</strong> {{ $sale->invoice_no }}</div>
        <div><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y h:i A') }}</div>
        <div><strong>Cashier:</strong> {{ $sale->creator->name ?? 'Staff' }}</div>
        @if($sale->customer_name)
            <div><strong>Customer:</strong> {{ $sale->customer_name }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Item</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 35%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>
                        {{ $item->inventoryItem?->item_name ?? 'Product' }}
                        <div style="font-size: 10px; color: #444;">
                            {{ $item->quantity }} x {{ number_format($item->unit_price, 2) }}
                            @if($item->discount > 0)
                                <span style="color: #b91c1c;">(Disc: -{{ number_format($item->discount, 2) }})</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-end">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="border-top my-2"></div>

    <table style="font-size: 12px;">
        <tr>
            <td>Gross Subtotal:</td>
            <td class="text-end">Rs. {{ number_format($sale->subtotal, 2) }}</td>
        </tr>
        @if($sale->items_discount_total > 0)
            <tr>
                <td>Item Discounts:</td>
                <td class="text-end">- Rs. {{ number_format($sale->items_discount_total, 2) }}</td>
            </tr>
        @endif
        @if($sale->discount > 0)
            <tr>
                <td>Bill Discount:</td>
                <td class="text-end">- Rs. {{ number_format($sale->discount, 2) }}</td>
            </tr>
        @endif
        <tr class="fw-bold" style="font-size: 14px;">
            <td class="py-1">NET TOTAL:</td>
            <td class="text-end py-1">Rs. {{ number_format($sale->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Paid ({{ ucfirst($sale->payment_method) }}):</td>
            <td class="text-end">Rs. {{ number_format($sale->paid_amount, 2) }}</td>
        </tr>
        @if($sale->change_amount > 0)
            <tr>
                <td>Change:</td>
                <td class="text-end">Rs. {{ number_format($sale->change_amount, 2) }}</td>
            </tr>
        @endif
    </table>

    <div class="border-top my-2"></div>

    <div class="text-center" style="font-size: 11px;">
        <div>Thank you for visiting us!</div>
        <div style="font-size: 10px; margin-top: 4px;">Items can be exchanged within 7 days with this slip.</div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            // Auto open print dialog if directly opened
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
