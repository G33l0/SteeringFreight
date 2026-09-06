<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores shipment documents on the private disk.
 *
 * Files are written with a generated name so nothing can be guessed from a URL,
 * and the original filename is kept only as a label for the download response.
 */
class DocumentService
{
    public const DISK = 'local';

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(Shipment $shipment, UploadedFile $file, array $attributes, User $user): ShipmentDocument
    {
        $path = $file->storeAs(
            $this->directory($shipment),
            Str::ulid()->toBase32().'.'.$this->extension($file),
            ['disk' => self::DISK],
        );

        $document = $shipment->documents()->create([
            'title' => $attributes['title'],
            'type' => $attributes['type'],
            'description' => $attributes['description'] ?? null,
            'original_name' => $this->safeName($file->getClientOriginalName()),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'visibility' => $attributes['visibility'],
            'uploaded_by' => $user->getKey(),
        ]);

        $this->audit->record(
            'document.uploaded',
            $shipment,
            "Uploaded {$document->title} to {$shipment->tracking_number}",
            ['document_id' => $document->getKey(), 'visibility' => $document->visibility->value],
            $user,
        );

        return $document;
    }

    public function delete(ShipmentDocument $document, User $user): void
    {
        $shipment = $document->shipment;
        $title = $document->title;
        $id = $document->getKey();

        Storage::disk(self::DISK)->delete($document->path);
        $document->delete();

        $this->audit->record(
            'document.deleted',
            $shipment,
            "Deleted {$title} from {$shipment->tracking_number}",
            ['document_id' => $id],
            $user,
        );
    }

    public function exists(ShipmentDocument $document): bool
    {
        return Storage::disk(self::DISK)->exists($document->path);
    }

    private function directory(Shipment $shipment): string
    {
        return 'shipments/'.$shipment->getKey().'/documents';
    }

    private function extension(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        return preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';
    }

    private function safeName(string $name): string
    {
        $name = str_replace(['/', '\\', "\0"], '', $name);

        return Str::limit(trim($name), 180, '');
    }
}
