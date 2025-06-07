<?php

namespace App\Http\Requests;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in([Address::TYPE_SHIPPING, Address::TYPE_BILLING])],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['sometimes', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['sometimes', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'string', 'max:20'],
            'country' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de dirección debe ser shipping o billing.',
            'first_name.string' => 'El nombre debe ser una cadena de texto.',
            'last_name.string' => 'El apellido debe ser una cadena de texto.',
            'address_line_1.string' => 'La dirección debe ser una cadena de texto.',
            'city.string' => 'La ciudad debe ser una cadena de texto.',
            'state.string' => 'El estado/provincia debe ser una cadena de texto.',
            'postal_code.string' => 'El código postal debe ser una cadena de texto.',
            'country.string' => 'El país debe ser una cadena de texto.',
        ];
    }
}
