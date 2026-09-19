<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a customer representative raise tracking numbers, within an allowance.
 *
 * The allowance is a count of shipments the account has created, not a rate:
 * once it is used up the representative asks the administrator to raise it,
 * which is the conversation the business wanted to have anyway.
 *
 * `assigned_to` is the other half. A representative works on what they raised
 * themselves, plus anything an administrator has handed to them — a holiday, a
 * handover, an escalation — and nothing else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('tracking_quota')->default(5)->after('access_expires_at');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('updated_by')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tracking_quota');
        });
    }
};
