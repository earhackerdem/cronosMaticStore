<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ShowProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // La validación principal del slug (existencia, actividad) se maneja en withValidator
        // o a través de Route Model Binding en el controlador.
        return [];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $slug = $this->route('slug'); // Obtener slug del parámetro de ruta
            if ($slug) {
                $product = \App\Models\Product::where('slug', $slug)->where('is_active', true)->first();
                if (!$product) {
                    $validator->errors()->add('slug', 'The selected product is invalid or not active.');
                }
            }
        });
    }
}
