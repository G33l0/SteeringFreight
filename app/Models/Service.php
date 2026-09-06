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
     * The uploaded photograph if there is one, otherwise the bundled artwork.
     */
    public function imageUrl(): string
    {
        return MediaService::url($this->image_path) ?? $this->bundledIllustration();
    }

    public function bundledIllustration(): string
    {
        $slug = in_array($this->slug, self::BUNDLED_ILLUSTRATIONS, true) ? $this->slug : 'cargo-handling';

        return asset("assets/illustrations/{$slug}.svg");
    }

    public function hasUploadedImage(): bool
    {
        return filled($this->image_path);
    }

    public function imageAlt(): string
    {
        return $this->image_alt ?: $this->title;
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
