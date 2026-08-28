<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($this->route('category')?->id)], 'description' => ['nullable', 'string'], 'status' => ['required', Rule::in(['active', 'inactive'])]];
    }
}
