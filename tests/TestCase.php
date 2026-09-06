<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\ShipmentStatus;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\ShipmentStatusSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed the settings and the default tracking statuses, which most of the
     * application depends on.
     */
    protected function seedCoreData(): void
    {
        $this->seed(SettingsSeeder::class);
        $this->seed(ShipmentStatusSeeder::class);
        ShipmentStatus::forgetCachedTimeline();
    }

    protected function administrator(array $attributes = []): User
    {
        return User::factory()->administrator()->create($attributes);
    }

    protected function agent(array $attributes = []): User
    {
        return User::factory()->create(['role' => UserRole::Agent] + $attributes);
    }

    protected function trackingStatus(string $slug): ShipmentStatus
    {
        return ShipmentStatus::where('slug', $slug)->firstOrFail();
    }
}
