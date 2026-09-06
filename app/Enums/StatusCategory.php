<?php

namespace App\Enums;

enum StatusCategory: string
{
    case Milestone = 'milestone';
    case Exception = 'exception';

    public function label(): string
    {
        return match ($this) {
            self::Milestone => 'Milestone',
            self::Exception => 'Exception',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category) => [$category->value => $category->label()])
            ->all();
    }
}
