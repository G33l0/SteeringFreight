<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShipmentEventRequest;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\ShipmentStatus;
use App\Services\ShipmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShipmentEventController extends Controller
{
    public function __construct(private readonly ShipmentService $shipments) {}

    public function store(ShipmentEventRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->shipments->addEvent($shipment, $request->validated(), $request->user());

        return redirect()
            ->route('admin.shipments.show', $shipment)
            ->with('status', 'Tracking update added.');
    }

    public function edit(Shipment $shipment, ShipmentEvent $event): View
    {
        $this->authorize('manageEvents', $shipment);
        abort_unless($event->shipment_id === $shipment->getKey(), 404);

        return view('admin.shipments.events.edit', [
            'shipment' => $shipment,
            'event' => $event,
            'statuses' => ShipmentStatus::active()->ordered()->get(),
        ]);
    }

    public function update(ShipmentEventRequest $request, Shipment $shipment, ShipmentEvent $event): RedirectResponse
    {
        abort_unless($event->shipment_id === $shipment->getKey(), 404);

        $this->shipments->updateEvent($event, $request->safe()->only([
            'shipment_status_id', 'location', 'occurred_at', 'description', 'internal_note', 'is_public',
        ]), $request->user());

        return redirect()
            ->route('admin.shipments.show', $shipment)
            ->with('status', 'Tracking update saved.');
    }

    public function destroy(Request $request, Shipment $shipment, ShipmentEvent $event): RedirectResponse
    {
        $this->authorize('manageEvents', $shipment);
        abort_unless($event->shipment_id === $shipment->getKey(), 404);

        $this->shipments->deleteEvent($event, $request->user());

        return redirect()
            ->route('admin.shipments.show', $shipment)
            ->with('status', 'Tracking update removed.');
    }
}
