<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization will be handled by middleware
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'], // Add max length for content
            'type' => ['required', 'string', 'in:info,warning,promo'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'], // Remove the after:start_date rule
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->sometimes('end_date', 'after:start_date', function ($input) {
            return !empty($input->start_date) && !empty($input->end_date);
        });
    }

    public function messages(): array
    {
        return [
            'image.required' => 'An image is required for the popup.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The image must be a JPG, JPEG, or PNG file.',
            'image.max' => 'The image size must not exceed 2MB.',
            'title.required' => 'Title is required for the popup.',
            'content.required' => 'Content is required for the popup.',
            'content.max' => 'Content must not exceed 5000 characters.',
            'type.required' => 'Type is required for the popup.',
            'type.in' => 'Type must be one of: info, warning, promo.',
            'end_date.after' => 'End date must be after start date when both dates are provided.',
        ];
    }
}
