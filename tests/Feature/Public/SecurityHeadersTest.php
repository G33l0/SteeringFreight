<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The headers every response carries.
 *
 * Strict-Transport-Security gets the most attention here because it is the one
 * a site cannot take back once a browser has been told it: the promise is
 * remembered for its full duration whatever the site does afterwards. So it
 * must never be sent over a connection that is not already secure.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();
    }

    public function test_every_response_carries_the_basic_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
    }

    public function test_hsts_is_sent_over_https(): void
    {
        config(['portlane.security.hsts_max_age' => 31536000]);

        $this->withServerVariables(['HTTPS' => 'on'])
            ->get('https://localhost/')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    /**
     * The important one. A site part way through being set up, or one whose
     * certificate has not been issued yet, would otherwise tell a browser to
     * refuse the only protocol currently working.
     */
    public function test_hsts_is_never_sent_over_plain_http(): void
    {
        config(['portlane.security.hsts_max_age' => 31536000]);

        $this->get('http://localhost/')
            ->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_can_be_turned_off(): void
    {
        config(['portlane.security.hsts_max_age' => 0]);

        $this->withServerVariables(['HTTPS' => 'on'])
            ->get('https://localhost/')
            ->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_the_duration_is_configurable_for_a_cautious_first_deployment(): void
    {
        config(['portlane.security.hsts_max_age' => 300]);

        $this->withServerVariables(['HTTPS' => 'on'])
            ->get('https://localhost/')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=300');
    }
}
