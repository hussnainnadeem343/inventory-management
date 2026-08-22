<?php

namespace App\Http\Requests;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('inventory')?->id;

        return ['item_name' => ['required', 'string', 'max:255'], 'sku' => ['nullable', 'string', 'max:255', Rule::unique('inventory_items')->ignore($id)], 'brand_id' => ['required', Rule::exists(Brand::class, 'id')->where(fn ($q) => $q->where('status', 'active'))], 'category_id' => ['required', Rule::exists(Category::class, 'id')->where(fn ($q) => $q->where('status', 'active'))], 'quantity' => ['required', 'numeric', 'min:0'], 'unit' => ['nullable', Rule::in(InventoryItem::UNITS)], 'purchase_price' => ['required', 'numeric', 'min:0'], 'selling_price' => ['nullable', 'numeric', 'min:0'], 'supplier' => ['nullable', 'string', 'max:255'], 'status' => ['required', Rule::in(['active', 'inactive'])]];
    }
}
