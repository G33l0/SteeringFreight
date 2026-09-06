<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('public.services.index', [
            'services' => Service::published()->ordered()->get(),
            'metaTitle' => 'Freight services — '.company_name(),
            'metaDescription' => 'Sea freight, air freight, customs clearance, warehousing, door to door delivery and cargo consolidation.',
        ]);
    }

    public function show(Service $service): View
    {
        if (! $service->is_published) {
            throw new NotFoundHttpException;
        }

        return view('public.services.show', [
            'service' => $service,
            'related' => Service::published()->ordered()->whereKeyNot($service->getKey())->limit(4)->get(),
            'faqs' => Faq::published()->ordered()->where('category', $service->title)->get(),
            'metaTitle' => $service->metaTitle().' — '.company_name(),
            'metaDescription' => $service->metaDescription(),
        ]);
    }
}
