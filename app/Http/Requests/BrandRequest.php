<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255', Rule::unique('brands')->ignore($this->route('brand')?->id)], 'description' => ['nullable', 'string'], 'status' => ['required', Rule::in(['active', 'inactive'])]];
    }
}
