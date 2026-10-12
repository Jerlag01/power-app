<?php

return [
    'resource' => ['singular' => 'geblokkeerd IP-adres', 'plural' => 'Geblokkeerde IP-adressen'],
    'sections' => ['status' => 'Status'],
    'fields' => [
        'ip' => 'IP-adres of bereik',
        'ip_address' => 'IP-adres',
        'reason' => 'Reden (intern)',
        'is_active' => 'Blokkering actief',
        'expires_at' => 'Geblokkeerd tot',
        'hits' => 'Geweigerde bezoeken',
        'created_by' => 'Geblokkeerd door',
    ],
    'help' => [
        'ip' => 'Eén adres (203.0.113.7 of een IPv6-adres) of een bereik in CIDR-notatie (203.0.113.0/24). Jouw eigen IP-adres is :own. Het beheerpaneel blijft altijd bereikbaar.',
        'reason' => 'Enkel zichtbaar in het beheerpaneel, nooit voor de bezoeker.',
        'is_active' => 'Uitgeschakeld = het adres wordt tijdelijk niet geblokkeerd, maar de regel blijft bewaard.',
        'expires_at' => 'Na dit tijdstip vervalt de blokkering vanzelf. Leeg = blijvend.',
        'own_ip' => 'Dit is je eigen IP-adres',
        'empty' => 'Geen geblokkeerde IP-adressen',
        'empty_description' => 'Geblokkeerde bezoekers zien in plaats van de website een pagina "Toegang geweigerd" (HTTP 410).',
    ],
    'status' => [
        'blocked' => 'Geblokkeerd',
        'expired' => 'Verlopen',
        'inactive' => 'Uitgeschakeld',
    ],
    'permanent' => 'Blijvend',
    'last_hit' => 'laatst op :time',
    'actions' => [
        'block' => 'IP blokkeren',
        'unblock' => 'Deblokkeren',
    ],
    'reason_from_message' => 'Contactbericht van :name <:email> op :date: ":subject"',
    'validation' => [
        'invalid' => 'Geef een geldig IP-adres of een bereik zoals 203.0.113.0/24.',
        'too_wide' => 'Dit bereik is te ruim. Gebruik minstens /:prefix.',
        'exists' => 'Dit IP-adres of bereik is al geblokkeerd.',
    ],
];
