<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashRegisterReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, Export
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
            'Date',
            'Branch / Shop',
            'Branch Code',
            'Completed Invoices',
            'Cash in Drawer (Rs.)',
            'Card / POS Machine (Rs.)',
            'Bank / Online Transfer (Rs.)',
            'Customer Credit / Udhaar (Rs.)',
            'Total Net Sales (Rs.)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->sale_date,
            $row->shop_name ?? 'Branch',
            $row->shop_code ?? 'N/A',
            $row->total_invoices,
            number_format($row->cash_total, 2, '.', ''),
            number_format($row->card_total, 2, '.', ''),
            number_format($row->bank_total, 2, '.', ''),
            number_format($row->credit_total, 2, '.', ''),
            number_format($row->net_sales, 2, '.', ''),
        ];
    }

    public function title(): string
    {
        return 'Daily Cash Register';
    }
}
