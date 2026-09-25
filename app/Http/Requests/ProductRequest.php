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

            'pack_size' => ['nullable', 'numeric', 'gt:0'],

            'unit' => ['nullable', 'string', 'max:20'],

            'expiry_date' => ['nullable', 'date'],

            'alert_quantity' => ['nullable', 'numeric', 'min:0'],

            'initial_quantity' => ['nullable', 'numeric', 'min:0'],

            'purchase_price' => ['nullable', 'numeric', 'min:0'],

            'selling_price' => ['nullable', 'numeric', 'min:0'],

            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
