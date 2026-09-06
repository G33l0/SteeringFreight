<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            // The route is captured as country plus city so enquiries can be
            // compared and searched. The original free text origin and
            // destination columns are kept and filled from these.
            $table->string('origin_country', 120)->nullable()->after('company');
            $table->string('origin_city', 120)->nullable()->after('origin_country');
            $table->string('destination_country', 120)->nullable()->after('origin_city');
            $table->string('destination_city', 120)->nullable()->after('destination_country');
            $table->string('dimensions', 160)->nullable()->after('approximate_weight');
            $table->string('incoterm', 20)->nullable()->after('dimensions');
            $table->string('goods_value', 60)->nullable()->after('incoterm');
            $table->timestamp('replied_at')->nullable()->after('handled_at');

            $table->index(['origin_country', 'destination_country'], 'quotes_route_index');
        });

        // Replies sent to the customer from the admin panel, kept against the
        // request so the whole exchange stays on one screen.
        Schema::create('quote_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_name');
            $table->string('subject');
            $table->text('body');
            $table->string('quoted_amount', 60)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('transit_time', 80)->nullable();
            $table->string('valid_until', 60)->nullable();
            $table->timestamps();

            $table->index(['quote_request_id', 'id'], 'quote_replies_request_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_replies');

        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropIndex('quotes_route_index');
            $table->dropColumn([
                'origin_country', 'origin_city', 'destination_country', 'destination_city',
                'dimensions', 'incoterm', 'goods_value', 'replied_at',
            ]);
        });
    }
};
