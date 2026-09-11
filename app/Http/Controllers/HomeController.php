<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('public.home', [
            'services' => Service::published()->where('show_on_home', true)->ordered()->get(),
            'reviews' => Review::published()->ordered()->limit(3)->get(),
            'faqs' => Faq::published()->where('show_on_home', true)->ordered()->limit(5)->get(),
            // The homepage tab reads as the company name alone. A tagline
            // appended here is what makes a browser tab truncate to nonsense.
            'metaTitle' => company_name(),
            'metaDescription' => setting('seo.meta_description'),
        ]);
    }

    public function about(): View
    {
        $page = Page::published()->where('slug', 'about')->first();

        return view('public.about', [
            'page' => $page,
            'services' => Service::published()->ordered()->get(),
            'metaTitle' => $page?->metaTitle() ?? 'About '.company_name(),
            'metaDescription' => $page?->meta_description ?? setting('seo.meta_description'),
        ]);
    }
}
