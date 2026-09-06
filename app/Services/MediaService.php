<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Images that are meant to be public: service photography, review portraits
 * and the logo. They are written to the public disk, which is exposed through
 * the storage symlink.
 */
class MediaService
{
    public const DISK = 'public';

    public function store(UploadedFile $file, string $directory): string
    {
        return $file->storeAs(
            $directory,
            Str::ulid()->toBase32().'.'.(preg_replace('/[^a-z0-9]/', '', strtolower($file->getClientOriginalExtension())) ?: 'jpg'),
            ['disk' => self::DISK],
        );
    }

    public function replace(?string $existing, UploadedFile $file, string $directory): string
    {
        $this->delete($existing);

        return $this->store($file, $directory);
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public static function url(?string $path): ?string
    {
        return $path ? Storage::disk(self::DISK)->url($path) : null;
    }
}
