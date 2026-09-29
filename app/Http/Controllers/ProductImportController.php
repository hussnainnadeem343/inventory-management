<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportController extends Controller
{
    public function sampleTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products_sample_template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Item Name',
            'SKU',
            'Brand',
            'Category',
            'Pack Size',
            'Unit',
            'Purchase Price',
            'Selling Price',
            'Opening Stock',
            'Alert Quantity',
            'Expiry Date',
        ];

        $sampleData = [
            [
                'Matte Velvet Lipstick Red',
                'LIP-RED-01',
                'Rivaj UK',
                'Cosmetics',
                '1 pc',
                'PCS',
                '250.00',
                '450.00',
                '20',
                '5',
                now()->addYear()->format('Y-m-d'),
            ],
            [
                'Waterproof Liquid Eyeliner Black',
                'EYE-BLK-02',
                'Golden Rose',
                'Eye Makeup',
                '5 ml',
                'PCS',
                '180.00',
                '320.00',
                '15',
                '3',
                now()->addMonths(18)->format('Y-m-d'),
            ],
            [
                'Aloe Vera Face Wash 150ml',
                'FW-ALOE-150',
                'Himalaya',
                'Skincare',
                '150 ml',
                'BOTTLE',
                '290.00',
                '420.00',
                '30',
                '6',
                now()->addYears(2)->format('Y-m-d'),
            ],
        ];

        return response()->stream(function () use ($columns, $sampleData): void {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens it with proper encoding
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);
            foreach ($sampleData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, $headers);
    }

    public function import(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('products.import'), 403, 'Unauthorized to import products.');

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
            'shop_id' => ['nullable', 'exists:shops,id'],
        ]);

        $shopId = $user->isSuperAdmin()
            ? (int) ($request->input('shop_id') ?: session('dashboard_shop_id') ?: Shop::first()->id)
            : $user->shop_id;

        $shop = Shop::findOrFail($shopId);
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = [];
        if (in_array($extension, ['csv', 'txt'], true)) {
            $rows = $this->parseCsv($file->getRealPath());
        } else {
            $rows = $this->parseExcel($file->getRealPath());
        }

        if (empty($rows)) {
            return back()->with('error', 'The uploaded file is empty or could not be parsed.');
        }

        $headers = array_shift($rows);
        $headerMap = $this->mapHeaders($headers);

        if (! isset($headerMap['item_name'])) {
            return back()->with('error', "Could not find 'Item Name' header column in the uploaded file. Please use the sample template.");
        }

        $successCount = 0;
        $skippedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $rowIndex => $rawRow) {
                $rowNumber = $rowIndex + 2; // account for header and 1-based indexing

                if (empty(array_filter($rawRow, fn ($v) => $v !== null && trim((string) $v) !== ''))) {
                    continue; // Skip entirely empty rows
                }

                $itemName = trim((string) ($rawRow[$headerMap['item_name']] ?? ''));
                if ($itemName === '') {
                    $errors[] = "Row {$rowNumber}: Missing item name.";
                    $skippedCount++;
                    continue;
                }

                $sku = isset($headerMap['sku']) ? trim((string) ($rawRow[$headerMap['sku']] ?? '')) : '';
                if ($sku === '') {
                    $sku = 'SKU-' . strtoupper(Str::random(7));
                }

                // Check duplicate SKU in same shop
                if (InventoryItem::where('shop_id', $shop->id)->where('sku', $sku)->exists()) {
                    $errors[] = "Row {$rowNumber}: SKU '{$sku}' already exists for product '{$itemName}'.";
                    $skippedCount++;
                    continue;
                }

                // Brand auto-creation or lookup
                $brandId = null;
                if (isset($headerMap['brand'])) {
                    $brandName = trim((string) ($rawRow[$headerMap['brand']] ?? ''));
                    if ($brandName !== '') {
                        $brand = Brand::firstOrCreate(
                            ['shop_id' => $shop->id, 'name' => $brandName],
                            ['status' => 'active', 'created_by' => $user->id]
                        );
                        $brandId = $brand->id;
                    }
                }

                // Category auto-creation or lookup
                $categoryId = null;
                if (isset($headerMap['category'])) {
                    $categoryName = trim((string) ($rawRow[$headerMap['category']] ?? ''));
                    if ($categoryName !== '') {
                        $category = Category::firstOrCreate(
                            ['shop_id' => $shop->id, 'name' => $categoryName],
                            ['status' => 'active', 'created_by' => $user->id]
                        );
                        $categoryId = $category->id;
                    }
                }

                $packSize = isset($headerMap['pack_size']) ? trim((string) ($rawRow[$headerMap['pack_size']] ?? '')) : null;
                $unit = isset($headerMap['unit']) ? strtoupper(trim((string) ($rawRow[$headerMap['unit']] ?? 'PCS'))) : 'PCS';
                if (! in_array($unit, InventoryItem::UNITS, true)) {
                    $unit = 'PCS';
                }

                $purchasePrice = isset($headerMap['purchase_price']) ? max(0, (float) ($rawRow[$headerMap['purchase_price']] ?? 0)) : 0;
                $sellingPrice = isset($headerMap['selling_price']) ? max(0, (float) ($rawRow[$headerMap['selling_price']] ?? 0)) : 0;
                $openingStock = isset($headerMap['opening_stock']) ? max(0, (float) ($rawRow[$headerMap['opening_stock']] ?? 0)) : 0;
                $alertQuantity = isset($headerMap['alert_quantity']) ? max(0, (int) ($rawRow[$headerMap['alert_quantity']] ?? 5)) : 5;

                $expiryDate = null;
                if (isset($headerMap['expiry_date'])) {
                    $rawExpiry = trim((string) ($rawRow[$headerMap['expiry_date']] ?? ''));
                    if ($rawExpiry !== '') {
                        try {
                            $expiryDate = Carbon::parse($rawExpiry)->format('Y-m-d');
                        } catch (\Throwable) {
                            $expiryDate = null;
                        }
                    }
                }

                $product = InventoryItem::create([
                    'shop_id' => $shop->id,
                    'item_name' => $itemName,
                    'sku' => $sku,
                    'brand_id' => $brandId,
                    'category_id' => $categoryId,
                    'pack_size' => $packSize,
                    'unit' => $unit,
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'initial_quantity' => $openingStock,
                    'quantity' => $openingStock,
                    'sold_quantity' => 0,
                    'alert_quantity' => $alertQuantity,
                    'expiry_date' => $expiryDate,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]);

                if ($openingStock > 0) {
                    $batch = ProductBatch::create([
                        'shop_id' => $shop->id,
                        'inventory_item_id' => $product->id,
                        'batch_no' => 'BATCH-001',
                        'purchase_price' => $purchasePrice,
                        'selling_price' => $sellingPrice,
                        'initial_quantity' => $openingStock,
                        'quantity' => $openingStock,
                        'expiry_date' => $expiryDate,
                        'status' => 'active',
                        'created_by' => $user->id,
                    ]);

                    InventoryTransaction::create([
                        'shop_id' => $shop->id,
                        'inventory_item_id' => $product->id,
                        'product_batch_id' => $batch->id,
                        'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                        'quantity' => $openingStock,
                        'balance_before' => 0,
                        'balance_after' => $openingStock,
                        'unit_cost' => $purchasePrice,
                        'unit_sale_price' => $sellingPrice,
                        'expiry_date' => $expiryDate,
                        'notes' => 'Bulk Import Opening Stock',
                        'created_by' => $user->id,
                    ]);
                }

                $successCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Import failed due to an unexpected error: ' . $e->getMessage());
        }

        $message = "Imported {$successCount} products successfully into {$shop->name}!";
        if ($skippedCount > 0) {
            $errList = implode('<br>&bull; ', array_slice($errors, 0, 8));
            return redirect()->route('products.index')->with('warning', "{$message} {$skippedCount} rows were skipped:<br>&bull; {$errList}");
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 4096, ',')) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        return $rows;
    }

    private function parseExcel(string $path): array
    {
        try {
            $sheets = Excel::toArray(new class {}, $path);
            return $sheets[0] ?? [];
        } catch (\Throwable) {
            return $this->parseCsv($path);
        }
    }

    private function mapHeaders(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $raw) {
            $h = strtolower(trim((string) $raw));
            $h = preg_replace('/[\xEF\xBB\xBF]/', '', $h); // remove BOM
            $h = str_replace([' ', '_', '-'], '', $h);

            if (in_array($h, ['itemname', 'name', 'productname', 'item', 'product'], true)) {
                $map['item_name'] = $index;
            } elseif (in_array($h, ['sku', 'code', 'barcode', 'itemcode'], true)) {
                $map['sku'] = $index;
            } elseif (in_array($h, ['brand', 'brandname'], true)) {
                $map['brand'] = $index;
            } elseif (in_array($h, ['category', 'categoryname', 'cat'], true)) {
                $map['category'] = $index;
            } elseif (in_array($h, ['packsize', 'pack', 'size'], true)) {
                $map['pack_size'] = $index;
            } elseif (in_array($h, ['unit', 'uom'], true)) {
                $map['unit'] = $index;
            } elseif (in_array($h, ['purchaseprice', 'costprice', 'cost', 'buyprice'], true)) {
                $map['purchase_price'] = $index;
            } elseif (in_array($h, ['sellingprice', 'saleprice', 'retailprice', 'price'], true)) {
                $map['selling_price'] = $index;
            } elseif (in_array($h, ['openingstock', 'stock', 'quantity', 'qty', 'initialqty'], true)) {
                $map['opening_stock'] = $index;
            } elseif (in_array($h, ['alertquantity', 'alertqty', 'minstock', 'lowstockalert'], true)) {
                $map['alert_quantity'] = $index;
            } elseif (in_array($h, ['expirydate', 'expiry', 'expdate'], true)) {
                $map['expiry_date'] = $index;
            }
        }

        return $map;
    }
}
