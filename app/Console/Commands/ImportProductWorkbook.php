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
use Throwable;

class ImportProductWorkbook extends Command
{
    protected $signature = 'products:import-workbook {path : Path to the Excel workbook} {--sheet=Stock (2)} {--with-stock : Import opening stock and create STOCK_IN transactions} {--dry-run : Validate and roll back all changes}';

    protected $description = 'Import product master data from the stock workbook without changing inventory quantities';

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
            $workbook = IOFactory::load($path);
            $sheetName = (string) $this->option('sheet');
            $sheet = $workbook->getSheetByName($sheetName);
            if (! $sheet) {
                $this->error("Worksheet not found: {$sheetName}");

                return self::FAILURE;
            }

            DB::beginTransaction();
            $stats = (function () use ($sheet, $creator): array {
                $stats = ['rows' => 0, 'created' => 0, 'updated' => 0, 'duplicates' => 0, 'brands_created' => 0, 'categories_created' => 0, 'stock_transactions' => 0, 'stock_quantity' => 0, 'stock_skipped' => 0];
                $seen = [];
                $products = [];
                $openingStock = [];

                foreach ($sheet->toArray(null, true, true, true) as $rowNumber => $row) {
                    if ($rowNumber === 1 || ! is_numeric($row['A'] ?? null) || blank($row['B'] ?? null)) {
                        continue;
                    }

                    $stats['rows']++;
                    $name = trim((string) $row['B']);
                    $categoryName = trim((string) ($row['C'] ?? '')) ?: 'Uncategorized';
                    $brandName = trim((string) ($row['D'] ?? '')) ?: 'N/A';
                    [$packSize, $unit] = $this->parsePackSize($row['E'] ?? null);
                    $purchasePrice = is_numeric($row['I'] ?? null) ? round((float) $row['I'], 2) : null;

                    $brand = $this->findOrCreateBrand($brandName, $creator->id, $stats);
                    $category = $this->findOrCreateCategory($categoryName, $creator->id, $stats);
                    $key = implode('|', [$this->normalize($name), $brand->id, $category->id, $packSize ?? '', $this->normalize($unit ?? '')]);
                    $openingStock[$key] = ($openingStock[$key] ?? 0) + (is_numeric($row['H'] ?? null) ? (float) $row['H'] : 0);

                    if (isset($seen[$key])) {
                        $stats['duplicates']++;

                        continue;
                    }
                    $seen[$key] = true;

                    $product = InventoryItem::withTrashed()
                        ->whereRaw('LOWER(TRIM(item_name)) = ?', [$this->normalize($name)])
                        ->where('brand_id', $brand->id)
                        ->where('category_id', $category->id)
                        ->where(function ($query) use ($packSize): void {
                            $packSize === null ? $query->whereNull('pack_size') : $query->where('pack_size', $packSize);
                        })
                        ->where(function ($query) use ($unit): void {
                            blank($unit) ? $query->where(fn ($q) => $q->whereNull('unit')->orWhere('unit', '')) : $query->whereRaw('LOWER(TRIM(unit)) = ?', [$this->normalize($unit)]);
                        })
                        ->first();

                    if ($product) {
                        $product->purchase_price = $purchasePrice;
                        $product->status = 'active';
                        $product->save();
                        if ($product->trashed()) {
                            $product->restore();
                        }
                        $products[$key] = $product;
                        $stats['updated']++;

                        continue;
                    }

                    $products[$key] = InventoryItem::create([
                        'item_name' => $name,
                        'sku' => null,
                        'brand_id' => $brand->id,
                        'category_id' => $category->id,
                        'quantity' => 0,
                        'sold_quantity' => 0,
                        'pack_size' => $packSize,
                        'unit' => $unit,
                        'purchase_price' => $purchasePrice,
                        'selling_price' => null,
                        'supplier' => null,
                        'status' => 'active',
                        'created_by' => $creator->id,
                    ]);
                    $stats['created']++;
                }

                if ($this->option('with-stock')) {
                    foreach ($openingStock as $key => $quantity) {
                        if ($quantity <= 0 || ! isset($products[$key])) {
                            continue;
                        }

                        $product = InventoryItem::query()->lockForUpdate()->findOrFail($products[$key]->id);
                        if ((float) $product->quantity !== 0.0 || $product->transactions()->exists()) {
                            $stats['stock_skipped']++;

                            continue;
                        }

                        $product->update(['quantity' => $quantity]);
                        InventoryTransaction::create([
                            'inventory_item_id' => $product->id,
                            'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                            'quantity' => $quantity,
                            'created_by' => $creator->id,
                        ]);
                        $stats['stock_transactions']++;
                        $stats['stock_quantity'] += $quantity;
                    }
                }

                return $stats;
            })();

            $this->option('dry-run') ? DB::rollBack() : DB::commit();

            $this->table(['Workbook rows', 'Created', 'Updated', 'Duplicates skipped', 'Brands created', 'Categories created', 'Stock transactions', 'Stock quantity', 'Stock skipped'], [[...array_values($stats)]]);
            $this->info($this->option('dry-run') ? 'Dry run complete; no database changes were saved.' : 'Product import completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function findOrCreateBrand(string $name, int $creatorId, array &$stats): Brand
    {
        $brand = Brand::withTrashed()->whereRaw('LOWER(TRIM(name)) = ?', [$this->normalize($name)])->first();
        if ($brand) {
            if ($brand->trashed()) {
                $brand->restore();
            }
            if ($brand->status !== 'active') {
                $brand->update(['status' => 'active']);
            }

            return $brand;
        }

        $stats['brands_created']++;

        return Brand::create(['name' => $name, 'description' => null, 'status' => 'active', 'created_by' => $creatorId]);
    }

    private function findOrCreateCategory(string $name, int $creatorId, array &$stats): Category
    {
        $category = Category::withTrashed()->whereRaw('LOWER(TRIM(name)) = ?', [$this->normalize($name)])->first();
        if ($category) {
            if ($category->trashed()) {
                $category->restore();
            }
            if ($category->status !== 'active') {
                $category->update(['status' => 'active']);
            }

            return $category;
        }

        $stats['categories_created']++;

        return Category::create(['name' => $name, 'description' => null, 'status' => 'active', 'created_by' => $creatorId]);
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

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
