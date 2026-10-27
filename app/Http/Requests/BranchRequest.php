<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isSuperAdmin() || $user->isBusinessOwner());
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('shops', 'code')->ignore($branchId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];

        // On branch creation, optionally provision a Branch Admin account
        if (! $branchId && $this->filled('admin_name')) {
            $rules['admin_name'] = ['required', 'string', 'max:255'];
            $rules['admin_username'] = ['required', 'string', 'max:255', 'unique:users,username'];
            $rules['admin_email'] = ['nullable', 'email', 'max:255', 'unique:users,email'];
            $rules['admin_password'] = ['required', 'string', 'min:8'];
        }

        return $rules;
    }
}
