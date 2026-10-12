<?php

return [
    'resource' => ['singular' => 'blocked IP address', 'plural' => 'Blocked IP addresses'],
    'sections' => ['status' => 'Status'],
    'fields' => [
        'ip' => 'IP address or range',
        'ip_address' => 'IP address',
        'reason' => 'Reason (internal)',
        'is_active' => 'Block active',
        'expires_at' => 'Blocked until',
        'hits' => 'Refused visits',
        'created_by' => 'Blocked by',
    ],
    'help' => [
        'ip' => 'One address (203.0.113.7 or an IPv6 address) or a range in CIDR notation (203.0.113.0/24). Your own IP address is :own. The admin panel always stays reachable.',
        'reason' => 'Only visible in the admin panel, never to the visitor.',
        'is_active' => 'Off = the address is temporarily not blocked, but the rule is kept.',
        'expires_at' => 'After this moment the block ends by itself. Empty = permanent.',
        'own_ip' => 'This is your own IP address',
        'empty' => 'No blocked IP addresses',
        'empty_description' => 'Blocked visitors see an "Access denied" page (HTTP 410) instead of the website.',
    ],
    'status' => [
        'blocked' => 'Blocked',
        'expired' => 'Expired',
        'inactive' => 'Switched off',
    ],
    'permanent' => 'Permanent',
    'last_hit' => 'last at :time',
    'actions' => [
        'block' => 'Block IP',
        'unblock' => 'Unblock',
    ],
    'reason_from_message' => 'Contact message from :name <:email> on :date: ":subject"',
    'validation' => [
        'invalid' => 'Enter a valid IP address or a range like 203.0.113.0/24.',
        'too_wide' => 'This range is too wide. Use at least /:prefix.',
        'exists' => 'This IP address or range is already blocked.',
    ],
];
