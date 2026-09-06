<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShippingMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShipmentRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
use App\Services\ShipmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentService $shipments) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Shipment::class);

        return view('admin.shipments.index', [
            'shipments' => $this->filtered($request, Shipment::active())
                ->paginate((int) config('portlane.per_page.admin'))
                ->withQueryString(),
            'statuses' => ShipmentStatus::ordered()->get(),
            'methods' => ShippingMethod::options(),
            'filters' => $this->filters($request),
            'archived' => false,
        ]);
    }

    public function archived(Request $request): View
    {
        $this->authorize('viewAny', Shipment::class);

        return view('admin.shipments.index', [
            'shipments' => $this->filtered($request, Shipment::archived())
                ->paginate((int) config('portlane.per_page.admin'))
                ->withQueryString(),
            'statuses' => ShipmentStatus::ordered()->get(),
            'methods' => ShippingMethod::options(),
            'filters' => $this->filters($request),
            'archived' => true,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Shipment::class);

        return view('admin.shipments.create', $this->formData());
    }

    public function store(ShipmentRequest $request): RedirectResponse
    {
        $shipment = $this->shipments->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.shipments.show', $shipment)
            ->with('status', "Shipment {$shipment->tracking_number} created.");
    }

    public function show(Shipment $shipment): View
    {
        $this->authorize('view', $shipment);

        $shipment->load([
            'status',
            'customer',
            'creator',
            'updater',
            'events.status',
            'events.creator',
            'documents.uploader',
            'conversations.latestMessage',
        ]);

        return view('admin.shipments.show', [
            'shipment' => $shipment,
            'statuses' => ShipmentStatus::active()->ordered()->get(),
            'history' => AuditLog::with('user')
                ->where('auditable_type', Shipment::class)
                ->where('auditable_id', $shipment->getKey())
                ->latest('id')
                ->limit(30)
                ->get(),
        ]);
    }

    public function edit(Shipment $shipment): View
    {
        $this->authorize('update', $shipment);

        return view('admin.shipments.edit', $this->formData(['shipment' => $shipment]));
    }

    public function update(ShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->shipments->update($shipment, $request->validated(), $request->user());

        return redirect()
            ->route('admin.shipments.show', $shipment)
            ->with('status', 'Shipment updated.');
    }

    public function archive(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('archive', $shipment);

        $this->shipments->archive($shipment, $request->user());

        return redirect()->route('admin.shipments.index')->with('status', "Shipment {$shipment->tracking_number} archived.");
    }

    public function restore(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('archive', $shipment);

        $this->shipments->restore($shipment, $request->user());

        return redirect()->route('admin.shipments.show', $shipment)->with('status', 'Shipment restored.');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function formData(array $extra = []): array
    {
        return array_merge([
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'company', 'email']),
            'statuses' => ShipmentStatus::active()->ordered()->get(),
            'methods' => ShippingMethod::options(),
        ], $extra);
    }

    /**
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    private function filtered(Request $request, Builder $query): Builder
    {
        $filters = $this->filters($request);

        return $query
            ->with(['status', 'customer'])
            ->search($filters['q'])
            ->when($filters['status'], fn (Builder $query, $status) => $query->where('shipment_status_id', $status))
            ->when($filters['method'], fn (Builder $query, $method) => $query->where('shipping_method', $method))
            ->when($filters['from'], fn (Builder $query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, $to) => $query->whereDate('created_at', '<=', $to))
            ->orderBy('created_at', $filters['sort'] === 'oldest' ? 'asc' : 'desc');
    }

    /**
     * @return array<string, string|null>
     */
    private function filters(Request $request): array
    {
        return [
            'q' => $request->string('q')->trim()->value() ?: null,
            'status' => $request->integer('status') ?: null,
            'method' => $request->string('method')->value() ?: null,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
            'sort' => $request->string('sort')->value() === 'oldest' ? 'oldest' : 'newest',
        ];
    }
}
