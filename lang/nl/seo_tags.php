<?php

// SEO tags managed in the admin panel (Content → SEO tags).

return [
    'resource' => ['singular' => 'SEO-tag', 'plural' => 'SEO-tags'],
    'sections' => ['where' => 'Op welke pagina\'s'],
    'fields' => [
        'label' => 'Omschrijving',
        'type' => 'Soort',
        'name' => 'Naam (name of property)',
        'rel' => 'Relatie (rel)',
        'value' => 'Inhoud',
        'path' => 'Pagina (pad)',
        'locale' => 'Taal',
        'is_active' => 'Actief',
    ],
    'types' => [
        'title' => 'Paginatitel',
        'description' => 'Beschrijving',
        'meta' => 'Meta-tag',
        'link' => 'Link-tag',
        'json_ld' => 'Gestructureerde gegevens (JSON-LD)',
    ],
    'type_help' => [
        'title' => 'Vervangt de volledige titel in het browsertabblad en in Google (best max. 60 tekens).',
        'description' => 'Vervangt de beschrijving onder de titel in Google en bij het delen (best max. 160 tekens).',
        'meta' => 'Een <meta>-tag. Namen die met og: beginnen worden een property (Open Graph). Dezelfde naam als een automatische tag vervangt die.',
        'link' => 'Een <link>-tag, bv. canonical (vervangt de automatische), preconnect of me.',
        'json_ld' => 'Extra schema.org-gegevens voor Google, bv. een FAQ of een dienst.',
    ],
    'values' => [
        'title' => 'Titel',
        'description' => 'Beschrijving',
        'meta' => 'Inhoud (content)',
        'link' => 'Adres (href)',
        'json_ld' => 'JSON',
    ],
    'help' => [
        'label' => 'Enkel voor jezelf, om de tag terug te vinden.',
        'name' => 'Bv. keywords, author, og:image of twitter:site.',
        'rel' => 'Bv. canonical, preconnect of me.',
        'description' => 'Wordt ingekort tot 160 tekens.',
        'json_ld' => 'Een JSON-object met "@context": "https://schema.org" en "@type". Ongeldige JSON wordt geweigerd.',
        'path' => 'Leeg = alle pagina\'s. Anders het pad zonder domein, bv. /nl/contact. Met * voor "eender wat": /*/projects/* (alle realisaties in elke taal). Een tag voor een pad gaat voor op een tag voor alle pagina\'s.',
        'table' => 'De website maakt zelf al titels, beschrijvingen, Open Graph, Twitter, canonical, taalversies, gestructureerde gegevens, robots.txt en een sitemap. Hier voeg je tags toe of vervang je er één (zelfde naam), voor alle pagina\'s of per pagina.',
    ],
    'all_pages' => 'Alle pagina\'s',
    'all_languages' => 'Alle talen',
    'empty' => 'Nog geen eigen SEO-tags',
    'empty_description' => 'De automatische tags volstaan meestal. Voeg hier extra of vervangende tags toe.',
    'errors' => [
        'json' => 'Dit is geen geldige JSON.',
        'url' => 'Geef een volledig adres (https://…) of een pad (/…).',
    ],
];
