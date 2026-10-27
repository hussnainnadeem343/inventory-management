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

class ProductSalesReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, Export
{
    use Exportable;

    public function __construct(protected Collection $products) {}

    public function collection(): Collection
    {
        return $this->products;
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Product Name',
            'SKU',
            'Brand',
            'Category',
            'Units Sold',
            'Gross Sales (Rs.)',
            'Discounts Given (Rs.)',
            'Net Revenue (Rs.)',
            'Cost (COGS Rs.)',
            'Gross Profit (Rs.)',
            'Margin %',
            'Stock Remaining',
        ];
    }

    public function map($item): array
    {
        return [
            $item->product_id,
            $item->item_name,
            $item->sku ?: 'N/A',
            $item->brand_name ?: 'General',
            $item->category_name ?: 'General',
            number_format($item->units_sold, 2, '.', ''),
            number_format($item->gross_revenue, 2, '.', ''),
            number_format($item->discount_given, 2, '.', ''),
            number_format($item->net_revenue, 2, '.', ''),
            number_format($item->cogs, 2, '.', ''),
            number_format($item->gross_profit, 2, '.', ''),
            $item->margin_percent . '%',
            number_format($item->stock_remaining, 2, '.', ''),
        ];
    }

    public function title(): string
    {
        return 'Product Sales & Margin';
    }
}
