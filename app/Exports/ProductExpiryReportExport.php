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

class ProductExpiryReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, Export
{
    use Exportable;

    public function __construct(protected Collection $items) {}

    public function collection(): Collection
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'Product Item',
            'SKU',
            'Brand',
            'Category',
            'Branch / Shop',
            'Expiry Date',
            'Status / Days Remaining',
            'Current Stock',
            'Purchase Price (Rs.)',
            'Total Value at Risk (Rs.)',
        ];
    }

    public function map($item): array
    {
        $days = $item->expiry_date ? now()->diffInDays($item->expiry_date, false) : 0;
        $status = $days < 0 ? 'EXPIRED (' . abs($days) . ' days ago)' : ($days == 0 ? 'EXPIRES TODAY' : $days . ' days left');
        $val = (float) $item->quantity * (float) $item->purchase_price;

        return [
            $item->item_name,
            $item->sku ?: 'N/A',
            $item->brand->name ?? 'General',
            $item->category->name ?? 'General',
            $item->shop->name ?? 'Default',
            $item->expiry_date?->format('d M Y') ?? 'N/A',
            $status,
            number_format($item->quantity, 2, '.', ''),
            number_format($item->purchase_price, 2, '.', ''),
            number_format($val, 2, '.', ''),
        ];
    }

    public function title(): string
    {
        return 'Expiry Alerts';
    }
}
