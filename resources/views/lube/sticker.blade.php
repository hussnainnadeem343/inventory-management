<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Windshield Sticker - {{ $serviceRecord->vehicle->plate_number ?? 'Auto Lube' }}</title>
    <style>
        @page {
            margin: 0;
            size: 60mm 50mm; /* Standard 2.3 x 2 inch static cling sticker */
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 4px;
            background: #fff;
            color: #000;
            box-sizing: border-box;
            width: 58mm;
            height: 48mm;
        }
        .sticker-box {
            border: 2px solid #000;
            border-radius: 6px;
            padding: 5px;
            height: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
        }
        .header {
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        .shop-name {
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .shop-phone {
            font-size: 9px;
            color: #222;
        }
        .target-box {
            background: #000;
            color: #fff;
            border-radius: 4px;
            padding: 3px 2px;
            margin: 3px 0;
        }
        .target-title {
            font-size: 8px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .target-km {
            font-size: 16px;
            font-weight: 900;
            font-family: 'Courier New', Courier, monospace;
        }
        .details-grid {
            font-size: 8.5px;
            text-align: left;
            line-height: 1.25;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px;
            padding: 2px 0;
        }
        .footer {
            border-top: 1px dashed #000;
            font-size: 7.5px;
            padding-top: 2px;
            text-transform: uppercase;
            font-weight: bold;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 6px; text-align: center;">
        <button onclick="window.print()" style="font-size: 11px; padding: 4px 8px; font-weight: bold; background: #000; color: #fff; border: 0; border-radius: 3px; cursor: pointer;">
            🖨️ Print Sticker
        </button>
        <button onclick="window.close()" style="font-size: 11px; padding: 4px 8px; margin-left: 4px; border: 1px solid #ccc; background: #fff; border-radius: 3px; cursor: pointer;">
            Close
        </button>
    </div>

    <div class="sticker-box">
        <div class="header">
            <div class="shop-name">{{ $serviceRecord->shop->name ?? 'DENFIN AUTO LUBE' }}</div>
            <div class="shop-phone">Tel: {{ $serviceRecord->shop->phone ?? '+923114670195' }}</div>
        </div>

        <div class="details-grid">
            <div><strong>PLATE:</strong> {{ $serviceRecord->vehicle->plate_number ?? '-' }}</div>
            <div><strong>DATE:</strong> {{ $serviceRecord->created_at->format('d/m/Y') }}</div>
            <div><strong>SERVICED AT:</strong> {{ $serviceRecord->current_odometer ? number_format($serviceRecord->current_odometer) . ' KM' : '-' }}</div>
            <div><strong>GRADE:</strong> {{ $serviceRecord->oil_grade ?: 'N/A' }}</div>
        </div>

        @if($serviceRecord->next_due_odometer)
            <div class="target-box">
                <div class="target-title">NEXT SERVICE DUE AT:</div>
                <div class="target-km">{{ number_format($serviceRecord->next_due_odometer) }} KM</div>
                @if($serviceRecord->next_due_date)
                    <div style="font-size: 8.5px;">OR BY: {{ $serviceRecord->next_due_date->format('d-M-Y') }}</div>
                @endif
            </div>
        @else
            <div class="target-box" style="background: #333;">
                <div class="target-title">SERVICE COMPLETED</div>
                <div class="target-km" style="font-size: 12px; margin: 2px 0;">PIT / LABOR SERVICE</div>
            </div>
        @endif

        <div class="footer">
            Keep Engine Smooth • Drive Safely
        </div>
    </div>

</body>
</html>
