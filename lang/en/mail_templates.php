<?php

// E-mail templates and the welcome mail of new admin users.

return [
    'resource' => ['singular' => 'e-mail template', 'plural' => 'E-mail templates'],
    'keys' => [
        'welcome' => 'Welcome mail new user',
        'password_reset' => 'Forgot password',
        'contact_message' => 'New contact message',
        'job_application' => 'New application',
    ],
    'descriptions' => [
        'welcome' => 'To a new user of the admin panel, in their language, with the link to choose a password.',
        'password_reset' => 'After "Forgot password?" on the sign-in page, in the user\'s language.',
        'contact_message' => 'To the address of Settings → Contact details for every message of the contact form, in the website\'s main language.',
        'job_application' => 'To the recipients of the vacancy for every application, with the CV attached, in the website\'s main language.',
    ],
    'fields' => [
        'mail' => 'E-mail',
        'language' => 'Language',
        'subject' => 'Subject',
        'body' => 'Text',
        'is_active' => 'Use own text',
        'updated' => 'Changed',
    ],
    'placeholders' => 'Available placeholders: :list',
    'body_help' => 'Bold, italic, links and lists are possible. Placeholders between braces are filled in when the mail is sent.',
    'is_active_help' => 'Off = the default text is sent.',
    'preview' => 'Preview',
    'reset' => 'Default text',
    'reset_help' => 'The subject and text are replaced by the default text.',
    'reset_done' => 'Default text restored',
    'welcome' => [
        'send' => 'Send welcome mail',
        'send_help' => 'The user gets an e-mail (in the chosen language) with a link to choose a password themselves. Off = you choose a password now.',
        'sent' => 'Welcome mail sent to :email',
        'failed' => 'The welcome mail could not be sent',
        'resend' => 'Send welcome mail again',
        'resend_help' => 'A new welcome mail with a link to choose a password goes to :email. The current password keeps working until a new one is chosen.',
        'link_used' => 'This link is no longer valid: a password has already been chosen. Sign in, or use "Forgot password?".',
    ],
];
