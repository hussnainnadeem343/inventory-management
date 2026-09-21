<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class ReplaceProductWorkbook extends Command
{
    protected $signature = 'products:replace-workbook {path} {--sheet=Stock (2)} {--dry-run}';

    protected $description = 'Atomically replace product and stock records with a validated final workbook';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        if (! is_file($path)) {
            $this->error("Workbook not found: {$path}");

            return self::FAILURE;
        }

        $creator = User::query()->where('role', 'super_admin')->orderBy('id')->first();
        if (! $creator) {
            $this->error('A Super Admin user is required to own imported records.');

            return self::FAILURE;
        }

        try {
            $sheet = IOFactory::load($path)->getSheetByName((string) $this->option('sheet'));
            if (! $sheet) {
                throw new RuntimeException('Expected worksheet was not found.');
            }

            $rows = $sheet->toArray(null, true, false, true);
            $headers = $rows[1] ?? [];
            if (trim((string) ($headers['F'] ?? '')) !== 'YK Stock' || trim((string) ($headers['G'] ?? '')) !== 'MK Stock') {
                throw new RuntimeException('The workbook does not have the expected YK Stock and MK Stock columns.');
            }

            $products = [];
            $sourceRows = 0;
            foreach ($rows as $number => $row) {
                if ($number === 1 || ! is_numeric($row['A'] ?? null) || blank($row['B'] ?? null)) {
                    continue;
                }

                $sourceRows++;
                $name = trim((string) $row['B']);
                $category = trim((string) ($row['C'] ?? '')) ?: 'Uncategorized';
                $brand = trim((string) ($row['D'] ?? '')) ?: 'N/A';
                [$packSize, $unit] = $this->parsePackSize($row['E'] ?? null);
                $yk = $this->stockValue($row['F'] ?? null, $number, 'YK Stock');
                $mk = $this->stockValue($row['G'] ?? null, $number, 'MK Stock');
                $total = $this->stockValue($row['H'] ?? null, $number, 'Total Stock');
                if ($total !== null && abs($total - ($yk + $mk)) > 0.001) {
                    throw new RuntimeException("Row {$number}: Total Stock does not equal YK Stock plus MK Stock.");
                }
                if (! is_numeric($row['I'] ?? null) || (float) $row['I'] < 0) {
                    throw new RuntimeException("Row {$number}: purchase price is invalid.");
                }

                $key = implode('|', [mb_strtolower($name), mb_strtolower($category), mb_strtolower($brand), $packSize ?? '', mb_strtolower($unit ?? '')]);
                if (! isset($products[$key])) {
                    $products[$key] = compact('name', 'category', 'brand', 'packSize', 'unit') + ['price' => round((float) $row['I'], 2), 'yk' => 0, 'mk' => 0];
                } elseif (abs($products[$key]['price'] - (float) $row['I']) > 0.001) {
                    throw new RuntimeException("Row {$number}: duplicate product has a different purchase price.");
                }
                $products[$key]['yk'] += $yk;
                $products[$key]['mk'] += $mk;
            }

            if ($sourceRows === 0) {
                throw new RuntimeException('No product rows were found. Existing records were not changed.');
            }

            $expectedYk = array_sum(array_column($products, 'yk'));
            $expectedMk = array_sum(array_column($products, 'mk'));
            $this->table(['Source rows', 'Distinct products', 'YK stock', 'MK stock', 'Total stock'], [[$sourceRows, count($products), $expectedYk, $expectedMk, $expectedYk + $expectedMk]]);
            if ($this->option('dry-run')) {
                $this->info('Validation completed; database was not changed.');

                return self::SUCCESS;
            }

            DB::transaction(function () use ($products, $creator, $expectedYk, $expectedMk): void {
                DB::table('inventory_transactions')->delete();
                DB::table('inventory_items')->delete();

                $brands = [];
                $categories = [];
                $now = now();
                foreach ($products as $data) {
                    $brandKey = mb_strtolower($data['brand']);
                    $categoryKey = mb_strtolower($data['category']);
                    $brands[$brandKey] ??= $this->brand($data['brand'], $creator->id);
                    $categories[$categoryKey] ??= $this->category($data['category'], $creator->id);
                    $total = $data['yk'] + $data['mk'];

                    $item = InventoryItem::create([
                        'item_name' => $data['name'], 'sku' => null,
                        'brand_id' => $brands[$brandKey], 'category_id' => $categories[$categoryKey],
                        'quantity' => $total, 'yk_stock' => $data['yk'], 'mk_stock' => $data['mk'],
                        'sold_quantity' => 0, 'pack_size' => $data['packSize'], 'unit' => $data['unit'],
                        'purchase_price' => $data['price'], 'selling_price' => null,
                        'supplier' => null, 'status' => 'active', 'created_by' => $creator->id,
                    ]);

                    if ($total > 0) {
                        InventoryTransaction::create([
                            'inventory_item_id' => $item->id,
                            'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                            'quantity' => $total,
                            'created_by' => $creator->id,
                        ]);
                    }
                }

                if (InventoryItem::count() !== count($products)
                    || abs((float) InventoryItem::sum('yk_stock') - $expectedYk) > 0.001
                    || abs((float) InventoryItem::sum('mk_stock') - $expectedMk) > 0.001) {
                    throw new RuntimeException('Imported totals did not reconcile. All changes were rolled back.');
                }
            });

            $this->info('Previous product and stock records were replaced successfully.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function stockValue(mixed $value, int $row, string $label): float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0;
        }
        if (! is_numeric($value) || (float) $value < 0) {
            throw new RuntimeException("Row {$row}: {$label} must be a non-negative number.");
        }

        return round((float) $value, 2);
    }

    private function brand(string $name, int $creatorId): int
    {
        $brand = Brand::withTrashed()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();
        if (! $brand) {
            $brand = Brand::create(['name' => $name, 'status' => 'active', 'created_by' => $creatorId]);
        } else {
            $brand->restore();
            $brand->update(['status' => 'active']);
        }

        return $brand->id;
    }

    private function category(string $name, int $creatorId): int
    {
        $category = Category::withTrashed()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();
        if (! $category) {
            $category = Category::create(['name' => $name, 'status' => 'active', 'created_by' => $creatorId]);
        } else {
            $category->restore();
            $category->update(['status' => 'active']);
        }

        return $category->id;
    }

    private function parsePackSize(mixed $value): array
    {
        $text = trim((string) $value);
        if ($text === '' || strcasecmp($text, 'N/A') === 0) {
            return [null, null];
        }
        if (preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([^0-9\s].*)?$/u', $text, $matches) !== 1) {
            return [null, mb_substr($text, 0, 20)];
        }

        $unit = isset($matches[2]) ? trim($matches[2]) : null;

        return [(float) $matches[1], blank($unit) ? null : mb_substr($unit, 0, 20)];
    }
}
