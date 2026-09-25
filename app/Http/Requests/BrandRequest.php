<?php

namespace App\Http\Requests;

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
        $shopId = $this->user()?->shop_id ?? $this->input('shop_id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands')
                    ->where(fn ($query) => $shopId ? $query->where('shop_id', $shopId) : $query)
                    ->ignore($this->route('brand')?->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
