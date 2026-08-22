<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)], 'password' => [$user ? 'nullable' : 'required', 'confirmed', 'min:8'], 'role' => ['required', Rule::in(['super_admin', 'user'])], 'status' => ['required', Rule::in(['active', 'inactive'])]];
    }
}
