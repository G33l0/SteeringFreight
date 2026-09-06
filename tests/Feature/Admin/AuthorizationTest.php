<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_an_agent_cannot_reach_settings_users_or_audit_logs(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($agent)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($agent)->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    public function test_an_agent_cannot_manage_website_content(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)->get(route('admin.services.index'))->assertForbidden();
        $this->actingAs($agent)->get(route('admin.pages.index'))->assertForbidden();
        $this->actingAs($agent)->get(route('admin.reviews.index'))->assertForbidden();
    }

    public function test_an_agent_can_still_run_shipments(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)->get(route('admin.shipments.index'))->assertOk();
        $this->actingAs($agent)->get(route('admin.shipments.create'))->assertOk();
        $this->actingAs($agent)->get(route('admin.messages.index'))->assertOk();
    }

    public function test_a_manager_can_manage_content_but_not_administrators(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('admin.services.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_an_archived_shipment_cannot_be_edited(): void
    {
        $shipment = Shipment::factory()->archived()->create();

        $this->actingAs($this->administrator())
            ->get(route('admin.shipments.edit', $shipment))
            ->assertForbidden();
    }

    public function test_an_agent_cannot_archive_a_shipment(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAs($this->agent())
            ->post(route('admin.shipments.archive', $shipment))
            ->assertForbidden();

        $this->assertNull($shipment->fresh()->archived_at);
    }

    public function test_an_administrator_cannot_delete_their_own_account(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->delete(route('admin.users.destroy', $administrator))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $administrator->id]);
    }

    public function test_the_last_active_administrator_cannot_be_removed(): void
    {
        $administrator = $this->administrator();
        $second = User::factory()->administrator()->create();

        // Removing one of two administrators is allowed.
        $this->actingAs($administrator)->delete(route('admin.users.destroy', $second))->assertRedirect();

        $third = User::factory()->create(['role' => UserRole::Agent]);

        $this->actingAs($third);
        $this->delete(route('admin.users.destroy', $administrator))->assertForbidden();
    }
}
