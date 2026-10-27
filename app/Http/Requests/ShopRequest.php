<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('business_type') === 'Other' && $this->filled('custom_business_type')) {
            $this->merge([
                'business_type' => $this->input('custom_business_type'),
            ]);
        }
    }

    public function rules(): array
    {
        $shopId = $this->route('shop')?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('shops', 'code')->ignore($shopId)],
            'business_type' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];

        // On creation, optionally create the initial shop/business admin account
        if (! $shopId && $this->filled('admin_name')) {
            $rules['admin_name'] = ['required', 'string', 'max:255'];
            $rules['admin_username'] = ['required', 'string', 'max:255', 'unique:users,username'];
            $rules['admin_email'] = ['nullable', 'email', 'max:255', 'unique:users,email'];
            $rules['admin_password'] = ['required', 'string', 'min:8'];
            $rules['admin_role'] = ['nullable', 'string', Rule::in(['business_owner', 'shop_admin'])];
        }

        return $rules;
    }
}
