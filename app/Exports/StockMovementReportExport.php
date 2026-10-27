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

class StockMovementReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, Export
{
    use Exportable;

    public function __construct(protected Collection $transactions) {}

    public function collection(): Collection
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Date & Time',
            'Product Item',
            'SKU',
            'Branch / Shop',
            'Transaction Type',
            'Quantity Changed',
            'Balance Before',
            'Balance After',
            'Unit Cost (Rs.)',
            'Unit Sale Price (Rs.)',
            'Operator / Staff',
            'Notes / Reference',
        ];
    }

    public function map($t): array
    {
        return [
            $t->created_at?->format('d M Y, h:i A') ?? 'N/A',
            $t->inventoryItem->item_name ?? 'Item #' . $t->inventory_item_id,
            $t->inventoryItem->sku ?? 'N/A',
            $t->shop->name ?? 'Default',
            str_replace('_', ' ', strtoupper($t->transaction_type)),
            number_format($t->quantity, 2, '.', ''),
            number_format($t->balance_before ?? 0, 2, '.', ''),
            number_format($t->balance_after ?? 0, 2, '.', ''),
            number_format($t->unit_cost ?? 0, 2, '.', ''),
            number_format($t->unit_sale_price ?? 0, 2, '.', ''),
            $t->creator->name ?? 'System',
            $t->notes ?: '-',
        ];
    }

    public function title(): string
    {
        return 'Stock Movement Audit';
    }
}
