<?php

namespace App\Http\Requests\Admin;

use App\Enums\ShippingMethod;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shipment = $this->route('shipment');

        return $shipment instanceof Shipment
            ? $this->user()->can('update', $shipment)
            : $this->user()->can('create', Shipment::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $shipment = $this->route('shipment');

        return [
            'tracking_number' => [
                'nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('shipments', 'tracking_number')->ignore($shipment?->getKey()),
            ],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_name' => ['nullable', 'string', 'max:160'],
            'customer_email' => ['nullable', 'email:filter', 'max:180'],
            'customer_phone' => ['nullable', 'string', 'max:40'],

            'origin_country' => ['nullable', 'string', 'max:120'],
            'origin_city' => ['nullable', 'string', 'max:120'],
            'destination_country' => ['nullable', 'string', 'max:120'],
            'destination_city' => ['nullable', 'string', 'max:120'],
            'current_location' => ['nullable', 'string', 'max:200'],

            'shipping_method' => ['nullable', Rule::enum(ShippingMethod::class)],
            'service_level' => ['nullable', 'string', 'max:60'],
            'cargo_description' => ['nullable', 'string', 'max:2000'],
            'package_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'dimensions' => ['nullable', 'string', 'max:120'],
            'declared_value' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'declared_value_currency' => ['nullable', 'string', 'size:3', 'alpha'],

            'container_number' => ['nullable', 'string', 'max:40'],
            'vessel_name' => ['nullable', 'string', 'max:120'],
            'voyage_number' => ['nullable', 'string', 'max:60'],
            'air_waybill_number' => ['nullable', 'string', 'max:60'],
            'flight_number' => ['nullable', 'string', 'max:40'],
            'bill_of_lading_number' => ['nullable', 'string', 'max:60'],

            'estimated_departure' => ['nullable', 'date'],
            'estimated_arrival' => ['nullable', 'date', 'after_or_equal:estimated_departure'],
            'estimated_delivery' => ['nullable', 'date', 'after_or_equal:estimated_departure'],

            'shipment_status_id' => ['nullable', 'integer', Rule::exists('shipment_statuses', 'id')],
            'exception_note' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'notifications_enabled' => ['boolean'],
        ];
    }

    /**
     * An exception status that is set straight on the shipment needs the same
     * written explanation as one recorded through a tracking update, so a
     * customer never sees an exception without knowing why.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $status = ShipmentStatus::find($this->input('shipment_status_id'));

                if ($status?->requires_explanation && trim((string) $this->input('exception_note')) === '') {
                    $validator->errors()->add(
                        'exception_note',
                        "\"{$status->name}\" needs a short explanation for the customer.",
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notifications_enabled' => $this->boolean('notifications_enabled'),
            'tracking_number' => $this->filled('tracking_number')
                ? strtoupper(trim((string) $this->input('tracking_number')))
                : null,
            'declared_value_currency' => $this->filled('declared_value_currency')
                ? strtoupper(trim((string) $this->input('declared_value_currency')))
                : null,
        ]);
    }
}
