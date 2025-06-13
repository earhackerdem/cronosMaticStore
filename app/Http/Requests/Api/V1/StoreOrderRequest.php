<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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
        // Check both sanctum (API) and web (session) authentication
        $isAuthenticated = Auth::guard('sanctum')->check() || Auth::guard('web')->check();

        // Debug logging
        Log::info('StoreOrderRequest validation', [
            'is_authenticated' => $isAuthenticated,
            'sanctum_authenticated' => Auth::guard('sanctum')->check(),
            'web_authenticated' => Auth::guard('web')->check(),
            'sanctum_user_id' => Auth::guard('sanctum')->id(),
            'web_user_id' => Auth::guard('web')->id(),
            'has_bearer_token' => $this->bearerToken() !== null,
            'authorization_header' => $this->header('Authorization') ? 'present' : 'missing'
        ]);

        $rules = [
            'payment_method' => ['required', 'string', 'in:paypal'],
            'shipping_cost' => ['numeric', 'min:0', 'max:9999.99'],
            'shipping_method_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($isAuthenticated) {
            // For authenticated users, require address IDs
            $rules['shipping_address_id'] = ['required', 'integer', 'exists:addresses,id'];
            $rules['billing_address_id'] = ['nullable', 'integer', 'exists:addresses,id'];
            $rules['guest_email'] = ['nullable', 'email', 'max:255'];
        } else {
            // For guest users, require email and embedded address data
            $rules['guest_email'] = ['required', 'email', 'max:255'];

            // Shipping address rules
            $rules['shipping_address'] = ['required', 'array'];
            $rules['shipping_address.first_name'] = ['required', 'string', 'max:100'];
            $rules['shipping_address.last_name'] = ['required', 'string', 'max:100'];
            $rules['shipping_address.company'] = ['nullable', 'string', 'max:100'];
            $rules['shipping_address.address_line_1'] = ['required', 'string', 'max:255'];
            $rules['shipping_address.address_line_2'] = ['nullable', 'string', 'max:255'];
            $rules['shipping_address.city'] = ['required', 'string', 'max:100'];
            $rules['shipping_address.state'] = ['required', 'string', 'max:100'];
            $rules['shipping_address.postal_code'] = ['required', 'string', 'max:20'];
            $rules['shipping_address.country'] = ['required', 'string', 'max:100'];
            $rules['shipping_address.phone'] = ['nullable', 'string', 'max:20'];

            // Billing address rules
            $rules['billing_address'] = ['required', 'array'];
            $rules['billing_address.first_name'] = ['required', 'string', 'max:100'];
            $rules['billing_address.last_name'] = ['required', 'string', 'max:100'];
            $rules['billing_address.company'] = ['nullable', 'string', 'max:100'];
            $rules['billing_address.address_line_1'] = ['required', 'string', 'max:255'];
            $rules['billing_address.address_line_2'] = ['nullable', 'string', 'max:255'];
            $rules['billing_address.city'] = ['required', 'string', 'max:100'];
            $rules['billing_address.state'] = ['required', 'string', 'max:100'];
            $rules['billing_address.postal_code'] = ['required', 'string', 'max:20'];
            $rules['billing_address.country'] = ['required', 'string', 'max:100'];
            $rules['billing_address.phone'] = ['nullable', 'string', 'max:20'];
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
            'guest_email.required' => 'El email es obligatorio para usuarios invitados.',
            'guest_email.email' => 'El email debe tener un formato válido.',
            'payment_method.required' => 'El método de pago es obligatorio.',
            'payment_method.in' => 'El método de pago debe ser PayPal.',
            'shipping_cost.numeric' => 'El costo de envío debe ser un número.',
            'shipping_cost.min' => 'El costo de envío no puede ser negativo.',
            'shipping_cost.max' => 'El costo de envío no puede exceder $9,999.99.',
            'shipping_method_name.max' => 'El nombre del método de envío no puede exceder 100 caracteres.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',

            // Shipping address messages
            'shipping_address.required' => 'La dirección de envío es obligatoria.',
            'shipping_address.first_name.required' => 'El nombre es obligatorio.',
            'shipping_address.last_name.required' => 'El apellido es obligatorio.',
            'shipping_address.address_line_1.required' => 'La dirección es obligatoria.',
            'shipping_address.city.required' => 'La ciudad es obligatoria.',
            'shipping_address.state.required' => 'El estado es obligatorio.',
            'shipping_address.postal_code.required' => 'El código postal es obligatorio.',
            'shipping_address.country.required' => 'El país es obligatorio.',

            // Billing address messages
            'billing_address.required' => 'La dirección de facturación es obligatoria.',
            'billing_address.first_name.required' => 'El nombre es obligatorio.',
            'billing_address.last_name.required' => 'El apellido es obligatorio.',
            'billing_address.address_line_1.required' => 'La dirección es obligatoria.',
            'billing_address.city.required' => 'La ciudad es obligatoria.',
            'billing_address.state.required' => 'El estado es obligatorio.',
            'billing_address.postal_code.required' => 'El código postal es obligatorio.',
            'billing_address.country.required' => 'El país es obligatorio.',
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

        // If user is authenticated (either via sanctum or web), we don't require guest_email
        if (Auth::guard('sanctum')->check() || Auth::guard('web')->check()) {
            $this->merge(['guest_email' => null]);
        }
    }
}
