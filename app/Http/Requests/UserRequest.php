<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $currentUser = $this->user();
        if (! $currentUser) {
            return false;
        }

        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        if ($currentUser->isShopAdmin()) {
            $targetUser = $this->route('user');
            if ($targetUser) {
                // Shop Admin can only manage staff belonging to their own shop
                return $targetUser->shop_id === $currentUser->shop_id && ! $targetUser->isSuperAdmin();
            }

            return true;
        }

        return false;
    }

    public function rules(): array
    {
        $currentUser = $this->user();
        $targetUser = $this->route('user');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($targetUser?->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($targetUser?->id)],
            'password' => [$targetUser ? 'nullable' : 'required', 'confirmed', 'min:8'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['shop_id'] = ['nullable', 'exists:shops,id'];
            $rules['role'] = ['required', Rule::in(['super_admin', 'shop_admin', 'staff', 'user'])];
        } else {
            // Shop Admin can create/update shop admins and staff within their own shop
            $rules['role'] = ['required', Rule::in(['shop_admin', 'staff', 'user'])];
        }

        return $rules;
    }
}
