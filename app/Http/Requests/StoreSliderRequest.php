<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization will be handled by middleware
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:0', 'unique:sliders,position'],
            'link' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'An image is required for the slider.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The image must be a JPG, JPEG, or PNG file.',
            'image.max' => 'The image size must not exceed 2MB.',
            'position.required' => 'Position is required for ordering sliders.',
            'position.min' => 'Position must be a non-negative number.',
            'position.unique' => 'A slider with this position already exists. Please choose a different position.',
            'link.url' => 'The link must be a valid URL.',
        ];
    }
}
