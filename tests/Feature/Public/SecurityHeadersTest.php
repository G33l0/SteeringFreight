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

    public function test_a_content_security_policy_is_sent(): void
    {
        config(['portlane.security.csp' => 'enforce']);

        $response = $this->get(route('home'))->assertOk();
        $policy = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($policy, 'no content security policy was sent');

        // Nothing loads from another origin.
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);

        // No third-party script host is allowed in, which is the main thing
        // this buys: an injected <script src="..."> is refused.
        $this->assertStringNotContainsString('http:', $policy);
        $this->assertStringNotContainsString('https:', $policy);
        $this->assertStringNotContainsString('*', $policy);
    }

    /**
     * Named rather than hidden. Alpine evaluates the expressions written in the
     * markup, so removing this breaks the navigation and the chat; a test that
     * asserts it is present stops it being dropped by accident and stops
     * anybody believing the policy is stricter than it is.
     */
    public function test_the_policy_allows_what_alpine_needs_and_says_so(): void
    {
        config(['portlane.security.csp' => 'enforce']);

        $policy = $this->get(route('home'))->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self' 'unsafe-eval'", $policy);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $policy);
    }

    public function test_the_policy_can_be_introduced_in_report_only_mode(): void
    {
        config(['portlane.security.csp' => 'report']);

        $response = $this->get(route('home'))->assertOk();

        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function test_the_policy_can_be_turned_off(): void
    {
        config(['portlane.security.csp' => 'off']);

        $this->get(route('home'))
            ->assertOk()
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only');
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
