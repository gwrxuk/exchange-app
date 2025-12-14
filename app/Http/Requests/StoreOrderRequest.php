<?php

namespace App\Http\Requests;

use App\Models\Symbol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symbol' => [
                'required',
                'string',
                Rule::exists('symbols', 'code')->where('is_active', true),
            ],
            'side' => ['required', 'string', Rule::in(['buy', 'sell'])],
            'price' => ['required', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'symbol.exists' => 'The selected symbol is not available for trading.',
            'side.in' => 'Side must be either buy or sell.',
            'price.gt' => 'Price must be greater than zero.',
            'amount.gt' => 'Amount must be greater than zero.',
        ];
    }
}

