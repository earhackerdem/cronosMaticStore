<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow both authenticated users and guests
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'shipping_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'billing_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'payment_method' => ['required', 'string', 'in:paypal'],
            'shipping_cost' => ['numeric', 'min:0', 'max:9999.99'],
            'shipping_method_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // Only require guest_email if user is not authenticated
        if (!Auth::guard('sanctum')->check()) {
            $rules['guest_email'] = ['required', 'email', 'max:255'];
        } else {
            $rules['guest_email'] = ['nullable', 'email', 'max:255'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'shipping_address_id.required' => 'La dirección de envío es obligatoria.',
            'shipping_address_id.exists' => 'La dirección de envío especificada no existe.',
            'billing_address_id.exists' => 'La dirección de facturación especificada no existe.',
            'guest_email.required_unless' => 'El email es obligatorio para usuarios invitados.',
            'guest_email.email' => 'El email debe tener un formato válido.',
            'payment_method.required' => 'El método de pago es obligatorio.',
            'payment_method.in' => 'El método de pago debe ser PayPal.',
            'shipping_cost.numeric' => 'El costo de envío debe ser un número.',
            'shipping_cost.min' => 'El costo de envío no puede ser negativo.',
            'shipping_cost.max' => 'El costo de envío no puede exceder $9,999.99.',
            'shipping_method_name.max' => 'El nombre del método de envío no puede exceder 100 caracteres.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default shipping cost if not provided
        if (!$this->has('shipping_cost')) {
            $this->merge(['shipping_cost' => 0.00]);
        }

        // If user is authenticated, we don't require guest_email
        if (Auth::guard('sanctum')->check()) {
            $this->merge(['guest_email' => null]);
        }
    }
}
