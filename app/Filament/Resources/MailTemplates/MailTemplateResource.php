<?php

namespace App\Filament\Resources\MailTemplates;

use App\Enums\AdminNavigationGroup;
use App\Enums\MailTemplateKey;
use App\Filament\Resources\MailTemplates\Pages\ManageMailTemplates;
use App\Models\MailTemplate;
use App\Support\MailTemplates;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Every e-mail the website sends, with its subject and text in Dutch, French and English. Placeholders such
 * as {name} are filled in when the mail goes out.
 */
class MailTemplateResource extends Resource
{
    protected static ?string $model = MailTemplate::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $slug = 'mail-templates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::System;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('mail_templates.resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mail_templates.resource.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(fn (?MailTemplate $record): string => ($record?->key->description() ?? '').' '.__('mail_templates.placeholders', [
                    'list' => implode(', ', array_map(fn (string $name): string => '{'.$name.'}', $record?->key->placeholders() ?? [])),
                ])),
                TextInput::make('subject')->label(__('mail_templates.fields.subject'))->required()->maxLength(255),
                MarkdownEditor::make('body')
                    ->label(__('mail_templates.fields.body'))
                    ->helperText(__('mail_templates.body_help'))
                    ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']])
                    ->required()
                    ->minHeight('18rem'),
                Toggle::make('is_active')->label(__('mail_templates.fields.is_active'))->helperText(__('mail_templates.is_active_help')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('editor')->orderBy('key')->orderByRaw("CASE locale WHEN 'nl' THEN 1 WHEN 'fr' THEN 2 ELSE 3 END"))
            ->defaultGroup(Group::make('key')
                ->getTitleFromRecordUsing(fn (MailTemplate $record): string => $record->key->getLabel())
                ->getDescriptionFromRecordUsing(fn (MailTemplate $record): string => $record->key->description())
                ->collapsible())
            ->paginated(false)
            ->columns([
                TextColumn::make('locale')->label(__('mail_templates.fields.language'))->badge()->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('subject')->label(__('mail_templates.fields.subject'))->wrap(),
                IconColumn::make('is_active')->label(__('mail_templates.fields.is_active'))->boolean(),
                TextColumn::make('updated_at')->label(__('mail_templates.fields.updated'))->since()->description(fn (MailTemplate $record): ?string => $record->editor?->name),
            ])
            ->filters([
                SelectFilter::make('key')->label(__('mail_templates.fields.mail'))->options(MailTemplateKey::class),
                SelectFilter::make('locale')->label(__('mail_templates.fields.language'))->options(config('app.locales')),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'updated_by' => auth()->id()]),
                Action::make('preview')
                    ->label(__('mail_templates.preview'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (MailTemplate $record) => view('mail.preview', MailTemplates::render(
                        $record->key,
                        $record->locale,
                        self::sampleValues() + array_fill_keys($record->key->placeholders(), '…'),
                    ))),
                Action::make('reset')
                    ->label(__('mail_templates.reset'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(__('mail_templates.reset_help'))
                    ->visible(fn (MailTemplate $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (MailTemplate $record): void {
                        $record->update([...MailTemplates::default($record->key, $record->locale), 'is_active' => true, 'updated_by' => auth()->id()]);
                        Notification::make()->title(__('mail_templates.reset_done'))->success()->send();
                    }),
            ]);
    }

    /**
     * Example values for the preview.
     *
     * @return array<string, string|HtmlString>
     */
    protected static function sampleValues(): array
    {
        return [
            'name' => 'Eva Peeters', 'email' => 'eva@example.com', 'phone' => '+32 470 12 34 56', 'login_url' => url('/admin/login'),
            'password_link' => url('/admin/login'), 'reset_link' => url('/admin/login'), 'link_days' => '7', 'minutes' => '60',
            'subject' => 'Offerte verdeelbord', 'vacancy' => 'Elektricien', 'language' => 'NL', 'cv_name' => 'cv-eva-peeters.pdf', 'admin_url' => url('/admin'),
            'message' => MailTemplates::quote("Goedendag,\nGraag een offerte voor een nieuw verdeelbord."),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMailTemplates::route('/'),
        ];
    }
}
