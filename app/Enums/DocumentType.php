<?php

namespace App\Enums;

enum DocumentType: string
{
    case CommercialInvoice = 'commercial_invoice';
    case PackingList = 'packing_list';
    case ShippingDocument = 'shipping_document';
    case DeliveryDocument = 'delivery_document';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CommercialInvoice => 'Commercial invoice',
            self::PackingList => 'Packing list',
            self::ShippingDocument => 'Shipping document',
            self::DeliveryDocument => 'Delivery document',
            self::Other => 'Other shipment document',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
