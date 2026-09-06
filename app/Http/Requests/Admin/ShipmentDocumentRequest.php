<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShipmentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageDocuments', $this->route('shipment'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::enum(DocumentType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', Rule::enum(DocumentVisibility::class)],
            'file' => [
                'required',
                'file',
                'max:'.upload_max_kb(),
                'mimes:'.implode(',', (array) config('portlane.uploads.document_mimes')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['file' => 'document'];
    }
}
