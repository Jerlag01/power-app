<?php

namespace App\Models;

use Database\Factories\SeoTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A tag for search engines and social networks, managed in the admin panel (Content → SEO tags): a page
 * title or description, a meta tag (name or property, e.g. "keywords" or "og:image"), a link tag or a block of
 * structured data (JSON-LD). It applies to every page or to the pages matching its path, in one or all languages,
 * and replaces the automatic tag with the same name. A tag for a specific path wins over one for every page.
 */
#[Fillable(['label', 'type', 'key', 'value', 'path', 'locale', 'is_active', 'sort_order'])]
class SeoTag extends Model
{
    /** @use HasFactory<SeoTagFactory> */
    use HasFactory;

    public const CACHE_KEY = 'seo-tags';

    public const TYPES = ['title', 'description', 'meta', 'link', 'json_ld'];

    /** Meta keys written as <meta property="…"> (Open Graph and friends); the others as <meta name="…">. */
    public const PROPERTY_PREFIXES = ['og:', 'article:', 'fb:', 'profile:', 'book:', 'music:', 'video:', 'product:'];

    protected static function booted(): void
    {
        static::saving(function (SeoTag $tag): void {
            $tag->path = static::normalizePath($tag->path);
            $tag->key = filled($tag->key) ? trim((string) $tag->key) : null;
        });

        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * "nl/projects/" → "/nl/projects"; empty stays empty (every page).
     */
    public static function normalizePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || $path === '*' || $path === '/*') {
            return null;
        }

        $path = (string) parse_url($path, PHP_URL_PATH) ?: $path;

        return '/'.trim($path, '/');
    }

    /**
     * @param  Builder<SeoTag>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The active tags for a page: first those for every page, then those for its path (so they win), each
     * in their order.
     *
     * @return Collection<int, SeoTag>
     */
    public static function forPage(string $path, string $locale): Collection
    {
        $path = '/'.trim($path, '/');

        /** @var Collection<int, SeoTag> $tags */
        $tags = Cache::rememberForever(self::CACHE_KEY, fn (): Collection => static::query()->where('is_active', true)->ordered()->get());

        return $tags
            ->filter(fn (SeoTag $tag): bool => ($tag->locale === null || $tag->locale === $locale) && $tag->matches($path))
            ->sortBy(fn (SeoTag $tag): array => [$tag->path === null ? 0 : (str_contains($tag->path, '*') ? 1 : 2), $tag->sort_order, $tag->id])
            ->values();
    }

    public function matches(string $path): bool
    {
        return $this->path === null || Str::is($this->path, $path) || ($this->path !== '/' && Str::is($this->path, rtrim($path, '/')));
    }

    public function isProperty(): bool
    {
        return Str::startsWith((string) $this->key, self::PROPERTY_PREFIXES);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
