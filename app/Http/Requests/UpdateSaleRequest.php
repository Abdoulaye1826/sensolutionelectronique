<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'array', 'min:1'],
            'unit_price.*' => ['required', 'numeric', 'min:0'],
            'imei' => ['nullable', 'array'],
            'imei.*' => ['nullable', 'string', 'max:20'],
                        'sale_type' => ['required', 'in:vente,echange'],
            'exchange_product_id' => ['exclude_unless:sale_type,echange', 'required', 'exists:products,id'],
            'exchange_quantity' => ['exclude_unless:sale_type,echange', 'required', 'integer', 'min:1'],
            'exchange_added_amount' => ['exclude_unless:sale_type,echange', 'nullable', 'numeric', 'min:0'],
            'exchange_imei' => ['exclude_unless:sale_type,echange', 'nullable', 'string', 'max:20'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,validated,cancelled'],
            'payment_method' => ['nullable', 'in:wave,orange_money,cash'],
            'amount_given' => ['nullable', 'numeric', 'min:0'],
            'warranty_duration' => ['required', 'in:none,30d,3m,6m,1y'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sale_type' => $this->filled('sale_type') ? $this->input('sale_type') : 'vente',
            'discount_amount' => $this->filled('discount_amount') ? $this->input('discount_amount') : 0,
            'exchange_quantity' => $this->filled('exchange_quantity') ? $this->input('exchange_quantity') : 1,
            'exchange_added_amount' => $this->filled('exchange_added_amount') ? $this->input('exchange_added_amount') : 0,
            'warranty_duration' => $this->filled('warranty_duration') ? $this->input('warranty_duration') : '30d',
        ]);
    }
}
