<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFAQRequest extends FormRequest
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
            'question' => 'sometimes|required|string|max:255',
            'answer' => 'sometimes|required|string|max:2000',
            'order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
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
            'question.required' => 'The question field is required.',
            'question.max' => 'The question must not exceed 255 characters.',
            'answer.required' => 'The answer field is required.',
            'answer.max' => 'The answer must not exceed 2000 characters.',
            'order.integer' => 'The order must be a number.',
            'order.min' => 'The order must be at least 0.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }
}
