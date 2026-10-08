<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\PostFactory;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'slug', 'title', 'excerpt', 'body', 'image', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasTranslations;

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeLatestPublished(Builder $query): void
    {
        $query->published()->latest('published_at')->latest('id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /**
     * The body in the current locale as sanitized HTML.
     */
    public function bodyHtml(): string
    {
        return RichContentRenderer::make((string) $this->translate('body'))->toHtml();
    }

    /**
     * The excerpt in the current locale, or a shortened plain-text body.
     */
    public function summary(): string
    {
        return (string) ($this->translate('excerpt') ?: Str::limit(strip_tags((string) $this->translate('body')), 180));
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
            'excerpt' => 'json:unicode',
            'body' => 'json:unicode',
            'published_at' => 'datetime',
        ];
    }
}
