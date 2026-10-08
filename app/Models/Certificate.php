<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A company certification (VCA**, ISO 9001, ...) with its logo, pictures and optional PDF.
 */
#[Fillable(['name', 'issuer', 'number', 'valid_until', 'description', 'logo', 'images', 'document', 'is_visible', 'sort_order'])]
class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory, HasTranslations;

    public const DISK = 'public';

    protected static function booted(): void
    {
        // Uploads are not removed by the admin form itself, so clean up replaced and removed files here.
        static::updated(function (Certificate $certificate): void {
            Storage::disk(self::DISK)->delete(array_values(array_diff(
                $certificate->storedFiles($certificate->getOriginal()),
                $certificate->storedFiles($certificate->getAttributes()),
            )));
        });

        static::deleted(function (Certificate $certificate): void {
            Storage::disk(self::DISK)->delete($certificate->storedFiles($certificate->getAttributes()));
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Certificates shown on the website: visible and not expired.
     *
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_visible', true)
            ->where(fn (Builder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()));
    }

    /**
     * Whether the website has any certificate to show; checked once per request for the header and footer links.
     */
    public static function anyPublished(): bool
    {
        return once(fn (): bool => static::published()->exists());
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isBefore(today());
    }

    public function logoUrl(): ?string
    {
        return filled($this->logo) ? Storage::disk(self::DISK)->url($this->logo) : null;
    }

    public function documentUrl(): ?string
    {
        return filled($this->document) ? Storage::disk(self::DISK)->url($this->document) : null;
    }

    /**
     * @return list<string>
     */
    public function imageUrls(): array
    {
        return collect($this->images ?? [])
            ->filter(fn (mixed $path): bool => is_string($path) && filled($path))
            ->map(fn (string $path): string => Storage::disk(self::DISK)->url($path))
            ->values()
            ->all();
    }

    /**
     * Every file path held by a set of raw attributes (logo, document and gallery images).
     *
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private function storedFiles(array $attributes): array
    {
        $images = $attributes['images'] ?? [];
        $images = is_string($images) ? (json_decode($images, true) ?: []) : (array) $images;

        return collect([$attributes['logo'] ?? null, $attributes['document'] ?? null, ...$images])
            ->filter(fn (mixed $path): bool => is_string($path) && filled($path))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'description' => 'json:unicode',
            'images' => 'array',
            'is_visible' => 'boolean',
        ];
    }
}
