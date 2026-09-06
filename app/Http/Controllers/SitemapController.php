<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Service;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('services.index'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('track.index'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('quote.create'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('contact.create'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('faq'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => route('reviews'), 'priority' => '0.5', 'changefreq' => 'monthly'],
        ]);

        foreach (Service::published()->ordered()->get() as $service) {
            $urls->push([
                'loc' => route('services.show', $service),
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $service->updated_at?->toAtomString(),
            ]);
        }

        foreach (Page::published()->get() as $page) {
            $urls->push([
                'loc' => $this->pageUrl($page),
                'priority' => '0.3',
                'changefreq' => 'yearly',
                'lastmod' => $page->updated_at?->toAtomString(),
            ]);
        }

        return response()
            ->view('public.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $lines = setting('seo.indexable', true)
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /track/', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain');
    }

    private function pageUrl(Page $page): string
    {
        return match ($page->slug) {
            'privacy-policy' => route('privacy'),
            'terms-of-service' => route('terms'),
            'about' => route('about'),
            default => route('pages.show', $page),
        };
    }
}
