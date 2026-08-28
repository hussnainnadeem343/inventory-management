<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SellInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['sell_quantity' => ['required', 'integer', 'gt:0']];
    }
}
