<?php

namespace App\Support;

use App\Models\Shipment;
use App\Models\ShipmentStatus;

/**
 * Which drawing stands for what on the tracking page.
 *
 * A customer reading a tracking page is usually looking for one thing: is it
 * moving, and is anything wrong. A picture answers that across a room and in a
 * language nobody had to translate, which a status name does not.
 *
 * Everything here is an SVG from the icon component, never an emoji: the public
 * site draws no emoji except the country flags beside a country name, because
 * Windows renders many of them as a box or as two letters.
 */
class TrackingIcons
{
    /**
     * The vehicle carrying this shipment.
     *
     * A domestic movement is drawn as a van whatever the booking says, because
     * that is what turns up at the door, and it is the leg the customer can
     * picture. Everything crossing a border keeps the icon of how it travels.
     */
    public static function vehicleFor(Shipment $shipment): string
    {
        if (self::isDomestic($shipment)) {
            return 'truck';
        }

        return match (true) {
            $shipment->shipping_method?->isSea() => 'vessel',
            $shipment->shipping_method?->isAir() => 'aircraft',
            $shipment->shipping_method?->value === 'rail' => 'train',
            $shipment->shipping_method?->value === 'road' => 'truck',
            $shipment->shipping_method?->value === 'courier' => 'truck',
            default => 'container',
        };
    }

    /**
     * Both ends of the same country: a local move rather than a freight lane.
     */
    public static function isDomestic(Shipment $shipment): bool
    {
        return filled($shipment->origin_country)
            && filled($shipment->destination_country)
            && mb_strtolower(trim($shipment->origin_country)) === mb_strtolower(trim($shipment->destination_country));
    }

    /**
     * A drawing that carries the meaning of the status, so an exception reads
     * as its own kind of problem rather than as a generic warning triangle.
     */
    public static function forStatus(?ShipmentStatus $status): string
    {
        if (! $status) {
            return 'container';
        }

        return match ($status->slug) {
            'booking-confirmed' => 'documents',
            'cargo-received', 'destination-warehouse' => 'warehouse',
            'processing' => 'documents',
            'packing', 'ready-for-dispatch' => 'container',
            'shipped', 'in-transit' => 'container',
            'arrived-at-port' => 'pin',
            'customs-clearance', 'clearance-completed', 'customs-inspection', 'customs-hold' => 'customs',
            'released' => 'check',
            'out-for-delivery', 'delivery-attempted', 'delivery-rescheduled' => 'truck',
            'delivered' => 'home',

            'delayed', 'weather-delay', 'port-congestion' => 'clock',
            'documentation-required', 'missing-documentation' => 'documents',
            'cargo-verification' => 'search',
            'damaged-cargo' => 'alert',

            // Anything an operator adds later, by the kind of status it is.
            default => $status->isException() ? 'alert' : 'container',
        };
    }
}
