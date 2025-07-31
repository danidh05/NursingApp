<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'second_name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'description' => 'required|string|max:2000',
            'phone_number' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'The first name is required.',
            'first_name.max' => 'The first name must not exceed 255 characters.',
            'second_name.required' => 'The second name is required.',
            'second_name.max' => 'The second name must not exceed 255 characters.',
            'address.required' => 'The address is required.',
            'address.max' => 'The address must not exceed 1000 characters.',
            'description.required' => 'The description is required.',
            'description.max' => 'The description must not exceed 2000 characters.',
            'phone_number.max' => 'The phone number must not exceed 20 characters.',
        ];
    }
} 