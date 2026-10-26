<?php

namespace App\Http\Requests;

use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $category = $this->route('category');
        if ($category) {
            return $user->isSuperAdmin() || ($user->isShopAdmin() && $user->shop_id === $category->shop_id);
        }

        // Staff, Shop Admin, and Super Admin can create
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $shopId = $user?->shop_id 
            ?? $this->input('shop_id') 
            ?? session('dashboard_shop_id') 
            ?? ($this->route('category')?->shop_id) 
            ?? Shop::where('status', 'active')->first()?->id 
            ?? 1;

        return [
            'shop_id' => [
                'nullable',
                Rule::exists('shops', 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where(fn ($query) => $query->where('shop_id', $shopId))
                    ->ignore($this->route('category')?->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
