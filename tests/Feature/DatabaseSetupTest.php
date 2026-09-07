<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Shipment;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * SQLite is a supported production database, not only the test harness. These
 * cover the settings and the habits that make that true.
 */
class DatabaseSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sqlite_connection_is_configured_for_concurrent_use(): void
    {
        $this->assertSame('WAL', config('database.connections.sqlite.journal_mode'));
        $this->assertSame(5000, (int) config('database.connections.sqlite.busy_timeout'));
        $this->assertSame('NORMAL', config('database.connections.sqlite.synchronous'));
        $this->assertTrue((bool) config('database.connections.sqlite.foreign_key_constraints'));
    }

    public function test_foreign_keys_are_enforced_so_a_deleted_conversation_takes_its_messages(): void
    {
        $conversation = ChatConversation::factory()->create(['shipment_id' => Shipment::factory()->create()->id]);
        ChatMessage::factory()->count(2)->create(['chat_conversation_id' => $conversation->id]);

        $conversation->delete();

        $this->assertSame(0, ChatMessage::where('chat_conversation_id', $conversation->id)->count());
    }

    public function test_polling_an_up_to_date_conversation_writes_nothing(): void
    {
        $shipment = Shipment::factory()->create();
        $conversation = ChatConversation::factory()->create([
            'shipment_id' => $shipment->id,
            'last_message_at' => now(),
            'unread_for_customer' => 0,
        ]);
        ChatMessage::factory()->create([
            'chat_conversation_id' => $conversation->id,
            'read_at' => now(),
        ]);

        $writes = [];
        DB::listen(function ($query) use (&$writes): void {
            if (preg_match('/^\s*(insert|update|delete)/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });

        // What the tracking page does every few seconds while it is open.
        app(ChatService::class)->markReadByCustomer($conversation);

        $this->assertSame([], $writes, 'An idle chat poll should not write to the database.');
    }

    public function test_a_new_reply_is_still_marked_read(): void
    {
        $shipment = Shipment::factory()->create();
        $conversation = ChatConversation::factory()->create([
            'shipment_id' => $shipment->id,
            'unread_for_customer' => 1,
        ]);
        $message = ChatMessage::factory()->fromStaff()->create([
            'chat_conversation_id' => $conversation->id,
            'read_at' => null,
        ]);

        app(ChatService::class)->markReadByCustomer($conversation);

        $this->assertNotNull($message->fresh()->read_at);
        $this->assertSame(0, $conversation->fresh()->unread_for_customer);
    }

    public function test_the_backup_command_writes_a_usable_copy(): void
    {
        // The suite runs in memory, where there is nothing to copy, so point the
        // command at a real file the way a deployed install would.
        $source = storage_path('framework/testing/backup-source.sqlite');
        $directory = storage_path('framework/testing/backups');
        $original = config('database.default');

        File::ensureDirectoryExists(dirname($source));
        File::put($source, '');
        File::deleteDirectory($directory);

        config([
            'database.connections.backup_test' => array_merge(config('database.connections.sqlite'), ['database' => $source]),
            'database.default' => 'backup_test',
        ]);

        try {
            DB::connection('backup_test')->statement('create table probe (id integer primary key, note text)');
            DB::connection('backup_test')->table('probe')->insert(['note' => 'a shipment record']);

            $this->artisan('portlane:backup-database', ['--path' => $directory])->assertSuccessful();

            $copies = File::files($directory);
            $this->assertCount(1, $copies);

            // The copy opens on its own and holds the same rows.
            config(['database.connections.backup_copy' => array_merge(
                config('database.connections.sqlite'),
                ['database' => $copies[0]->getPathname()],
            )]);

            $this->assertSame('a shipment record', DB::connection('backup_copy')->table('probe')->value('note'));
            DB::purge('backup_copy');
        } finally {
            // Close the file handles before removing the files, or SQLite leaves
            // its write ahead log behind and the next test inherits it.
            DB::purge('backup_test');
            config(['database.default' => $original]);

            foreach ([$source, $source.'-wal', $source.'-shm'] as $file) {
                File::delete($file);
            }

            File::deleteDirectory($directory);
        }
    }

    public function test_the_database_directory_is_not_served_over_http(): void
    {
        $this->assertFileExists(base_path('database/.htaccess'));
        $this->assertStringContainsString('Require all denied', File::get(base_path('database/.htaccess')));
    }
}
