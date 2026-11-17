<?php

// SEO tags managed in the admin panel (Content → SEO tags).

return [
    'resource' => ['singular' => 'SEO tag', 'plural' => 'SEO tags'],
    'sections' => ['where' => 'On which pages'],
    'fields' => [
        'label' => 'Internal description',
        'type' => 'Kind',
        'name' => 'Name (name or property)',
        'rel' => 'Relation (rel)',
        'value' => 'Content',
        'path' => 'Page (path)',
        'locale' => 'Language',
        'is_active' => 'Active',
    ],
    'types' => [
        'title' => 'Page title',
        'description' => 'Description',
        'meta' => 'Meta tag',
        'link' => 'Link tag',
        'json_ld' => 'Structured data (JSON-LD)',
    ],
    'type_help' => [
        'title' => 'Replaces the whole title in the browser tab and in Google (preferably max. 60 characters).',
        'description' => 'Replaces the description under the title in Google and when shared (preferably max. 160 characters).',
        'meta' => 'A <meta> tag. Names starting with og: become a property (Open Graph). The same name as an automatic tag replaces it.',
        'link' => 'A <link> tag, e.g. canonical (replaces the automatic one), preconnect or me.',
        'json_ld' => 'Extra schema.org data for Google, e.g. an FAQ or a service.',
    ],
    'values' => [
        'title' => 'Title',
        'description' => 'Description',
        'meta' => 'Content',
        'link' => 'Address (href)',
        'json_ld' => 'JSON',
    ],
    'help' => [
        'label' => 'Only for yourself, to find the tag again.',
        'name' => 'E.g. keywords, author, og:image or twitter:site.',
        'rel' => 'E.g. canonical, preconnect or me.',
        'description' => 'Shortened to 160 characters.',
        'json_ld' => 'A JSON object with "@context": "https://schema.org" and "@type". Invalid JSON is refused.',
        'path' => 'Empty = every page. Otherwise the path without the domain, e.g. /en/contact. Use * for "anything": /*/projects/* (every project in every language). A tag for a path wins over one for every page.',
        'table' => 'The website already makes titles, descriptions, Open Graph, Twitter, canonical, language versions, structured data, robots.txt and a sitemap by itself. Here you add tags or replace one (same name), for every page or per page.',
    ],
    'all_pages' => 'All pages',
    'all_languages' => 'All languages',
    'empty' => 'No custom SEO tags yet',
    'empty_description' => 'The automatic tags are usually enough. Add extra or replacing tags here.',
    'errors' => [
        'json' => 'This is not valid JSON.',
        'url' => 'Enter a full address (https://…) or a path (/…).',
    ],
];
