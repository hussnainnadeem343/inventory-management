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

        if ($currentUser->isBusinessOwner()) {
            $targetUser = $this->route('user');
            if ($targetUser) {
                $business = $currentUser->shop ? $currentUser->shop->getRootBusiness() : null;
                $allowedIds = $business ? $business->getAllBranchIds() : [];

                if ($targetUser->isSuperAdmin() || ($targetUser->isBusinessOwner() && ! $currentUser->is($targetUser))) {
                    return false;
                }

                return in_array((int) $targetUser->shop_id, $allowedIds, true);
            }

            return true;
        }

        if ($currentUser->isShopAdmin()) {
            $targetUser = $this->route('user');
            if ($targetUser) {
                // Shop Admin can only manage staff belonging to their own branch, never super admins or business owners
                return (int) $targetUser->shop_id === (int) $currentUser->shop_id 
                    && ! $targetUser->isSuperAdmin() 
                    && ! $targetUser->isBusinessOwner();
            }

            return true;
        }

        return false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('assigned_role')) {
            $assigned = (string) $this->input('assigned_role');

            if ($assigned === 'system:super_admin' || $assigned === 'super_admin') {
                $this->merge([
                    'role' => 'super_admin',
                    'role_id' => null,
                ]);
            } elseif ($assigned === 'system:business_owner' || $assigned === 'business_owner') {
                $this->merge([
                    'role' => 'business_owner',
                    'role_id' => null,
                ]);
            } elseif ($assigned === 'system:shop_admin' || $assigned === 'shop_admin') {
                $this->merge([
                    'role' => 'shop_admin',
                    'role_id' => null,
                ]);
            } elseif (str_starts_with($assigned, 'role_')) {
                $roleId = (int) substr($assigned, 5);
                $this->merge([
                    'role' => 'staff',
                    'role_id' => $roleId > 0 ? $roleId : null,
                ]);
            } elseif (is_numeric($assigned)) {
                $roleId = (int) $assigned;
                $this->merge([
                    'role' => 'staff',
                    'role_id' => $roleId > 0 ? $roleId : null,
                ]);
            } elseif ($assigned === 'system:staff' || $assigned === 'staff') {
                $this->merge([
                    'role' => 'staff',
                    'role_id' => null,
                ]);
            }
        }
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
            'assigned_role' => ['nullable', 'string', 'max:50'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['shop_id'] = ['nullable', 'exists:shops,id'];
            $rules['role'] = ['required', Rule::in(['super_admin', 'business_owner', 'shop_admin', 'staff', 'user'])];
        } elseif ($currentUser->isBusinessOwner()) {
            $business = $currentUser->shop ? $currentUser->shop->getRootBusiness() : null;
            $allowedIds = $business ? $business->getAllBranchIds() : [];
            $rules['shop_id'] = ['required', Rule::in($allowedIds)];
            $rules['role'] = ['required', Rule::in(['shop_admin', 'staff', 'user'])];
        } else {
            $rules['shop_id'] = ['nullable'];
            $rules['role'] = ['required', Rule::in(['shop_admin', 'staff', 'user'])];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'role_id.exists' => 'The selected role is invalid or does not exist.',
            'assigned_role.required' => 'Please select a role for this user.',
        ];
    }
}
