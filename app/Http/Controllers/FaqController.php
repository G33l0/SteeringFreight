<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::published()->ordered()->get()->groupBy(fn (Faq $faq) => $faq->category ?: 'General');

        return view('public.faq', [
            'groups' => $faqs,
            'metaTitle' => 'Frequently asked questions — '.company_name(),
            'metaDescription' => 'Answers to common questions about bookings, transit times, customs documents and shipment tracking.',
        ]);
    }
}
