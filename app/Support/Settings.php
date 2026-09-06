<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Reads and writes the editable site settings.
 *
 * Values are cached as a single array so a page render costs one query at
 * most, which matters on shared hosting.
 */
class Settings
{
    public const CACHE_KEY = 'site_settings.all';

    /** @var array<string, mixed>|null */
    private ?array $loaded = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
            return $values[$key];
        }

        if ($default !== null) {
            return $default;
        }

        return SettingDefinitions::find($key)['default'] ?? null;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : (bool) $value;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    /** @return array<int, array<string, string>> */
    public function list(string $key): array
    {
        $value = $this->get($key);

        return is_array($value) ? $value : [];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $stored = Cache::rememberForever(self::CACHE_KEY, function (): array {
            return SiteSetting::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn (SiteSetting $setting) => [
                    $setting->key => self::castValue($setting->value, $setting->type),
                ])
                ->all();
        });

        return $this->loaded = array_merge(SettingDefinitions::defaults(), $stored);
    }

    /**
     * Settings belonging to one group, merged with their defaults.
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $keys = collect(SettingDefinitions::all())
            ->filter(fn (array $definition) => $definition['group'] === $group)
            ->keys();

        $values = $this->all();

        return $keys->mapWithKeys(fn (string $key) => [$key => $values[$key] ?? null])->all();
    }

    public function set(string $key, mixed $value): void
    {
        $definition = SettingDefinitions::find($key);
        $type = $definition['type'] ?? SettingDefinitions::TYPE_STRING;

        SiteSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::serialiseValue($value, $type),
                'type' => $type,
                'group' => $definition['group'] ?? 'general',
            ],
        );

        $this->flush();
    }

    /** @param array<string, mixed> $values */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function flush(): void
    {
        $this->loaded = null;

        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // A missing cache table during installation must not break a save.
        }
    }

    private static function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            SettingDefinitions::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            SettingDefinitions::TYPE_INTEGER => (int) $value,
            SettingDefinitions::TYPE_JSON => json_decode((string) $value, true) ?: [],
            default => $value,
        };
    }

    private static function serialiseValue(mixed $value, string $type): ?string
    {
        return match ($type) {
            SettingDefinitions::TYPE_BOOLEAN => $value ? '1' : '0',
            SettingDefinitions::TYPE_INTEGER => (string) (int) $value,
            SettingDefinitions::TYPE_JSON => json_encode(array_values((array) $value)),
            default => $value === null ? null : (string) $value,
        };
    }
}
