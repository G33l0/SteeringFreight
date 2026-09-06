<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Support\SettingDefinitions;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Writes the default value of every editable setting into the database so the
 * settings screen starts from a complete, editable set of values.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SettingDefinitions::all() as $key => $definition) {
            SiteSetting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => match ($definition['type']) {
                        SettingDefinitions::TYPE_BOOLEAN => $definition['default'] ? '1' : '0',
                        SettingDefinitions::TYPE_JSON => json_encode($definition['default'] ?? []),
                        default => $definition['default'] === null ? null : (string) $definition['default'],
                    },
                    'type' => $definition['type'],
                    'group' => $definition['group'],
                ],
            );
        }

        app(Settings::class)->flush();
    }
}
