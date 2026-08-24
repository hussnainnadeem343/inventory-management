<?php

namespace App\Http\Requests;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('inventory')?->id;

        $priceRule = $this->user()?->isSuperAdmin() ? ['nullable', 'numeric', 'min:0'] : ['prohibited'];

        return ['item_name' => ['required', 'string', 'max:255'], 'sku' => ['nullable', 'string', 'max:255', Rule::unique('inventory_items')->ignore($id)], 'brand_id' => ['required', Rule::exists(Brand::class, 'id')->where(fn ($q) => $q->where('status', 'active'))], 'category_id' => ['required', Rule::exists(Category::class, 'id')->where(fn ($q) => $q->where('status', 'active'))], 'quantity' => ['required', 'numeric', 'min:0'], 'unit' => ['nullable', Rule::in(InventoryItem::UNITS)], 'purchase_price' => $priceRule, 'selling_price' => $priceRule, 'supplier' => ['nullable', 'string', 'max:255'], 'status' => ['required', Rule::in(['active', 'inactive'])]];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $item = $this->route('inventory');
            if ($item && is_numeric($this->input('quantity')) && (float) $this->input('quantity') < (float) $item->sold_quantity) {
                $validator->errors()->add('quantity', 'Original quantity cannot be less than the quantity already sold ('.number_format((float) $item->sold_quantity, 2).').');
            }
        }];
    }
}
