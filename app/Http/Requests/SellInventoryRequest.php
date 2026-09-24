<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'sell_quantity' => ['required', 'integer', 'gt:0'],
            'stock_source' => ['required', Rule::in(['yk_stock', 'mk_stock'])],
        ];
    }
}
