{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($pages as $page)
@foreach ($page['urls'] as $locale => $loc)
    <url>
        <loc>{{ $loc }}</loc>
@foreach ($page['urls'] as $alternateLocale => $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternate }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $page['urls'][$default] ?? reset($page['urls']) }}"/>
@if ($page['lastmod'])
        <lastmod>{{ $page['lastmod']->toAtomString() }}</lastmod>
@endif
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
@endforeach
</urlset>
