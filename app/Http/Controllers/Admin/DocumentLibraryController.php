<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentVisibility;
use App\Http\Controllers\Controller;
use App\Models\ShipmentDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DocumentLibraryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('documents.view');

        $visibility = $request->string('visibility')->value();

        return view('admin.documents.index', [
            'documents' => ShipmentDocument::with(['shipment', 'uploader'])
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $term = $request->string('q')->trim()->value();
                    $query->where(function ($query) use ($term): void {
                        $query->where('title', 'like', "%{$term}%")
                            ->orWhere('original_name', 'like', "%{$term}%")
                            ->orWhereHas('shipment', fn ($query) => $query->where('tracking_number', 'like', "%{$term}%"));
                    });
                })
                ->when(
                    in_array($visibility, ['customer', 'internal'], true),
                    fn ($query) => $query->where('visibility', $visibility),
                )
                ->latest('id')
                ->paginate((int) config('portlane.per_page.admin'))
                ->withQueryString(),
            'filters' => ['q' => $request->string('q')->value(), 'visibility' => $visibility],
            'visibilities' => DocumentVisibility::options(),
        ]);
    }
}
