<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\AuditLogger;
use App\Services\MediaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public const ICONS = ['container', 'vessel', 'aircraft', 'customs', 'warehouse', 'truck', 'consolidation', 'documents'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MediaService $media,
    ) {}

    public function index(): View
    {
        $this->authorize('services.view');

        return view('admin.services.index', [
            'services' => Service::ordered()->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('services.manage');

        return view('admin.services.create', [
            'service' => new Service(['is_published' => true, 'show_on_home' => true, 'icon' => 'container']),
            'icons' => self::ICONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->media->store($request->file('image'), 'services');
        }

        $service = Service::create($data);

        $this->audit->record('service.created', $service, "Created service {$service->title}");

        return redirect()->route('admin.services.index')->with('status', 'Service created.');
    }

    public function show(Service $service): RedirectResponse
    {
        return redirect()->route('admin.services.edit', $service);
    }

    public function edit(Service $service): View
    {
        $this->authorize('services.manage');

        return view('admin.services.edit', ['service' => $service, 'icons' => self::ICONS]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('services.manage');

        $data = $this->validated($request, $service);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->media->replace($service->image_path, $request->file('image'), 'services');
        } elseif ($request->boolean('remove_image')) {
            $this->media->delete($service->image_path);
            $data['image_path'] = null;
        }

        $service->update($data);

        $this->audit->record('service.updated', $service, "Updated service {$service->title}", AuditLogger::changes($service));

        return redirect()->route('admin.services.index')->with('status', 'Service saved.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('services.manage');

        $title = $service->title;
        $this->media->delete($service->image_path);
        $service->delete();

        $this->audit->record('service.deleted', null, "Deleted service {$title}");

        return redirect()->route('admin.services.index')->with('status', 'Service deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Service $service = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('services', 'slug')->ignore($service?->getKey())],
            'summary' => ['required', 'string', 'max:400'],
            'description' => ['nullable', 'string', 'max:20000'],
            'icon' => ['required', Rule::in(self::ICONS)],
            'image' => ['nullable', 'image', 'max:'.(int) config('portlane.uploads.image_max_kb'), 'mimes:'.implode(',', (array) config('portlane.uploads.image_mimes'))],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'highlights_text' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_published' => ['boolean'],
            'show_on_home' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['sort_order'] ??= 0;
        $data['is_published'] = $request->boolean('is_published');
        $data['show_on_home'] = $request->boolean('show_on_home');
        $data['highlights'] = collect(preg_split('/\R/', (string) ($data['highlights_text'] ?? '')))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();

        unset($data['highlights_text'], $data['image']);

        return $data;
    }
}
