<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusCategory;
use App\Http\Controllers\Controller;
use App\Models\ShipmentStatus;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShipmentStatusController extends Controller
{
    private const COLOURS = ['slate', 'blue', 'teal', 'amber', 'red', 'green'];

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        $this->authorize('statuses.view');

        $statuses = ShipmentStatus::withCount('shipments')->ordered()->get();

        return view('admin.statuses.index', [
            'milestones' => $statuses->where('category', StatusCategory::Milestone),
            'exceptions' => $statuses->where('category', StatusCategory::Exception),
        ]);
    }

    public function create(): View
    {
        $this->authorize('statuses.manage');

        return view('admin.statuses.create', [
            'status' => new ShipmentStatus(['category' => StatusCategory::Milestone, 'colour' => 'slate', 'is_active' => true]),
            'colours' => self::COLOURS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('statuses.manage');

        $status = ShipmentStatus::create($this->validated($request));

        $this->audit->record('status.created', $status, "Created status {$status->name}");

        return redirect()->route('admin.statuses.index')->with('status', 'Status created.');
    }

    public function edit(ShipmentStatus $status): View
    {
        $this->authorize('statuses.manage');

        return view('admin.statuses.edit', ['status' => $status, 'colours' => self::COLOURS]);
    }

    public function update(Request $request, ShipmentStatus $status): RedirectResponse
    {
        $this->authorize('statuses.manage');

        $status->update($this->validated($request, $status));

        $this->audit->record('status.updated', $status, "Updated status {$status->name}", AuditLogger::changes($status));

        return redirect()->route('admin.statuses.index')->with('status', 'Status saved.');
    }

    public function destroy(ShipmentStatus $status): RedirectResponse
    {
        $this->authorize('statuses.manage');

        if ($status->shipments()->exists() || $status->events()->exists()) {
            return back()->withErrors([
                'status' => 'This status is in use on existing shipments. Switch it off instead of deleting it.',
            ]);
        }

        $name = $status->name;
        $status->delete();

        $this->audit->record('status.deleted', null, "Deleted status {$name}");

        return redirect()->route('admin.statuses.index')->with('status', 'Status deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ShipmentStatus $status = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('shipment_statuses', 'slug')->ignore($status?->getKey())],
            'category' => ['required', Rule::enum(StatusCategory::class)],
            'stage' => ['nullable', 'integer', 'min:1', 'max:999'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'colour' => ['required', Rule::in(self::COLOURS)],
            'customer_label' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'is_final' => ['boolean'],
            'notify_customer' => ['boolean'],
            'requires_explanation' => ['boolean'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['sort_order'] ??= 0;
        $data['stage'] = $data['category'] === StatusCategory::Milestone->value ? $data['stage'] : null;

        foreach (['is_active', 'is_final', 'notify_customer', 'requires_explanation'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
