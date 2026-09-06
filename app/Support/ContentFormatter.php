<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Turns administrator written page copy into safe HTML.
 *
 * Raw HTML is never rendered. The formatter understands a very small amount of
 * markup, which keeps legal pages readable without exposing the site to stored
 * cross site scripting.
 *
 *   ## Heading
 *   ### Sub heading
 *   - bullet item
 *   1. numbered item
 *   [link text](https://example.com)
 *   **bold**
 */
class ContentFormatter
{
    public static function render(?string $content): HtmlString
    {
        $content = trim(ContentTokens::apply((string) $content));

        if ($content === '') {
            return new HtmlString('');
        }

        $blocks = preg_split('/\R{2,}/', str_replace("\r\n", "\n", $content)) ?: [];
        $html = '';

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            $lines = explode("\n", $block);

            if (self::allLinesMatch($lines, '/^\s*[-*]\s+/')) {
                $html .= self::renderList($lines, '/^\s*[-*]\s+/', 'ul');

                continue;
            }

            if (self::allLinesMatch($lines, '/^\s*\d+[.)]\s+/')) {
                $html .= self::renderList($lines, '/^\s*\d+[.)]\s+/', 'ol');

                continue;
            }

            if (Str::startsWith($block, '### ')) {
                $html .= '<h3>'.self::inline(Str::after($block, '### ')).'</h3>';

                continue;
            }

            if (Str::startsWith($block, '## ')) {
                $html .= '<h2>'.self::inline(Str::after($block, '## ')).'</h2>';

                continue;
            }

            $html .= '<p>'.implode('<br>', array_map(self::inline(...), $lines)).'</p>';
        }

        return new HtmlString($html);
    }

    /**
     * Plain text version, used for meta descriptions and email bodies.
     */
    public static function plain(?string $content, int $limit = 160): string
    {
        $text = preg_replace('/\s+/', ' ', strip_tags(self::render($content)->toHtml()));

        return Str::limit(trim((string) $text), $limit);
    }

    /** @param list<string> $lines */
    private static function allLinesMatch(array $lines, string $pattern): bool
    {
        foreach ($lines as $line) {
            if (trim($line) !== '' && preg_match($pattern, $line) !== 1) {
                return false;
            }
        }

        return true;
    }

    /** @param list<string> $lines */
    private static function renderList(array $lines, string $pattern, string $tag): string
    {
        $items = '';

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $items .= '<li>'.self::inline((string) preg_replace($pattern, '', $line)).'</li>';
        }

        return "<{$tag}>{$items}</{$tag}>";
    }

    private static function inline(string $text): string
    {
        $escaped = e(trim($text));

        // Bold.
        $escaped = (string) preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);

        // Links, restricted to http, https and mailto targets.
        return (string) preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            function (array $matches): string {
                $url = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');

                if (! preg_match('#^(https?://|mailto:|/)#i', $url)) {
                    return $matches[1];
                }

                $external = Str::startsWith($url, ['http://', 'https://']);
                $attributes = $external ? ' target="_blank" rel="noopener noreferrer"' : '';

                return '<a href="'.e($url).'"'.$attributes.'>'.$matches[1].'</a>';
            },
            $escaped,
        );
    }
}
