<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category') ? $this->route('category')->id : null;
        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => 'nullable|string',
            'image_path' => 'nullable|string|max:255',
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
