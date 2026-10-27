<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class LubeServicesReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, Export
{
    use Exportable;

    public function __construct(protected Collection $records) {}

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Date & Time',
            'Branch / Shop',
            'Plate Number',
            'Vehicle Make',
            'Vehicle Model',
            'Customer Name',
            'Customer Phone',
            'Current Odometer (KM)',
            'Oil Grade',
            'Labor Fee (Rs.)',
            'Technician',
            'Commission (Rs.)',
            'Commission Status',
            'Invoice No',
            'Total Invoice (Rs.)',
            'Payment Method',
        ];
    }

    public function map($row): array
    {
        $dt = $row->created_at ? Carbon::parse($row->created_at)->format('d M Y, h:i A') : 'N/A';

        return [
            $dt,
            $row->shop_name ?? 'Default',
            $row->plate_number,
            $row->make,
            $row->model,
            $row->customer_name ?: 'Walk-in Customer',
            $row->customer_phone ?: '-',
            $row->current_odometer ?? '-',
            $row->oil_grade ?? '5W-30',
            number_format($row->labor_amount ?? 0, 2, '.', ''),
            $row->technician_name ?: 'Staff',
            number_format($row->technician_commission ?? 0, 2, '.', ''),
            strtoupper($row->commission_status ?? 'PENDING'),
            $row->invoice_no ?: '-',
            number_format($row->invoice_total ?? 0, 2, '.', ''),
            strtoupper($row->payment_method ?? 'CASH'),
        ];
    }

    public function title(): string
    {
        return 'Auto Lube Services';
    }
}
