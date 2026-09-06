<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
use App\Services\ChatService;
use App\Services\TrackingNumberGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /** Session key listing the shipments looked up by this visitor. */
    public const SESSION_KEY = 'tracking.shipments';

    public function __construct(private readonly TrackingNumberGenerator $trackingNumbers) {}

    public function index(): View
    {
        return view('public.track.index', [
            'example' => $this->trackingNumbers->example(),
            'metaTitle' => 'Track a shipment — '.company_name(),
            'metaDescription' => 'Enter your tracking number to see the current status, location and delivery estimate for your shipment.',
        ]);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_number' => ['required', 'string', 'max:60'],
        ], [], ['tracking_number' => 'tracking number']);

        return redirect()->route('track.show', $this->trackingNumbers->normalise($validated['tracking_number']));
    }

    public function show(Request $request, string $tracking_number, ChatService $chat): View|RedirectResponse
    {
        $shipment = Shipment::query()
            ->with(['status'])
            ->where('tracking_number', $this->trackingNumbers->normalise($tracking_number))
            ->first();

        if (! $shipment) {
            return redirect()
                ->route('track.index')
                ->withInput(['tracking_number' => $tracking_number])
                ->withErrors(['tracking_number' => "We couldn't find a shipment with that tracking number. Check the number and try again."]);
        }

        $this->rememberShipment($request, $shipment);

        $shipment->load([
            'publicEvents.status',
            'customerDocuments',
        ]);

        $conversation = $this->currentConversation($request, $shipment, $chat);

        return view('public.track.show', [
            'shipment' => $shipment,
            'timeline' => ShipmentStatus::timeline(),
            'events' => $shipment->publicEvents,
            'documents' => $shipment->customerDocuments,
            'conversation' => $conversation,
            'messages' => $conversation?->messages()->get() ?? collect(),
            'chatEnabled' => (bool) setting('tracking.chat_enabled', true) && ! $shipment->isArchived(),
            'pollInterval' => chat_poll_interval(),
            'metaTitle' => 'Shipment '.$shipment->tracking_number.' — '.company_name(),
            'metaDescription' => 'Tracking information for shipment '.$shipment->tracking_number.'.',
            'robots' => 'noindex, nofollow',
        ]);
    }

    /**
     * Record in the session that this visitor supplied a valid tracking number,
     * which is what allows customer visible documents to be downloaded.
     */
    private function rememberShipment(Request $request, Shipment $shipment): void
    {
        $ids = (array) $request->session()->get(self::SESSION_KEY, []);
        $ids[] = $shipment->getKey();

        $request->session()->put(self::SESSION_KEY, array_values(array_unique($ids)));
    }

    private function currentConversation(Request $request, Shipment $shipment, ChatService $chat): ?ChatConversation
    {
        $tokens = (array) $request->session()->get(ChatService::SESSION_KEY, []);

        if ($tokens === []) {
            return null;
        }

        $conversation = ChatConversation::query()
            ->where('shipment_id', $shipment->getKey())
            ->whereIn('id', array_keys($tokens))
            ->latest('id')
            ->first();

        if ($conversation && $chat->sessionOwnsConversation($request, $conversation)) {
            $chat->markReadByCustomer($conversation);

            return $conversation;
        }

        return null;
    }
}
