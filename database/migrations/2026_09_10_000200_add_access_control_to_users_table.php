<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two things the staff accounts were missing.
 *
 * An access period: a representative can be given the panel for a fixed time
 * and is suspended automatically when it runs out, and can be paused by hand
 * at any point without deleting the account or losing the audit trail.
 *
 * A sign-in code: the password alone is no longer enough for a master admin.
 * Only the hash of the code is stored, alongside when it expires and how many
 * times it has been guessed, so the columns are useless to anyone reading the
 * database.
 *
 * The three two_factor_* columns reserved by the original migration are
 * dropped: they were for an authenticator app that was never built, nothing
 * ever wrote to them, and leaving two unused secret columns next to the real
 * ones would only invite writing to the wrong pair.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('is_active');
            $table->timestamp('access_expires_at')->nullable()->after('suspended_at');

            $table->string('login_code_hash')->nullable()->after('last_login_ip');
            $table->timestamp('login_code_expires_at')->nullable()->after('login_code_hash');
            $table->timestamp('login_code_sent_at')->nullable()->after('login_code_expires_at');
            $table->unsignedTinyInteger('login_code_attempts')->default(0)->after('login_code_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'suspended_at', 'access_expires_at',
                'login_code_hash', 'login_code_expires_at', 'login_code_sent_at', 'login_code_attempts',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('last_login_ip');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }
};
