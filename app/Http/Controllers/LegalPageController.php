<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegalPageController extends Controller
{
    /** Slugs that are reached through their own named route. */
    private const RESERVED_SLUGS = ['about', 'privacy-policy', 'terms-of-service'];

    public function privacy(): View
    {
        return $this->render('privacy-policy');
    }

    public function terms(): View
    {
        return $this->render('terms-of-service');
    }

    /**
     * Pages that are not part of the fixed navigation. The system pages have
     * their own addresses, so they are not served twice.
     */
    public function show(Page $page): View
    {
        if (! $page->is_published || in_array($page->slug, self::RESERVED_SLUGS, true)) {
            throw new NotFoundHttpException;
        }

        return $this->view($page);
    }

    private function render(string $slug): View
    {
        $page = Page::published()->where('slug', $slug)->first();

        if (! $page) {
            throw new NotFoundHttpException;
        }

        return $this->view($page);
    }

    private function view(Page $page): View
    {
        return view('public.page', [
            'page' => $page,
            'metaTitle' => $page->metaTitle().' — '.company_name(),
            'metaDescription' => $page->meta_description ?? $page->intro,
        ]);
    }
}
