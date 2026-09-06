<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the data the site needs to work: settings, tracking statuses,
     * services, pages and questions.
     *
     * Sample shipments, customers and reviews are only added outside of
     * production, and can be added anywhere with:
     *   php artisan db:seed --class=Database\\Seeders\\DemoDataSeeder
     */
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            ShipmentStatusSeeder::class,
            ServiceSeeder::class,
            PageSeeder::class,
            FaqSeeder::class,
        ]);

        if (! app()->environment('production')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
