<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_only_published_reviews_appear_on_the_website(): void
    {
        $published = Review::factory()->create(['body' => 'Published feedback about a booking.']);
        $hidden = Review::factory()->unpublished()->create(['body' => 'Draft feedback not ready to show.']);

        $this->get(route('reviews'))
            ->assertOk()
            ->assertSee($published->body)
            ->assertDontSee($hidden->body);
    }

    public function test_an_administrator_can_create_and_publish_a_review(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)->post(route('admin.reviews.store'), [
            'customer_name' => 'Ifeoma Balogun',
            'company' => 'Coastal Supplies',
            'location' => 'Port Harcourt, Nigeria',
            'rating' => 5,
            'body' => 'Paperwork was ready before the vessel arrived.',
            'is_published' => '1',
        ])->assertRedirect(route('admin.reviews.index'));

        $review = Review::firstOrFail();

        $this->assertTrue($review->is_published);
        $this->assertFalse($review->is_sample);
        $this->assertDatabaseHas('audit_logs', ['action' => 'review.created']);
    }

    public function test_publishing_can_be_toggled(): void
    {
        $review = Review::factory()->unpublished()->create();
        $user = $this->administrator();

        $this->actingAs($user)->post(route('admin.reviews.publish', $review))->assertRedirect();
        $this->assertTrue($review->fresh()->is_published);

        $this->actingAs($user)->post(route('admin.reviews.publish', $review))->assertRedirect();
        $this->assertFalse($review->fresh()->is_published);

        $this->assertDatabaseHas('audit_logs', ['action' => 'review.unpublished']);
    }

    public function test_sample_reviews_are_labelled_on_the_website(): void
    {
        Review::factory()->create(['is_sample' => true]);

        $this->get(route('reviews'))->assertOk()->assertSee('Sample content');
    }

    public function test_an_agent_cannot_publish_reviews(): void
    {
        $review = Review::factory()->unpublished()->create();

        $this->actingAs($this->agent())
            ->post(route('admin.reviews.publish', $review))
            ->assertForbidden();

        $this->assertFalse($review->fresh()->is_published);
    }
}
