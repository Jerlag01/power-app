<?php

namespace App\Support;

use App\Enums\MailTemplateKey;
use App\Models\MailTemplate;
use Illuminate\Database\QueryException;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * The e-mails of the website and their texts. They are edited under System → E-mail templates; the defaults
 * below are used until then (and for "reset to default"). Bodies are Markdown; placeholders like {name} are
 * replaced when the mail is sent. Values are escaped, except an HtmlString: Markdown the caller built (and
 * escaped) itself.
 */
class MailTemplates
{
    /**
     * The website's main language: internal mails (contact form, applications) are sent in it.
     */
    public static function defaultLocale(): string
    {
        return (string) array_key_first(config('app.locales'));
    }

    /**
     * @return array{subject: string, body: string}
     */
    public static function default(MailTemplateKey $key, string $locale): array
    {
        $templates = self::defaults()[$key->value];

        return $templates[$locale] ?? $templates[self::defaultLocale()];
    }

    /**
     * Makes sure every template exists in every language, so they can all be edited.
     */
    public static function sync(): void
    {
        foreach (MailTemplateKey::cases() as $key) {
            foreach (array_keys(config('app.locales')) as $locale) {
                MailTemplate::query()->firstOrCreate(['key' => $key->value, 'locale' => $locale], self::default($key, $locale));
            }
        }
    }

    /**
     * The subject and HTML body of a mail in the given language.
     *
     * @param  array<string, scalar|HtmlString|null>  $values
     * @return array{subject: string, html: string}
     */
    public static function render(MailTemplateKey $key, string $locale, array $values): array
    {
        try {
            $template = MailTemplate::query()->where('key', $key->value)->where('locale', $locale)->where('is_active', true)->first();
        } catch (QueryException) {
            $template = null;
        }

        $source = $template ? ['subject' => $template->subject, 'body' => $template->body] : self::default($key, $locale);
        $values = ['site_name' => (string) settings('general.site_name'), ...$values];

        return [
            'subject' => self::fill($source['subject'], $values, escapeMarkdown: false),
            'html' => Str::markdown(self::fill($source['body'], $values, escapeMarkdown: true), ['html_input' => 'strip', 'allow_unsafe_links' => false]),
        ];
    }

    /**
     * @param  array<string, scalar|HtmlString|null>  $values
     */
    public static function fill(string $text, array $values, bool $escapeMarkdown): string
    {
        return (string) preg_replace_callback('/\{([a-z_]+)\}/', function (array $match) use ($values, $escapeMarkdown): string {
            if (! array_key_exists($match[1], $values)) {
                return $match[0];
            }

            if ($values[$match[1]] instanceof HtmlString) {
                return $escapeMarkdown ? $values[$match[1]]->toHtml() : strip_tags($values[$match[1]]->toHtml());
            }

            $value = (string) $values[$match[1]];

            // Links stay usable inside Markdown links; other values cannot add formatting or HTML.
            return $escapeMarkdown && ! str_starts_with($value, 'http') ? self::escape($value) : $value;
        }, $text);
    }

    /**
     * Escapes text for use inside Markdown.
     */
    public static function escape(string $text): string
    {
        return addcslashes(e($text), '\\`*_[]#<>|');
    }

    /**
     * A text typed by a visitor (several lines) as an escaped Markdown quote.
     */
    public static function quote(?string $text): HtmlString
    {
        $lines = preg_split('/\R/', trim((string) $text)) ?: [];

        return new HtmlString(implode("\n", array_map(fn (string $line): string => '> '.self::escape($line).'  ', $lines)));
    }

    /**
     * @return array<string, array<string, array{subject: string, body: string}>>
     */
    public static function defaults(): array
    {
        return [
            MailTemplateKey::Welcome->value => [
                'nl' => ['subject' => 'Welkom op het beheerpaneel van {site_name}', 'body' => "Hallo {name},\n\nEr is een account voor je aangemaakt op het beheerpaneel van de website van {site_name}.\n\nJe meldt je aan met je e-mailadres: **{email}**\n\n[Kies je wachtwoord]({password_link})\n\nDaarna meld je je altijd aan op {login_url} met je e-mailadres en dat wachtwoord. De link werkt {link_days} dagen en maar één keer. Verlopen? Gebruik \"Wachtwoord vergeten?\" op de aanmeldpagina.\n\nMet vriendelijke groeten,\n{site_name}"],
                'fr' => ['subject' => "Bienvenue sur le panneau d'administration de {site_name}", 'body' => "Bonjour {name},\n\nUn compte a été créé pour vous sur le panneau d'administration du site de {site_name}.\n\nVous vous connectez avec votre adresse e-mail : **{email}**\n\n[Choisir votre mot de passe]({password_link})\n\nEnsuite, vous vous connectez toujours sur {login_url} avec votre adresse e-mail et ce mot de passe. Le lien fonctionne {link_days} jours et une seule fois. Expiré ? Utilisez « Mot de passe oublié ? » sur la page de connexion.\n\nCordialement,\n{site_name}"],
                'en' => ['subject' => 'Welcome to the {site_name} admin panel', 'body' => "Hello {name},\n\nAn account has been made for you on the admin panel of the {site_name} website.\n\nYou sign in with your e-mail address: **{email}**\n\n[Choose your password]({password_link})\n\nAfter that you always sign in at {login_url} with your e-mail address and that password. The link works for {link_days} days and only once. Expired? Use \"Forgot password?\" on the sign-in page.\n\nKind regards,\n{site_name}"],
            ],
            MailTemplateKey::PasswordReset->value => [
                'nl' => ['subject' => 'Nieuw wachtwoord voor {site_name}', 'body' => "Hallo {name},\n\nEr is gevraagd om het wachtwoord van je account ({email}) opnieuw in te stellen.\n\n[Kies een nieuw wachtwoord]({reset_link})\n\nDeze link werkt {minutes} minuten. Heb je dit niet gevraagd? Dan hoef je niets te doen: je wachtwoord blijft ongewijzigd.\n\nMet vriendelijke groeten,\n{site_name}"],
                'fr' => ['subject' => 'Nouveau mot de passe pour {site_name}', 'body' => "Bonjour {name},\n\nUne réinitialisation du mot de passe de votre compte ({email}) a été demandée.\n\n[Choisir un nouveau mot de passe]({reset_link})\n\nCe lien fonctionne {minutes} minutes. Vous n'avez rien demandé ? Vous ne devez rien faire : votre mot de passe reste inchangé.\n\nCordialement,\n{site_name}"],
                'en' => ['subject' => 'New password for {site_name}', 'body' => "Hello {name},\n\nA password reset was requested for your account ({email}).\n\n[Choose a new password]({reset_link})\n\nThis link works for {minutes} minutes. Didn't ask for this? Then you don't need to do anything: your password stays the same.\n\nKind regards,\n{site_name}"],
            ],
            MailTemplateKey::ContactMessage->value => [
                'nl' => ['subject' => 'Nieuw contactbericht: {subject}', 'body' => "**Naam:** {name}  \n**E-mail:** {email}  \n**Telefoon:** {phone}  \n**Onderwerp:** {subject}  \n**Taal:** {language}\n\n{message}\n\nBeantwoord deze e-mail om de afzender rechtstreeks te contacteren. Het bericht staat ook in het beheerpaneel: [Contactberichten]({admin_url})"],
                'fr' => ['subject' => 'Nouveau message de contact : {subject}', 'body' => "**Nom :** {name}  \n**E-mail :** {email}  \n**Téléphone :** {phone}  \n**Objet :** {subject}  \n**Langue :** {language}\n\n{message}\n\nRépondez à cet e-mail pour contacter directement l'expéditeur. Le message se trouve aussi dans le panneau d'administration : [Messages de contact]({admin_url})"],
                'en' => ['subject' => 'New contact message: {subject}', 'body' => "**Name:** {name}  \n**E-mail:** {email}  \n**Phone:** {phone}  \n**Subject:** {subject}  \n**Language:** {language}\n\n{message}\n\nReply to this e-mail to contact the sender directly. The message is also in the admin panel: [Contact messages]({admin_url})"],
            ],
            MailTemplateKey::JobApplication->value => [
                'nl' => ['subject' => 'Nieuwe sollicitatie: {vacancy} - {name}', 'body' => "**Vacature:** {vacancy}  \n**Naam:** {name}  \n**E-mail:** {email}  \n**Telefoon:** {phone}  \n**Taal:** {language}\n\n{message}\n\nHet cv zit als bijlage bij deze e-mail ({cv_name}). De sollicitatie staat ook in het beheerpaneel: [Sollicitaties]({admin_url})\n\nBeantwoord deze e-mail om de kandidaat rechtstreeks te contacteren."],
                'fr' => ['subject' => 'Nouvelle candidature : {vacancy} - {name}', 'body' => "**Offre :** {vacancy}  \n**Nom :** {name}  \n**E-mail :** {email}  \n**Téléphone :** {phone}  \n**Langue :** {language}\n\n{message}\n\nLe CV est joint à cet e-mail ({cv_name}). La candidature se trouve aussi dans le panneau d'administration : [Candidatures]({admin_url})\n\nRépondez à cet e-mail pour contacter directement le candidat."],
                'en' => ['subject' => 'New application: {vacancy} - {name}', 'body' => "**Vacancy:** {vacancy}  \n**Name:** {name}  \n**E-mail:** {email}  \n**Phone:** {phone}  \n**Language:** {language}\n\n{message}\n\nThe CV is attached to this e-mail ({cv_name}). The application is also in the admin panel: [Applications]({admin_url})\n\nReply to this e-mail to contact the candidate directly."],
            ],
        ];
    }
}
