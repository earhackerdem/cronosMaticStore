<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Se manejará con middleware de admin
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'image_path' => 'nullable|string|max:255', // Asumimos que la ruta se envía como string
            'is_active' => 'sometimes|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->name && empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name)
            ]);
        }
    }
}
