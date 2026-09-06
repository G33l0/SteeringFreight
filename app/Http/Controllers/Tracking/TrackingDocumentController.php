<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentDocument;
use App\Services\DocumentService;
use App\Services\TrackingNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves documents the administrator marked as visible to the customer.
 *
 * Files live outside the web root and are streamed only after the visitor has
 * looked the shipment up in this session, so nothing is reachable by guessing
 * a document id.
 */
class TrackingDocumentController extends Controller
{
    public function __construct(private readonly TrackingNumberGenerator $trackingNumbers) {}

    public function download(Request $request, string $tracking_number, ShipmentDocument $document): StreamedResponse
    {
        $shipment = Shipment::where('tracking_number', $this->trackingNumbers->normalise($tracking_number))->firstOrFail();

        abort_unless($document->shipment_id === $shipment->getKey(), 404);
        abort_unless($document->isVisibleToCustomer(), 404);
        abort_unless($this->sessionTracked($request, $shipment), 403);

        $disk = Storage::disk(DocumentService::DISK);

        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name);
    }

    private function sessionTracked(Request $request, Shipment $shipment): bool
    {
        $ids = (array) $request->session()->get(TrackingController::SESSION_KEY, []);

        return in_array($shipment->getKey(), array_map('intval', $ids), true);
    }
}
