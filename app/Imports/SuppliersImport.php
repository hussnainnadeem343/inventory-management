<?php

namespace App\Imports;

use App\Models\Purchase\Supplier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SuppliersImport implements ToCollection, WithHeadingRow
{
    protected ?int $shopId;
    protected int $userId;
    protected ?int $defaultLiabilityAccountId;

    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    public function __construct(?int $shopId, int $userId, ?int $defaultLiabilityAccountId = null)
    {
        $this->shopId = $shopId;
        $this->userId = $userId;
        $this->defaultLiabilityAccountId = $defaultLiabilityAccountId;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // account for header
            $name = trim((string) ($row['name'] ?? $row['supplier_name'] ?? ''));

            if ($name === '') {
                $this->skippedCount++;
                continue;
            }

            try {
                $openingBal = (float) ($row['opening_balance'] ?? 0);

                Supplier::create([
                    'shop_id' => $this->shopId,
                    'name' => $name,
                    'company_name' => trim((string) ($row['company_name'] ?? $row['company'] ?? '')) ?: null,
                    'phone' => trim((string) ($row['phone'] ?? $row['contact'] ?? $row['mobile'] ?? '')) ?: null,
                    'email' => trim((string) ($row['email'] ?? '')) ?: null,
                    'ntn' => trim((string) ($row['ntn'] ?? '')) ?: null,
                    'strn' => trim((string) ($row['strn'] ?? '')) ?: null,
                    'license_number' => trim((string) ($row['license_number'] ?? $row['licence_number'] ?? $row['license'] ?? '')) ?: null,
                    'liability_account_id' => $this->defaultLiabilityAccountId,
                    'address' => trim((string) ($row['address'] ?? '')) ?: null,
                    'opening_balance' => $openingBal,
                    'current_balance' => $openingBal,
                    'status' => 'active',
                    'created_by' => $this->userId,
                ]);

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum} ({$name}): " . $e->getMessage();
                $this->skippedCount++;
            }
        }
    }
}
