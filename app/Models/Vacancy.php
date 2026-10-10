<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Models\Concerns\HasTranslations;
use Database\Factories\VacancyFactory;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A job opening shown on the website with an application form.
 */
#[Fillable(['slug', 'title', 'intro', 'description', 'requirements', 'offer', 'location', 'employment_type', 'image', 'images',
    'contact_name', 'contact_email', 'contact_phone', 'notify_emails', 'closes_at', 'is_visible', 'sort_order'])]
class Vacancy extends Model
{
    /** @use HasFactory<VacancyFactory> */
    use HasFactory, HasTranslations;

    public const DISK = 'public';

    protected static function booted(): void
    {
        // Uploads are not removed by the admin form itself, so clean up replaced and removed files here.
        static::updated(function (Vacancy $vacancy): void {
            Storage::disk(self::DISK)->delete(array_values(array_diff(
                $vacancy->storedFiles($vacancy->getOriginal()),
                $vacancy->storedFiles($vacancy->getAttributes()),
            )));
        });

        static::deleted(function (Vacancy $vacancy): void {
            Storage::disk(self::DISK)->delete($vacancy->storedFiles($vacancy->getAttributes()));
        });
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Vacancies shown on the website: visible and not closed.
     *
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_visible', true)
            ->where(fn (Builder $query) => $query->whereNull('closes_at')->orWhereDate('closes_at', '>=', today()));
    }

    /**
     * Whether the website has any vacancy to show; without one the menu and footer links are hidden.
     * Not memoized with once(): that would outlive the request inside tests.
     */
    public static function anyPublished(): bool
    {
        return static::published()->exists();
    }

    public function isPublished(): bool
    {
        return $this->is_visible && ! $this->isClosed();
    }

    public function isClosed(): bool
    {
        return $this->closes_at !== null && $this->closes_at->isBefore(today());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * A translated rich-text field in the current locale as sanitized HTML.
     */
    public function html(string $attribute): string
    {
        $value = (string) $this->translate($attribute);

        return filled(strip_tags($value)) ? RichContentRenderer::make($value)->toHtml() : '';
    }

    public function imageUrl(): ?string
    {
        return filled($this->image) ? Storage::disk(self::DISK)->url($this->image) : null;
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
     * Who receives the applications: the vacancy's own list, else the site-wide
     * vacancy list from the settings, else the contact form address.
     *
     * @return list<string>
     */
    public function recipients(): array
    {
        foreach ([$this->notify_emails, settings('contact.vacancy_emails'), [settings('contact.notify_email')]] as $list) {
            $emails = collect((array) $list)
                ->map(fn (mixed $email): string => trim((string) $email))
                ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
                ->unique()
                ->values()
                ->all();

            if ($emails !== []) {
                return $emails;
            }
        }

        return [];
    }

    /**
     * Every file path held by a set of raw attributes (cover image and gallery).
     *
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private function storedFiles(array $attributes): array
    {
        $images = $attributes['images'] ?? [];
        $images = is_string($images) ? (json_decode($images, true) ?: []) : (array) $images;

        return collect([$attributes['image'] ?? null, ...$images])
            ->filter(fn (mixed $path): bool => is_string($path) && filled($path))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'title' => 'json:unicode',
            'intro' => 'json:unicode',
            'description' => 'json:unicode',
            'requirements' => 'json:unicode',
            'offer' => 'json:unicode',
            'employment_type' => EmploymentType::class,
            'images' => 'array',
            'notify_emails' => 'array',
            'closes_at' => 'date',
            'is_visible' => 'boolean',
        ];
    }
}
