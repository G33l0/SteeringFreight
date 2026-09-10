<?php

namespace App\Models;

use App\Services\MediaService;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'icon',
        'image_path',
        'image_alt',
        'highlights',
        'sort_order',
        'is_published',
        'show_on_home',
        'meta_title',
        'meta_description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @param Builder<Service> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param Builder<Service> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * Illustrations that ship with the application, used until the business
     * uploads its own photography for a service.
     *
     * @var list<string>
     */
    public const BUNDLED_ILLUSTRATIONS = [
        'sea-freight', 'air-freight', 'customs-clearance',
        'warehousing', 'door-to-door-delivery', 'cargo-consolidation',
    ];

    /**
     * Descriptions of the photographs bundled with the application, used as the
     * alt text when a service is still showing the bundled image. A photograph
     * appears here as soon as its file is added under public/assets/photos, and
     * a service with no photograph keeps its drawn artwork.
     *
     * @var array<string, string>
     */
    public const BUNDLED_PHOTO_ALTS = [
        'sea-freight' => 'A container being lifted by a ship to shore gantry crane over the quay, with a vessel alongside',
    ];

    /**
     * The uploaded photograph if there is one, otherwise the bundled artwork.
     */
    public function imageUrl(): string
    {
        return MediaService::url($this->image_path) ?? $this->bundledIllustration();
    }

    public function bundledIllustration(): string
    {
        if ($photo = $this->bundledPhotoPath()) {
            return asset($photo);
        }

        $slug = in_array($this->slug, self::BUNDLED_ILLUSTRATIONS, true) ? $this->slug : 'cargo-handling';

        return asset("assets/illustrations/{$slug}.svg");
    }

    /**
     * The bundled photograph for this service, when one has been added.
     *
     * Photographs arrive one at a time, so this looks for the file rather than
     * keeping a list that has to be edited alongside it. The answer is
     * remembered for the request: six services would otherwise stat six files
     * on every page that lists them.
     */
    public function bundledPhotoPath(): ?string
    {
        static $found = [];

        if (! array_key_exists($this->slug, $found)) {
            $path = "assets/photos/service-{$this->slug}.webp";
            $found[$this->slug] = is_file(public_path($path)) ? $path : null;
        }

        return $found[$this->slug];
    }

    public function hasUploadedImage(): bool
    {
        return filled($this->image_path);
    }

    public function imageAlt(): string
    {
        if ($this->image_alt) {
            return $this->image_alt;
        }

        // A bundled photograph gets a description of what is in it; anything else
        // is named by its service, which is all the drawn artwork depicts.
        if (! $this->hasUploadedImage() && $this->bundledPhotoPath()) {
            return self::BUNDLED_PHOTO_ALTS[$this->slug] ?? $this->title;
        }

        return $this->title;
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function metaDescription(): string
    {
        return $this->meta_description ?: $this->summary;
    }
}
