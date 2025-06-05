<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowCategoryRequest extends FormRequest
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
        return [
            // No hay parámetros de query para validar aquí, pero el FormRequest puede existir
            // para consistencia o futuras adiciones. La validación del slug se hace mejor
            // con el route model binding o verificando el slug directamente en el FormRequest si es necesario.
            // Si queremos validar el slug como parte del FormRequest:
            // 'slug' => ['required', 'string', Rule::exists('categories', 'slug')->where('is_active', true)]
            // Sin embargo, el slug ya está siendo usado para el route model binding implícito o firstOrFail.
            // Por ahora lo dejaré vacío, el ticket implica que se debe validar "el parámetro slug".
            // La mejor forma de validar un parámetro de ruta es a través del binding o directamente en el controlador.
            // Para forzar la validación del slug a través del FormRequest, necesitaríamos que el slug sea un campo del request.
            // Asumamos que la intención es validar que la categoría (identificada por slug) exista y esté activa.
            // Esto ya lo hace `firstOrFail()` y el route model binding.
            // Si se requiere que el form request valide el slug del parámetro de ruta, entonces:
        ];
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
                $category = \App\Models\Category::where('slug', $slug)->where('is_active', true)->first();
                if (!$category) {
                    $validator->errors()->add('slug', 'The selected category is invalid or not active.');
                }
            }
        });
    }
}
