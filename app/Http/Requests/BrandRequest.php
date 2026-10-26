<?php

namespace App\Http\Requests;

use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $brand = $this->route('brand');
        if ($brand) {
            return $user->isSuperAdmin() || ($user->isShopAdmin() && $user->shop_id === $brand->shop_id);
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
            ?? ($this->route('brand')?->shop_id) 
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
                Rule::unique('brands')
                    ->where(fn ($query) => $query->where('shop_id', $shopId))
                    ->ignore($this->route('brand')?->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
