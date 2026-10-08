<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Site-wide settings managed from the admin panel, stored per group
 * (general, contact, mail, appearance) in the settings table.
 */
class Settings
{
    public const CACHE_KEY = 'site-settings';

    /** @var array<string, array<string, mixed>>|null */
    protected ?array $values = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            'general' => [
                'site_name' => 'Power Installation NV',
                'founded' => 1998,
                'show_who_is_who' => true,
                'tagline' => [
                    'nl' => 'Elektrotechnische installaties sinds 1998',
                    'fr' => 'Installations électrotechniques depuis 1998',
                    'en' => 'Electrical installations since 1998',
                ],
                'hero_title' => [
                    'nl' => 'Wij brengen *energie* waar u ze nodig hebt',
                    'fr' => "Nous apportons l'*énergie* là où vous en avez besoin",
                    'en' => 'We bring *power* where you need it',
                ],
                'hero_text' => [
                    'nl' => 'Uw partner voor elektrische installaties, verdeelborden, verlichting, datanetwerken en laadinfrastructuur. Van studie tot onderhoud.',
                    'fr' => "Votre partenaire pour les installations électriques, tableaux de distribution, éclairage, réseaux de données et bornes de recharge. De l'étude à la maintenance.",
                    'en' => 'Your partner for electrical installations, switchboards, lighting, data networks and EV charging. From design to maintenance.',
                ],
                'intro_title' => [
                    'nl' => 'Vakmanschap in elektrotechniek, van ontwerp tot onderhoud',
                    'fr' => "Le savoir-faire électrotechnique, de la conception à l'entretien",
                    'en' => 'Electrical craftsmanship, from design to maintenance',
                ],
                'intro_text' => [
                    'nl' => 'Wij ontwerpen, installeren en onderhouden elektrische installaties die gebouwen veilig en efficiënt laten functioneren. Onze eigen ploegen nemen het volledige traject voor hun rekening: studie, uitvoering, keuring en nazorg.',
                    'fr' => "Nous concevons, installons et entretenons des installations électriques qui rendent les bâtiments sûrs et efficaces. Nos propres équipes prennent en charge l'ensemble du parcours : étude, exécution, contrôle et suivi.",
                    'en' => 'We design, install and maintain electrical installations that keep buildings safe and efficient. Our own crews handle the entire process: design, installation, inspection and aftercare.',
                ],
                'figures' => [],
            ],
            'contact' => [
                'street' => 'Drukpersstraat 4',
                'postal_code' => '1000',
                'city' => ['nl' => 'Brussel', 'fr' => 'Bruxelles', 'en' => 'Brussels'],
                'country' => ['nl' => 'België', 'fr' => 'Belgique', 'en' => 'Belgium'],
                'phone' => null,
                'email' => null,
                'vat' => null,
                'opening_hours' => [
                    'nl' => 'Maandag - vrijdag: 7u30 - 17u00',
                    'fr' => 'Lundi - vendredi : 7h30 - 17h00',
                    'en' => 'Monday - Friday: 7:30 am - 5:00 pm',
                ],
                'service_area' => [
                    'nl' => 'Brussel, Vlaanderen en Wallonië',
                    'fr' => 'Bruxelles, Flandre et Wallonie',
                    'en' => 'Brussels, Flanders and Wallonia',
                ],
                'notify_email' => null,
                'social' => [
                    'instagram' => null,
                    'facebook' => null,
                    'linkedin' => null,
                    'twitter' => null,
                ],
            ],
            'mail' => [
                'mailer' => null,
                'host' => null,
                'port' => 587,
                'username' => null,
                'password' => null,
                'scheme' => null,
                'from_address' => null,
                'from_name' => null,
            ],
            'appearance' => [
                'primary' => '#002b45',
                'primary_dark' => '#001a2e',
                'accent' => '#f7941d',
                'accent_hover' => '#e58413',
                'logo' => null,
                'favicon' => null,
                'hero_image' => null,
                'custom_css' => null,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->values ??= Cache::rememberForever(self::CACHE_KEY, function (): array {
            try {
                $stored = Setting::query()->pluck('value', 'key')->all();
            } catch (QueryException) {
                $stored = [];
            }

            return collect(static::defaults())
                ->map(fn (array $defaults, string $group): array => [...$defaults, ...($stored[$group] ?? [])])
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * Get a locale-keyed setting in the current locale.
     */
    public function translated(string $key, ?string $locale = null): mixed
    {
        return translated_value($this->get($key), $locale);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(string $group, array $values): void
    {
        Setting::updateOrCreate(['key' => $group], ['value' => $values]);

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    /**
     * Override the mail configuration with the values set in the admin panel.
     * When no mailer is chosen there, the .env configuration stays in effect.
     */
    public function applyMailConfig(): void
    {
        $mail = $this->get('mail');

        if (blank($mail['mailer'] ?? null)) {
            return;
        }

        config(array_filter([
            'mail.default' => $mail['mailer'],
            'mail.mailers.smtp.host' => $mail['host'],
            'mail.mailers.smtp.port' => $mail['port'],
            'mail.mailers.smtp.username' => $mail['username'],
            'mail.mailers.smtp.password' => filled($mail['password']) ? Crypt::decryptString($mail['password']) : null,
            'mail.mailers.smtp.scheme' => $mail['scheme'],
            'mail.from.address' => $mail['from_address'],
            'mail.from.name' => $mail['from_name'],
        ], fn (mixed $value): bool => filled($value)));
    }

    /**
     * Public URL of the uploaded logo, or null for the default lightning-bolt mark.
     */
    public function logoUrl(): ?string
    {
        $logo = $this->get('appearance.logo');

        return filled($logo) ? Storage::disk('public')->url($logo) : null;
    }

    /**
     * Public URL of the uploaded favicon, or null to use the default.
     */
    /**
     * Public URL of the homepage hero background, or null to keep the default navy background.
     */
    public function heroImageUrl(): ?string
    {
        $image = $this->get('appearance.hero_image');

        return filled($image) ? Storage::disk('public')->url($image) : null;
    }

    public function faviconUrl(): ?string
    {
        $favicon = $this->get('appearance.favicon');

        return filled($favicon) ? Storage::disk('public')->url($favicon) : null;
    }

    /**
     * CSS custom properties that override the Tailwind theme colours.
     */
    public function themeCss(): string
    {
        $colors = $this->get('appearance');
        $hex = fn (?string $value, string $fallback): string => preg_match('/^#[0-9a-f]{3,8}$/i', (string) $value) ? $value : $fallback;

        $primary = $hex($colors['primary'] ?? null, '#002b45');
        $primaryDark = $hex($colors['primary_dark'] ?? null, '#001a2e');
        $accent = $hex($colors['accent'] ?? null, '#f7941d');
        $accentHover = $hex($colors['accent_hover'] ?? null, '#e58413');

        $css = ":root{--color-navy-700:color-mix(in srgb,{$primary} 80%,white);--color-navy-800:{$primary};"
            ."--color-navy-900:{$primaryDark};--color-navy-950:color-mix(in srgb,{$primaryDark} 70%,black);"
            ."--color-accent:{$accent};--color-accent-hover:{$accentHover};}";

        // Custom CSS is written by admins only; stop it from closing the <style> element.
        return $css.str_ireplace('</style', '', (string) ($colors['custom_css'] ?? ''));
    }
}
