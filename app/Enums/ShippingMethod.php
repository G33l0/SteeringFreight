<?php

namespace App\Enums;

enum ShippingMethod: string
{
    case SeaFreightFcl = 'sea_fcl';
    case SeaFreightLcl = 'sea_lcl';
    case AirFreight = 'air';
    case RoadFreight = 'road';
    case RailFreight = 'rail';
    case Courier = 'courier';
    case Multimodal = 'multimodal';

    public function label(): string
    {
        return match ($this) {
            self::SeaFreightFcl => 'Sea freight (FCL)',
            self::SeaFreightLcl => 'Sea freight (LCL)',
            self::AirFreight => 'Air freight',
            self::RoadFreight => 'Road freight',
            self::RailFreight => 'Rail freight',
            self::Courier => 'Courier / express',
            self::Multimodal => 'Multimodal',
        };
    }

    public function isSea(): bool
    {
        return in_array($this, [self::SeaFreightFcl, self::SeaFreightLcl], true);
    }

    public function isAir(): bool
    {
        return $this === self::AirFreight;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $method) => [$method->value => $method->label()])
            ->all();
    }
}
