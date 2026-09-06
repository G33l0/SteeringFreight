<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShipmentDocumentRequest;
use App\Models\Shipment;
use App\Models\ShipmentDocument;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShipmentDocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    public function store(ShipmentDocumentRequest $request, Shipment $shipment): RedirectResponse
    {
        $document = $this->documents->store(
            $shipment,
            $request->file('file'),
            $request->safe()->only(['title', 'type', 'description', 'visibility']),
            $request->user(),
        );

        return redirect()
            ->route('admin.shipments.show', $shipment)
            ->with('status', "{$document->title} uploaded.");
    }

    public function download(Request $request, Shipment $shipment, ShipmentDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);
        abort_unless($document->shipment_id === $shipment->getKey(), 404);

        $disk = Storage::disk(DocumentService::DISK);
        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name);
    }

    public function destroy(Request $request, Shipment $shipment, ShipmentDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);
        abort_unless($document->shipment_id === $shipment->getKey(), 404);

        $this->documents->delete($document, $request->user());

        return redirect()
            ->route('admin.shipments.show', $shipment)
            ->with('status', 'Document deleted.');
    }
}
