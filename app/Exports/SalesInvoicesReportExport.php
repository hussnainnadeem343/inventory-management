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

class SalesInvoicesReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, Export
{
    use Exportable;

    public function __construct(protected Collection $invoices) {}

    public function collection(): Collection
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'Invoice No',
            'Date & Time',
            'Branch / Shop',
            'Customer Name',
            'Customer Phone',
            'Payment Method',
            'Total Items',
            'Subtotal (Rs.)',
            'Discount (Rs.)',
            'Net Amount (Rs.)',
            'Cashier / Staff',
            'Notes',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->invoice_no,
            $sale->created_at?->format('d M Y, h:i A') ?? 'N/A',
            $sale->shop->name ?? 'Default',
            $sale->customer_name ?: 'Walk-in Customer',
            $sale->customer_phone ?: '-',
            strtoupper($sale->payment_method ?? 'CASH'),
            $sale->total_items,
            number_format($sale->subtotal, 2, '.', ''),
            number_format(($sale->discount ?? 0) + ($sale->items_discount_total ?? 0), 2, '.', ''),
            number_format($sale->total_amount, 2, '.', ''),
            $sale->creator->name ?? 'Staff',
            $sale->notes ?: '-',
        ];
    }

    public function title(): string
    {
        return 'Sales Invoices';
    }
}
