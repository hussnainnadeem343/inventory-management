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
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $priceRule = $this->user()?->isSuperAdmin() ? ['nullable', 'numeric', 'min:0'] : ['prohibited'];

        return [
            'item_name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('inventory_items')->ignore($product?->id)],
            'brand_id' => ['required', Rule::exists(Brand::class, 'id')->where(fn ($query) => $query->where('status', 'active'))],
            'category_id' => ['required', Rule::exists(Category::class, 'id')->where(fn ($query) => $query->where('status', 'active'))],
            'pack_size' => ['nullable', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string', 'max:20'],
            'purchase_price' => $priceRule,
            'selling_price' => $priceRule,
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
