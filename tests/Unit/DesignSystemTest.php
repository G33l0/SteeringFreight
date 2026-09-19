<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The stylesheet is the site's design system, so the rules it is built on are
 * asserted here rather than left to a reviewer's eye:
 *
 *  - every colour is a custom property, because the palette is published from
 *    the settings and a hex buried in a component rule would not follow a
 *    rebrand;
 *  - x-cloak actually hides something, or every menu is painted open until
 *    Alpine boots;
 *  - anything that moves stops for somebody who has asked their system for
 *    reduced motion;
 *  - a control is big enough to hit with a thumb, and a field is big enough
 *    that Safari on iPhone does not zoom the page in when it takes focus.
 */
class DesignSystemTest extends TestCase
{
    private static function stylesheet(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');
    }

    /**
     * Everything outside the @theme block, which is where the tokens live.
     */
    private static function rulesWithoutTokens(): string
    {
        $css = self::stylesheet();
        $start = strpos($css, '@theme {');
        $end = strpos($css, "\n}", (int) $start);

        return substr($css, 0, (int) $start).substr($css, (int) $end);
    }

    public function test_colours_are_declared_once_as_tokens(): void
    {
        preg_match_all('/#[0-9a-fA-F]{3,8}\b/', self::rulesWithoutTokens(), $matches);

        $strays = array_values(array_filter(
            $matches[0],
            // Plain white and black are not brand colours and never change.
            fn (string $hex) => ! in_array(strtolower($hex), ['#fff', '#ffffff', '#000', '#000000'], true),
        ));

        $this->assertSame([], $strays, 'Colours outside the @theme block: '.implode(', ', $strays));
    }

    #[DataProvider('statusBadges')]
    public function test_each_status_badge_is_painted_from_tokens(string $badge): void
    {
        $this->assertMatchesRegularExpression(
            '/\.'.preg_quote($badge, '/').'\s*\{[^}]*background:\s*var\(--color-[a-z]+-100\);\s*color:\s*var\(--color-[a-z]+-700\);/',
            self::stylesheet(),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function statusBadges(): array
    {
        return [
            'slate' => ['badge-slate'],
            'blue' => ['badge-blue'],
            'teal' => ['badge-teal'],
            'green' => ['badge-green'],
            'amber' => ['badge-amber'],
            'red' => ['badge-red'],
        ];
    }

    public function test_alpine_cloaking_hides_a_menu_that_has_not_booted_yet(): void
    {
        $this->assertMatchesRegularExpression(
            '/\[x-cloak\]\s*\{\s*display:\s*none\s*!important;/',
            self::stylesheet(),
        );
    }

    public function test_motion_stops_for_anybody_who_has_asked_for_it(): void
    {
        $css = self::stylesheet();

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertMatchesRegularExpression('/animation-duration:\s*0\.01ms\s*!important/', $css);
        $this->assertMatchesRegularExpression('/transition-duration:\s*0\.01ms\s*!important/', $css);
    }

    public function test_controls_are_large_enough_to_hit(): void
    {
        $css = self::stylesheet();

        $this->assertMatchesRegularExpression('/\.btn\s*\{[^}]*min-height:\s*2\.75rem;/', $css);
        $this->assertMatchesRegularExpression('/\.input,\s*\.select,\s*\.textarea\s*\{[^}]*min-height:\s*2\.75rem;/', $css);
    }

    public function test_a_field_is_never_small_enough_to_make_safari_zoom_in(): void
    {
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 639\.98px\) \{\s*\.input,\s*\.select,\s*\.textarea\s*\{\s*font-size:\s*1rem;/',
            self::stylesheet(),
        );
    }

    public function test_the_type_and_spacing_steps_scale_with_the_viewport(): void
    {
        $css = self::stylesheet();

        foreach (['--text-display', '--text-heading', '--text-title', '--text-subtitle'] as $step) {
            $this->assertMatchesRegularExpression('/'.preg_quote($step, '/').':\s*clamp\(/', $css);
        }

        foreach (['--spacing-gutter', '--spacing-section', '--spacing-section-sm', '--spacing-hero'] as $step) {
            $this->assertMatchesRegularExpression('/'.preg_quote($step, '/').':\s*clamp\(/', $css);
        }
    }
}
