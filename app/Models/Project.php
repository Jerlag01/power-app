<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['expertise_id', 'slug', 'title', 'location', 'summary', 'description', 'highlights', 'image', 'is_published', 'is_featured', 'sort_order'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasTranslations;

    /**
     * @return BelongsTo<Expertise, $this>
     */
    public function expertise(): BelongsTo
    {
        return $this->belongsTo(Expertise::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Highlights with their label resolved in the current locale.
     *
     * @return list<array{value: string, label: string}>
     */
    public function translatedHighlights(): array
    {
        return collect($this->highlights ?? [])
            ->map(fn (array $highlight): array => [
                'value' => (string) ($highlight['value'] ?? ''),
                'label' => (string) translated_value($highlight['label'] ?? null),
            ])
            ->filter(fn (array $highlight): bool => filled($highlight['value']))
            ->values()
            ->all();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'json:unicode',
            'summary' => 'json:unicode',
            'description' => 'json:unicode',
            'highlights' => 'json:unicode',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }
}
