<?php

namespace App\Http\Requests;

use App\Enums\ShippingMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:160'],
            'origin' => ['required', 'string', 'max:180'],
            'destination' => ['required', 'string', 'max:180'],
            'shipping_method' => ['nullable', Rule::enum(ShippingMethod::class)],
            'cargo_type' => ['nullable', 'string', 'max:160'],
            'approximate_weight' => ['nullable', 'string', 'max:60'],
            'package_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'ready_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:4000'],
            // Hidden field that browsers leave empty and simple bots fill in.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website.prohibited' => 'Your request could not be submitted. Please try again.',
        ];
    }
}
