<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category') ?? $this->route('categories');
        $id = $category instanceof \App\Models\Category ? $category->id : $category;

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('categories', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:100']
        ];
    }
}
