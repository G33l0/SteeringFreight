<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            // The member of staff answering this customer. Null means the
            // conversation is still in the unassigned queue.
            $table->foreignId('assigned_to')->nullable()->after('customer_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');

            $table->index(['assigned_to', 'status'], 'conversations_assignee_status_index');
        });

        // Roles were reduced to master admin and customer representative.
        // Anything that was not an administrator becomes a representative.
        DB::table('users')
            ->whereNotIn('role', [UserRole::Administrator->value, UserRole::Representative->value])
            ->update(['role' => UserRole::Representative->value]);
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_assignee_status_index');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn('assigned_at');
        });
    }
};
