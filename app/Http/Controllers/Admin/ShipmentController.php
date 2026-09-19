<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShippingMethod;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShipmentRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
use App\Models\User;
use App\Services\ShipmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentService $shipments) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Shipment::class);

        return view('admin.shipments.index', [
            'shipments' => $this->filtered($request, $this->visibleTo($request, Shipment::active()))
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
            'shipments' => $this->filtered($request, $this->visibleTo($request, Shipment::archived()))
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
        // The policy has already refused anybody with nothing left, which is
        // what closes the screen. This second check exists for the race the
        // policy cannot see: two submissions in flight together both read the
        // same remaining allowance and both pass. Re-reading the account inside
        // a write transaction serialises them, so the second one finds the
        // allowance spent and is turned away rather than becoming a sixth
        // tracking number against a quota of five.
        $shipment = DB::transaction(function () use ($request): ?Shipment {
            $account = User::whereKey($request->user()->getKey())->lockForUpdate()->first();

            if (! $account?->canRaiseTracking()) {
                return null;
            }

            return $this->shipments->create($request->validated(), $request->user());
        });

        if (! $shipment) {
            return back()->withInput()->withErrors([
                'tracking_number' => 'You have used all '.$request->user()->tracking_quota.' of your tracking numbers. Ask the administrator to raise your allowance.',
            ]);
        }

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
            // Empty for anybody who may not assign, so the field never renders
            // for a representative in the first place.
            'representatives' => $this->assignableStaff(),
        ], $extra);
    }

    /**
     * Staff a shipment can be handed to: representatives who could actually
     * open it, which rules out disabled, paused and expired accounts.
     *
     * @return Collection<int, User>
     */
    private function assignableStaff(): Collection
    {
        if (! request()->user()?->can('assign', new Shipment)) {
            return collect();
        }

        return User::query()
            ->usable()
            ->where('role', UserRole::Representative->value)
            ->orderBy('name')
            ->get(['id', 'name', 'tracking_quota']);
    }

    /**
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    /**
     * A master admin sees every shipment. Anybody else sees the ones they
     * raised or were handed, and the list is narrowed in the query rather than
     * in the view, so a shipment they may not open never reaches the page.
     *
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    private function visibleTo(Request $request, Builder $query): Builder
    {
        $user = $request->user();

        return $user->role === UserRole::Administrator
            ? $query
            : $query->handledBy($user);
    }

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
