<?php

// SEO tags managed in the admin panel (Content → SEO tags).

return [
    'resource' => ['singular' => 'balise SEO', 'plural' => 'Balises SEO'],
    'sections' => ['where' => 'Sur quelles pages'],
    'fields' => [
        'label' => 'Description interne',
        'type' => 'Type',
        'name' => 'Nom (name ou property)',
        'rel' => 'Relation (rel)',
        'value' => 'Contenu',
        'path' => 'Page (chemin)',
        'locale' => 'Langue',
        'is_active' => 'Active',
    ],
    'types' => [
        'title' => 'Titre de page',
        'description' => 'Description',
        'meta' => 'Balise meta',
        'link' => 'Balise link',
        'json_ld' => 'Données structurées (JSON-LD)',
    ],
    'type_help' => [
        'title' => 'Remplace le titre complet dans l\'onglet du navigateur et dans Google (de préférence 60 caractères max.).',
        'description' => 'Remplace la description sous le titre dans Google et lors du partage (de préférence 160 caractères max.).',
        'meta' => 'Une balise <meta>. Les noms commençant par og: deviennent une property (Open Graph). Le même nom qu\'une balise automatique la remplace.',
        'link' => 'Une balise <link>, p. ex. canonical (remplace l\'automatique), preconnect ou me.',
        'json_ld' => 'Des données schema.org supplémentaires pour Google, p. ex. une FAQ ou un service.',
    ],
    'values' => [
        'title' => 'Titre',
        'description' => 'Description',
        'meta' => 'Contenu (content)',
        'link' => 'Adresse (href)',
        'json_ld' => 'JSON',
    ],
    'help' => [
        'label' => 'Seulement pour vous, pour retrouver la balise.',
        'name' => 'P. ex. keywords, author, og:image ou twitter:site.',
        'rel' => 'P. ex. canonical, preconnect ou me.',
        'description' => 'Raccourcie à 160 caractères.',
        'json_ld' => 'Un objet JSON avec "@context": "https://schema.org" et "@type". Un JSON invalide est refusé.',
        'path' => 'Vide = toutes les pages. Sinon le chemin sans domaine, p. ex. /fr/contact. Avec * pour « n\'importe quoi » : /*/projects/* (toutes les réalisations dans chaque langue). Une balise pour un chemin l\'emporte sur une balise pour toutes les pages.',
        'table' => 'Le site crée déjà lui-même les titres, descriptions, Open Graph, Twitter, canonical, versions linguistiques, données structurées, robots.txt et un sitemap. Ici vous ajoutez des balises ou en remplacez une (même nom), pour toutes les pages ou par page.',
    ],
    'all_pages' => 'Toutes les pages',
    'all_languages' => 'Toutes les langues',
    'empty' => 'Pas encore de balises SEO propres',
    'empty_description' => 'Les balises automatiques suffisent généralement. Ajoutez ici des balises supplémentaires ou de remplacement.',
    'errors' => [
        'json' => 'Ce n\'est pas un JSON valide.',
        'url' => 'Indiquez une adresse complète (https://…) ou un chemin (/…).',
    ],
];
