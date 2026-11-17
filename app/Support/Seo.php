<?php

namespace App\Support;

use App\Enums\EmploymentType;
use App\Models\Post;
use App\Models\SeoTag;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Everything search engines and social networks read in the <head> of a page: the title, description, robots
 * rule, canonical and language links, Open Graph and Twitter cards, verification codes and structured data
 * (schema.org JSON-LD for the company, the website and the page). The SEO tags of the admin panel are applied
 * on top: they add tags or replace the automatic tag with the same name.
 */
class Seo
{
    public const OG_LOCALES = ['nl' => 'nl_BE', 'fr' => 'fr_BE', 'en' => 'en_GB'];

    /**
     * Only the production website may be indexed (APP_ENV=production and not switched off in the settings),
     * so a development copy never ends up in Google.
     */
    public static function indexable(): bool
    {
        return app()->isProduction() && (bool) settings('seo.indexable', true);
    }

    public static function siteName(): string
    {
        return (string) settings('general.site_name');
    }

    public static function description(?string $description = null): string
    {
        $text = $description ?: settings()->translated('seo.description') ?: self::siteName().' - '.settings()->translated('general.tagline');

        return Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $text))), 160, '…');
    }

    public static function shareImage(?string $image = null): ?string
    {
        if (filled($image)) {
            return str_starts_with($image, 'http') ? $image : Storage::disk('public')->url($image);
        }

        $setting = settings('seo.share_image') ?: settings('appearance.hero_image');

        return filled($setting) ? Storage::disk('public')->url($setting) : settings()->logoUrl();
    }

    /**
     * The tags of the current page, as HTML for the <head>.
     *
     * @param  array<int, array<string, mixed>>  $schema  extra JSON-LD blocks for this page (an article, a job posting)
     */
    public static function head(?string $title = null, ?string $description = null, ?string $image = null, string $type = 'website', bool $noindex = false, array $schema = []): HtmlString
    {
        $request = request();
        $locale = app()->getLocale();
        $siteName = self::siteName();
        $pageTitle = $title ? $title.' | '.$siteName : $siteName.' - '.settings()->translated('general.tagline');
        $text = self::description($description);
        $imageUrl = self::shareImage($image);
        $canonical = $request->url();
        $indexable = self::indexable() && ! $noindex;

        /** @var array<string, array{0: string, 1: array<string, string>}|string> $tags  key => [element, attributes] or raw JSON */
        $tags = [
            'meta:description' => ['meta', ['name' => 'description', 'content' => $text]],
            'meta:robots' => ['meta', ['name' => 'robots', 'content' => $indexable ? 'index, follow, max-image-preview:large' : 'noindex, nofollow']],
            'link:canonical' => ['link', ['rel' => 'canonical', 'href' => $canonical]],
        ];

        foreach (self::alternates() as $hreflang => $url) {
            $tags["link:alternate:{$hreflang}"] = ['link', ['rel' => 'alternate', 'hreflang' => $hreflang, 'href' => $url]];
        }

        $tags += [
            'property:og:type' => ['meta', ['property' => 'og:type', 'content' => $type]],
            'property:og:site_name' => ['meta', ['property' => 'og:site_name', 'content' => $siteName]],
            'property:og:title' => ['meta', ['property' => 'og:title', 'content' => $title ?: $pageTitle]],
            'property:og:description' => ['meta', ['property' => 'og:description', 'content' => $text]],
            'property:og:url' => ['meta', ['property' => 'og:url', 'content' => $canonical]],
            'property:og:locale' => ['meta', ['property' => 'og:locale', 'content' => self::OG_LOCALES[$locale] ?? $locale]],
            'property:og:image' => $imageUrl ? ['meta', ['property' => 'og:image', 'content' => $imageUrl]] : null,
            'meta:twitter:card' => ['meta', ['name' => 'twitter:card', 'content' => $imageUrl ? 'summary_large_image' : 'summary']],
            'meta:twitter:title' => ['meta', ['name' => 'twitter:title', 'content' => $title ?: $pageTitle]],
            'meta:twitter:description' => ['meta', ['name' => 'twitter:description', 'content' => $text]],
            'meta:twitter:image' => $imageUrl ? ['meta', ['name' => 'twitter:image', 'content' => $imageUrl]] : null,
            'meta:google-site-verification' => filled(settings('seo.google_verification')) ? ['meta', ['name' => 'google-site-verification', 'content' => (string) settings('seo.google_verification')]] : null,
            'meta:msvalidate.01' => filled(settings('seo.bing_verification')) ? ['meta', ['name' => 'msvalidate.01', 'content' => (string) settings('seo.bing_verification')]] : null,
            'json_ld:site' => self::json(self::siteGraph()),
        ];

        foreach (array_values($schema) as $index => $block) {
            $tags["json_ld:page:{$index}"] = self::json(['@context' => 'https://schema.org', ...$block]);
        }

        foreach (SeoTag::forPage($request->path(), $locale) as $tag) {
            if ($tag->type === 'title') {
                $pageTitle = (string) $tag->value;
            } elseif ($tag->type === 'description') {
                $tags['meta:description'][1]['content'] = $tags['property:og:description'][1]['content'] = $tags['meta:twitter:description'][1]['content'] = self::description($tag->value);
            } elseif ($tag->type === 'meta') {
                $attribute = $tag->isProperty() ? 'property' : 'name';
                $tags["{$attribute}:{$tag->key}"] = ['meta', [$attribute => (string) $tag->key, 'content' => (string) $tag->value]];
            } elseif ($tag->type === 'link') {
                $tags['link:'.$tag->key.($tag->key === 'canonical' ? '' : ':'.$tag->value)] = ['link', ['rel' => (string) $tag->key, 'href' => (string) $tag->value]];
            } elseif ($tag->type === 'json_ld') {
                $tags['json_ld:tag:'.$tag->id] = self::validJson((string) $tag->value);
            }
        }

        $html = ['<title>'.e($pageTitle).'</title>'];

        foreach (array_filter($tags) as $tag) {
            $html[] = is_string($tag)
                ? '<script type="application/ld+json">'.$tag.'</script>'
                : '<'.$tag[0].' '.collect($tag[1])->map(fn (string $value, string $attribute): string => $attribute.'="'.e($value).'"')->implode(' ').'>';
        }

        return new HtmlString(implode("\n        ", $html));
    }

    /**
     * The same page in every language, plus x-default (the default language).
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $route = request()->route();

        // SetLocale takes the locale out of the parameters, so check the route definition.
        if (! $route?->getName() || ! in_array('locale', $route->parameterNames(), true)) {
            return [];
        }

        $urls = [];

        foreach (array_keys(config('app.locales')) as $locale) {
            $urls[$locale] = route($route->getName(), [...$route->parameters(), 'locale' => $locale]);
        }

        // The default language is the first one (SetLocale changes app.locale to the page's language).
        return $urls + ['x-default' => reset($urls)];
    }

    /**
     * The company and the website, on every page.
     *
     * @return array<string, mixed>
     */
    public static function siteGraph(): array
    {
        $home = route('home', ['locale' => app()->getLocale()]);

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                self::organization(),
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'name' => self::siteName(),
                    'url' => $home,
                    'inLanguage' => str_replace('_', '-', self::OG_LOCALES[app()->getLocale()] ?? app()->getLocale()),
                    'publisher' => ['@id' => url('/').'#organization'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        return array_filter([
            '@type' => ['Organization', 'Electrician'],
            '@id' => url('/').'#organization',
            'name' => self::siteName(),
            'description' => self::description(),
            'url' => url('/'),
            'logo' => settings()->logoUrl() ?? asset('favicon.svg'),
            'image' => self::shareImage(),
            'telephone' => settings('contact.phone'),
            'email' => settings('contact.email'),
            'vatID' => settings('contact.vat'),
            'foundingDate' => filled(settings('general.founded')) ? (string) settings('general.founded') : null,
            'areaServed' => settings()->translated('contact.service_area'),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => settings('contact.street'),
                'postalCode' => settings('contact.postal_code'),
                'addressLocality' => settings()->translated('contact.city'),
                'addressCountry' => 'BE',
            ]),
            'sameAs' => array_values(array_filter((array) settings('contact.social'))) ?: null,
        ]);
    }

    /**
     * A news item as a schema.org Article.
     *
     * @return array<string, mixed>
     */
    public static function article(Post $post): array
    {
        return array_filter([
            '@type' => 'NewsArticle',
            'headline' => Str::limit((string) $post->translate('title'), 110, ''),
            'description' => self::description($post->summary()),
            'image' => $post->image ? [self::shareImage($post->image)] : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'inLanguage' => app()->getLocale(),
            'mainEntityOfPage' => request()->url(),
            'author' => ['@id' => url('/').'#organization'],
            'publisher' => ['@id' => url('/').'#organization'],
        ]);
    }

    /**
     * A vacancy as a schema.org JobPosting, so it can appear in Google's job listings.
     *
     * @return array<string, mixed>
     */
    public static function jobPosting(Vacancy $vacancy): array
    {
        $types = [
            EmploymentType::FullTime->value => 'FULL_TIME', EmploymentType::PartTime->value => 'PART_TIME', EmploymentType::Temporary->value => 'TEMPORARY',
            EmploymentType::Student->value => 'PER_DIEM', EmploymentType::Internship->value => 'INTERN', EmploymentType::Freelance->value => 'CONTRACTOR',
        ];

        return array_filter([
            '@type' => 'JobPosting',
            'title' => (string) $vacancy->translate('title'),
            'description' => implode('', array_filter([
                filled($vacancy->translate('intro')) ? '<p>'.e((string) $vacancy->translate('intro')).'</p>' : null,
                $vacancy->html('description'), $vacancy->html('requirements'), $vacancy->html('offer'),
            ])),
            'datePosted' => $vacancy->created_at?->toDateString(),
            'validThrough' => $vacancy->closes_at?->endOfDay()->toIso8601String(),
            'employmentType' => $vacancy->employment_type ? ($types[$vacancy->employment_type->value] ?? null) : null,
            'hiringOrganization' => array_filter(['@type' => 'Organization', 'name' => self::siteName(), 'sameAs' => url('/'), 'logo' => settings()->logoUrl()]),
            'jobLocation' => [
                '@type' => 'Place',
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'addressLocality' => $vacancy->translate('location') ?: settings()->translated('contact.city'),
                    'addressCountry' => 'BE',
                ]),
            ],
            'image' => $vacancy->imageUrl(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function json(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /**
     * Structured data typed in the admin panel, re-encoded so it can never close the <script> tag; null when
     * it is not valid JSON (the form refuses that too).
     */
    public static function validJson(string $value): ?string
    {
        $data = json_decode($value, true);

        return is_array($data) ? self::json($data) : null;
    }
}
