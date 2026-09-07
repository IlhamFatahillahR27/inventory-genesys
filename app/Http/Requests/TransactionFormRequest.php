<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('user_id') && auth('web')->check()) {
            $this->merge([
                'user_id' => auth('web')->id(),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'quantity' => ['required', 'numeric', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:1'],
            'discount' => ['required', 'numeric', 'min:0']
        ];
    }
}
