<?php

// E-mail templates and the welcome mail of new admin users.

return [
    'resource' => ['singular' => "modèle d'e-mail", 'plural' => "Modèles d'e-mail"],
    'keys' => [
        'welcome' => 'E-mail de bienvenue nouvel utilisateur',
        'password_reset' => 'Mot de passe oublié',
        'contact_message' => 'Nouveau message de contact',
        'job_application' => 'Nouvelle candidature',
    ],
    'descriptions' => [
        'welcome' => "À un nouvel utilisateur du panneau d'administration, dans sa langue, avec le lien pour choisir un mot de passe.",
        'password_reset' => 'Après « Mot de passe oublié ? » sur la page de connexion, dans la langue de l\'utilisateur.',
        'contact_message' => "À l'adresse de Paramètres → Coordonnées pour chaque message du formulaire de contact, dans la langue principale du site.",
        'job_application' => "Aux destinataires de l'offre pour chaque candidature, avec le CV en pièce jointe, dans la langue principale du site.",
    ],
    'fields' => [
        'mail' => 'E-mail',
        'language' => 'Langue',
        'subject' => 'Objet',
        'body' => 'Texte',
        'is_active' => 'Utiliser un texte propre',
        'updated' => 'Modifié',
    ],
    'placeholders' => 'Variables disponibles : :list',
    'body_help' => "Gras, italique, liens et listes sont possibles. Les variables entre accolades sont remplies lors de l'envoi.",
    'is_active_help' => 'Désactivé = le texte par défaut est envoyé.',
    'preview' => 'Aperçu',
    'reset' => 'Texte par défaut',
    'reset_help' => "L'objet et le texte sont remplacés par le texte par défaut.",
    'reset_done' => 'Texte par défaut remis',
    'welcome' => [
        'send' => "Envoyer l'e-mail de bienvenue",
        'send_help' => "L'utilisateur reçoit un e-mail (dans la langue choisie) avec un lien pour choisir lui-même un mot de passe. Désactivé = vous choisissez un mot de passe maintenant.",
        'sent' => 'E-mail de bienvenue envoyé à :email',
        'failed' => "L'e-mail de bienvenue n'a pas pu être envoyé",
        'resend' => "Renvoyer l'e-mail de bienvenue",
        'resend_help' => "Un nouvel e-mail de bienvenue avec un lien pour choisir un mot de passe est envoyé à :email. Le mot de passe actuel reste valable jusqu'au choix d'un nouveau.",
        'link_used' => 'Ce lien n\'est plus valable : un mot de passe a déjà été choisi. Connectez-vous ou utilisez « Mot de passe oublié ? ».',
    ],
];
