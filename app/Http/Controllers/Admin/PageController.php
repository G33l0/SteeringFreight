<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        $this->authorize('pages.view');

        return view('admin.pages.index', ['pages' => Page::orderBy('title')->get()]);
    }

    public function create(): View
    {
        $this->authorize('pages.manage');

        return view('admin.pages.create', ['page' => new Page(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('pages.manage');

        $page = Page::create($this->validated($request) + ['updated_by' => $request->user()->getKey()]);

        $this->audit->record('page.created', $page, "Created page {$page->title}");

        return redirect()->route('admin.pages.index')->with('status', 'Page created.');
    }

    public function show(Page $page): RedirectResponse
    {
        return redirect()->route('admin.pages.edit', $page);
    }

    public function edit(Page $page): View
    {
        $this->authorize('pages.manage');

        return view('admin.pages.edit', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $this->authorize('pages.manage');

        $data = $this->validated($request, $page);

        // System pages are linked from the footer, so their address is fixed.
        if ($page->is_system) {
            unset($data['slug']);
        }

        $page->update($data + ['updated_by' => $request->user()->getKey()]);

        $this->audit->record('page.updated', $page, "Updated page {$page->title}", AuditLogger::changes($page));

        return redirect()->route('admin.pages.index')->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('pages.manage');

        if ($page->is_system) {
            return back()->withErrors(['page' => 'This page is linked from the site footer and cannot be deleted.']);
        }

        $title = $page->title;
        $page->delete();

        $this->audit->record('page.deleted', null, "Deleted page {$title}");

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash', Rule::unique('pages', 'slug')->ignore($page?->getKey())],
            'intro' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:60000'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'is_published' => ['boolean'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published'] ? ($page?->published_at ?? now()) : null;

        return $data;
    }
}
