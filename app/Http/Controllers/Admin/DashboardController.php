<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactStatus;
use App\Enums\QuoteStatus;
use App\Enums\StatusCategory;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ChatConversation;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\ShipmentStatus;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statuses = ShipmentStatus::query()->get(['id', 'name', 'slug', 'category', 'is_final']);

        $exceptionIds = $statuses->where('category', StatusCategory::Exception)->pluck('id');
        $deliveredIds = $statuses->where('is_final', true)->pluck('id');
        $inTransitIds = $statuses->whereIn('slug', ['in-transit', 'shipped', 'arrived-at-port'])->pluck('id');
        $customsIds = $statuses->filter(fn (ShipmentStatus $status) => str_contains($status->slug, 'customs')
            || str_contains($status->slug, 'clearance'))->pluck('id');
        $delayedIds = $statuses->whereIn('slug', ['delayed', 'weather-delay', 'port-congestion'])->pluck('id');
        $outForDeliveryIds = $statuses->where('slug', 'out-for-delivery')->pluck('id');

        $counts = [
            'total' => Shipment::count(),
            'active' => Shipment::active()->whereNotIn('shipment_status_id', $deliveredIds)->count(),
            'in_transit' => Shipment::active()->whereIn('shipment_status_id', $inTransitIds)->count(),
            'delayed' => Shipment::active()->whereIn('shipment_status_id', $delayedIds)->count(),
            'customs' => Shipment::active()->whereIn('shipment_status_id', $customsIds)->count(),
            'out_for_delivery' => Shipment::active()->whereIn('shipment_status_id', $outForDeliveryIds)->count(),
            'delivered' => Shipment::whereIn('shipment_status_id', $deliveredIds)->count(),
            'exceptions' => Shipment::active()->whereIn('shipment_status_id', $exceptionIds)->count(),
            'unread_messages' => ChatConversation::sum('unread_for_staff'),
            'new_quotes' => QuoteRequest::where('status', QuoteStatus::New->value)->count(),
            'new_contact' => ContactMessage::where('status', ContactStatus::New->value)->count(),
        ];

        return view('admin.dashboard', [
            'counts' => $counts,
            'recentShipments' => Shipment::with(['status', 'customer'])->latest('id')->limit(8)->get(),
            'recentEvents' => ShipmentEvent::with(['shipment', 'status', 'creator'])->latest('id')->limit(8)->get(),
            'recentConversations' => ChatConversation::with('shipment')->latest('last_message_at')->limit(6)->get(),
            'recentQuotes' => QuoteRequest::latest('id')->limit(6)->get(),
            'recentActivity' => AuditLog::with('user')->latest('id')->limit(8)->get(),
        ]);
    }
}
