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
        return InventoryItem::query()->with(['brand', 'category', 'creator'])->filtered($this->filters)->latest();
    }

    public function headings(): array
    {
        $headings = ['ID', 'Product', 'SKU', 'Brand', 'Category', 'Stock In', 'Sold Quantity', 'Remaining Quantity', 'Pack Size'];
        if ($this->includePrices) {
            array_push($headings, 'Purchase Price', 'Selling Price');
        }

        return [...$headings, 'Status', 'Created By', 'Created At'];
    }

    public function map($item): array
    {
        $row = [$item->id, $item->item_name, $item->sku, $item->brand->name, $item->category->name, $item->quantity, $item->sold_quantity, $item->remaining_quantity, $item->pack_label];
        if ($this->includePrices) {
            array_push($row, $item->purchase_price, $item->selling_price);
        }

        return [...$row, ucfirst($item->status), $item->creator->name, $item->created_at->format('Y-m-d H:i:s')];
    }
}
