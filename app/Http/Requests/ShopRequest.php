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

    public function rules(): array
    {
        $shopId = $this->route('shop')?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('shops', 'code')->ignore($shopId)],
            'business_type' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];

        // On creation, optionally create the initial shop admin account
        if (! $shopId && $this->filled('admin_name')) {
            $rules['admin_name'] = ['required', 'string', 'max:255'];
            $rules['admin_username'] = ['required', 'string', 'max:255', 'unique:users,username'];
            $rules['admin_email'] = ['nullable', 'email', 'max:255', 'unique:users,email'];
            $rules['admin_password'] = ['required', 'string', 'min:8'];
        }

        return $rules;
    }
}
