<?php

// E-mail templates and the welcome mail of new admin users.

return [
    'resource' => ['singular' => 'e-mailsjabloon', 'plural' => 'E-mailsjablonen'],
    'keys' => [
        'welcome' => 'Welkomstmail nieuwe gebruiker',
        'password_reset' => 'Wachtwoord vergeten',
        'contact_message' => 'Nieuw contactbericht',
        'job_application' => 'Nieuwe sollicitatie',
    ],
    'descriptions' => [
        'welcome' => 'Naar een nieuwe gebruiker van het beheerpaneel, in diens taal, met de link om een wachtwoord te kiezen.',
        'password_reset' => 'Na "Wachtwoord vergeten?" op de aanmeldpagina, in de taal van de gebruiker.',
        'contact_message' => 'Naar het adres van Instellingen → Contactgegevens bij elk bericht van het contactformulier, in de hoofdtaal van de website.',
        'job_application' => 'Naar de ontvangers van de vacature bij elke sollicitatie, met het cv als bijlage, in de hoofdtaal van de website.',
    ],
    'fields' => [
        'mail' => 'E-mail',
        'language' => 'Taal',
        'subject' => 'Onderwerp',
        'body' => 'Tekst',
        'is_active' => 'Eigen tekst gebruiken',
        'updated' => 'Gewijzigd',
    ],
    'placeholders' => 'Beschikbare plaatshouders: :list',
    'body_help' => 'Vet, schuin, links en lijsten kunnen. Plaatshouders tussen accolades worden bij het versturen ingevuld.',
    'is_active_help' => 'Uit = de standaardtekst wordt verstuurd.',
    'preview' => 'Voorbeeld',
    'reset' => 'Standaardtekst',
    'reset_help' => 'Het onderwerp en de tekst worden vervangen door de standaardtekst.',
    'reset_done' => 'Standaardtekst teruggezet',
    'welcome' => [
        'send' => 'Welkomstmail versturen',
        'send_help' => 'De gebruiker krijgt een e-mail (in de gekozen taal) met een link om zelf een wachtwoord te kiezen. Uit = jij kiest nu een wachtwoord.',
        'sent' => 'Welkomstmail verstuurd naar :email',
        'failed' => 'De welkomstmail kon niet verstuurd worden',
        'resend' => 'Welkomstmail opnieuw versturen',
        'resend_help' => 'Een nieuwe welkomstmail met een link om een wachtwoord te kiezen gaat naar :email. Het huidige wachtwoord blijft werken tot er een nieuw gekozen is.',
        'link_used' => 'Deze link is niet meer geldig: er is al een wachtwoord gekozen. Meld je aan, of gebruik "Wachtwoord vergeten?".',
    ],
];
