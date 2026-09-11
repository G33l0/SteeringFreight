<?php

namespace Tests\Feature\Public;

use App\Support\BusinessHours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The frame around every public page: what the browser tab says, what comes
 * first on the page, and what is deliberately not offered to visitors.
 */
class PublicChromeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_the_homepage_tab_is_the_company_name_alone(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>Portlane Shipping</title>', false);
    }

    public function test_the_tagline_does_not_sit_above_the_header(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()->assertDontSee(setting('company.tagline'));
    }

    public function test_the_public_site_does_not_advertise_the_staff_login(): void
    {
        foreach ([route('home'), route('about'), route('contact.create'), route('faq')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee(route('admin.login'))
                ->assertDontSee('Staff login');
        }
    }

    public function test_a_desk_that_never_closes_is_stated_once(): void
    {
        $this->assertTrue(BusinessHours::isAlwaysOpen());
        $this->assertSame(['Every day' => '24 hours'], BusinessHours::rows());
        $this->assertSame('every day, around the clock', BusinessHours::sentence());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Every day')
            ->assertDontSee('Monday to Friday');
    }

    public function test_office_hours_are_still_available_to_anyone_who_wants_them(): void
    {
        $this->setSetting('contact.hours_weekdays', '08:00 - 18:00');
        $this->setSetting('contact.hours_saturday', '09:00 - 13:00');
        $this->setSetting('contact.hours_sunday', 'Closed');

        $this->assertFalse(BusinessHours::isAlwaysOpen());
        $this->assertSame('08:00 - 18:00 on weekdays', BusinessHours::sentence());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Monday to Friday')
            ->assertSee('Saturday');
    }

    public function test_the_footer_describes_the_company_without_explaining_its_workings(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('arranges sea and air freight, handles customs entries and delivers cargo', false)
            ->assertDontSee('named coordinator');
    }
}
