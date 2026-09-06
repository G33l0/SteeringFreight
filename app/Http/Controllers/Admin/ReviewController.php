<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\AuditLogger;
use App\Services\MediaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('reviews.view');

        return view('admin.reviews.index', [
            'reviews' => Review::ordered()->paginate((int) config('portlane.per_page.admin')),
        ]);
    }

    public function create(): View
    {
        $this->authorize('reviews.manage');

        return view('admin.reviews.create', ['review' => new Review(['rating' => 5])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('reviews.manage');

        $data = $this->validated($request);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->media->store($request->file('photo'), 'reviews');
        }

        $review = Review::create($data + ['created_by' => $request->user()->getKey()]);

        $this->audit->record('review.created', $review, "Created review from {$review->customer_name}");

        return redirect()->route('admin.reviews.index')->with('status', 'Review created.');
    }

    public function show(Review $review): RedirectResponse
    {
        return redirect()->route('admin.reviews.edit', $review);
    }

    public function edit(Review $review): View
    {
        $this->authorize('reviews.manage');

        return view('admin.reviews.edit', ['review' => $review]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('reviews.manage');

        $data = $this->validated($request);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->media->replace($review->photo_path, $request->file('photo'), 'reviews');
        } elseif ($request->boolean('remove_photo')) {
            $this->media->delete($review->photo_path);
            $data['photo_path'] = null;
        }

        $review->update($data);

        $this->audit->record('review.updated', $review, "Updated review from {$review->customer_name}", AuditLogger::changes($review));

        return redirect()->route('admin.reviews.index')->with('status', 'Review saved.');
    }

    public function togglePublish(Review $review): RedirectResponse
    {
        $this->authorize('reviews.manage');

        $review->update(['is_published' => ! $review->is_published]);

        $this->audit->record(
            $review->is_published ? 'review.published' : 'review.unpublished',
            $review,
            ($review->is_published ? 'Published' : 'Unpublished')." review from {$review->customer_name}",
        );

        return back()->with('status', $review->is_published ? 'Review published.' : 'Review hidden.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('reviews.manage');

        $name = $review->customer_name;
        $this->media->delete($review->photo_path);
        $review->delete();

        $this->audit->record('review.deleted', null, "Deleted review from {$name}");

        return redirect()->route('admin.reviews.index')->with('status', 'Review deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:160'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'max:3000'],
            'service_used' => ['nullable', 'string', 'max:120'],
            'reviewed_on' => ['nullable', 'date', 'before_or_equal:today'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_published' => ['boolean'],
            'is_sample' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:'.(int) config('portlane.uploads.image_max_kb'), 'mimes:'.implode(',', (array) config('portlane.uploads.image_mimes'))],
        ]);

        $data['sort_order'] ??= 0;
        $data['is_published'] = $request->boolean('is_published');
        $data['is_sample'] = $request->boolean('is_sample');

        unset($data['photo']);

        return $data;
    }
}
