<?php

namespace App\Http\Requests;

use App\Enums\ShippingMethod;
use App\Support\Countries;
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
            // Who is asking
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email:filter', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],

            // Where it is going
            'origin_country' => ['required', 'string', 'max:120', Rule::in(Countries::names())],
            'origin_city' => ['nullable', 'string', 'max:120'],
            'destination_country' => ['required', 'string', 'max:120', Rule::in(Countries::names())],
            'destination_city' => ['nullable', 'string', 'max:120'],
            'shipping_method' => ['nullable', Rule::enum(ShippingMethod::class)],
            'incoterm' => ['nullable', 'string', 'max:20'],

            // What is moving
            'cargo_type' => ['required', 'string', 'max:160'],
            'approximate_weight' => ['nullable', 'string', 'max:60'],
            'dimensions' => ['nullable', 'string', 'max:160'],
            'package_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'goods_value' => ['nullable', 'string', 'max:60'],
            'ready_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:4000'],

            // Hidden field that browsers leave empty and simple bots fill in.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'origin_country' => 'collection country',
            'destination_country' => 'delivery country',
            'cargo_type' => 'type of goods',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website.prohibited' => 'Your request could not be submitted. Please try again.',
            'origin_country.in' => 'Choose the collection country from the list.',
            'destination_country.in' => 'Choose the delivery country from the list.',
        ];
    }
}
