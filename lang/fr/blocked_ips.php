<?php

return [
    'resource' => ['singular' => 'adresse IP bloquée', 'plural' => 'Adresses IP bloquées'],
    'sections' => ['status' => 'Statut'],
    'fields' => [
        'ip' => 'Adresse IP ou plage',
        'ip_address' => 'Adresse IP',
        'reason' => 'Raison (interne)',
        'is_active' => 'Blocage actif',
        'expires_at' => 'Bloquée jusqu\'au',
        'hits' => 'Visites refusées',
        'created_by' => 'Bloquée par',
    ],
    'help' => [
        'ip' => 'Une adresse (203.0.113.7 ou une adresse IPv6) ou une plage en notation CIDR (203.0.113.0/24). Votre propre adresse IP est :own. Le panneau d\'administration reste toujours accessible.',
        'reason' => 'Visible uniquement dans le panneau d\'administration, jamais pour le visiteur.',
        'is_active' => 'Désactivé = l\'adresse n\'est temporairement plus bloquée, mais la règle est conservée.',
        'expires_at' => 'Après ce moment, le blocage expire automatiquement. Vide = permanent.',
        'own_ip' => 'C\'est votre propre adresse IP',
        'empty' => 'Aucune adresse IP bloquée',
        'empty_description' => 'Les visiteurs bloqués voient une page « Accès refusé » (HTTP 410) au lieu du site.',
    ],
    'status' => [
        'blocked' => 'Bloquée',
        'expired' => 'Expirée',
        'inactive' => 'Désactivée',
    ],
    'permanent' => 'Permanent',
    'last_hit' => 'dernière fois le :time',
    'actions' => [
        'block' => 'Bloquer l\'IP',
        'unblock' => 'Débloquer',
    ],
    'reason_from_message' => 'Message de contact de :name <:email> le :date : « :subject »',
    'validation' => [
        'invalid' => 'Indiquez une adresse IP valide ou une plage comme 203.0.113.0/24.',
        'too_wide' => 'Cette plage est trop large. Utilisez au moins /:prefix.',
        'exists' => 'Cette adresse IP ou plage est déjà bloquée.',
    ],
];
