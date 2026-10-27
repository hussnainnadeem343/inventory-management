<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class SalesReportExport implements WithMultipleSheets, Export
{
    use Exportable;

    public function __construct(
        protected array $financialSummary,
        protected Collection $productBreakdown,
        protected Collection $branchComparison,
        protected Collection $invoices,
        protected Carbon $startDate,
        protected Carbon $endDate,
        protected string $scopeLabel
    ) {}

    public function sheets(): array
    {
        return [
            new SalesReportSummarySheet(
                $this->financialSummary,
                $this->branchComparison,
                $this->startDate,
                $this->endDate,
                $this->scopeLabel
            ),
            new SalesReportProductsSheet($this->productBreakdown),
            new SalesReportInvoicesSheet($this->invoices),
        ];
    }
}

class SalesReportSummarySheet implements FromArray, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected array $summary,
        protected Collection $branchComparison,
        protected Carbon $startDate,
        protected Carbon $endDate,
        protected string $scopeLabel
    ) {}

    public function array(): array
    {
        $rows = [
            ['DENFIN ERP - SALES & FINANCIAL REPORT'],
            ['Generated At:', now()->format('d M Y, h:i A')],
            ['Reporting Period:', $this->startDate->format('d M Y') . ' to ' . $this->endDate->format('d M Y')],
            ['Branch Scope:', $this->scopeLabel],
            [''],
            ['EXECUTIVE FINANCIAL METRICS', 'AMOUNT (PKR)'],
            ['Total Completed Invoices', number_format($this->summary['totalInvoices'])],
            ['Total Units Sold', number_format($this->summary['totalUnitsSold'], 2)],
            ['Gross Subtotal Sales', number_format($this->summary['grossSubtotal'], 2)],
            ['Total Discounts Given', number_format($this->summary['totalDiscounts'], 2)],
            ['Customer Returns / Refunds', number_format($this->summary['totalRefunds'], 2)],
            ['NET SALES REVENUE', number_format($this->summary['netSales'], 2)],
            ['Cost of Goods Sold (COGS)', number_format($this->summary['cogs'], 2)],
            ['GROSS PROFIT', number_format($this->summary['grossProfit'], 2)],
            ['Gross Margin %', $this->summary['marginPercent'] . '%'],
            ['Average Order Value', number_format($this->summary['averageOrderValue'], 2)],
            [''],
            ['PAYMENT METHOD & CASH DRAWER BREAKDOWN', 'AMOUNT (PKR)'],
            ['Cash Collected (Drawer)', number_format($this->summary['cashTotal'], 2)],
            ['Card / POS Machine', number_format($this->summary['cardTotal'], 2)],
            ['Bank / Digital Transfer', number_format($this->summary['bankTotal'], 2)],
            ['Customer Credit (Udhaar Pending)', number_format($this->summary['creditTotal'], 2)],
        ];

        if (! empty($this->summary['lubeLaborTotal']) || ! empty($this->summary['lubeServicesCount'])) {
            $rows[] = [''];
            $rows[] = ['AUTO LUBE SERVICE BREAKDOWN', 'AMOUNT (PKR)'];
            $rows[] = ['Lube Pit Services Performed', number_format($this->summary['lubeServicesCount'])];
            $rows[] = ['Pit Labor & Service Revenue', number_format($this->summary['lubeLaborTotal'], 2)];
            $rows[] = ['Product / Lubricant Revenue', number_format($this->summary['partsRevenue'], 2)];
            $rows[] = ['Technician Commissions Incurred', number_format($this->summary['lubeCommissionsTotal'], 2)];
        }

        if ($this->branchComparison->isNotEmpty()) {
            $rows[] = [''];
            $rows[] = ['MULTI-BRANCH COMPARISON LEADERBOARD'];
            $rows[] = [
                'Branch Name',
                'Branch Code',
                'Invoices',
                'Cash (Rs.)',
                'Digital/Bank (Rs.)',
                'Credit/Udhaar (Rs.)',
                'Net Sales (Rs.)',
                'COGS (Rs.)',
                'Gross Profit (Rs.)',
                'Margin %',
                'Chain Share %',
            ];

            foreach ($this->branchComparison as $b) {
                $rows[] = [
                    $b->name . ($b->is_hq ? ' (HQ)' : ''),
                    $b->code,
                    $b->total_invoices,
                    number_format($b->cash_amount, 2),
                    number_format($b->digital_amount, 2),
                    number_format($b->credit_amount, 2),
                    number_format($b->net_sales, 2),
                    number_format($b->cogs, 2),
                    number_format($b->gross_profit, 2),
                    $b->margin_percent . '%',
                    $b->share_percent . '%',
                ];
            }
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Financial Summary';
    }
}

class SalesReportProductsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
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
            'Discount (Rs.)',
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
            $item->units_sold,
            $item->gross_revenue,
            $item->discount_given,
            $item->net_revenue,
            $item->cogs,
            $item->gross_profit,
            $item->margin_percent . '%',
            $item->stock_remaining,
        ];
    }

    public function title(): string
    {
        return 'Product Sales';
    }
}

class SalesReportInvoicesSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
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
            ucfirst($sale->payment_method ?? 'cash'),
            $sale->total_items,
            $sale->subtotal,
            $sale->discount + $sale->items_discount_total,
            $sale->total_amount,
            $sale->creator->name ?? 'System',
        ];
    }

    public function title(): string
    {
        return 'Invoices Ledger';
    }
}
