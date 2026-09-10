<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;

/**
 * Clears the alt text the service seeder used to write.
 *
 * It stamped "<Title> illustration" on every service, which describes the drawn
 * artwork rather than whatever picture the service is actually showing. Now that
 * a service can carry a photograph, that text tells anyone using a screen reader
 * something untrue. Emptying it lets the model describe the picture in front of
 * the reader instead, and anything staff wrote themselves is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Service::query()
            ->whereNotNull('image_alt')
            ->each(function (Service $service): void {
                if ($service->image_alt === $service->title.' illustration') {
                    $service->forceFill(['image_alt' => null])->save();
                }
            });
    }

    public function down(): void
    {
        // The seeded text is not worth restoring: it described the wrong picture.
    }
};
