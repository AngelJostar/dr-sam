<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['superadmin', 'admin', 'patient', 'operational'], true);
    }

    public function rules(): array
    {
        return [
            'pharmacy_product_id' => ['required', 'exists:pharmacy_products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'delivery_mode' => ['required', 'string', 'max:80'],
            'payment_method' => ['required', 'string', 'max:80'],
        ];
    }
}
