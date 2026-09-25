<?php

namespace App\Exports;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private array $filters, private bool $includePrices) {}

    public function query(): Builder
    {
        return InventoryItem::query()
            ->with(['brand', 'category', 'creator', 'shop'])
            ->filtered($this->filters)
            ->latest();
    }

    public function headings(): array
    {
        $headings = [
            'ID',
            'Shop',
            'Product Name',
            'SKU',
            'Brand',
            'Category',
            'Initial Quantity',
            'Sold Quantity',
            'Remaining Stock',
            'Pack / Variant',
            'Expiry Date',
        ];

        if ($this->includePrices) {
            array_push($headings, 'Purchase Price (Rs.)', 'Selling Price (Rs.)');
        }

        return [...$headings, 'Status', 'Created By', 'Created At'];
    }

    public function map($item): array
    {
        $row = [
            $item->id,
            $item->shop->name ?? 'Default',
            $item->item_name,
            $item->sku ?: '-',
            $item->brand->name ?? '-',
            $item->category->name ?? '-',
            $item->initial_quantity,
            $item->sold_quantity,
            $item->quantity,
            $item->pack_label ?: '-',
            $item->expiry_date?->format('Y-m-d') ?: 'N/A',
        ];

        if ($this->includePrices) {
            array_push($row, $item->purchase_price, $item->selling_price);
        }

        return [
            ...$row,
            ucfirst($item->status),
            $item->creator->name ?? 'System',
            $item->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
