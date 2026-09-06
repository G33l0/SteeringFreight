<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\Shipment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;

/**
 * Removes everything created by the demo data seeder. Records created by your
 * own staff are left untouched.
 */
class ClearDemoData extends Command
{
    protected $signature = 'portlane:clear-demo-data {--force : Skip the confirmation}';

    protected $description = 'Delete the sample shipments, customers, reviews and enquiries created by the demo seeder';

    public function handle(): int
    {
        $shipments = Shipment::where('is_sample', true)->count();
        $customers = Customer::where('is_sample', true)->count();
        $reviews = Review::where('is_sample', true)->count();

        if ($shipments + $customers + $reviews === 0) {
            $this->components->info('There is no sample data to remove.');

            return self::SUCCESS;
        }

        $this->components->warn("This will delete {$shipments} sample shipments, {$customers} sample customers and {$reviews} sample reviews.");

        if (! $this->option('force') && ! confirm('Continue?', default: false)) {
            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            // Events, documents and conversations are removed by the foreign keys.
            Shipment::where('is_sample', true)->get()->each->delete();
            Customer::where('is_sample', true)->delete();
            Review::where('is_sample', true)->delete();
            QuoteRequest::where('email', 'like', 'sample.%@example.com')->delete();
            ContactMessage::where('email', 'like', 'sample.%@example.com')->delete();
        });

        $this->components->info('Sample data removed.');

        return self::SUCCESS;
    }
}
