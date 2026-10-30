<?php

namespace App\Http\Requests;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isSuperAdmin() || $user->isShopAdmin());
    }

    protected function prepareForValidation(): void
    {
        $productNature = $this->input('product_nature');
        $oilSubType = $this->input('oil_sub_type');

        if ($productNature === 'general') {
            // General retail product or spare part / filter (no oil or loose properties)
            $productType = 'standard';
            $isLoose = false;
            $liters = null;
            $packSize = $this->input('pack_size');
        } elseif ($productNature === 'oil') {
            if ($oilSubType === 'loose') {
                $productType = 'loose';
                $isLoose = true;
                $liters = 1.0;
                $packSize = 1.0;
                if (! $this->filled('unit')) {
                    $this->merge(['unit' => 'LTR']);
                }
            } elseif ($oilSubType === 'drum') {
                $productType = 'drum';
                $isLoose = false;
                $liters = $this->filled('liters') ? (float) $this->input('liters') : null;
                $packSize = $liters;
                if (! $this->filled('unit')) {
                    $this->merge(['unit' => 'DRUM']);
                }
            } else {
                // Packaged bottle or can
                $productType = 'standard';
                $isLoose = false;
                $liters = $this->filled('liters') ? (float) $this->input('liters') : null;
                $packSize = $liters ?: $this->input('pack_size');
            }
        } else {
            // Fallback for direct inputs or generic non-lube shops
            $productType = $this->input('product_type', 'standard');
            $isLoose = $this->boolean('is_loose') || $productType === 'loose';
            $liters = $this->input('liters');
            $packSize = $this->input('pack_size');

            if ($liters !== null && $packSize === null) {
                $packSize = $liters;
            } elseif ($packSize !== null && $liters === null) {
                $liters = $packSize;
            }
        }

        $this->merge([
            'product_type' => $isLoose ? 'loose' : ($productType ?: 'standard'),
            'is_loose' => $isLoose,
            'liters' => $liters,
            'pack_size' => $packSize,
        ]);
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $shopId = $this->user()?->shop_id ?? $this->input('shop_id', $product?->shop_id);

        return [
            'shop_id' => [
                'nullable',
                Rule::exists('shops', 'id'),
            ],

            'item_name' => ['required', 'string', 'max:255'],

            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventory_items')
                    ->where(fn ($query) => $shopId ? $query->where('shop_id', $shopId) : $query)
                    ->ignore($product?->id),
            ],

            'brand_id' => [
                'required',
                Rule::exists(Brand::class, 'id'),
            ],

            'category_id' => [
                'required',
                Rule::exists(Category::class, 'id'),
            ],

            'pack_size' => ['nullable', 'numeric', 'min:0'],

            'liters' => ['nullable', 'numeric', 'min:0'],

            'unit' => ['nullable', 'string', 'max:20'],

            'product_type' => ['nullable', 'string', Rule::in(['standard', 'loose', 'drum', 'packaged'])],

            'is_loose' => ['nullable', 'boolean'],

            'expiry_date' => ['nullable', 'date'],

            'alert_quantity' => ['nullable', 'numeric', 'min:0'],

            'initial_quantity' => ['nullable', 'numeric', 'min:0'],

            'purchase_price' => ['nullable', 'numeric', 'min:0'],

            'selling_price' => ['nullable', 'numeric', 'min:0'],

            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
