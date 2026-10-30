<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Invoice #{{ $sale->invoice_no }} - {{ $sale->shop->name ?? 'DenFin Auto Lube' }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
            color: #000;
            background: #fff;
            width: 78mm;
            margin: 0 auto;
            padding: 8px 4px;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .double-divider { border-top: 2px solid #000; margin: 6px 0; }
        .d-flex { display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        th, td { padding: 3px 0; }
        .badge { display: inline-block; padding: 2px 5px; border: 1px solid #000; font-weight: bold; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 12px; text-align: center;">
        <button onclick="window.print()" style="padding: 6px 14px; font-weight: bold; background: #000; color: #fff; border: 0; border-radius: 4px; cursor: pointer;">
            🖨️ Print Invoice (80mm)
        </button>
        <button onclick="window.close()" style="padding: 6px 14px; margin-left: 8px; border: 1px solid #ccc; background: #fff; border-radius: 4px; cursor: pointer;">
            Close
        </button>
    </div>

    <div class="text-center">
        <h2 style="margin: 0; font-size: 16px; text-transform: uppercase;">{{ $sale->shop->name ?? 'DENFIN AUTO LUBE' }}</h2>
        <div style="font-size: 11px;">{{ $sale->shop->address ?? 'Main Boulevard, Auto Bay' }}</div>
        <div style="font-size: 11px;">Tel: {{ $sale->shop->phone ?? '+92 311 467 0195' }}</div>
    </div>

    <div class="divider"></div>

    <div class="d-flex">
        <span>INVOICE #: <strong>{{ $sale->invoice_no }}</strong></span>
        <span>{{ $sale->created_at->format('d-M-y H:i') }}</span>
    </div>

    @if($serviceRecord && $serviceRecord->vehicle)
        <div class="divider"></div>
        <div class="d-flex">
            <span>PLATE #:</span>
            <span class="fw-bold" style="font-size: 13px;">{{ $serviceRecord->vehicle->plate_number }}</span>
        </div>
        <div class="d-flex">
            <span>VEHICLE:</span>
            <span>{{ $serviceRecord->vehicle->make }} {{ $serviceRecord->vehicle->model }} ({{ $serviceRecord->vehicle->vehicle_type }})</span>
        </div>
        <div class="d-flex">
            <span>CUSTOMER:</span>
            <span>{{ $serviceRecord->vehicle->customer_name }}</span>
        </div>
        <div class="d-flex">
            <span>PHONE:</span>
            <span>{{ $serviceRecord->vehicle->customer_phone }}</span>
        </div>
        @if($serviceRecord->current_odometer)
            <div class="d-flex">
                <span>CURRENT ODOMETER:</span>
                <span class="fw-bold">{{ number_format($serviceRecord->current_odometer) }} KM</span>
            </div>
        @endif
        @if($serviceRecord->oil_grade)
            <div class="d-flex">
                <span>OIL INSTALLED:</span>
                <span class="fw-bold">{{ $serviceRecord->oil_grade }}</span>
            </div>
        @endif
        @if($serviceRecord->technician)
            <div class="d-flex">
                <span>PIT TECHNICIAN:</span>
                <span>{{ $serviceRecord->technician->name }} (Bay {{ $serviceRecord->technician->bay_number ?? '1' }})</span>
            </div>
        @endif
    @endif

    <div class="divider"></div>

    {{-- Consumables Table --}}
    <table>
        <thead>
            <tr style="border-bottom: 1px solid #000;">
                <th style="text-align: left;">ITEM / SERVICE</th>
                <th style="text-align: center; width: 35px;">QTY</th>
                <th style="text-align: right; width: 65px;">PRICE</th>
                <th style="text-align: right; width: 70px;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td style="text-align: left;">
                        {{ $item->inventoryItem->item_name ?? 'Service Item' }}
                        @if($item->discount > 0)
                            <div style="font-size: 9.5px; color: #444;">(Disc: -Rs. {{ number_format($item->discount, 2) }})</div>
                        @endif
                    </td>
                    <td style="text-align: center;">{{ number_format($item->quantity, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;" class="fw-bold">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="d-flex">
        <span>GROSS SUBTOTAL:</span>
        <span class="fw-bold">Rs. {{ number_format($sale->subtotal, 2) }}</span>
    </div>
    @if($sale->items_discount_total > 0)
        <div class="d-flex">
            <span>ITEM DISCOUNTS:</span>
            <span class="fw-bold">- Rs. {{ number_format($sale->items_discount_total, 2) }}</span>
        </div>
    @endif
    @if($sale->discount > 0)
        <div class="d-flex">
            <span>ORDER DISCOUNT:</span>
            <span class="fw-bold">- Rs. {{ number_format($sale->discount, 2) }}</span>
        </div>
    @endif
    <div class="double-divider"></div>
    <div class="d-flex" style="font-size: 14px;">
        <span class="fw-bold">NET TOTAL:</span>
        <span class="fw-bold">Rs. {{ number_format($sale->total_amount, 2) }}</span>
    </div>
    <div class="d-flex" style="font-size: 11px;">
        <span>PAYMENT ({{ strtoupper($sale->payment_method) }}):</span>
        <span>Rs. {{ number_format($sale->paid_amount, 2) }}</span>
    </div>

    @if($serviceRecord && $serviceRecord->next_due_odometer)
        <div class="divider"></div>
        <div class="text-center" style="border: 1px dashed #000; padding: 6px; margin-top: 6px;">
            <div style="font-size: 10px; text-transform: uppercase;">*** NEXT SERVICE TARGET ***</div>
            <div style="font-size: 15px; font-weight: bold; margin: 3px 0;">
                >>> {{ number_format($serviceRecord->next_due_odometer) }} KM <<<
            </div>
            @if($serviceRecord->next_due_date)
                <div style="font-size: 11px;">OR ESTIMATED BY: <strong>{{ $serviceRecord->next_due_date->format('d-M-Y') }}</strong></div>
            @endif
        </div>
    @endif

        @if($serviceRecord && !empty($serviceRecord->inspection_checklist))
            <div style="font-size: 10px; margin-top: 6px;">
                <span class="fw-bold">PIT INSPECTION:</span><br>
                @foreach($serviceRecord->inspection_checklist as $k => $val)
                    @if($val)
                        [✓] {{ ucwords(str_replace('_', ' ', $k)) }} &nbsp;
                    @endif
                @endforeach
            </div>
        @endif

    <div class="divider"></div>
    <div class="text-center" style="font-size: 10.5px; margin-top: 6px;">
        Thank you for trusting {{ $sale->shop->name ?? 'DenFin Auto Lube' }}!<br>
        Drive Safely &amp; Keep Engine Smooth!
    </div>

</body>
</html>
