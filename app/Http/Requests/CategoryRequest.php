<?php

namespace App\Http\Requests;

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
        $shopId = $this->user()?->shop_id ?? $this->input('shop_id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where(fn ($query) => $shopId ? $query->where('shop_id', $shopId) : $query)
                    ->ignore($this->route('category')?->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
