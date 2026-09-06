<?php

namespace Tests\Unit;

use App\Support\ContentFormatter;
use PHPUnit\Framework\TestCase;

class ContentFormatterTest extends TestCase
{
    public function test_it_renders_headings_paragraphs_and_lists(): void
    {
        $html = ContentFormatter::render("## Sea freight\n\nFirst paragraph.\n\n- One\n- Two")->toHtml();

        $this->assertStringContainsString('<h2>Sea freight</h2>', $html);
        $this->assertStringContainsString('<p>First paragraph.</p>', $html);
        $this->assertStringContainsString('<li>One</li>', $html);
    }

    public function test_it_escapes_html_supplied_by_an_administrator(): void
    {
        $html = ContentFormatter::render('<script>alert("x")</script>')->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_it_only_allows_safe_link_targets(): void
    {
        $safe = ContentFormatter::render('[Our terms](https://example.com/terms)')->toHtml();
        $this->assertStringContainsString('href="https://example.com/terms"', $safe);

        $unsafe = ContentFormatter::render('[Click me](javascript:alert(1))')->toHtml();
        $this->assertStringNotContainsString('javascript:', $unsafe);
        $this->assertStringContainsString('Click me', $unsafe);
    }

    public function test_it_returns_an_empty_string_for_empty_content(): void
    {
        $this->assertSame('', ContentFormatter::render(null)->toHtml());
    }
}
