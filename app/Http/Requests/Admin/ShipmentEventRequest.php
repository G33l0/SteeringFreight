<?php

namespace App\Http\Requests\Admin;

use App\Models\ShipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ShipmentEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageEvents', $this->route('shipment'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shipment_status_id' => ['required', 'integer', Rule::exists('shipment_statuses', 'id')],
            'location' => ['nullable', 'string', 'max:200'],
            'occurred_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['boolean'],
            'update_shipment' => ['boolean'],
            'notify_customer' => ['boolean'],
        ];
    }

    /**
     * Exception statuses that are flagged as needing an explanation may not be
     * recorded without one, so nothing is ever published to a customer without
     * an administrator having written it.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $status = ShipmentStatus::find($this->input('shipment_status_id'));

                if ($status?->requires_explanation && trim((string) $this->input('description')) === '') {
                    $validator->errors()->add(
                        'description',
                        "\"{$status->name}\" needs a short explanation for the customer before it can be recorded.",
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_public' => $this->boolean('is_public'),
            'update_shipment' => $this->boolean('update_shipment'),
            'notify_customer' => $this->boolean('notify_customer'),
        ]);
    }
}
