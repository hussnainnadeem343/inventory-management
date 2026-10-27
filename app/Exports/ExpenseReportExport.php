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

class ExpenseReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, Export
{
    use Exportable;

    public function __construct(protected Collection $expenses) {}

    public function collection(): Collection
    {
        return $this->expenses;
    }

    public function headings(): array
    {
        return [
            'Voucher #',
            'Date',
            'Branch / Store',
            'Branch Code',
            'Expense Head / Account',
            'Paid From Account',
            'Category',
            'Description',
            'Receipt Reference',
            'Amount (Rs.)',
            'Created By',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->voucher_no,
            $expense->date?->format('Y-m-d') ?? '',
            $expense->shop->name ?? 'Default Store',
            $expense->shop->code ?? '',
            $expense->expenseAccount->name ?? 'Expense',
            $expense->paymentAccount->name ?? 'Cash/Bank',
            $expense->category ?: 'General',
            $expense->description,
            $expense->receipt_ref ?: '-',
            number_format((float) $expense->amount, 2, '.', ''),
            $expense->creator->name ?? 'Staff',
        ];
    }

    public function title(): string
    {
        return 'Expenses Ledger';
    }
}
