<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Contracts\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('public.reviews', [
            'reviews' => Review::published()->ordered()->paginate((int) config('portlane.per_page.public'))->withQueryString(),
            'metaTitle' => 'Client reviews — '.company_name(),
            'metaDescription' => 'Feedback from importers, exporters and forwarding partners we work with.',
        ]);
    }
}
