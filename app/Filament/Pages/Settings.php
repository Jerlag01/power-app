<?php

namespace App\Filament\Pages;

use App\Enums\AdminNavigationGroup;
use App\Filament\Support\TranslatableTabs;
use App\Support\Settings as SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::System;

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role?->allowsAny('settings') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.title');
    }

    public function mount(SiteSettings $settings): void
    {
        $values = $settings->all();
        $values['mail']['password'] = null;

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make(__('admin.settings.tabs.general'))
                            ->visible(fn (): bool => static::canEdit('general'))
                            ->icon(Heroicon::OutlinedHome)
                            ->schema($this->generalFields()),
                        Tab::make(__('admin.settings.tabs.contact'))
                            ->visible(fn (): bool => static::canEdit('contact'))
                            ->icon(Heroicon::OutlinedMapPin)
                            ->schema($this->contactFields()),
                        Tab::make(__('admin.settings.tabs.mail'))
                            ->visible(fn (): bool => static::canEdit('mail'))
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema($this->mailFields()),
                        Tab::make(__('admin.settings.tabs.appearance'))
                            ->visible(fn (): bool => static::canEdit('appearance'))
                            ->icon(Heroicon::OutlinedSwatch)
                            ->schema($this->appearanceFields()),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('admin.actions.save'))
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(SiteSettings $settings): void
    {
        $data = $this->form->getState();

        if (static::canEdit('mail')) {
            $data['mail']['password'] = filled($data['mail']['password'] ?? null)
                ? Crypt::encryptString($data['mail']['password'])
                : $settings->get('mail.password');
        }

        if (static::canEdit('appearance')) {
            foreach (['logo', 'favicon', 'hero_image'] as $image) {
                $this->deleteReplacedImage($settings->get("appearance.{$image}"), $data['appearance'][$image] ?? null);
            }
        }

        // Only groups the user may edit are saved, whatever the request contains.
        foreach (array_keys(SiteSettings::defaults()) as $group) {
            if (static::canEdit($group) && isset($data[$group])) {
                $settings->save($group, $data[$group]);
            }
        }

        $this->data['mail']['password'] = null;

        Notification::make()
            ->title(__('admin.settings.saved'))
            ->success()
            ->send();
    }

    public static function canEdit(string $group): bool
    {
        return auth()->user()?->hasPermission("settings.{$group}") ?? false;
    }

    protected function deleteReplacedImage(?string $old, ?string $new): void
    {
        if (filled($old) && $old !== $new) {
            Storage::disk('public')->delete($old);
        }
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestMail')
                ->label(__('admin.settings.test_mail'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->visible(fn (): bool => static::canEdit('mail'))
                ->requiresConfirmation()
                ->modalDescription(fn (): string => __('admin.settings.test_mail_description', ['email' => auth()->user()->email]))
                ->action(function (SiteSettings $settings): void {
                    $settings->applyMailConfig();
                    Mail::purge();

                    try {
                        Mail::raw(__('admin.settings.test_mail_body', ['site' => $settings->get('general.site_name')]), function ($message) use ($settings): void {
                            $message->to(auth()->user()->email)->subject($settings->get('general.site_name').' - test');
                        });

                        Notification::make()->title(__('admin.settings.test_mail_sent'))->success()->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title(__('admin.settings.test_mail_failed'))
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * @return array<Component|Field>
     */
    protected function generalFields(): array
    {
        return [
            Section::make(__('admin.settings.sections.pages'))
                ->schema([
                    Toggle::make('general.show_who_is_who')
                        ->label(__('admin.settings.fields.show_who_is_who'))
                        ->helperText(__('admin.settings.help.show_who_is_who')),
                ]),
            Section::make(__('admin.settings.sections.identity'))
                ->columns(2)
                ->schema([
                    TextInput::make('general.site_name')
                        ->label(__('admin.settings.fields.site_name'))
                        ->required()
                        ->maxLength(100),
                    TextInput::make('general.founded')
                        ->label(__('admin.settings.fields.founded'))
                        ->numeric()
                        ->minValue(1800)
                        ->maxValue((int) date('Y'))
                        ->required(),
                ]),
            TranslatableTabs::make(fn (string $locale, bool $isDefault): array => [
                TextInput::make("general.tagline.{$locale}")
                    ->label(__('admin.settings.fields.tagline'))
                    ->helperText(__('admin.settings.help.tagline'))
                    ->required($isDefault)
                    ->maxLength(150),
                TextInput::make("general.hero_title.{$locale}")
                    ->label(__('admin.settings.fields.hero_title'))
                    ->helperText(__('admin.settings.help.hero_title'))
                    ->required($isDefault)
                    ->maxLength(150),
                Textarea::make("general.hero_text.{$locale}")
                    ->label(__('admin.settings.fields.hero_text'))
                    ->rows(3),
                TextInput::make("general.intro_title.{$locale}")
                    ->label(__('admin.settings.fields.intro_title'))
                    ->maxLength(150),
                Textarea::make("general.intro_text.{$locale}")
                    ->label(__('admin.settings.fields.intro_text'))
                    ->rows(4),
            ], __('admin.settings.sections.texts')),
            Section::make(__('admin.settings.sections.figures'))
                ->description(__('admin.settings.help.figures'))
                ->schema([
                    Repeater::make('general.figures')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('value')
                                ->label(__('admin.fields.value'))
                                ->required()
                                ->maxLength(20),
                            Grid::make(3)->schema(collect(config('app.locales'))
                                ->map(fn (string $language, string $locale) => TextInput::make("label.{$locale}")
                                    ->label(__('admin.fields.label').' ('.strtoupper($locale).')')
                                    ->required($locale === config('app.locale'))
                                    ->maxLength(60))
                                ->values()
                                ->all()),
                        ])
                        ->addActionLabel(__('admin.actions.add_figure'))
                        ->maxItems(4)
                        ->reorderable()
                        ->defaultItems(0),
                ]),
        ];
    }

    /**
     * @return array<Component|Field>
     */
    protected function contactFields(): array
    {
        return [
            Section::make(__('admin.settings.sections.address'))
                ->columns(2)
                ->schema([
                    TextInput::make('contact.street')
                        ->label(__('admin.settings.fields.street'))
                        ->required()
                        ->maxLength(150),
                    TextInput::make('contact.postal_code')
                        ->label(__('admin.settings.fields.postal_code'))
                        ->required()
                        ->maxLength(10),
                    TextInput::make('contact.phone')
                        ->label(__('admin.fields.phone'))
                        ->tel()
                        ->maxLength(30),
                    TextInput::make('contact.email')
                        ->label(__('admin.fields.email'))
                        ->email()
                        ->maxLength(150),
                    TextInput::make('contact.vat')
                        ->label(__('admin.settings.fields.vat'))
                        ->placeholder('BE 0123.456.789')
                        ->maxLength(30),
                    TextInput::make('contact.notify_email')
                        ->label(__('admin.settings.fields.notify_email'))
                        ->helperText(__('admin.settings.help.notify_email'))
                        ->email()
                        ->maxLength(150),
                ]),
            TranslatableTabs::make(fn (string $locale, bool $isDefault): array => [
                Grid::make(2)->schema([
                    TextInput::make("contact.city.{$locale}")
                        ->label(__('admin.settings.fields.city'))
                        ->required($isDefault)
                        ->maxLength(100),
                    TextInput::make("contact.country.{$locale}")
                        ->label(__('admin.settings.fields.country'))
                        ->required($isDefault)
                        ->maxLength(100),
                    TextInput::make("contact.opening_hours.{$locale}")
                        ->label(__('admin.settings.fields.opening_hours'))
                        ->maxLength(150),
                    TextInput::make("contact.service_area.{$locale}")
                        ->label(__('admin.settings.fields.service_area'))
                        ->maxLength(150),
                ]),
            ], __('admin.settings.sections.localized_contact')),
            Section::make(__('admin.settings.sections.social'))
                ->description(__('admin.settings.help.social'))
                ->columns(2)
                ->schema(collect(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'twitter' => 'X (Twitter)'])
                    ->map(fn (string $label, string $network): TextInput => TextInput::make("contact.social.{$network}")
                        ->label($label)
                        ->url()
                        ->rule('url:http,https')
                        ->maxLength(255)
                        ->placeholder("https://{$network}.com/…")
                        ->prefixIcon(Heroicon::OutlinedLink))
                    ->values()
                    ->all()),
        ];
    }

    /**
     * @return array<Component|Field>
     */
    protected function mailFields(): array
    {
        $usesSmtp = fn (Get $get): bool => $get('mail.mailer') === 'smtp';

        return [
            Section::make(__('admin.settings.sections.mail'))
                ->description(__('admin.settings.help.mail'))
                ->columns(2)
                ->schema([
                    Select::make('mail.mailer')
                        ->label(__('admin.settings.fields.mailer'))
                        ->options([
                            'smtp' => 'SMTP',
                            'sendmail' => 'Sendmail',
                            'log' => __('admin.settings.mailers.log'),
                        ])
                        ->placeholder(__('admin.settings.mailers.env'))
                        ->live(),
                    TextInput::make('mail.host')
                        ->label(__('admin.settings.fields.host'))
                        ->placeholder('smtp.example.com')
                        ->required($usesSmtp)
                        ->visible($usesSmtp),
                    TextInput::make('mail.port')
                        ->label(__('admin.settings.fields.port'))
                        ->numeric()
                        ->default(587)
                        ->required($usesSmtp)
                        ->visible($usesSmtp),
                    Select::make('mail.scheme')
                        ->label(__('admin.settings.fields.scheme'))
                        ->options([
                            'smtp' => 'STARTTLS (587)',
                            'smtps' => 'SSL/TLS (465)',
                        ])
                        ->placeholder(__('admin.settings.fields.scheme_auto'))
                        ->visible($usesSmtp),
                    TextInput::make('mail.username')
                        ->label(__('admin.settings.fields.username'))
                        ->autocomplete('off')
                        ->visible($usesSmtp),
                    TextInput::make('mail.password')
                        ->label(__('admin.fields.password'))
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->helperText(__('admin.settings.help.mail_password'))
                        ->visible($usesSmtp),
                    TextInput::make('mail.from_address')
                        ->label(__('admin.settings.fields.from_address'))
                        ->email(),
                    TextInput::make('mail.from_name')
                        ->label(__('admin.settings.fields.from_name')),
                ]),
        ];
    }

    /**
     * @return array<Component|Field>
     */
    protected function appearanceFields(): array
    {
        return [
            Section::make(__('admin.settings.sections.colors'))
                ->description(__('admin.settings.help.colors'))
                ->columns(2)
                ->schema([
                    ColorPicker::make('appearance.primary')
                        ->label(__('admin.settings.fields.primary'))
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(),
                    ColorPicker::make('appearance.primary_dark')
                        ->label(__('admin.settings.fields.primary_dark'))
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(),
                    ColorPicker::make('appearance.accent')
                        ->label(__('admin.settings.fields.accent'))
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(),
                    ColorPicker::make('appearance.accent_hover')
                        ->label(__('admin.settings.fields.accent_hover'))
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(),
                ]),
            Section::make(__('admin.settings.sections.logo'))
                ->description(__('admin.settings.help.logo'))
                ->schema([
                    // No SVG uploads: an SVG on the public disk could carry script.
                    FileUpload::make('appearance.logo')
                        ->hiddenLabel()
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/webp', 'image/jpeg'])
                        ->maxSize(1024)
                        ->disk('public')
                        ->directory('logo')
                        ->visibility('public')
                        ->imagePreviewHeight('80'),
                ]),
            Section::make(__('admin.settings.sections.hero_image'))
                ->description(__('admin.settings.help.hero_image'))
                ->schema([
                    // No SVG uploads: an SVG on the public disk could carry script.
                    FileUpload::make('appearance.hero_image')
                        ->hiddenLabel()
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('hero')
                        ->visibility('public')
                        ->imageEditor()
                        ->imagePreviewHeight('160'),
                ]),
            Section::make(__('admin.settings.sections.favicon'))
                ->description(__('admin.settings.help.favicon'))
                ->schema([
                    FileUpload::make('appearance.favicon')
                        ->hiddenLabel()
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/webp'])
                        ->maxSize(512)
                        ->disk('public')
                        ->directory('favicon')
                        ->visibility('public')
                        ->imagePreviewHeight('64'),
                ]),
            Section::make(__('admin.settings.sections.custom_css'))
                ->description(__('admin.settings.help.custom_css'))
                ->schema([
                    Textarea::make('appearance.custom_css')
                        ->hiddenLabel()
                        ->rows(12)
                        ->extraInputAttributes(['class' => 'font-mono', 'spellcheck' => 'false'])
                        ->placeholder(".hero h1 {\n    letter-spacing: -0.02em;\n}"),
                ]),
        ];
    }
}
